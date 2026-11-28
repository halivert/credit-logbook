<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Security events older than 90 days
        $schedule->command('model:prune')->daily();

        // Unusually many wrong passwords, codes or lockouts in the last hour
        $schedule->command('security:alert')->hourlyAt(5);

        // Database backups, kept config('backup.keep_days'); an email if it fails
        $backup = $schedule->command('db:backup')->dailyAt('04:00');

        if ($email = config('security.alerts.email')) {
            $backup->emailOutputOnFailure($email);
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
