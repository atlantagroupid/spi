<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->companySuffix(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'category' => fake()->randomElement(['Workshop', 'Studio', 'Kontraktor', 'Customer']), // NOT NULL column
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
            'top_days' => fake()->numberBetween(0, 90),
            'credit_limit' => fake()->numberBetween(1000000, 50000000),
            'user_id' => User::factory(), // Sales yang menangani customer ini
        ];
    }

    /**
     * Indicate that the customer has no credit limit.
     */
    public function noCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => 0,
            'top_days' => 0,
        ]);
    }

    /**
     * Indicate that the customer has high credit limit.
     */
    public function highCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => fake()->numberBetween(50000000, 200000000),
            'top_days' => fake()->numberBetween(30, 90),
        ]);
    }
}
