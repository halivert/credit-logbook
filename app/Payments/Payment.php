<?php

namespace App\Payments;

use App\CreditCards\CreditCard;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'datetime',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'datetime' => 'date:Y-m-d',
            'amount' => 'decimal:2'
        ];
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function user(): BelongsTo
    {
        return $this->creditCard->user();
    }


    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return PaymentFactory::new();
    }
}
