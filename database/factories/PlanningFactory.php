<?php

namespace Database\Factories;

use App\Models\Planning;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanningFactory extends Factory
{
    protected $model = Planning::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'document_b64' => base64_encode($this->faker->paragraph()),
            'start_plan' => $this->faker->date(),
            'end_plan' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'school_name' => $this->faker->company(),
            'class_name' => $this->faker->word(),
            'deleted_at' => null,
            'user_id' => User::factory(),
        ];
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_plan' => $start = $this->faker->date(),
            'end_plan' => date('Y-m-d', strtotime($start.' +7 days')),
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_plan' => $date = $this->faker->date(),
            'end_plan' => $date,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
