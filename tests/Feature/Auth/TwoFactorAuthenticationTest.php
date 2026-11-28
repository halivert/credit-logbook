<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->google2fa = new Google2FA;
    }

    private function userWithTwoFactor(bool $confirmed = true): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-one', 'recovery-two'])),
            'two_factor_confirmed_at' => $confirmed ? now() : null,
        ])->save();

        return $user;
    }

    private function codeFor(User $user): string
    {
        return $this->google2fa->getCurrentOtp(decrypt($user->two_factor_secret));
    }

    private function logIn(User $user): void
    {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
    }

    #[Test]
    public function the_password_alone_is_not_enough(): void
    {
        $this->logIn($this->userWithTwoFactor());

        // The protected pages stay hidden until the code is given
        $this->get(route('credit-cards.index'))->assertNotFound();
    }

    #[Test]
    public function the_code_from_the_app_completes_the_login(): void
    {
        $user = $this->userWithTwoFactor();

        $this->logIn($user);

        $this->post(route('two-factor.login.store'), ['code' => $this->codeFor($user)])
            ->assertRedirect(route('credit-cards.index'));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function a_wrong_code_is_rejected(): void
    {
        $user = $this->userWithTwoFactor();

        $this->logIn($user);

        $this->post(route('two-factor.login.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    #[Test]
    public function a_recovery_code_works_only_once(): void
    {
        $user = $this->userWithTwoFactor();

        $this->logIn($user);
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-one']);
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));
        $this->logIn($user);

        $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-one'])
            ->assertSessionHasErrors('recovery_code');

        $this->assertGuest();
        $this->assertNotContains('recovery-one', $user->fresh()->recoveryCodes());
    }

    #[Test]
    public function guessing_codes_is_rate_limited(): void
    {
        $user = $this->userWithTwoFactor();

        $this->logIn($user);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('two-factor.login.store'), ['code' => '000000']);
        }

        $this->post(route('two-factor.login.store'), ['code' => $this->codeFor($user)])
            ->assertTooManyRequests();

        $this->assertGuest();
    }

    #[Test]
    public function the_challenge_needs_a_pending_login(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post(route('two-factor.login.store'), ['code' => $this->codeFor($user)]);

        $this->assertGuest();
    }

    #[Test]
    public function an_unconfirmed_setup_does_not_ask_for_a_code(): void
    {
        $user = $this->userWithTwoFactor(confirmed: false);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('credit-cards.index'));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function setting_it_up_needs_the_password_and_a_valid_code(): void
    {
        $user = User::factory()->create();

        // Without confirming the password first
        $this->actingAs($user)
            ->post(route('two-factor.enable'))
            ->assertRedirect(route('password.confirm'));

        $this->assertNull($user->fresh()->two_factor_secret);

        $this->actingAs($user)
            ->post(route('password.confirm.store'), ['password' => 'password']);

        $this->actingAs($user)->post(route('two-factor.enable'));

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertCount(8, $user->recoveryCodes());

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code', errorBag: 'confirmTwoFactorAuthentication');

        $this->assertNull($user->fresh()->two_factor_confirmed_at);

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => $this->codeFor($user)])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    #[Test]
    public function turning_it_off_needs_the_password(): void
    {
        $user = $this->userWithTwoFactor();

        $this->actingAs($user)
            ->delete(route('two-factor.disable'))
            ->assertRedirect(route('password.confirm'));

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);

        $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'password']);
        $this->actingAs($user)->delete(route('two-factor.disable'));

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }
}
