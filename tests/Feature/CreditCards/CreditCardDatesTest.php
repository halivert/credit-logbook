<?php

namespace Tests\Feature\CreditCards;

use App\CreditCards\CreditCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreditCardDatesTest extends TestCase
{
    use RefreshDatabase;

    public static function dates(): array
    {
        return [
            'later this month' => ['2026-11-05 12:00', 20, '2026-11-20'],
            'today is the day' => ['2026-11-20 12:00', 20, '2026-11-20'],
            'already passed' => ['2026-11-27 12:00', 9, '2026-12-09'],
            'across the year' => ['2026-12-27 12:00', 9, '2027-01-09'],
            'short month' => ['2027-02-10 12:00', 30, '2027-02-28'],
            'passed into short month' => ['2027-01-31 12:00', 30, '2027-02-28'],
            // 02:00 UTC is still the 19th in Mexico City
            'card timezone, not utc' => ['2026-11-20 02:00', 20, '2026-11-20'],
            'card timezone, day before' => ['2026-11-21 02:00', 20, '2026-11-20'],
        ];
    }

    #[Test]
    #[DataProvider('dates')]
    public function next_closing_and_due_dates_are_today_or_the_next_occurrence(
        string $nowUtc,
        int $day,
        string $expected,
    ): void {
        Carbon::setTestNow(Carbon::parse($nowUtc, 'UTC'));

        $creditCard = CreditCard::factory()->create([
            'closing_date' => $day,
            'due_date' => $day,
            'timezone' => 'America/Mexico_City',
        ]);

        $this->assertSame($expected, $creditCard->next_closing_date);
        $this->assertSame($expected, $creditCard->next_due_date);
    }
}
