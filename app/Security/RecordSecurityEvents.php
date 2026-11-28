<?php

namespace App\Security;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;

/**
 * Writes down sign ins and changes to how an account signs in. Failed
 * attempts on emails without an account are kept without a user (and
 * without the email) for monitoring; nobody sees them in their activity.
 */
class RecordSecurityEvents
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'login',
            Failed::class => 'failed',
            Lockout::class => 'lockout',
            TwoFactorAuthenticationFailed::class => 'twoFactorFailed',
            PasswordUpdatedViaController::class => fn ($event) => $this->record($event->user, SecurityEvent::PASSWORD_CHANGED),
            PasswordReset::class => fn ($event) => $this->record($event->user, SecurityEvent::PASSWORD_RESET),
            TwoFactorAuthenticationConfirmed::class => fn ($event) => $this->record($event->user, SecurityEvent::TWO_FACTOR_ENABLED),
            TwoFactorAuthenticationDisabled::class => fn ($event) => $this->record($event->user, SecurityEvent::TWO_FACTOR_DISABLED),
            RecoveryCodesGenerated::class => fn ($event) => $this->record($event->user, SecurityEvent::RECOVERY_CODES_GENERATED),
            PasskeyRegistered::class => fn ($event) => $this->record($event->user, SecurityEvent::PASSKEY_ADDED, detail: $event->passkey->name),
            PasskeyDeleted::class => fn ($event) => $this->record($event->user, SecurityEvent::PASSKEY_REMOVED, detail: $event->passkey->name),
        ];
    }

    public function login(Login $event): void
    {
        $this->record($event->user, SecurityEvent::LOGIN, method: $this->loginMethod($event));
    }

    public function failed(Failed $event): void
    {
        LoginIpThrottle::hit((string) request()->ip());

        // Also without an account: that's how leaked lists show up
        $this->record($event->user, SecurityEvent::LOGIN_FAILED, anonymous: true);
    }

    public function twoFactorFailed(TwoFactorAuthenticationFailed $event): void
    {
        $this->record($event->user, SecurityEvent::TWO_FACTOR_FAILED);
    }

    public function lockout(Lockout $event): void
    {
        $email = $event->request->input(Fortify::username());

        $this->record(
            is_string($email) ? User::query()->where('email', Str::lower($email))->first() : null,
            SecurityEvent::LOCKOUT,
            anonymous: true,
        );
    }

    private function loginMethod(Login $event): string
    {
        $request = request();

        return match ($request->route()?->getName()) {
            'login.store' => 'password',
            'two-factor.login.store' => $request->filled('recovery_code') ? 'recovery_code' : 'two_factor',
            'passkey.login' => 'passkey',
            'register.store' => 'registration',
            default => $event->remember ? 'remember' : 'other',
        };
    }

    /**
     * Anonymous events (no account) never keep the email, only where the
     * attempt came from, for monitoring.
     */
    private function record(mixed $user, string $type, ?string $method = null, ?string $detail = null, bool $anonymous = false): void
    {
        if (! $user instanceof User && ! $anonymous) {
            return;
        }

        $request = request();

        SecurityEvent::query()->forceCreate([
            'user_id' => $user instanceof User ? $user->getKey() : null,
            'type' => $type,
            'method' => $method,
            'detail' => $detail === null ? null : Str::limit($detail, 250, ''),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, '') ?: null,
        ]);
    }
}
