<?php

namespace App\CreditCards;

use App\Models\User;
use App\Payments\Payment;
use App\Purchases\Purchase;
use App\Support\Money;
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
            fn () => $this->billingCycle()->nextClosing()->toDateString()
        );
    }

    protected function nextDueDate(): Attribute
    {
        return new Attribute(
            fn () => $this->billingCycle()->nextDue()->toDateString()
        );
    }

    /**
     * The statement of the latest closing, from the logbook.
     */
    public function statement(): Statement
    {
        $charges = $this->purchases()
            ->get(['id', 'credit_card_id', 'date', 'applied_on', 'amount', 'installment_count', 'installment_amount', 'commission'])
            ->map(fn (Purchase $purchase) => [
                'day' => $purchase->day(),
                'cents' => Money::toCents($purchase->getRawOriginal('amount')),
                'plan' => $purchase->installmentPlan(),
            ]);

        $payments = $this->payments()
            ->toBase()
            ->get(['date', 'amount'])
            ->map(fn (object $payment) => [
                'day' => BillingCycle::day($payment->date),
                'cents' => Money::toCents($payment->amount),
            ]);

        $cycle = $this->billingCycle();

        return Statement::calculate($cycle, $cycle->today(), $charges, $payments);
    }

    protected function currentStatement(): Attribute
    {
        return new Attribute(fn () => $this->statement()->toArray());
    }

    public function billingCycle(): BillingCycle
    {
        return BillingCycle::of($this);
    }

    protected function availableCredit(): Attribute
    {
        return new Attribute(
            fn () => Money::fromCents(Money::toCents($this->limit) - $this->balanceCents())
        );
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
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CreditCardFactory::new();
    }
}
