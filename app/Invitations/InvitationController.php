<?php

namespace App\Invitations;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class InvitationController
{
    /**
     * Sends the invited person to choose their password, with a fresh reset
     * token: it lasts an hour, the invitation a week.
     */
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        if (! Invitation::isValid($user, $request->string('key')->toString())) {
            return redirect()->route('login')
                ->with('status', 'Esta invitación ya se usó. Entra con tu correo y contraseña.');
        }

        $token = Password::broker(config('fortify.passwords'))->createToken($user);

        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $user->email,
            'invitacion' => 1,
        ]);
    }
}
