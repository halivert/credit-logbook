<?php

namespace App\Security;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * First step of the sign in: refuses IPs with too many failed attempts.
 */
class EnsureIpIsNotBlocked
{
    public function handle(Request $request, Closure $next)
    {
        $ip = (string) $request->ip();

        if (! LoginIpThrottle::isBlocked($ip)) {
            return $next($request);
        }

        if (LoginIpThrottle::shouldRecordBlock($ip)) {
            SecurityEvent::query()->create([
                'type' => SecurityEvent::IP_BLOCKED,
                'ip_address' => $ip,
                'user_agent' => substr((string) $request->userAgent(), 0, 500) ?: null,
            ]);
        }

        $seconds = LoginIpThrottle::availableIn($ip);

        throw ValidationException::withMessages([
            Fortify::username() => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}
