<?php

namespace App\Invitations;

use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * A link for an invited person to choose their password, shared by hand (no
 * email needed). It's signed, expires, and stops working once the password
 * changes, so it can be used only once.
 */
final class Invitation
{
    public const DAYS = 7;

    public static function url(User $user): string
    {
        return URL::temporarySignedRoute(
            'invitation.accept',
            now()->addDays(self::DAYS),
            ['user' => $user, 'key' => self::key($user)],
        );
    }

    /**
     * Derived from the password hash: a new password, a new key.
     */
    public static function key(User $user): string
    {
        return substr(hash('sha256', $user->getAuthPassword()), 0, 32);
    }

    public static function isValid(User $user, string $key): bool
    {
        return hash_equals(self::key($user), $key);
    }
}
