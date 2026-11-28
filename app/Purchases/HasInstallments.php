<?php

namespace App\Purchases;

use App\CreditCards\BillingCycle;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Like the bank, installments are billed one per closing date starting with
 * the first closing on or after the purchase was applied, whether or not
 * they're paid. Paying is tracked by the card's balance, not per purchase.
 * The math lives in InstallmentPlan and BillingCycle.
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

            $cycle = $this->creditCard->billingCycle();

            return min(
                $cycle->closingsBetween($this->day(), $cycle->today()),
                $this->installment_count,
            );
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
        return new Attribute(fn () => Money::fromCents(
            $this->installmentPlan()?->billedCents($this->billed_installments)
                ?? Money::toCents($this->getRawOriginal('amount'))
        ));
    }

    /**
     * Part of the purchase still to be billed in future statements.
     */
    protected function remainingAmount(): Attribute
    {
        return new Attribute(fn () => Money::fromCents(
            $this->installmentPlan()?->remainingCents($this->billed_installments) ?? 0
        ));
    }

    /**
     * The next installment to be billed.
     */
    protected function installmentAmount(): Attribute
    {
        return new Attribute(fn () => ($plan = $this->installmentPlan())
            ? Money::fromCents($plan->nextInstallmentCents($this->billed_installments))
            : null
        );
    }

    public function installmentPlan(): ?InstallmentPlan
    {
        if (! $this->installment_count) {
            return null;
        }

        return new InstallmentPlan(
            amountCents: Money::toCents($this->getRawOriginal('amount')),
            count: $this->installment_count,
            installmentCents: Money::toCents($this->getRawOriginal('installment_amount')),
            commissionCents: Money::toCents($this->getRawOriginal('commission')),
        );
    }

    /**
     * The day the purchase counts from: the bank's posting date when it's
     * known.
     */
    public function day(): CarbonImmutable
    {
        return BillingCycle::day(
            $this->getRawOriginal('applied_on') ?? $this->getRawOriginal('date')
        );
    }
}
