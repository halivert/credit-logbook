<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

/**
 * How a person signs in: two factor authentication and passkeys. The page
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
        ]);
    }
}
