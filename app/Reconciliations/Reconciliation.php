<?php

namespace App\Reconciliations;

use App\CreditCards\Statement;

/**
 * Compares what the logbook says to pay to avoid interest at a closing with
 * what the bank's statement says, so a missing purchase or payment shows up.
 */
final readonly class Reconciliation
{
    public function __construct(
        /** What the logbook says was owed at the closing (negative in favor) */
        public int $logbookCents,
        /** The statement's "pago para no generar intereses" */
        public int $bankCents,
    ) {}

    public static function of(Statement $statement, int $bankCents): self
    {
        return new self($statement->closingBalanceCents, $bankCents);
    }

    /**
     * Positive: the bank charged more than the logbook has (a purchase or a
     * fee missing). Negative: the logbook has more (a payment or a return
     * missing, or something twice). Recording an adjustment of exactly this
     * amount on the closing date makes both match.
     */
    public function differenceCents(): int
    {
        // Nothing to pay on both sides, even if the logbook has a credit
        if ($this->bankCents === 0 && $this->logbookCents <= 0) {
            return 0;
        }

        return $this->bankCents - $this->logbookCents;
    }

    public function isMatched(): bool
    {
        return $this->differenceCents() === 0;
    }
}
