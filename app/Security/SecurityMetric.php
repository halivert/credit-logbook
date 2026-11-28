<?php

namespace App\Security;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Anonymous hourly counts of suspicious events, for monitoring. Unlike the
 * per account activity, it also counts attempts on emails without an
 * account, which is how guessing passwords from leaked lists shows up.
 */
class SecurityMetric extends Model
{
    use MassPrunable;

    public $timestamps = false;

    public const LOGIN_FAILED = 'login_failed';
    /** For an email without an account */
    public const LOGIN_FAILED_UNKNOWN = 'login_failed_unknown';
    public const TWO_FACTOR_FAILED = 'two_factor_failed';
    public const LOCKOUT = 'lockout';

    public const TYPES = [
        self::LOGIN_FAILED,
        self::LOGIN_FAILED_UNKNOWN,
        self::TWO_FACTOR_FAILED,
        self::LOCKOUT,
    ];

    public const KEEP_DAYS = 365;

    protected $fillable = ['hour', 'type', 'total'];

    protected $casts = [
        'hour' => 'datetime',
        'total' => 'integer',
    ];

    public static function record(string $type): void
    {
        static::query()->upsert(
            [['hour' => Carbon::now('UTC')->startOfHour(), 'type' => $type, 'total' => 1]],
            ['hour', 'type'],
            ['total' => DB::raw('total + 1')],
        );
    }

    /**
     * Totals per type for the hours starting in [$from, $to).
     *
     * @return array<string, int>
     */
    public static function totals(Carbon $from, Carbon $to): array
    {
        $totals = static::query()
            ->where('hour', '>=', $from)
            ->where('hour', '<', $to)
            ->groupBy('type')
            ->selectRaw('type, sum(total) as total')
            ->pluck('total', 'type');

        return collect(self::TYPES)
            ->mapWithKeys(fn (string $type) => [$type => (int) ($totals[$type] ?? 0)])
            ->all();
    }

    public function prunable(): Builder
    {
        return static::where('hour', '<', now()->subDays(self::KEEP_DAYS));
    }
}
