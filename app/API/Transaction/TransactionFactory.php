<?php

namespace App\API\Transaction;

use App\API\CreditCard\CreditCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\API\Transaction\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credit_card_id' => CreditCard::factory(),
            'concept' => fake()->paragraph(4),
            'datetime' => fake()->dateTimeThisYear(),
            'amount' => fake()->randomFloat(2, 1, 10000),
        ];
    }

    /**
     * Indicate the transaction has a deadline months, commission and
     * interest rate
     */
    public function withDeadlineMonths(): static
    {
        return $this->state(fn () => [
            'commission' => fake()->randomFloat(2, 0, 1000),
            'deadline_months' => fake()->numberBetween(0, 24),
            'interest_rate' => fake()->randomFloat(2, 0, 100),
        ]);
    }
}
