<?php

namespace Database\Factories;

use App\Models\PlanningHour;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanningHourFactory extends Factory
{
    protected $model = PlanningHour::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
            'user_id' => User::factory(),
        ];
    }
}
