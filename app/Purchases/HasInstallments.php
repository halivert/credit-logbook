<?php

namespace App\Purchases;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasInstallments
{
    /**
     * @param string|array $key
     * @param mixed $default
     * @return mixed
     */
    abstract public function getRawOriginal($key = null, $default = null);

    protected function isInstallmentPurchase(): Attribute
    {
        return new Attribute(fn() => !!$this->installment_count);
    }

    protected function paidMonths(): Attribute
    {
        return new Attribute(function () {
            return min(
                $this->payments->first()?->pivot->installment_number ?? 0,
                $this->installment_count
            );
        });
    }

    protected function remainingMonths(): Attribute
    {
        return new Attribute(function () {
            return $this->installment_count - $this->paid_months;
        });
    }

    public function paidAmount(): Attribute
    {
        $installmentAmount = $this->getRawOriginal('installment_amount');

        return new Attribute(function () use ($installmentAmount) {
            if ($this->paid_at) return $this->amount;

            $paid = $this->paid_months * $installmentAmount;

            return min($paid, $this->amount);
        });
    }

    public function remainingAmount(): Attribute
    {
        return new Attribute(
            fn() => max(0, $this->amount - $this->paid_amount)
        );
    }

    public function installmentAmount(): Attribute
    {
        return new Attribute(function ($amount) {
            $installmentAmount = min($this->remaining_amount, $amount);

            if ($this->paid_months < 1) {
                return $installmentAmount + $this->commission;
            }

            return $installmentAmount;
        });
    }
}
