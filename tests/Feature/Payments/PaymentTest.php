<?php

namespace Tests\Feature\Payments;

use App\CreditCards\CreditCard;
use App\Models\User;
use App\Payments\Payment;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $creditCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditCard = CreditCard::factory()->create();
    }

    #[Test]
    public function the_payment_form_shows_the_current_balance(): void
    {
        Purchase::factory()->for($this->creditCard)->create(['amount' => 750]);

        $this->actingAs($this->creditCard->user)
            ->get(route('credit-cards.payments.create', $this->creditCard))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payments/Create')
                ->where('creditCard.balance_used', 750)
            );
    }

    #[Test]
    public function a_payment_lowers_the_balance(): void
    {
        Purchase::factory()->for($this->creditCard)->create(['amount' => 2000]);

        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'date' => today()->toDateString(),
                'amount' => '500',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('credit-cards.show', $this->creditCard));

        $payment = $this->creditCard->payments()->sole();

        $this->assertEquals(500, $payment->amount);
        $this->assertEqualsWithDelta(1500, $this->creditCard->fresh()->balance_used, 0.001);
    }

    #[Test]
    public function payments_are_validated(): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'date' => today()->addDay()->toDateString(),
                'amount' => '0',
            ])
            ->assertSessionHasErrors(['date', 'amount']);

        $this->assertDatabaseCount(Payment::class, 0);
    }

    #[Test]
    public function a_user_cannot_pay_someone_elses_card(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'date' => today()->toDateString(),
                'amount' => '100',
            ])
            ->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get(route('credit-cards.payments.create', $this->creditCard))
            ->assertNotFound();

        $this->assertDatabaseCount(Payment::class, 0);
    }
}
