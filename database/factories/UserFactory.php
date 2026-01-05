<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => fake()->randomElement([
                'manager_operasional',
                'manager_bisnis',
                'sales_field',
                'sales_store',
                'kepala_gudang',
                'admin_gudang',
                'purchase',
                'finance',
                'kasir'
            ]),
            'phone' => fake()->optional()->phoneNumber(),
            'sales_target' => fake()->numberBetween(10000000, 50000000), // Ensure not null
            'daily_visit_target' => fake()->numberBetween(3, 10),
            'credit_limit_quota' => fake()->numberBetween(1000000, 10000000),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
