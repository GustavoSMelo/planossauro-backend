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
        Plans::query()->updateOrCreate(
            ["uuid" => "91842dba-9965-42c9-af2a-07fef464b315"],
            [
                "price" => 0,
                "plan_name" => "free",
                "amount_planning_day" => 2,
                "amount_planning_week" => 2,
                "has_cloud_save" => true,
            ],
        );

        Plans::query()->updateOrCreate(
            [
                "uuid" => "9b1e61af-46fa-42db-b67b-583066731e75",
            ],
            [
                "price" => 20.0,
                "plan_name" => "essential",
                "amount_planning_day" => 10,
                "amount_planning_week" => 10,
                "has_cloud_save" => true,
            ],
        );

        Plans::query()->updateOrCreate(
            [
                "uuid" => "11eab11f-bf6b-4129-a241-d2feed7a2376",
            ],
            [
                "price" => 55.0,
                "plan_name" => "premium",
                "amount_planning_day" => 90,
                "amount_planning_week" => 90,
                "has_cloud_save" => true,
            ],
        );

        Plans::query()->updateOrCreate(
            [
                "uuid" => "034fbe0b-6eb4-4fb3-b4af-5448e8cefebe",
            ],
            [
                "price" => 0,
                "plan_name" => "adm",
                "amount_planning_day" => 180,
                "amount_planning_week" => 180,
                "has_cloud_save" => true,
            ],
        );
    }
}
