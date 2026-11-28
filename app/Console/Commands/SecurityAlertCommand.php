<?php

namespace App\Console\Commands;

use App\Security\SecurityMetric;
use App\Security\SuspiciousActivityDetected;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Runs every hour: emails an alert when the hour that just ended had many
 * more suspicious events than usual.
 */
class SecurityAlertCommand extends Command
{
    protected $signature = 'security:alert';

    protected $description = 'Email an alert when the last hour had unusually many suspicious events';

    public function handle(): int
    {
        $email = config('security.alerts.email');

        if (! $email) {
            $this->components->warn('SECURITY_ALERT_EMAIL is not set, nobody would be alerted.');

            return self::SUCCESS;
        }

        $to = Carbon::now('UTC')->startOfHour();
        $from = $to->copy()->subHour();

        $totals = SecurityMetric::totals($from, $to);
        $total = array_sum($totals);

        // Hourly average of the week before
        $baseline = array_sum(SecurityMetric::totals($from->copy()->subDays(7), $from)) / (7 * 24);

        $threshold = max(
            config('security.alerts.minimum'),
            (int) ceil($baseline * config('security.alerts.multiplier')),
        );

        if ($total < $threshold) {
            $this->components->info("{$total} eventos sospechosos en la última hora, el umbral es {$threshold}.");

            return self::SUCCESS;
        }

        Notification::route('mail', $email)
            ->notify(new SuspiciousActivityDetected($from, $totals, $baseline));

        $this->components->warn("{$total} eventos sospechosos en la última hora: alerta enviada.");

        return self::SUCCESS;
    }
}
