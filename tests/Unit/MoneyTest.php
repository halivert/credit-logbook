<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public static function amounts(): array
    {
        return [
            'decimal string' => ['333.33', 33333],
            // 0.1 and 0.07 can't be represented exactly as floats
            'tenth' => ['0.1', 10],
            'float noise' => [0.07, 7],
            'database decimal' => ['1250.5000', 125050],
            'negative return' => ['-300.00', -30000],
            'largest accepted' => [Money::MAX, 99999999999],
            'integer' => [42, 4200],
            'missing' => [null, 0],
        ];
    }

    #[Test]
    #[DataProvider('amounts')]
    public function amounts_become_whole_cents(string|int|float|null $amount, int $cents): void
    {
        $this->assertSame($cents, Money::toCents($amount));
    }

    public static function cents(): array
    {
        return [
            'zero' => [0],
            'one cent' => [1],
            'odd cents' => [33333],
            'float trap' => [7],
            'negative return' => [-30000],
            'one cent back' => [-1],
            'largest accepted' => [99999999999],
        ];
    }

    #[Test]
    #[DataProvider('cents')]
    public function cents_round_trip_through_amounts(int $cents): void
    {
        $amount = Money::fromCents($cents);

        // A float, so JSON and the frontend always get a number with cents
        $this->assertIsFloat($amount);
        $this->assertSame($cents, Money::toCents($amount));
        $this->assertSame(number_format($cents / 100, 2, '.', ''), number_format($amount, 2, '.', ''));
    }
}
