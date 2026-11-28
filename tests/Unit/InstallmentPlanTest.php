<?php

namespace Tests\Unit;

use App\Purchases\InstallmentPlan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InstallmentPlanTest extends TestCase
{
    public static function evenSplits(): array
    {
        return [
            'exact' => [1200_00, 12, 100_00],
            'rounded up' => [1000_00, 3, 333_34],
            'odd cents' => [1000_01, 7, 142_86],
            'tiny' => [5, 3, 2],
            'one month' => [999_99, 1, 999_99],
        ];
    }

    #[Test]
    #[DataProvider('evenSplits')]
    public function the_even_split_rounds_up_to_the_cent(int $amount, int $count, int $installment): void
    {
        $this->assertSame($installment, InstallmentPlan::evenInstallmentCents($amount, $count));
    }

    #[Test]
    public function an_even_plan_bills_the_same_every_month(): void
    {
        $plan = new InstallmentPlan(1200_00, 12, 100_00);

        $this->assertSame(100_00, $plan->nextInstallmentCents(0));
        $this->assertSame(400_00, $plan->billedCents(4));
        $this->assertSame(800_00, $plan->remainingCents(4));
        $this->assertSame(100_00, $plan->nextInstallmentCents(11));
        $this->assertSame(0, $plan->remainingCents(12));
        $this->assertSame(0, $plan->nextInstallmentCents(12));
    }

    #[Test]
    public function the_last_installment_takes_what_a_short_installment_leaves(): void
    {
        // The bank says 333.33 for 1000 in 3
        $plan = new InstallmentPlan(1000_00, 3, 333_33);

        $this->assertSame(333_33, $plan->nextInstallmentCents(1));
        $this->assertSame(333_34, $plan->nextInstallmentCents(2));
        $this->assertSame(0, $plan->remainingCents(3));
    }

    #[Test]
    public function the_last_installment_is_capped_after_a_long_installment(): void
    {
        // 333.34 for 1000 in 3, or the user's own 350
        $this->assertSame(333_32, (new InstallmentPlan(1000_00, 3, 333_34))->nextInstallmentCents(2));
        $this->assertSame(300_00, (new InstallmentPlan(1000_00, 3, 350_00))->nextInstallmentCents(2));
    }

    #[Test]
    public function the_commission_is_billed_with_the_first_installment(): void
    {
        $plan = new InstallmentPlan(1200_00, 12, 100_00, commissionCents: 35_00);

        $this->assertSame(135_00, $plan->nextInstallmentCents(0));
        $this->assertSame(100_00, $plan->nextInstallmentCents(1));
        // The commission is a charge on its own, not part of the purchase
        $this->assertSame(1200_00, $plan->billedCents(12));
    }

    public static function plans(): array
    {
        return [
            'even' => [1200_00, 12, 100_00, 0],
            'short installment' => [1000_00, 3, 333_33, 0],
            'long installment' => [1000_00, 3, 333_34, 0],
            'user rounded up' => [1000_00, 3, 350_00, 0],
            'odd amount with commission' => [1000_01, 7, 142_86, 10],
            'one month' => [459_90, 1, 459_90, 25_00],
            'cents' => [5, 3, 2, 0],
        ];
    }

    #[Test]
    #[DataProvider('plans')]
    public function every_installment_adds_up_to_the_purchase_and_its_commission(
        int $amount,
        int $count,
        int $installment,
        int $commission,
    ): void {
        $plan = new InstallmentPlan($amount, $count, $installment, $commission);

        $billed = 0;

        foreach (range(0, $count - 1) as $alreadyBilled) {
            $next = $plan->nextInstallmentCents($alreadyBilled);

            $this->assertGreaterThanOrEqual(0, $next);
            $billed += $next;
        }

        $this->assertSame($amount + $commission, $billed);
        $this->assertSame(0, $plan->remainingCents($count));
    }
}
