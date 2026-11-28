<?php

namespace App\CreditCards;

use App\Purchases\InstallmentPlan;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * The statement of the latest closing (estado de cuenta), from the logbook:
 * how much to pay by the due date to avoid interest and how much of it is
 * still missing. Interest and minimum payments are not modeled; reconciling
 * with the bank takes care of the differences.
 *
 * Amounts are in cents. Charges and payments are plain arrays so the math
 * doesn't depend on the database:
 * - charges: ['day' => CarbonImmutable, 'cents' => int, 'plan' => ?InstallmentPlan]
 *   (returns and adjustments in favor have negative cents)
 * - payments: ['day' => CarbonImmutable, 'cents' => int]
 */
final readonly class Statement
{
    public function __construct(
        public CarbonImmutable $closing,
        public CarbonImmutable $due,
        public CarbonImmutable $nextClosing,
        /** What was owed at the closing, negative when in favor */
        public int $closingBalanceCents,
        /** Pago para no generar intereses */
        public int $toAvoidInterestCents,
        public int $paidSinceClosingCents,
        public int $remainingCents,
        /** Charged since the closing, billed in the next statement */
        public int $periodChargesCents,
        public bool $isOverdue,
    ) {}

    /**
     * @param  iterable<array{day: CarbonImmutable, cents: int, plan: ?InstallmentPlan}>  $charges
     * @param  iterable<array{day: CarbonImmutable, cents: int}>  $payments
     */
    public static function calculate(
        BillingCycle $cycle,
        CarbonImmutable $today,
        iterable $charges,
        iterable $payments,
    ): self {
        $charges = collect($charges);
        $payments = collect($payments);

        $closing = $cycle->lastClosing($today);
        $due = $cycle->dueAfter($closing);

        $billedThrough = fn (CarbonImmutable $day) => $charges->sum(
            fn (array $charge) => self::billed($cycle, $charge, $day)
        );

        $paidThrough = fn (CarbonImmutable $day) => $payments
            ->filter(fn (array $payment) => $payment['day']->lte($day))
            ->sum('cents');

        $closingBalance = $billedThrough($closing) - $paidThrough($closing);
        $toAvoidInterest = max(0, $closingBalance);
        $paidSinceClosing = $paidThrough($today) - $paidThrough($closing);
        $remaining = max(0, $toAvoidInterest - $paidSinceClosing);

        return new self(
            closing: $closing,
            due: $due,
            nextClosing: $cycle->nextClosing($closing->addDay()),
            closingBalanceCents: $closingBalance,
            toAvoidInterestCents: $toAvoidInterest,
            paidSinceClosingCents: $paidSinceClosing,
            remainingCents: $remaining,
            periodChargesCents: $billedThrough($today) - $billedThrough($closing),
            isOverdue: $remaining > 0 && $today->gt($due),
        );
    }

    /**
     * What a charge had billed by the end of $day: a single payment purchase
     * or return once it happened, installments one per closing (the first
     * one with the commission).
     */
    private static function billed(BillingCycle $cycle, array $charge, CarbonImmutable $day): int
    {
        if ($charge['day']->gt($day)) {
            return 0;
        }

        /** @var InstallmentPlan|null $plan */
        $plan = $charge['plan'];

        if (! $plan) {
            return $charge['cents'];
        }

        $billed = min($cycle->closingsBetween($charge['day'], $day), $plan->count);

        if ($billed === 0) {
            return 0;
        }

        return $plan->billedCents($billed) + $plan->commissionCents;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'closing_date' => $this->closing->toDateString(),
            'due_date' => $this->due->toDateString(),
            'next_closing_date' => $this->nextClosing->toDateString(),
            'to_avoid_interest' => Money::fromCents($this->toAvoidInterestCents),
            'paid_since_closing' => Money::fromCents($this->paidSinceClosingCents),
            'remaining' => Money::fromCents($this->remainingCents),
            'period_charges' => Money::fromCents($this->periodChargesCents),
            'is_overdue' => $this->isOverdue,
        ];
    }
}
