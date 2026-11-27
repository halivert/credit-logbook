<?php

namespace Tests\Feature\Security;

use App\CreditCards\CreditCard;
use App\Models\User;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function userWithTwoFactor(): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('SECRET'),
            'two_factor_recovery_codes' => encrypt('["CODE"]'),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    #[Test]
    public function two_factor_secrets_never_reach_the_browser(): void
    {
        $user = $this->userWithTwoFactor();

        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());

        $this->actingAs($user)
            ->get(route('credit-cards.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.id', $user->id)
                ->missing('auth.user.two_factor_secret')
                ->missing('auth.user.two_factor_recovery_codes')
                ->missing('auth.user.subscription')
            );
    }

    #[Test]
    public function a_payment_cannot_settle_purchases_of_another_card(): void
    {
        $creditCard = CreditCard::factory()->create();
        $foreign = Purchase::factory()->create();
        $foreignInstallments = Purchase::factory()->create([
            'installment_count' => 3,
            'installment_amount' => 100,
            'amount' => 300,
        ]);

        $this->actingAs($creditCard->user)
            ->post(route('credit-cards.payments.store', $creditCard), [
                'datetime' => today()->toDateString(),
                'amount' => '100',
                'purchases' => [$foreign->id],
                'installment_purchases' => [
                    ['id' => $foreignInstallments->id, 'installment_number' => 3],
                ],
            ])
            ->assertSessionHasErrors(['purchases.0', 'installment_purchases.0.id']);

        $this->assertNull($foreign->fresh()->paid_at);
        $this->assertNull($foreignInstallments->fresh()->paid_at);
    }

    #[Test]
    public function a_payment_cannot_settle_an_already_paid_purchase_again(): void
    {
        $creditCard = CreditCard::factory()->create();
        $paid = Purchase::factory()->for($creditCard)->create(['paid_at' => now()->subMonth()]);

        $this->actingAs($creditCard->user)
            ->post(route('credit-cards.payments.store', $creditCard), [
                'datetime' => today()->toDateString(),
                'amount' => '100',
                'purchases' => [$paid->id],
            ])
            ->assertSessionHasErrors(['purchases.0']);
    }

    #[Test]
    public function api_tokens_are_not_issued_to_users_with_two_factor(): void
    {
        $user = $this->userWithTwoFactor();

        $this->postJson('/api/v1/login', [
            'username' => $user->username,
            'password' => 'password',
        ])->assertUnprocessable();

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function api_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/login', [
                'username' => $user->username,
                'password' => 'wrong',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/login', [
            'username' => $user->username,
            'password' => 'password',
        ])->assertTooManyRequests();
    }

    #[Test]
    public function responses_carry_security_headers(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security');
    }

    #[Test]
    public function signed_in_pages_are_not_cached(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('credit-cards.index'));

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function the_welcome_page_does_not_disclose_versions(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->missing('laravelVersion')
            ->missing('phpVersion')
        );
    }
}
