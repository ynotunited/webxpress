<?php

namespace Database\Factories;

use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'company' => fake()->optional()->company(),
            'project_title' => fake()->sentence(3),
            'project_type' => 'web',
            'amount' => 1_000_000,
            'upfront_percent' => 70,
            'delivery_percent' => 30,
            'status' => Deal::STATUS_DRAFT,
            // 24 characters, matching the column and the portal URL.
            'access_token' => Str::lower(Str::random(24)),
            'created_via' => 'manual',
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => Deal::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => ['status' => Deal::STATUS_IN_PROGRESS]);
    }
}