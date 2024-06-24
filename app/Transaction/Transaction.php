<?php

namespace App\Transaction;

use App\CreditCard\CreditCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'concept',
        'datetime',
        'amount',
        'deadline_months',
        'commission',
        'interest_rate',
        'parent_transaction_id',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'amount' => 'decimal:4',
        'deadline_months' => 'integer',
        'commission' => 'decimal:4',
        'interest_rate' => 'decimal:2',
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

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return TransactionFactory::new();
    }
}
