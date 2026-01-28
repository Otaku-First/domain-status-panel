<?php

namespace Database\Factories;

use App\Enums\CheckResult;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainCheck>
 */
class DomainCheckFactory extends Factory
{
    public function definition(): array
    {
        return [
            'domain_id' => Domain::factory(),
            'result' => CheckResult::SUCCESS,
            'response_code' => 200,
            'response_time_ms' => fake()->numberBetween(50, 500),
            'error_message' => null,
            'checked_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => CheckResult::FAIL,
            'response_code' => fake()->randomElement([500, 502, 503, 504]),
            'error_message' => 'Server error',
        ]);
    }

    public function timeout(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => CheckResult::TIMEOUT,
            'response_code' => null,
            'response_time_ms' => null,
            'error_message' => 'Connection timed out',
        ]);
    }

    public function dnsError(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => CheckResult::DNS_ERROR,
            'response_code' => null,
            'response_time_ms' => null,
            'error_message' => 'Could not resolve host',
        ]);
    }
}