<?php

namespace App\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something that happened to how an account signs in, so its owner can
 * notice what they didn't do. Kept for 90 days.
 */
class SecurityEvent extends Model
{
    use HasUuids, MassPrunable;

    public const UPDATED_AT = null;

    public const LOGIN = 'login';
    public const LOGIN_FAILED = 'login_failed';
    public const TWO_FACTOR_FAILED = 'two_factor_failed';
    public const LOCKOUT = 'lockout';
    public const PASSWORD_CHANGED = 'password_changed';
    public const PASSWORD_RESET = 'password_reset';
    public const TWO_FACTOR_ENABLED = 'two_factor_enabled';
    public const TWO_FACTOR_DISABLED = 'two_factor_disabled';
    public const RECOVERY_CODES_GENERATED = 'recovery_codes_generated';
    public const PASSKEY_ADDED = 'passkey_added';
    public const PASSKEY_REMOVED = 'passkey_removed';
    /** A sign in refused because its IP failed too many times */
    public const IP_BLOCKED = 'ip_blocked';

    /** Events worth a second look */
    public const SUSPICIOUS = [self::LOGIN_FAILED, self::TWO_FACTOR_FAILED, self::LOCKOUT, self::IP_BLOCKED];

    public const KEEP_DAYS = 90;

    protected $fillable = [
        'type',
        'method',
        'detail',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuspicious(Builder $query): void
    {
        $query->whereIn('type', self::SUSPICIOUS);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }
}
