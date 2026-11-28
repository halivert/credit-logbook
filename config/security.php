<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | Anonymous counts of suspicious events (wrong passwords and codes,
    | lockouts). `php artisan security:report` shows them, and an hourly
    | check emails an alert when an hour has many more than usual.
    |
    */

    // Dates in reports and alerts
    'timezone' => env('SECURITY_TIMEZONE', 'America/Mexico_City'),

    'alerts' => [
        // Nobody is alerted without it
        'email' => env('SECURITY_ALERT_EMAIL'),

        // An hour alerts when it has at least this many suspicious events…
        'minimum' => (int) env('SECURITY_ALERT_MINIMUM', 20),

        // …and this many times the hourly average of the previous week
        'multiplier' => (float) env('SECURITY_ALERT_MULTIPLIER', 5),
    ],

];
