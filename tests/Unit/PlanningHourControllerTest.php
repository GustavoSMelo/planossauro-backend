<?php

namespace Tests\Unit;

use App\Models\PlanningHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningHourControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function disableMiddleware(): void
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        $this->withoutMiddleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByRoute::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByBody::class);
    }

    public function test_show_returns_planning_hour_by_user_uuid(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        PlanningHour::factory()->create(['user_id' => $user->uuid]);

        $response = $this->getJson("/api/planninghour/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('user_id', (string) $user->uuid);
    }

    public function test_show_returns_400_for_short_uuid(): void
    {
        $this->disableMiddleware();

        $response = $this->getJson('/api/planninghour/ab');

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'uuid provided was not valid');
    }

    public function test_store_creates_planning_hour(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $planningHourData = [
            'interval_between_classes' => '00:30',
            'initial_hour' => '08:00',
            'user_id' => $user->uuid,
        ];

        $response = $this->postJson('/api/planninghour/', $planningHourData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'PlanningHour created with success!');

        $this->assertDatabaseHas('planning_hour', [
            'user_id' => $user->uuid,
            'initial_hour' => '08:00',
        ]);
    }

    public function test_store_fails_with_invalid_user_uuid(): void
    {
        $this->disableMiddleware();

        $planningHourData = [
            'interval_between_classes' => '00:30',
            'initial_hour' => '08:00',
            'user_id' => 'nonexistent-uuid',
        ];

        $response = $this->postJson('/api/planninghour/', $planningHourData);

        $response->assertStatus(400);
    }

    public function test_store_uses_default_values_when_not_provided(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $planningHourData = [
            'user_id' => $user->uuid,
        ];

        $response = $this->postJson('/api/planninghour/', $planningHourData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'PlanningHour created with success!');

        $this->assertDatabaseHas('planning_hour', [
            'user_id' => $user->uuid,
            'interval_between_classes' => '00:30',
            'initial_hour' => '12:00',
        ]);
    }

    public function test_update_modifies_planning_hour(): void
    {
        $this->disableMiddleware();

        $planningHour = PlanningHour::factory()->create();
        $user = User::factory()->create();

        $updateData = [
            'interval_between_classes' => '01:00',
            'initial_hour' => '09:00',
        ];

        $response = $this->putJson("/api/planninghour/{$planningHour->user_id}", $updateData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'PlanningHour updated with success');

        $planningHour->refresh();
        $this->assertEquals('01:00', $planningHour->interval_between_classes);
    }

    public function test_update_returns_400_for_nonexistent_planning_hour(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $updateData = [
            'interval_between_classes' => '01:00',
            'initial_hour' => '09:00',
        ];

        $response = $this->putJson("/api/planninghour/{$user->uuid}", $updateData);

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'PlanningHour not founded');
    }

    public function test_update_fails_with_invalid_user(): void
    {
        $this->disableMiddleware();

        $updateData = [
            'interval_between_classes' => '01:00',
            'initial_hour' => '09:00',
            'user_id' => 'nonexistent-uuid',
        ];

        $response = $this->putJson('/api/planninghour/nonexistent-uuid', $updateData);

        $response->assertStatus(400);
    }

    public function test_destroy_deletes_planning_hour(): void
    {
        $this->disableMiddleware();

        $planningHour = PlanningHour::factory()->create();

        $response = $this->deleteJson("/api/planninghour/{$planningHour->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'PlanningHour deleted with success');

        $this->assertDatabaseMissing('planning_hour', [
            'uuid' => $planningHour->uuid,
        ]);
    }

    public function test_destroy_returns_400_for_short_uuid(): void
    {
        $this->disableMiddleware();

        $response = $this->deleteJson('/api/planninghour/ab');

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'uuid provided was not valid');
    }

    public function test_destroy_returns_400_for_nonexistent_planning_hour(): void
    {
        $this->disableMiddleware();

        $response = $this->deleteJson('/api/planninghour/nonexistent-uuid');

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'PlanningHour not founded');
    }
}
