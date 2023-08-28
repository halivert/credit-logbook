<?php

namespace App\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    public function store(LoginRequest $request): string
    {
        $attrs = $request->validated();

        $user = User::query()->where(
            Fortify::username(),
            $attrs[Fortify::username()]
        )->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
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
