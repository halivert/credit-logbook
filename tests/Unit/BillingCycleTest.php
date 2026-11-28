<?php

namespace Tests\Unit;

use App\CreditCards\BillingCycle;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BillingCycleTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    private function cycle(int $closingDay = 20, int $dueDay = 9): BillingCycle
    {
        return new BillingCycle($closingDay, $dueDay, 'America/Mexico_City');
    }

    public static function nextDates(): array
    {
        return [
            'later this month' => ['2026-11-05', 20, '2026-11-20'],
            'today is the day' => ['2026-11-20', 20, '2026-11-20'],
            'already passed' => ['2026-11-27', 9, '2026-12-09'],
            'across the year' => ['2026-12-27', 9, '2027-01-09'],
            'short month' => ['2027-02-10', 30, '2027-02-28'],
            'passed into a short month' => ['2027-01-31', 30, '2027-02-28'],
            'leap year' => ['2028-02-10', 30, '2028-02-29'],
        ];
    }

    #[Test]
    #[DataProvider('nextDates')]
    public function the_next_closing_and_due_dates_are_today_or_the_next_occurrence(
        string $today,
        int $day,
        string $expected,
    ): void {
        $cycle = $this->cycle(closingDay: $day, dueDay: $day);
        $from = BillingCycle::day($today);

        $this->assertSame($expected, $cycle->nextClosing($from)->toDateString());
        $this->assertSame($expected, $cycle->nextDue($from)->toDateString());
    }

    public static function closings(): array
    {
        return [
            'before the closing' => ['2026-10-15', '2026-10-19', 0],
            'on the closing' => ['2026-10-15', '2026-10-20', 1],
            'bought on the closing day' => ['2026-10-20', '2026-10-20', 1],
            'bought after the closing' => ['2026-10-21', '2026-11-19', 0],
            'one month later' => ['2026-10-21', '2026-11-20', 1],
            'across the year' => ['2026-11-25', '2027-02-27', 3],
            'several months' => ['2026-01-10', '2026-11-27', 11],
        ];
    }

    #[Test]
    #[DataProvider('closings')]
    public function closings_between_two_days_include_both(string $from, string $to, int $closings): void
    {
        $this->assertSame(
            $closings,
            $this->cycle()->closingsBetween(BillingCycle::day($from), BillingCycle::day($to)),
        );
    }

    #[Test]
    public function short_months_close_on_their_last_day(): void
    {
        $cycle = $this->cycle(closingDay: 30);

        $this->assertSame(1, $cycle->closingsBetween(BillingCycle::day('2027-02-01'), BillingCycle::day('2027-02-28')));
        $this->assertSame(0, $cycle->closingsBetween(BillingCycle::day('2027-02-01'), BillingCycle::day('2027-02-27')));
    }

    #[Test]
    public function today_is_in_the_cards_timezone(): void
    {
        // 02:00 UTC on the 21st is still the 20th in Mexico City
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-21 02:00', 'UTC'));

        $this->assertSame('2026-11-20', $this->cycle()->today()->toDateString());
        $this->assertSame('2026-11-20', $this->cycle()->nextClosing()->toDateString());
    }

    public static function lastClosings(): array
    {
        return [
            'earlier this month' => ['2026-11-25', 20, '2026-11-20'],
            'on the closing day' => ['2026-11-20', 20, '2026-11-20'],
            'last month' => ['2026-11-19', 20, '2026-10-20'],
            'across the year' => ['2027-01-03', 20, '2026-12-20'],
            'short month' => ['2027-03-10', 30, '2027-02-28'],
        ];
    }

    #[Test]
    #[DataProvider('lastClosings')]
    public function the_last_closing_is_today_or_before(string $on, int $closingDay, string $expected): void
    {
        $this->assertSame(
            $expected,
            $this->cycle(closingDay: $closingDay)->lastClosing(BillingCycle::day($on))->toDateString(),
        );
    }

    #[Test]
    public function a_statement_is_due_on_the_first_due_day_after_its_closing(): void
    {
        $this->assertSame('2026-12-09', $this->cycle(20, 9)->dueAfter(BillingCycle::day('2026-11-20'))->toDateString());
        $this->assertSame('2026-11-25', $this->cycle(5, 25)->dueAfter(BillingCycle::day('2026-11-05'))->toDateString());
        // A due day equal to the closing day is the next month's
        $this->assertSame('2026-12-20', $this->cycle(20, 20)->dueAfter(BillingCycle::day('2026-11-20'))->toDateString());
    }
}
