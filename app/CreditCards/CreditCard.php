<?php

namespace App\CreditCards;

use App\Models\User;
use App\Payments\Payment;
use App\Purchases\Purchase;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CreditCard extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'due_date',
        'closing_date',
        'interest_rate',
        'limit',
        'timezone',
    ];

    protected $casts = [
        'due_date' => 'integer',
        'closing_date' => 'integer',
        'interest_rate' => 'decimal:2',
        'limit' => 'encrypted'
    ];

    protected $appends = [
        'next_due_date',
        'next_closing_date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchases(): HasMany
    {
        // Installment progress depends on the card's closing dates
        return $this->hasMany(Purchase::class)->chaperone();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function nextClosingDate(): Attribute
    {
        return new Attribute(
            fn () => $this->nextOccurrenceOfDay($this->closing_date)->toDateString()
        );
    }

    protected function nextDueDate(): Attribute
    {
        return new Attribute(
            fn () => $this->nextOccurrenceOfDay($this->due_date)->toDateString()
        );
    }

    protected function availableCredit(): Attribute
    {
        return new Attribute(
            fn () => Money::fromCents(Money::toCents($this->limit) - $this->balanceCents())
        );
    }

    /**
     * Today or the next date falling on the given day of the month, in the
     * card's timezone. Short months use their last day (a card closing on
     * the 30th closes on February 28th).
     */
    private function nextOccurrenceOfDay(int $day): CarbonImmutable
    {
        $today = CarbonImmutable::today($this->timezone);

        $candidate = $today->setDay(min($day, $today->daysInMonth));

        if ($candidate->gte($today)) {
            return $candidate;
        }

        $nextMonth = $today->startOfMonth()->addMonth();

        return $nextMonth->setDay(min($day, $nextMonth->daysInMonth));
    }

    /**
     * What the bank says you owe: every charge (purchases, including the
     * installments not billed yet, and their commissions) minus every credit
     * (payments and returns, which are negative purchases).
     */
    protected function balanceUsed(): Attribute
    {
        return new Attribute(fn () => Money::fromCents($this->balanceCents()));
    }

    /**
     * The sums are exact in the database (decimal columns), each one is turned
     * into cents before subtracting.
     */
    private function balanceCents(): int
    {
        $charges = $this->purchases()
            ->toBase()
            ->sum(DB::raw('amount + coalesce(commission, 0)'));

        $payments = $this->payments()->toBase()->sum('amount');

        return Money::toCents($charges) - Money::toCents($payments);
    }

    /**
     * Closing dates on or after $from and up to $to (both calendar days).
     */
    public function closingsBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $closings = 0;
        $month = $from->startOfMonth();

        while ($month->lte($to)) {
            $closing = $month->setDay(min($this->closing_date, $month->daysInMonth));

            if ($closing->gte($from) && $closing->lte($to)) {
                $closings++;
            }

            $month = $month->addMonth();
        }

        return $closings;
    }

    /**
     * Today in the card's timezone, as a plain calendar day.
     */
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            CarbonImmutable::today($this->timezone)->toDateString(),
            'UTC',
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CreditCardFactory::new();
    }
}
