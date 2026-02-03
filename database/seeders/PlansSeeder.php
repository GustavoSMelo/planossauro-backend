<?php

namespace Database\Seeders;

use App\Models\Plans;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plans::query()->create([
            'price' => 0,
            'plan_name' => 'free',
            'amount_planning_day' => 3,
            'amount_planning_week' => 3,
            'has_cloud_save' => true,
        ]);

        Plans::query()->create([
            'price' => 20.00,
            'plan_name' => 'essencial',
            'amount_planning_day' => 10,
            'amount_planning_week' => 10,
            'has_cloud_save' => true
        ]);

        Plans::query()->create([
            'price' => 55.00,
            'plan_name' => 'premium',
            'amount_planning_day' => 90,
            'amount_planning_week' => 90,
            'has_cloud_save' => true
        ]);
    }
}
