<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'full_name' => fake()->name(),
            'cellphone_number' => fake()->numerify('119########'),
            'github_email' => fake()->unique()->safeEmail(),
            'github_id' => (string) fake()->unique()->randomNumber(8),
            'google_email' => null,
            'google_id' => null,
            'github_validation_code' => rand(10000, 99999),
            'google_validation_code' => rand(10000, 99999),
            'sms_validation_code' => rand(10000, 99999),
            'facebook_validation_code' => rand(10000, 99999),
            'github_is_validated' => false,
            'google_is_validated' => false,
            'sms_is_validated' => false,
            'facebook_is_validated' => false,
            'deleted_at' => null,
        ];
    }

    public function withGithub(): static
    {
        return $this->state(fn (array $attributes) => [
            'github_is_validated' => true,
        ]);
    }

    public function withGoogle(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_email' => fake()->unique()->safeEmail(),
            'google_id' => (string) fake()->unique()->randomNumber(8),
            'google_is_validated' => true,
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
