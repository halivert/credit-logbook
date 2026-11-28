<?php

namespace App\Purchases;

use App\Support\Money;
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
        return new Attribute(fn () => Money::fromCents($this->billedCents()));
    }

    /**
     * Part of the purchase still to be billed in future statements.
     */
    protected function remainingAmount(): Attribute
    {
        return new Attribute(fn () => Money::fromCents($this->remainingCents()));
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

            $cents = $isLast
                ? $this->remainingCents()
                : min($this->remainingCents(), Money::toCents($amount));

            if ($this->billed_installments < 1) {
                $cents += Money::toCents($this->commission);
            }

            return Money::fromCents($cents);
        });
    }

    private function billedCents(): int
    {
        $amount = Money::toCents($this->getRawOriginal('amount'));

        // Single payment purchases and fully billed installments
        if (! $this->installment_count || $this->billed_installments >= $this->installment_count) {
            return $amount;
        }

        $installment = Money::toCents($this->getRawOriginal('installment_amount'));

        return min($this->billed_installments * $installment, $amount);
    }

    private function remainingCents(): int
    {
        return max(0, Money::toCents($this->getRawOriginal('amount')) - $this->billedCents());
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
