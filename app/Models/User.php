<?php

namespace App\Models;

use App\CreditCards\CreditCard;
use App\Enums\Subscription;
use App\Notifications\ResetPassword;
use App\Security\SecurityEvent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements PasskeyUser
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'subscription' => Subscription::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'subscription' => Subscription::Free,
    ];

    /**
     * Stored in lowercase: sign in lowercases what's typed too.
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $email) => $email === null ? null : Str::lower($email));
    }

    /**
     * Queued (see App\Notifications\ResetPassword) and limited per account
     * and per day (config auth.reset_emails). Over a limit it isn't sent, but
     * the page answers the same, so it doesn't tell whether the account exists.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $limits = config('auth.reset_emails');

        $sent = RateLimiter::attempt("reset-email:{$this->id}", $limits['per_account_per_hour'], fn () => true, 3600)
            && RateLimiter::attempt('reset-email', $limits['per_day'], function () use ($token) {
                $this->notify(new ResetPassword($token));

                return true;
            }, 86400);

        if (! $sent) {
            Log::warning('Reset password email not sent: limit reached', ['user' => $this->id]);
        }
    }

    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class);
    }

    public function creditCards(): HasMany
    {
        return $this->hasMany(CreditCard::class);
    }
}
