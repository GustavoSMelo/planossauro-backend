<?php

namespace Database\Factories;

use App\Models\Plans;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlansFactory extends Factory
{
    protected $model = Plans::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'plan_name' => $this->faker->word(),
            'price' => $this->faker->randomFloat(2, 0, 100),
            'amount_planning_day' => $this->faker->numberBetween(1, 10),
            'amount_planning_week' => $this->faker->numberBetween(1, 50),
            'has_cloud_save' => $this->faker->boolean(),
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
            'plan_name' => 'free',
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 49.90,
            'plan_name' => 'premium',
        ]);
    }
}
