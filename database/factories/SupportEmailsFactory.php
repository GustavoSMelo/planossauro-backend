<?php

namespace Database\Factories;

use App\Models\SupportEmails;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupportEmailsFactory extends Factory
{
    protected $model = SupportEmails::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->word(),
            'ticketId' => Str::uuid(),
            'user_id' => User::factory(),
        ];
    }
}
