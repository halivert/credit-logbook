<?php

namespace App\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Failed sign ins per IP, whatever the email: the per email limit doesn't
 * stop an IP trying a few passwords on each of many emails (leaked lists).
 * Signing in successfully doesn't reset it, or an attacker could reset it
 * with an account of their own.
 */
final class LoginIpThrottle
{
    public static function isBlocked(string $ip): bool
    {
        return RateLimiter::tooManyAttempts(self::key($ip), config('security.login.ip_max_failures'));
    }

    public static function hit(string $ip): void
    {
        RateLimiter::hit(self::key($ip), config('security.login.ip_decay_minutes') * 60);
    }

    public static function availableIn(string $ip): int
    {
        return RateLimiter::availableIn(self::key($ip));
    }

    /**
     * Blocked attempts are recorded once a minute per IP, so a flood
     * doesn't flood the table too.
     */
    public static function shouldRecordBlock(string $ip): bool
    {
        return Cache::add('login-ip-blocked:'.$ip, true, 60);
    }

    private static function key(string $ip): string
    {
        return 'login-ip:'.$ip;
    }
}
