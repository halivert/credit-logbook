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
 * Installments ("meses sin intereses") are billed one per closing date,
 * starting with the first closing on or after the purchase, like the bank.
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
            'bought after it, next closing' => ['2026-11-27', '2026-12-27 18:00', 1],
            'bought after the closing' => ['2026-10-25', '2026-11-27 18:00', 1],
            'bought on the closing day' => ['2026-10-20', '2026-10-20 18:00', 1],
            'several months' => ['2026-01-10', '2026-11-27 18:00', 11],
            'never more than its count' => ['2024-01-10', '2026-11-27 18:00', 12],
            // 02:00 UTC is still the 20th in Mexico City
            "the card's timezone" => ['2026-10-15', '2026-10-21 02:00', 1],
        ];
    }

    #[Test]
    #[DataProvider('billing')]
    public function installments_are_billed_on_each_closing_date(
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
        $purchase = $this->installmentPurchase('2026-10-19', [
            'applied_on' => '2026-10-21',
        ]);

        $this->at('2026-11-27 18:00');

        $this->assertSame(1, $purchase->billed_installments);
    }

    #[Test]
    public function short_months_close_on_their_last_day(): void
    {
        $creditCard = CreditCard::factory()->create(['closing_date' => 30]);

        $purchase = Purchase::factory()->for($creditCard)->create([
            'date' => '2027-02-01',
            'amount' => 300,
            'installment_count' => 3,
            'installment_amount' => 100,
        ])->fresh();

        $this->at('2027-02-28 18:00');

        $this->assertSame(1, $purchase->billed_installments);
    }

    #[Test]
    public function the_first_installment_includes_the_commission(): void
    {
        $purchase = $this->installmentPurchase('2026-10-15', ['commission' => 35]);

        $this->at('2026-10-18 18:00');
        $this->assertEquals(135, $purchase->installment_amount);

        $this->at('2026-10-21 18:00');
        $this->assertEquals(100, $purchase->installment_amount);
    }

    #[Test]
    public function the_last_installment_is_capped_to_what_is_left(): void
    {
        // 1000 in 3 installments of 333.34 leaves 333.32 for the last one
        $purchase = $this->installmentPurchase('2026-09-15', [
            'amount' => 1000,
            'installment_count' => 3,
            'installment_amount' => 333.34,
        ]);

        $this->at('2026-10-27 18:00');

        $this->assertSame(2, $purchase->billed_installments);
        $this->assertEqualsWithDelta(333.32, $purchase->installment_amount, 0.001);
    }

    #[Test]
    public function the_last_installment_takes_what_an_uneven_installment_leaves(): void
    {
        // The bank says 333.33 for 1000 in 3: the last one is 333.34
        $purchase = $this->installmentPurchase('2026-09-15', [
            'amount' => 1000,
            'installment_count' => 3,
            'installment_amount' => 333.33,
        ]);

        $this->at('2026-09-18 18:00');
        $this->assertEqualsWithDelta(333.33, $purchase->installment_amount, 0.001);

        $this->at('2026-10-27 18:00');
        $this->assertSame(2, $purchase->billed_installments);
        $this->assertEqualsWithDelta(333.34, $purchase->installment_amount, 0.001);

        $this->at('2026-11-27 18:00');
        $this->assertSame(0, $purchase->remaining_installments);
        $this->assertEquals(0, $purchase->remaining_amount);
    }

    #[Test]
    public function the_users_installment_is_used_even_if_it_is_not_the_exact_split(): void
    {
        $purchase = $this->installmentPurchase('2026-09-15', [
            'amount' => 1000,
            'installment_count' => 3,
            'installment_amount' => 350,
        ]);

        $this->at('2026-09-18 18:00');
        $this->assertEqualsWithDelta(350, $purchase->installment_amount, 0.001);

        $this->at('2026-10-27 18:00');
        $this->assertEqualsWithDelta(300, $purchase->remaining_amount, 0.001);
        $this->assertEqualsWithDelta(300, $purchase->installment_amount, 0.001);
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
