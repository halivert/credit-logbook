<?php

namespace App\Purchases;

use App\CreditCards\CreditCard;
use App\Models\User;
use App\Payments\Payment;
use App\Payments\PaymentPurchase;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Purchase extends Model
{
    use HasFactory, HasUuids, HasInstallments;

    protected $fillable = [
        'concept',
        'datetime',
        'amount',
        'installment_count',
        'installment_amount',
        'commission',
        'interest_rate',
        'paid_at',
        'applied_at',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'amount' => 'decimal:2',
        'installment_count' => 'integer',
        'installment_amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'paid_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    protected $appends = [
        'is_installment_purchase',
        'paid_months',
        'remaining_months',
        'paid_amount',
        'remaining_amount',
    ];

    protected $with = [
        'payments',
    ];

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class)
            ->using(PaymentPurchase::class)
            ->withPivot('installment_number')
            ->orderByPivot('installment_number', 'desc');
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            CreditCard::class,
            'id',
            'id',
            'credit_card_id',
            'user_id'
        );
    }

    protected function appliedAt(): Attribute
    {
        return new Attribute(fn ($value) => $value ?? $this->datetime);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return PurchaseFactory::new();
    }
}
