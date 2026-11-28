<?php

namespace App\CreditCards;

use Carbon\CarbonImmutable;

/**
 * A card's monthly calendar: the day it closes (corte) and the day the
 * statement is due (fecha límite de pago), in the card's timezone.
 *
 * Days are plain calendar days, represented as midnight UTC. Months shorter
 * than the day use their last day (a card closing on the 30th closes on
 * February 28th).
 */
final readonly class BillingCycle
{
    public function __construct(
        public int $closingDay,
        public int $dueDay,
        public string $timezone,
    ) {}

    public static function of(CreditCard $creditCard): self
    {
        return new self($creditCard->closing_date, $creditCard->due_date, $creditCard->timezone);
    }

    /**
     * Today in the card's timezone.
     */
    public function today(): CarbonImmutable
    {
        return self::day(CarbonImmutable::now($this->timezone)->toDateString());
    }

    public function nextClosing(?CarbonImmutable $from = null): CarbonImmutable
    {
        return $this->nextOccurrence($this->closingDay, $from ?? $this->today());
    }

    public function nextDue(?CarbonImmutable $from = null): CarbonImmutable
    {
        return $this->nextOccurrence($this->dueDay, $from ?? $this->today());
    }

    /**
     * The latest closing, $on itself included.
     */
    public function lastClosing(?CarbonImmutable $on = null): CarbonImmutable
    {
        $on ??= $this->today();

        $closing = $this->dayOfMonth($on, $this->closingDay);

        if ($closing->lte($on)) {
            return $closing;
        }

        return $this->dayOfMonth($on->startOfMonth()->subMonth(), $this->closingDay);
    }

    /**
     * When the statement of a closing is due: the first due day after it.
     */
    public function dueAfter(CarbonImmutable $closing): CarbonImmutable
    {
        return $this->nextDue($closing->addDay());
    }

    /**
     * Closings on or after $from and up to $to, both included.
     */
    public function closingsBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $closings = 0;
        $month = $from->startOfMonth();

        while ($month->lte($to)) {
            $closing = $this->dayOfMonth($month, $this->closingDay);

            if ($closing->gte($from) && $closing->lte($to)) {
                $closings++;
            }

            $month = $month->addMonth();
        }

        return $closings;
    }

    public static function day(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, 'UTC')->startOfDay();
    }

    /**
     * $from itself or the next date falling on $day.
     */
    private function nextOccurrence(int $day, CarbonImmutable $from): CarbonImmutable
    {
        $candidate = $this->dayOfMonth($from, $day);

        if ($candidate->gte($from)) {
            return $candidate;
        }

        return $this->dayOfMonth($from->startOfMonth()->addMonth(), $day);
    }

    private function dayOfMonth(CarbonImmutable $month, int $day): CarbonImmutable
    {
        return $month->setDay(min($day, $month->daysInMonth));
    }
}
