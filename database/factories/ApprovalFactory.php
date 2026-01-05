<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Approval;
use App\Models\User;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Approval>
 */
class ApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'model_type' => Product::class,
            'model_id' => Product::factory(),
            'action' => fake()->randomElement(['create', 'update', 'delete', 'credit_limit_update']),
            'original_data' => fake()->optional()->passthrough(['name' => fake()->word()]),
            'new_data' => fake()->optional()->passthrough(['name' => fake()->word()]),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'reason' => fake()->optional()->sentence(),
            'requester_id' => User::factory(),
            'approver_id' => fake()->optional()->passthrough(User::factory()),
        ];
    }

    /**
     * Indicate that the approval is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approver_id' => null,
        ]);
    }

    /**
     * Indicate that the approval is for order approval.
     */
    public function orderApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => \App\Models\Order::class,
            'action' => 'approve_order',
        ]);
    }

    /**
     * Indicate that the approval is for payment approval.
     */
    public function paymentApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => \App\Models\PaymentLog::class,
            'action' => 'approve_payment',
        ]);
    }
}
