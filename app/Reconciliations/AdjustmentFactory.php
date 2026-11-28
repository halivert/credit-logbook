<?php

namespace App\Reconciliations;

use App\CreditCards\CreditCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Adjustment>
 */
class AdjustmentFactory extends Factory
{
    protected $model = Adjustment::class;

    public function definition(): array
    {
        return [
            'credit_card_id' => CreditCard::factory(),
            'date' => fake()->dateTimeThisYear('today')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, -500, 500),
            'concept' => 'Ajuste de conciliación',
        ];
    }
}
