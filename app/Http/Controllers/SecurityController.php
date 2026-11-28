<?php

namespace App\Http\Controllers;

use App\Security\Device;
use App\Security\SecurityEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

/**
 * How a person signs in: passkeys and two factor authentication. The page
 * asks for the password first, so everything done from it is confirmed.
 */
class SecurityController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        $pending = $user->two_factor_secret && ! $user->two_factor_confirmed_at;

        // Recovery codes are only shown right after they're created
        $showRecoveryCodes = $user->two_factor_confirmed_at && in_array(session('status'), [
            Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED,
            Fortify::RECOVERY_CODES_GENERATED,
        ]);

        return Inertia::render('Profile/Security', [
            'twoFactor' => [
                'enabled' => (bool) $user->two_factor_confirmed_at,
                'pending' => (bool) $pending,
                'qrCodeSvg' => $pending ? $user->twoFactorQrCodeSvg() : null,
                'setupKey' => $pending ? decrypt($user->two_factor_secret) : null,
                'recoveryCodes' => $showRecoveryCodes ? $user->recoveryCodes() : null,
            ],
            // Never the credential itself
            'passkeys' => $user->passkeys()
                ->latest()
                ->get()
                ->map(fn ($passkey) => [
                    'id' => $passkey->id,
                    'name' => $passkey->name,
                    'authenticator' => $passkey->authenticator,
                    'createdAt' => $passkey->created_at->toDateString(),
                    'lastUsedAt' => $passkey->last_used_at?->toDateString(),
                ]),
            'events' => $user->securityEvents()
                ->latest()
                ->limit(30)
                ->get()
                ->map(fn (SecurityEvent $event) => [
                    'id' => $event->id,
                    'type' => $event->type,
                    'method' => $event->method,
                    'detail' => $event->detail,
                    'device' => Device::describe($event->user_agent),
                    'ip' => $event->ip_address,
                    'at' => $event->created_at->toIso8601String(),
                    'suspicious' => in_array($event->type, SecurityEvent::SUSPICIOUS),
                ]),
        ]);
    }
}
