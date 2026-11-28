<?php

namespace App\Purchases;

use App\CreditCards\CreditCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Purchase extends Model
{
    use HasFactory, HasUuids, HasInstallments;

    protected $fillable = [
        'concept',
        'date',
        'amount',
        'installment_count',
        'installment_amount',
        'commission',
        'interest_rate',
        'applied_on',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'installment_count' => 'integer',
        'installment_amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'applied_on' => 'date:Y-m-d',
    ];

    protected $appends = [
        'is_installment_purchase',
        'is_refund',
        'billed_installments',
        'remaining_installments',
        'remaining_amount',
    ];


    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
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

    /**
     * Returns are stored as negative purchases: they're a credit to the card.
     */
    protected function isRefund(): Attribute
    {
        return new Attribute(fn () => $this->amount < 0);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return PurchaseFactory::new();
    }
}
