<?php

/**
 * Daily database backups (php artisan db:backup), run by the scheduler.
 */
return [
    // Kept on the same server: copy them elsewhere from time to time
    'path' => env('BACKUP_PATH') ?: storage_path('app/backups'),

    // The privacy notice promises deleted data is gone from backups by then
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 30),
];
