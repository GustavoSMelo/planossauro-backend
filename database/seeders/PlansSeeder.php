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
            'amount_planning' => 5,
            'has_cloud_save' => true,
        ]);

        Plans::query()->create([
            'price' => 5,
            'plan_name' => 'premium',
            'amount_planning' => 10,
            'has_cloud_save' => true
        ]);
    }
}
