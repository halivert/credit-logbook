<?php

namespace App\Reconciliations;

use App\CreditCards\CreditCard;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What the bank's statement said for a closing.
 */
class BankStatement extends Model
{
    use HasUuids;

    protected $fillable = [
        'closing_date',
        'to_avoid_interest',
    ];

    protected $casts = [
        'closing_date' => 'date:Y-m-d',
        'to_avoid_interest' => 'decimal:2',
    ];

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }
}
