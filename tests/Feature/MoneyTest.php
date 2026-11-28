<?php

namespace Tests\Feature;

use App\CreditCards\CreditCard;
use App\Payments\Payment;
use App\Purchases\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Amounts are whole cents within a sane range, and sums are exact.
 */
class MoneyTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $creditCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creditCard = CreditCard::factory()->create(['limit' => '10000']);
    }

    public static function invalidAmounts(): array
    {
        return [
            'tiny negative' => ['-0.0000000000001'],
            'tiny positive' => ['0.0000000000001'],
            'negative' => ['-1'],
            'negative zero' => ['-0'],
            'zero' => ['0'],
            'fractions of a cent' => ['0.001'],
            'scientific notation' => ['1e3'],
            'thousands separator' => ['1,000'],
            'hexadecimal' => ['0x1A'],
            'not a number' => ['NaN'],
            'infinity' => ['INF'],
            'text' => ['mil pesos'],
            'too large' => ['1000000000'],
            'way too large' => ['99999999999999999999999'],
        ];
    }

    #[Test]
    #[DataProvider('invalidAmounts')]
    public function purchases_reject_invalid_amounts(string $amount): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'concept' => 'Super',
                'date' => today()->toDateString(),
                'amount' => $amount,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount(Purchase::class, 0);
    }

    #[Test]
    #[DataProvider('invalidAmounts')]
    public function returns_reject_invalid_amounts(string $amount): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'is_refund' => true,
                'concept' => 'Devolución',
                'date' => today()->toDateString(),
                'amount' => $amount,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount(Purchase::class, 0);
    }

    #[Test]
    #[DataProvider('invalidAmounts')]
    public function payments_reject_invalid_amounts(string $amount): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.payments.store', $this->creditCard), [
                'date' => today()->toDateString(),
                'amount' => $amount,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount(Payment::class, 0);
    }

    #[Test]
    #[DataProvider('invalidAmounts')]
    public function card_limits_reject_invalid_amounts(string $amount): void
    {
        // A limit of 0 is allowed (unknown yet), everything else applies
        if (in_array($amount, ['0', '-0'])) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->actingAs($this->creditCard->user)
            ->put(route('credit-cards.update', $this->creditCard), ['limit' => $amount])
            ->assertSessionHasErrors('limit');

        $this->assertSame('10000', $this->creditCard->fresh()->limit);
    }

    #[Test]
    public function cents_are_valid_amounts(): void
    {
        foreach (['0.01', '0.5', '.5', '999999999.99'] as $amount) {
            $this->actingAs($this->creditCard->user)
                ->post(route('credit-cards.purchases.store', $this->creditCard), [
                    'concept' => "Compra de {$amount}",
                    'date' => today()->toDateString(),
                    'amount' => $amount,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount(Purchase::class, 4);
    }

    public static function invalidInstallments(): array
    {
        return [
            'zero months' => [['installment_count' => 0], 'installment_count'],
            'negative months' => [['installment_count' => -3], 'installment_count'],
            'too many months' => [['installment_count' => 61], 'installment_count'],
            'fractional months' => [['installment_count' => '1.5'], 'installment_count'],
            'negative installment' => [['installment_amount' => '-100'], 'installment_amount'],
            'installment over the amount' => [['installment_amount' => '1001'], 'installment_amount'],
            'negative commission' => [['commission' => '-0.01'], 'commission'],
            'commission in fractions of a cent' => [['commission' => '0.001'], 'commission'],
        ];
    }

    #[Test]
    #[DataProvider('invalidInstallments')]
    public function installment_purchases_reject_invalid_terms(array $data, string $error): void
    {
        $this->actingAs($this->creditCard->user)
            ->post(route('credit-cards.purchases.store', $this->creditCard), [
                'is_installment_purchase' => true,
                'concept' => 'Laptop',
                'date' => today()->toDateString(),
                'amount' => '1000',
                'installment_count' => 3,
                ...$data,
            ])
            ->assertSessionHasErrors($error);

        $this->assertDatabaseCount(Purchase::class, 0);
    }

    #[Test]
    public function sums_that_floats_get_wrong_are_exact(): void
    {
        // 0.1 + 0.2 - 0.3 is 5.551115123125783e-17 with floats
        Purchase::factory()->for($this->creditCard)->create(['amount' => '0.10']);
        Purchase::factory()->for($this->creditCard)->create(['amount' => '0.20']);
        Payment::factory()->for($this->creditCard)->create(['amount' => '0.30']);

        $this->assertSame(0.0, $this->creditCard->fresh()->balance_used);
        $this->assertSame(10000.0, $this->creditCard->fresh()->available_credit);
    }

    #[Test]
    public function many_small_amounts_add_up_exactly(): void
    {
        Purchase::factory()->for($this->creditCard)->count(100)->create(['amount' => '0.01']);
        Purchase::factory()->for($this->creditCard)->count(10)->create(['amount' => '0.07']);
        Payment::factory()->for($this->creditCard)->create(['amount' => '1.70']);

        $this->assertSame(0.0, $this->creditCard->fresh()->balance_used);
    }

    #[Test]
    public function installments_of_an_odd_amount_add_up_to_it(): void
    {
        // Billed on the 20th: read before (15th) and after (25th) each closing
        $this->creditCard->update(['closing_date' => 20]);

        $purchase = Purchase::factory()->for($this->creditCard)->create([
            'date' => '2026-01-10',
            'amount' => '1000.01',
            'installment_count' => 7,
            'installment_amount' => '142.86',
            'commission' => '0.10',
        ]);

        $billed = 0;

        foreach (range(1, 7) as $month) {
            $this->travelTo(now()->setDate(2026, $month, 15));
            $billed += round($purchase->fresh()->installment_amount * 100);
            $this->travelTo(now()->setDate(2026, $month, 25));
        }

        // Every installment, commission included, adds up to the cent
        $this->assertSame(100011.0, (float) $billed);
        $this->assertSame(0.0, $purchase->fresh()->remaining_amount);
    }
}
