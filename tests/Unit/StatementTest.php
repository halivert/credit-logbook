<?php

namespace Tests\Unit;

use App\CreditCards\BillingCycle;
use App\CreditCards\Statement;
use App\Purchases\InstallmentPlan;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A card closing on the 20th and due on the 9th.
 */
class StatementTest extends TestCase
{
    private array $charges = [];

    private array $payments = [];

    private function buy(string $day, int $cents, ?InstallmentPlan $plan = null): void
    {
        $this->charges[] = ['day' => BillingCycle::day($day), 'cents' => $cents, 'plan' => $plan];
    }

    private function pay(string $day, int $cents): void
    {
        $this->payments[] = ['day' => BillingCycle::day($day), 'cents' => $cents];
    }

    private function statement(string $today, int $closingDay = 20, int $dueDay = 9): Statement
    {
        return Statement::calculate(
            new BillingCycle($closingDay, $dueDay, 'America/Mexico_City'),
            BillingCycle::day($today),
            $this->charges,
            $this->payments,
        );
    }

    #[Test]
    public function it_asks_for_what_was_bought_up_to_the_closing(): void
    {
        $this->buy('2026-10-05', 500_00);
        $this->buy('2026-10-20', 100_00); // on the closing day: included
        $this->buy('2026-10-25', 300_00); // after it: next statement

        $statement = $this->statement('2026-11-01');

        $this->assertSame('2026-10-20', $statement->closing->toDateString());
        $this->assertSame('2026-11-09', $statement->due->toDateString());
        $this->assertSame('2026-11-20', $statement->nextClosing->toDateString());
        $this->assertSame(600_00, $statement->toAvoidInterestCents);
        $this->assertSame(600_00, $statement->remainingCents);
        $this->assertSame(300_00, $statement->periodChargesCents);
        $this->assertFalse($statement->isOverdue);
    }

    #[Test]
    public function payments_after_the_closing_cover_it(): void
    {
        $this->buy('2026-10-05', 500_00);
        $this->pay('2026-10-28', 200_00);

        $statement = $this->statement('2026-11-01');

        $this->assertSame(500_00, $statement->toAvoidInterestCents);
        $this->assertSame(200_00, $statement->paidSinceClosingCents);
        $this->assertSame(300_00, $statement->remainingCents);

        $this->pay('2026-11-08', 300_00);

        $this->assertSame(0, $this->statement('2026-11-08')->remainingCents);
    }

    #[Test]
    public function it_is_overdue_only_after_the_due_date_and_while_something_is_missing(): void
    {
        $this->buy('2026-10-05', 500_00);

        $this->assertFalse($this->statement('2026-11-09')->isOverdue);
        $this->assertTrue($this->statement('2026-11-10')->isOverdue);

        $this->pay('2026-11-12', 500_00);

        $this->assertFalse($this->statement('2026-11-12')->isOverdue);
    }

    #[Test]
    public function installments_ask_only_for_what_each_closing_billed(): void
    {
        $this->buy('2026-10-15', 12_000_00, new InstallmentPlan(12_000_00, 12, 1_000_00, commissionCents: 100_00));

        // First closing: the first installment and the commission
        $this->assertSame(1_100_00, $this->statement('2026-11-01')->toAvoidInterestCents);

        $this->pay('2026-11-05', 1_100_00);

        // Second closing: only the new installment
        $this->assertSame(1_000_00, $this->statement('2026-11-25')->toAvoidInterestCents);
    }

    #[Test]
    public function an_unpaid_statement_carries_over_to_the_next_one(): void
    {
        $this->buy('2026-10-05', 500_00);
        $this->buy('2026-10-25', 300_00);

        $this->assertSame(800_00, $this->statement('2026-11-25')->toAvoidInterestCents);
    }

    #[Test]
    public function returns_lower_what_to_pay(): void
    {
        $this->buy('2026-10-05', 500_00);
        $this->buy('2026-10-18', -200_00);

        $this->assertSame(300_00, $this->statement('2026-11-01')->toAvoidInterestCents);
    }

    #[Test]
    public function a_balance_in_favor_asks_for_nothing(): void
    {
        $this->buy('2026-10-05', 500_00);
        $this->pay('2026-10-10', 800_00);

        $statement = $this->statement('2026-11-01');

        $this->assertSame(0, $statement->toAvoidInterestCents);
        $this->assertSame(0, $statement->remainingCents);
        $this->assertFalse($this->statement('2026-11-15')->isOverdue);
    }

    #[Test]
    public function the_due_date_can_be_in_the_same_month_as_the_closing(): void
    {
        $this->buy('2026-11-01', 500_00);

        $statement = $this->statement('2026-11-10', closingDay: 5, dueDay: 25);

        $this->assertSame('2026-11-05', $statement->closing->toDateString());
        $this->assertSame('2026-11-25', $statement->due->toDateString());
        $this->assertSame(500_00, $statement->toAvoidInterestCents);
    }

    #[Test]
    public function a_new_card_asks_for_nothing(): void
    {
        $this->buy('2026-10-25', 300_00);

        $statement = $this->statement('2026-11-01');

        $this->assertSame(0, $statement->toAvoidInterestCents);
        $this->assertSame(300_00, $statement->periodChargesCents);
    }
}
