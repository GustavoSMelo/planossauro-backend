<?php

namespace Tests\Unit;

use App\Models\Plans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlansControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_plans(): void
    {
        Plans::factory()->count(3)->create();

        $response = $this->getJson('/api/plans/');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'plans' => [
                '*' => [
                    'uuid',
                    'plan_name',
                    'price',
                    'amount_planning_day',
                    'amount_planning_week',
                    'has_cloud_save',
                ],
            ],
        ]);
    }

    public function test_index_returns_empty_array_when_no_plans(): void
    {
        $response = $this->getJson('/api/plans/');

        $response->assertStatus(200);
        $response->assertJsonPath('plans', []);
    }

    public function test_show_returns_plan_by_uuid(): void
    {
        $plan = Plans::factory()->create([
            'plan_name' => 'Premium Plan',
        ]);

        $response = $this->getJson("/api/plans/{$plan->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('uuid', (string) $plan->uuid);
        $response->assertJsonPath('plan_name', 'Premium Plan');
    }

    public function test_show_returns_plan_with_all_fields(): void
    {
        $plan = Plans::factory()->create([
            'plan_name' => 'Basic Plan',
            'price' => 19.90,
            'amount_planning_day' => 5,
            'amount_planning_week' => 20,
            'has_cloud_save' => true,
        ]);

        $response = $this->getJson("/api/plans/{$plan->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('price', 19.90);
        $response->assertJsonPath('amount_planning_day', 5);
        $response->assertJsonPath('amount_planning_week', 20);
        $this->assertEquals(1, $response->json('has_cloud_save'));
    }

    public function test_show_returns_null_for_nonexistent_uuid(): void
    {
        $response = $this->getJson('/api/plans/nonexistent-uuid');

        $response->assertStatus(200);
    }
}
