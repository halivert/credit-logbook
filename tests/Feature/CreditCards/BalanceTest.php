<?php

namespace Tests\Feature\CreditCards;

use App\CreditCards\CreditCard;
use App\Payments\Payment;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Like the bank: balance = charges − credits, whatever each payment covers.
 */
class BalanceTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $creditCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditCard = CreditCard::factory()->create(['limit' => '10000']);
    }

    private function balance(): float
    {
        return $this->creditCard->fresh()->balance_used;
    }

    #[Test]
    public function purchases_and_their_commissions_are_charges(): void
    {
        Purchase::factory()->for($this->creditCard)->create(['amount' => 500]);
        Purchase::factory()->for($this->creditCard)->create(['amount' => 250.5]);

        // Installments block the whole amount, like the bank does
        Purchase::factory()->for($this->creditCard)->create([
            'amount' => 1200,
            'installment_count' => 12,
            'installment_amount' => 100,
            'commission' => 35,
        ]);

        $this->assertEqualsWithDelta(1985.5, $this->balance(), 0.001);
        $this->assertEqualsWithDelta(8014.5, $this->creditCard->fresh()->available_credit, 0.001);
    }

    #[Test]
    public function payments_lower_the_balance_without_saying_what_they_pay(): void
    {
        Purchase::factory()->for($this->creditCard)->create(['amount' => 2000]);
        Payment::factory()->for($this->creditCard)->create(['amount' => 1500]);

        $this->assertEqualsWithDelta(500, $this->balance(), 0.001);
        $this->assertEqualsWithDelta(9500, $this->creditCard->fresh()->available_credit, 0.001);
    }

    #[Test]
    public function returns_are_credits(): void
    {
        Purchase::factory()->for($this->creditCard)->create(['amount' => 800]);
        Purchase::factory()->for($this->creditCard)->create(['amount' => -300]);

        $this->assertEqualsWithDelta(500, $this->balance(), 0.001);
    }

    #[Test]
    public function paying_more_than_owed_leaves_a_balance_in_favor(): void
    {
        Purchase::factory()->for($this->creditCard)->create(['amount' => 100]);
        Payment::factory()->for($this->creditCard)->create(['amount' => 150]);

        $this->assertEqualsWithDelta(-50, $this->balance(), 0.001);
        $this->assertEqualsWithDelta(10050, $this->creditCard->fresh()->available_credit, 0.001);
    }

    #[Test]
    public function other_cards_do_not_count(): void
    {
        Purchase::factory()->create(['amount' => 999]);
        Payment::factory()->create(['amount' => 111]);

        $this->assertEqualsWithDelta(0, $this->balance(), 0.001);
    }

    #[Test]
    public function deleting_a_purchase_keeps_the_payments(): void
    {
        $purchase = Purchase::factory()->for($this->creditCard)->create(['amount' => 1000]);
        $payment = Payment::factory()->for($this->creditCard)->create(['amount' => 1000]);

        $this->actingAs($this->creditCard->user)
            ->delete(route('purchases.destroy', $purchase))
            ->assertRedirect(route('credit-cards.show', $this->creditCard));

        $this->assertModelExists($payment);
        $this->assertEqualsWithDelta(-1000, $this->balance(), 0.001);
    }
}
