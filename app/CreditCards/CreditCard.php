<?php

namespace App\CreditCards;

use App\Models\User;
use App\Payments\Payment;
use App\Purchases\Purchase;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function nextClosingDate(): Attribute
    {
        return new Attribute(function () {
            $today = today()->tz($this->timezone);

            return today()->tz($this->timezone)
                ->setDay($this->closing_date)
                ->addMonth($today->day > $this->closing_date);
        });
    }

    protected function nextDueDate(): Attribute
    {
        return new Attribute(function () {
            $today = today()->tz($this->timezone);

            return today()->tz($this->timezone)
                ->setDay($this->due_date)
                ->addMonth($today->day > $this->due_date);
        });
    }

    protected function balanceUsed(): Attribute
    {
        return new Attribute(function () {
            $this->loadMissing([
                'purchases' => fn($query) => $query
                    ->whereNull('paid_at')
            ]);

            $purchasesSum = $this->purchases->whereNull('installment_count')
                ->sum('amount');

            $installmentsSum = $this->purchases->whereNotNull('installment_count')
                ->sum('remaining_amount');

            return $purchasesSum + $installmentsSum;
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CreditCardFactory::new();
    }
}
