<?php

namespace App\Security;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reads suspicious events for monitoring: counts per category and the IPs
 * behind them. Never which account, beyond how many.
 */
final class SuspiciousActivity
{
    public const LABELS = [
        'login_failed' => 'Contraseña incorrecta',
        'login_failed_unknown' => 'Correo sin cuenta',
        SecurityEvent::TWO_FACTOR_FAILED => 'Código incorrecto',
        SecurityEvent::LOCKOUT => 'Bloqueos',
        SecurityEvent::IP_BLOCKED => 'IP bloqueada',
    ];

    /**
     * Wrong passwords split by whether the email had an account.
     */
    public static function category(SecurityEvent $event): string
    {
        if ($event->type === SecurityEvent::LOGIN_FAILED) {
            return $event->user_id ? 'login_failed' : 'login_failed_unknown';
        }

        return $event->type;
    }

    /**
     * @return Collection<int, SecurityEvent>
     */
    public static function between(Carbon $from, Carbon $to): Collection
    {
        return SecurityEvent::query()
            ->suspicious()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->get(['user_id', 'type', 'ip_address', 'created_at']);
    }

    /**
     * @return array<string, int>
     */
    public static function totals(Collection $events): array
    {
        $counts = $events->countBy(fn (SecurityEvent $event) => self::category($event));

        return collect(self::LABELS)->map(fn ($label, $category) => $counts[$category] ?? 0)->all();
    }

    /**
     * The IPs with the most suspicious events, and whether anyone signed in
     * from them in the same period.
     *
     * @return Collection<int, array{ip: string, attempts: int, accounts: int, unknown: int, lockouts: int, sign_ins: int}>
     */
    public static function topIps(Collection $events, Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        $byIp = $events->whereNotNull('ip_address')->groupBy('ip_address')
            ->sortByDesc(fn (Collection $events) => $events->count())
            ->take($limit);

        $signIns = SecurityEvent::query()
            ->where('type', SecurityEvent::LOGIN)
            ->whereIn('ip_address', $byIp->keys())
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->get(['ip_address'])
            ->countBy('ip_address');

        return $byIp->map(fn (Collection $events, string $ip) => [
            'ip' => $ip,
            'attempts' => $events->count(),
            'accounts' => $events->whereNotNull('user_id')->pluck('user_id')->unique()->count(),
            'unknown' => $events->filter(fn ($event) => self::category($event) === 'login_failed_unknown')->count(),
            'lockouts' => $events->whereIn('type', [SecurityEvent::LOCKOUT, SecurityEvent::IP_BLOCKED])->count(),
            'sign_ins' => $signIns[$ip] ?? 0,
        ])->values();
    }
}
