<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | Suspicious events (wrong passwords and codes, lockouts, blocked IPs),
    | also on emails without an account. `php artisan security:report`
    | shows them with the IPs behind them, and an hourly check emails an
    | alert when an hour has many more than usual.
    |
    */

    // Failed sign ins per IP, whatever the email, before refusing it
    'login' => [
        'ip_max_failures' => (int) env('SECURITY_LOGIN_IP_MAX_FAILURES', 30),
        'ip_decay_minutes' => (int) env('SECURITY_LOGIN_IP_DECAY_MINUTES', 60),
    ],

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
