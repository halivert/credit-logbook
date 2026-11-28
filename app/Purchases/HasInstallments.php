<?php

namespace App\Purchases;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Like the bank, installments are billed one per closing date starting with
 * the first closing on or after the purchase was applied, whether or not
 * they're paid. Paying is tracked by the card's balance, not per purchase.
 */
trait HasInstallments
{
    abstract public function getRawOriginal($key = null, $default = null);

    protected function isInstallmentPurchase(): Attribute
    {
        return new Attribute(fn () => (bool) $this->installment_count);
    }

    protected function billedInstallments(): Attribute
    {
        return new Attribute(function () {
            if (! $this->installment_count) {
                return null;
            }

            $closings = $this->creditCard->closingsBetween(
                $this->day(),
                $this->creditCard->today(),
            );

            return min($closings, $this->installment_count);
        });
    }

    protected function remainingInstallments(): Attribute
    {
        return new Attribute(
            fn () => $this->installment_count
                ? $this->installment_count - $this->billed_installments
                : null
        );
    }

    /**
     * Part of the purchase already billed in a statement.
     */
    protected function billedAmount(): Attribute
    {
        return new Attribute(function () {
            if (! $this->installment_count) {
                return (float) $this->amount;
            }

            // Once every installment is billed, the whole purchase is
            if ($this->billed_installments >= $this->installment_count) {
                return (float) $this->amount;
            }

            $billed = $this->billed_installments * $this->getRawOriginal('installment_amount');

            return round(min($billed, $this->amount), 2);
        });
    }

    /**
     * Part of the purchase still to be billed in future statements.
     */
    protected function remainingAmount(): Attribute
    {
        return new Attribute(
            fn () => round(max(0, $this->amount - $this->billed_amount), 2)
        );
    }

    /**
     * The next installment to be billed. The bank's amount is used as is,
     * even when it doesn't divide the purchase evenly: the last installment
     * takes whatever is left, and the first one includes the commission.
     */
    protected function installmentAmount(): Attribute
    {
        return new Attribute(function ($amount) {
            if (! $this->installment_count) {
                return null;
            }

            $isLast = $this->billed_installments === $this->installment_count - 1;

            $installmentAmount = $isLast
                ? $this->remaining_amount
                : min($this->remaining_amount, (float) $amount);

            if ($this->billed_installments < 1) {
                return round($installmentAmount + $this->commission, 2);
            }

            return round($installmentAmount, 2);
        });
    }

    /**
     * The day the purchase counts from: the bank's posting date when it's
     * known.
     */
    public function day(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $this->getRawOriginal('applied_on') ?? $this->getRawOriginal('date'),
            'UTC',
        )->startOfDay();
    }
}
