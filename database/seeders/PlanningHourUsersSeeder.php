<?php

namespace Database\Seeders;

use App\Models\PlanningHour;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlanningHourUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                PlanningHour::firstOrCreate([
                    "initial_hour" => "12:00",
                    "interval_between_classes" => "00:30",
                    "user_id" => $user->uuid,
                ]);
            }
        });
    }
}
