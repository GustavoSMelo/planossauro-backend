<?php

namespace Tests\Unit;

use App\Http\Controllers\PlansController;
use App\Models\Plans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_index_handles_database_exception(): void
    {
        $plansMock = Mockery::mock('alias:' . Plans::class);
        $plansMock->shouldReceive('all')->once()->andThrow(new \Exception('Database error'));

        $controller = new PlansController();
        $response = $controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Error to receive plans from database', $data['error']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_show_handles_database_exception(): void
    {
        $mockQuery = Mockery::mock();
        $mockQuery->shouldReceive('where')->once()->with('uuid', '=', 'test-uuid')->andReturnSelf();
        $mockQuery->shouldReceive('first')->once()->andThrow(new \Exception('Database error'));

        $plansMock = Mockery::mock('alias:' . Plans::class);
        $plansMock->shouldReceive('query')->once()->andReturn($mockQuery);

        $controller = new PlansController();
        $response = $controller->show('test-uuid');

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('a plan with this uuid was not found', $data['error']);
    }
}
