<?php

namespace App\Console\Commands;

use App\Security\SuspiciousActivity;
use App\Security\SuspiciousActivityDetected;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Runs every hour: emails the admin when the hour that just ended had many
 * more suspicious events than usual.
 */
class SecurityAlertCommand extends Command
{
    protected $signature = 'security:alert';

    protected $description = 'Email the admin when the last hour had unusually many suspicious events';

    public function handle(): int
    {
        $email = config('security.alerts.email');

        if (! $email) {
            $this->components->warn('SECURITY_ALERT_EMAIL is not set, nobody would be alerted.');

            return self::SUCCESS;
        }

        $to = Carbon::now('UTC')->startOfHour();
        $from = $to->copy()->subHour();

        $events = SuspiciousActivity::between($from, $to);

        // Hourly average of the week before
        $baseline = SuspiciousActivity::between($from->copy()->subDays(7), $from)->count() / (7 * 24);

        $threshold = max(
            config('security.alerts.minimum'),
            (int) ceil($baseline * config('security.alerts.multiplier')),
        );

        if ($events->count() < $threshold) {
            $this->components->info("{$events->count()} eventos sospechosos en la última hora, el umbral es {$threshold}.");

            return self::SUCCESS;
        }

        Notification::route('mail', $email)->notify(new SuspiciousActivityDetected(
            $from,
            SuspiciousActivity::totals($events),
            $baseline,
            SuspiciousActivity::topIps($events, $from, $to, limit: 3)->all(),
        ));

        $this->components->warn("{$events->count()} eventos sospechosos en la última hora: alerta enviada.");

        return self::SUCCESS;
    }
}
