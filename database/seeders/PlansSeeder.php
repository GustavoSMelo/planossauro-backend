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
            'uuid' => '91842dba-9965-42c9-af2a-07fef464b315',
        ]);

        Plans::query()->create([
            'price' => 20.00,
            'plan_name' => 'essencial',
            'amount_planning_day' => 10,
            'amount_planning_week' => 10,
            'has_cloud_save' => true,
            'uuid' => '9b1e61af-46fa-42db-b67b-583066731e75'
        ]);

        Plans::query()->create([
            'price' => 55.00,
            'plan_name' => 'premium',
            'amount_planning_day' => 90,
            'amount_planning_week' => 90,
            'has_cloud_save' => true,
            'uuid' => '11eab11f-bf6b-4129-a241-d2feed7a2376',
        ]);
    }
}
