<?php

namespace App\Payments;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PaymentPurchase extends Pivot
{
    protected $attributes = [
        'installment_number' => null
    ];
}
