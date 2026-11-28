<?php

namespace App\Purchases;

/**
 * How a purchase in installments ("meses") is billed, in cents.
 *
 * The bank's installment is used as is, even when it doesn't divide the
 * purchase evenly: the last installment takes whatever is left. The opening
 * commission is billed with the first one.
 */
final readonly class InstallmentPlan
{
    public function __construct(
        public int $amountCents,
        public int $count,
        public int $installmentCents,
        public int $commissionCents = 0,
    ) {}

    /**
     * The amount split evenly, rounded up to the cent like banks do.
     */
    public static function evenInstallmentCents(int $amountCents, int $count): int
    {
        return intdiv($amountCents + $count - 1, $count);
    }

    /**
     * Part of the purchase billed after $billed installments.
     */
    public function billedCents(int $billed): int
    {
        if ($billed >= $this->count) {
            return $this->amountCents;
        }

        return min(max(0, $billed) * $this->installmentCents, $this->amountCents);
    }

    public function remainingCents(int $billed): int
    {
        return $this->amountCents - $this->billedCents($billed);
    }

    /**
     * What the next statement bills after $billed installments, 0 when done.
     */
    public function nextInstallmentCents(int $billed): int
    {
        if ($billed >= $this->count) {
            return 0;
        }

        $remaining = $this->remainingCents($billed);

        $installment = $billed === $this->count - 1
            ? $remaining
            : min($remaining, $this->installmentCents);

        return $billed === 0 ? $installment + $this->commissionCents : $installment;
    }
}
