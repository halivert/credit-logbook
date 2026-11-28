<?php

namespace Tests\Unit;

use App\Reconciliations\Reconciliation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReconciliationTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'matches' => [3_550_50, 3_550_50, 0],
            'a purchase is missing' => [3_200_50, 3_550_50, 350_00],
            'a payment is missing' => [3_550_50, 3_200_50, -350_00],
            'a cent off' => [100_00, 100_01, 1],
            'both owe nothing' => [0, 0, 0],
            'logbook in favor, bank asks nothing' => [-500_00, 0, 0],
            'logbook in favor, bank asks something' => [-100_00, 50_00, 150_00],
            'logbook asks nothing, bank asks something' => [0, 80_00, 80_00],
        ];
    }

    #[Test]
    #[DataProvider('cases')]
    public function the_difference_is_what_the_bank_has_that_the_logbook_does_not(
        int $logbook,
        int $bank,
        int $difference,
    ): void {
        $reconciliation = new Reconciliation($logbook, $bank);

        $this->assertSame($difference, $reconciliation->differenceCents());
        $this->assertSame($difference === 0, $reconciliation->isMatched());
    }

    #[Test]
    #[DataProvider('cases')]
    public function adjusting_by_the_difference_matches(int $logbook, int $bank, int $difference): void
    {

        $this->assertTrue((new Reconciliation($logbook + $difference, $bank))->isMatched());
    }
}
