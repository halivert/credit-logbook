<?php

namespace App\Console\Commands;

use App\Security\SecurityMetric;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Suspicious events per day, anonymous, compared with the previous period.
 */
class SecurityReportCommand extends Command
{
    protected $signature = 'security:report {--days=7 : How many days, today included}';

    protected $description = 'Anonymous counts of suspicious sign in events';

    private const LABELS = [
        SecurityMetric::LOGIN_FAILED => 'Contraseña incorrecta',
        SecurityMetric::LOGIN_FAILED_UNKNOWN => 'Correo sin cuenta',
        SecurityMetric::TWO_FACTOR_FAILED => 'Código incorrecto',
        SecurityMetric::LOCKOUT => 'Bloqueos',
    ];

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $timezone = config('security.timezone');

        $to = Carbon::now($timezone)->startOfDay()->addDay();
        $from = $to->copy()->subDays($days);

        $rows = SecurityMetric::query()
            ->where('hour', '>=', $from->copy()->utc())
            ->where('hour', '<', $to->copy()->utc())
            ->get()
            ->groupBy(fn (SecurityMetric $metric) => $metric->hour->setTimezone($timezone)->toDateString());

        $table = [];
        for ($day = $from->copy(); $day->lt($to); $day->addDay()) {
            $metrics = $rows->get($day->toDateString(), collect());
            $counts = array_map(
                fn (string $type) => (int) $metrics->where('type', $type)->sum('total'),
                SecurityMetric::TYPES,
            );

            $table[] = [$day->toDateString(), ...$counts, array_sum($counts)];
        }

        $this->table(['Día', ...array_values(self::LABELS), 'Total'], $table);

        $current = SecurityMetric::totals($from->copy()->utc(), $to->copy()->utc());
        $previous = SecurityMetric::totals($from->copy()->subDays($days)->utc(), $from->copy()->utc());

        $this->components->twoColumnDetail(
            "Total en {$days} días",
            $this->comparison(array_sum($current), array_sum($previous)),
        );

        return self::SUCCESS;
    }

    private function comparison(int $current, int $previous): string
    {
        if ($previous === 0) {
            return "{$current} (antes: 0)";
        }

        $change = (int) round(($current - $previous) / $previous * 100);

        return sprintf('%d (%s%d %% contra el periodo anterior)', $current, $change >= 0 ? '+' : '', $change);
    }
}
