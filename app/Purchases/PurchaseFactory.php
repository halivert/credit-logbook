<?php

namespace App\Purchases;

use App\CreditCard\CreditCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Purchases\Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credit_card_id' => CreditCard::factory(),
            'concept' => fake()->sentence(4),
            'datetime' => fake()->dateTimeThisYear('today')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 1, 10000),
        ];
    }
}
