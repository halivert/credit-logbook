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

    #[Test]
    public function cents_become_amounts(): void
    {
        $this->assertSame(333.33, Money::fromCents(33333));
        $this->assertSame(-0.01, Money::fromCents(-1));
        $this->assertSame(0.0, Money::fromCents(0));
    }
}
