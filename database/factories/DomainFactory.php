<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hostname' => fake()->unique()->domainName(),
            'created_by' => User::factory(),
            'interval' => fake()->randomElement([30, 60, 120, 300]),
            'timeout' => fake()->numberBetween(10, 30),
            'method' => fake()->randomElement(['GET', 'HEAD']),
            'body' => null,
            'is_active' => true,
            'last_checked_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withLastCheck(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_checked_at' => now()->subMinutes(5),
        ]);
    }
}