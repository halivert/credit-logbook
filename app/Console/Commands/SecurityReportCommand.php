<?php

namespace App\Console\Commands;

use App\Security\SecurityEvent;
use App\Security\SuspiciousActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Suspicious events per day and the IPs behind them, for the admin.
 */
class SecurityReportCommand extends Command
{
    protected $signature = 'security:report {--days=7 : How many days, today included (up to 90)}';

    protected $description = 'Suspicious sign in events per day and the IPs behind them';

    public function handle(): int
    {
        $days = min(SecurityEvent::KEEP_DAYS, max(1, (int) $this->option('days')));
        $timezone = config('security.timezone');

        $to = Carbon::now($timezone)->startOfDay()->addDay();
        $from = $to->copy()->subDays($days);

        $events = SuspiciousActivity::between($from->copy()->utc(), $to->copy()->utc());
        $byDay = $events->groupBy(fn (SecurityEvent $event) => $event->created_at->setTimezone($timezone)->toDateString());

        $rows = [];
        for ($day = $from->copy(); $day->lt($to); $day->addDay()) {
            $totals = SuspiciousActivity::totals($byDay->get($day->toDateString(), collect()));
            $rows[] = [$day->toDateString(), ...array_values($totals), array_sum($totals)];
        }

        $this->table(['Día', ...array_values(SuspiciousActivity::LABELS), 'Total'], $rows);

        $previous = SuspiciousActivity::between($from->copy()->subDays($days)->utc(), $from->copy()->utc());

        $this->components->twoColumnDetail(
            "Total en {$days} días",
            $this->comparison($events->count(), $previous->count()),
        );

        $ips = SuspiciousActivity::topIps($events, $from->copy()->utc(), $to->copy()->utc());

        if ($ips->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['IP', 'Intentos', 'Cuentas', 'Correos sin cuenta', 'Bloqueos', 'Entradas'],
                $ips->map(fn (array $ip) => array_values($ip))->all(),
            );

            if ($ips->contains(fn (array $ip) => $ip['sign_ins'] > 0)) {
                $this->components->warn('Hay IPs con intentos fallidos que también entraron: revisa si fue su dueño o si alguien adivinó una contraseña.');
            }
        }

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
