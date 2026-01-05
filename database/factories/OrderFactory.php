<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\User;
use App\Models\Customer;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'invoice_number' => 'SO-' . date('Ymd') . '-' . fake()->unique()->numberBetween(1000, 9999),
            'total_price' => fake()->numberBetween(50000, 500000),
            'status' => fake()->randomElement(['pending_approval', 'approved', 'shipped', 'completed', 'rejected']),
            'payment_status' => fake()->randomElement(['unpaid', 'partial', 'paid']),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'notes' => fake()->optional()->sentence(),
            'payment_type' => fake()->randomElement(['cash', 'top', 'kredit']),
        ];
    }

    /**
     * Indicate that the order is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Indicate that the order is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);
    }
}
