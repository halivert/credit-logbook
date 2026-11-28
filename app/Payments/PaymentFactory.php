<?php

namespace App\Payments;

use App\CreditCards\CreditCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Payments\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credit_card_id' => CreditCard::factory(),
            'datetime' => fake()->dateTimeBetween('-1 week'),
            'amount' => fake()->randomNumber(4),
        ];
    }
}
