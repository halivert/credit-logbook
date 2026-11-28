<?php

namespace App\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Security\EnsureIpIsNotBlocked;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    public function store(LoginRequest $request): string
    {
        $attrs = $request->validated();

        // The same limit per IP as the web sign in
        app(EnsureIpIsNotBlocked::class)->handle($request, fn () => null);

        $email = Str::lower($attrs[Fortify::username()]);

        $user = User::query()->where(Fortify::username(), $email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Counted like a web attempt: activity, metrics and the IP limit
            event(new Failed('sanctum', $user, [Fortify::username() => $email]));

            throw ValidationException::withMessages([
                Fortify::username() => [__('auth.failed')],
            ]);
        }

        // Tokens can't go through the two factor challenge yet, so they
        // must not become a way around it.
        if ($user->two_factor_confirmed_at) {
            throw ValidationException::withMessages([
                Fortify::username() => [__('auth.failed')],
            ]);
        }

        return $user->createToken(
            $attrs['device_name'] ?? 'Web'
        )->plainTextToken;
    }

    public function destroy(Request $request): JsonResponse
    {
        $tokenId = str($request->header('Authorization'))
            ->before('|')
            ->after('Bearer')
            ->trim();

        $request->user()->tokens()->where('id', $tokenId)->delete();

        return response()->json(null, 204);
    }
}
