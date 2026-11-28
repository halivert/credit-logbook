<?php

namespace Tests\Feature\Purchases;

use App\CreditCards\CreditCard;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Purchases in installments use their card's calendar and their stored
 * terms. The math itself is covered by InstallmentPlanTest and
 * BillingCycleTest.
 */
class InstallmentsTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $creditCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditCard = CreditCard::factory()->create([
            'closing_date' => 20,
            'timezone' => 'America/Mexico_City',
        ]);
    }

    private function installmentPurchase(string $date, array $attributes = []): Purchase
    {
        return Purchase::factory()->for($this->creditCard)->create([
            'date' => $date,
            'amount' => 1200,
            'installment_count' => 12,
            'installment_amount' => 100,
            ...$attributes,
        ])->fresh();
    }

    private function at(string $nowUtc): void
    {
        Carbon::setTestNow(Carbon::parse($nowUtc, 'UTC'));
    }

    public static function billing(): array
    {
        return [
            'before its first closing' => ['2026-10-15', '2026-10-18 18:00', 0],
            'on its first closing' => ['2026-10-15', '2026-10-20 18:00', 1],
            'never more than its count' => ['2024-01-10', '2026-11-27 18:00', 12],
            // 02:00 UTC is still the 20th in Mexico City
            "in the card's timezone" => ['2026-10-15', '2026-10-21 02:00', 1],
        ];
    }

    #[Test]
    #[DataProvider('billing')]
    public function installments_are_billed_on_the_cards_closing_dates(
        string $purchasedOn,
        string $nowUtc,
        int $billed,
    ): void {
        $purchase = $this->installmentPurchase($purchasedOn);

        $this->at($nowUtc);

        $this->assertSame($billed, $purchase->billed_installments);
        $this->assertSame(12 - $billed, $purchase->remaining_installments);
        $this->assertEquals(1200 - 100 * $billed, $purchase->remaining_amount);
    }

    #[Test]
    public function the_posting_date_wins_over_the_purchase_date(): void
    {
        $purchase = $this->installmentPurchase('2026-10-19', ['applied_on' => '2026-10-21']);

        $this->at('2026-11-27 18:00');

        $this->assertSame(1, $purchase->billed_installments);
    }

    #[Test]
    public function the_stored_terms_and_commission_are_used(): void
    {
        $purchase = $this->installmentPurchase('2026-10-15', [
            'amount' => 1000,
            'installment_count' => 3,
            'installment_amount' => 333.33,
            'commission' => 35,
        ]);

        $this->at('2026-10-18 18:00');
        $this->assertEquals(368.33, $purchase->installment_amount);

        $this->at('2026-11-27 18:00');
        $this->assertEquals(333.34, $purchase->installment_amount);
    }

    #[Test]
    public function single_payment_purchases_have_no_installments(): void
    {
        $purchase = Purchase::factory()->for($this->creditCard)->create(['amount' => 500])->fresh();

        $this->assertFalse($purchase->is_installment_purchase);
        $this->assertNull($purchase->billed_installments);
        $this->assertNull($purchase->installment_amount);
        $this->assertEquals(0, $purchase->remaining_amount);
    }
}
