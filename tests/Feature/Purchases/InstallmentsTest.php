<?php

namespace Tests\Feature\Purchases;

use App\CreditCards\CreditCard;
use App\Payments\Payment;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Money math for installment purchases ("meses sin intereses").
 */
class InstallmentsTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $creditCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditCard = CreditCard::factory()->create();
    }

    private function installmentPurchase(array $attributes = []): Purchase
    {
        return Purchase::factory()->for($this->creditCard)->create([
            'amount' => 1200,
            'installment_count' => 12,
            'installment_amount' => 100,
            ...$attributes,
        ]);
    }

    private function payInstallment(Purchase $purchase, int $installmentNumber): void
    {
        $payment = Payment::factory()->for($this->creditCard)->create();

        $payment->purchases()->attach($purchase, [
            'installment_number' => $installmentNumber,
        ]);
    }

    #[Test]
    public function a_new_installment_purchase_has_nothing_paid(): void
    {
        $purchase = $this->installmentPurchase()->fresh();

        $this->assertSame(0, $purchase->paid_months);
        $this->assertSame(12, $purchase->remaining_months);
        $this->assertEquals(0, $purchase->paid_amount);
        $this->assertEquals(1200, $purchase->remaining_amount);
    }

    #[Test]
    public function the_latest_paid_installment_determines_progress(): void
    {
        $purchase = $this->installmentPurchase();

        $this->payInstallment($purchase, 1);
        $this->payInstallment($purchase, 3);
        $this->payInstallment($purchase, 2);

        $purchase = $purchase->fresh();

        $this->assertSame(3, $purchase->paid_months);
        $this->assertSame(9, $purchase->remaining_months);
        $this->assertEquals(300, $purchase->paid_amount);
        $this->assertEquals(900, $purchase->remaining_amount);
    }

    #[Test]
    public function paid_months_never_exceed_the_installment_count(): void
    {
        $purchase = $this->installmentPurchase();

        $this->payInstallment($purchase, 15);

        $purchase = $purchase->fresh();

        $this->assertSame(12, $purchase->paid_months);
        $this->assertSame(0, $purchase->remaining_months);
        $this->assertEquals(1200, $purchase->paid_amount);
        $this->assertEquals(0, $purchase->remaining_amount);
    }

    #[Test]
    public function the_first_installment_includes_the_commission(): void
    {
        $purchase = $this->installmentPurchase(['commission' => 35])->fresh();

        $this->assertEquals(135, $purchase->installment_amount);

        $this->payInstallment($purchase, 1);

        $this->assertEquals(100, $purchase->fresh()->installment_amount);
    }

    #[Test]
    public function the_last_installment_is_capped_to_the_remaining_amount(): void
    {
        // 1000 in 3 installments of 333.34 leaves 333.32 for the last one
        $purchase = $this->installmentPurchase([
            'amount' => 1000,
            'installment_count' => 3,
            'installment_amount' => 333.34,
        ]);

        $this->payInstallment($purchase, 2);

        $this->assertEqualsWithDelta(333.32, $purchase->fresh()->installment_amount, 0.001);
    }

    #[Test]
    public function a_purchase_marked_as_paid_is_fully_paid(): void
    {
        $purchase = $this->installmentPurchase(['paid_at' => now()])->fresh();

        $this->assertEquals(1200, $purchase->paid_amount);
        $this->assertEquals(0, $purchase->remaining_amount);
    }

    #[Test]
    public function used_balance_adds_unpaid_purchases_and_pending_installments(): void
    {
        Purchase::factory()->for($this->creditCard)->create(['amount' => 500]);
        Purchase::factory()->for($this->creditCard)->create(['amount' => 250.5]);
        Purchase::factory()->for($this->creditCard)->create(['amount' => 9999, 'paid_at' => now()]);

        $purchase = $this->installmentPurchase();
        $this->payInstallment($purchase, 4);

        // 500 + 250.50 + (1200 - 400)
        $this->assertEqualsWithDelta(1550.5, $this->creditCard->fresh()->balance_used, 0.001);
    }
}
