<?php

namespace App\Payments;

use App\CreditCards\CreditCard;
use App\Purchases\Purchase;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function purchases(): BelongsToMany
    {
        return $this->belongsToMany(Purchase::class)
            ->using(PaymentPurchase::class)
            ->withPivot('installment_number')
            ->orderByPivot('installment_number', 'desc');
    }
}
