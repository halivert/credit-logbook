<?php

namespace App\Security;

/**
 * "Chrome en macOS" from a user agent: enough to recognize your own devices.
 * Unknown parts are left out rather than guessed.
 */
final class Device
{
    private const BROWSERS = [
        'Edg/' => 'Edge',
        'OPR/' => 'Opera',
        'SamsungBrowser/' => 'Samsung Internet',
        'Firefox/' => 'Firefox',
        'FxiOS/' => 'Firefox',
        'CriOS/' => 'Chrome',
        'Chrome/' => 'Chrome',
        'Safari/' => 'Safari',
    ];

    private const SYSTEMS = [
        'iPhone' => 'iPhone',
        'iPad' => 'iPad',
        'Android' => 'Android',
        'CrOS' => 'ChromeOS',
        'Mac OS X' => 'macOS',
        'Windows' => 'Windows',
        'Linux' => 'Linux',
    ];

    public static function describe(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $browser = self::first(self::BROWSERS, $userAgent);
        $system = self::first(self::SYSTEMS, $userAgent);

        return match (true) {
            $browser && $system => "{$browser} en {$system}",
            default => $browser ?? $system,
        };
    }

    private static function first(array $needles, string $userAgent): ?string
    {
        foreach ($needles as $needle => $name) {
            if (str_contains($userAgent, $needle)) {
                return $name;
            }
        }

        return null;
    }
}
