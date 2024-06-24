<?php

namespace App\CreditCard;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditCard>
 */
class CreditCardFactory extends Factory
{
    protected $model = CreditCard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word(),
            'due_date' => fake()->numberBetween(1, 28),
            'closing_date' => fake()->numberBetween(1, 28),
            'interest_rate' => fake()->randomFloat(2, 0, 100),
            'limit' => fake()->randomFloat(2, 0, 10000),
        ];
    }
}
