<?php

namespace App\Support;

/**
 * Money is validated to whole cents and computed in integer cents, so sums
 * like 0.10 + 0.20 - 0.30 are exactly 0.
 */
final class Money
{
    /** Largest amount accepted: any real card, well within decimal(19, 4) */
    public const MAX = '999999999.99';

    /**
     * Rules for an amount with at most two decimals. The decimal rule also
     * rejects things like "1e3", "1,000" or "-0.0000000000001".
     *
     * @return list<string>
     */
    public static function rules(string $min = '0.01'): array
    {
        return ['decimal:0,2', "min:{$min}", 'max:'.self::MAX];
    }

    public static function toCents(string|int|float|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): float
    {
        return $cents / 100;
    }
}
