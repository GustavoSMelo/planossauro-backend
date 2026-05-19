<?php

namespace Tests\Unit;

use App\Models\Planning;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlanningControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function disableMiddleware(): void
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        $this->withoutMiddleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByRoute::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByBody::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByBodyUserID::class);
        $this->withoutMiddleware(\App\Http\Middleware\Planning\ValidatePlanningID::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByBody::class);
    }

    public function test_index_returns_plannings_for_user(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        Planning::factory()->create(['user_id' => $user->uuid]);
        Planning::factory()->create(['user_id' => $user->uuid]);

        $response = $this->getJson("/api/planning/paginate/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
        ]);
    }

    public function test_index_excludes_deleted_plannings(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        Planning::factory()->create(['user_id' => $user->uuid]);
        Planning::factory()->create([
            'user_id' => $user->uuid,
            'deleted_at' => now(),
        ]);

        $response = $this->getJson("/api/planning/paginate/{$user->uuid}");

        $response->assertStatus(200);
    }

    public function test_show_returns_planning_by_uuid(): void
    {
        $this->disableMiddleware();

        $planning = Planning::factory()->create();

        $response = $this->getJson("/api/planning/show/{$planning->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('uuid', (string) $planning->uuid);
    }

    public function test_show_returns_400_for_short_uuid(): void
    {
        $this->disableMiddleware();

        $response = $this->getJson('/api/planning/show/ab');

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'uuid provided was not valid');
    }

    public function test_store_creates_planning(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $planningData = [
            'document_b64' => base64_encode('test content'),
            'start_plan' => '2024-01-15',
            'end_plan' => '2024-01-22',
            'school_name' => 'Test School',
            'class_name' => 'Math Class',
            'user_id' => $user->uuid,
        ];

        $response = $this->postJson('/api/planning/', $planningData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Planning created with success!');

        $this->assertDatabaseHas('planning', [
            'school_name' => 'Test School',
            'class_name' => 'Math Class',
        ]);
    }

    public function test_store_fails_with_invalid_data(): void
    {
        $this->disableMiddleware();

        $planningData = [
            'document_b64' => '',
        ];

        $response = $this->postJson('/api/planning/', $planningData);

        $response->assertStatus(400);
    }

    public function test_store_fails_with_end_date_before_start_date(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $planningData = [
            'document_b64' => base64_encode('test content'),
            'start_plan' => '2024-01-22',
            'end_plan' => '2024-01-15',
            'school_name' => 'Test School',
            'class_name' => 'Math Class',
            'user_id' => $user->uuid,
        ];

        $response = $this->postJson('/api/planning/', $planningData);

        $response->assertStatus(400);
    }

    public function test_update_modifies_planning(): void
    {
        $this->disableMiddleware();

        $planning = Planning::factory()->create();
        $user = User::factory()->create();

        $updateData = [
            'document_b64' => base64_encode('updated content'),
            'start_plan' => '2024-02-01',
            'end_plan' => '2024-02-10',
            'school_name' => 'Updated School',
            'class_name' => 'Updated Class',
            'user_id' => $user->uuid,
        ];

        $response = $this->putJson("/api/planning/{$planning->uuid}", $updateData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Planning updated with success');

        $planning->refresh();
        $this->assertEquals('Updated School', $planning->school_name);
    }

    public function test_update_returns_400_for_invalid_uuid(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $updateData = [
            'document_b64' => base64_encode('updated content'),
            'start_plan' => '2024-02-01',
            'end_plan' => '2024-02-10',
            'school_name' => 'Updated School',
            'class_name' => 'Updated Class',
            'user_id' => $user->uuid,
        ];

        $response = $this->putJson('/api/planning/nonexistent-uuid-12345', $updateData);

        $response->assertStatus(400);
    }

    public function test_archive_soft_deletes_planning(): void
    {
        $this->disableMiddleware();

        $planning = Planning::factory()->create();

        $response = $this->patchJson("/api/planning/archive/{$planning->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Planning archived with success');

        $planning->refresh();
        $this->assertNotNull($planning->deleted_at);
    }

    public function test_archive_returns_400_for_nonexistent_planning(): void
    {
        $this->disableMiddleware();

        $response = $this->patchJson('/api/planning/archive/nonexistent-uuid');

        $response->assertStatus(400);
    }

    public function test_unarchive_restores_planning(): void
    {
        $this->disableMiddleware();

        $planning = Planning::factory()->create([
            'deleted_at' => now(),
        ]);

        $response = $this->patchJson("/api/planning/unarchive/{$planning->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Planning unarchived with success');

        $planning->refresh();
        $this->assertNull($planning->deleted_at);
    }

    public function test_destroy_deletes_planning_permanently(): void
    {
        $this->disableMiddleware();

        $planning = Planning::factory()->create();

        $response = $this->deleteJson("/api/planning/{$planning->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Planning deleted with success');

        $this->assertDatabaseMissing('planning', [
            'uuid' => $planning->uuid,
        ]);
    }

    public function test_destroy_returns_400_for_short_uuid(): void
    {
        $this->disableMiddleware();

        $response = $this->deleteJson('/api/planning/ab');

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'uuid provided was not valid');
    }

    public function test_search_by_filters_returns_matching_plannings(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        Planning::factory()->create([
            'user_id' => $user->uuid,
            'school_name' => 'School A',
            'class_name' => 'Math',
        ]);
        Planning::factory()->create([
            'user_id' => $user->uuid,
            'school_name' => 'School B',
            'class_name' => 'Science',
        ]);

        $response = $this->postJson("/api/planning/search/{$user->uuid}", [
            'school_name' => 'School A',
        ]);

        $response->assertStatus(200);
    }

    public function test_search_by_filters_with_planning_type_weekly(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $weeklyPlanning = Planning::factory()->weekly()->create([
            'user_id' => $user->uuid,
        ]);

        $response = $this->postJson("/api/planning/search/{$user->uuid}", [
            'planning_type' => 'Semanal',
        ]);

        $response->assertStatus(200);
    }

    public function test_search_by_filters_with_planning_type_daily(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $dailyPlanning = Planning::factory()->daily()->create([
            'user_id' => $user->uuid,
        ]);

        $response = $this->postJson("/api/planning/search/{$user->uuid}", [
            'planning_type' => 'Diario',
        ]);

        $response->assertStatus(200);
    }

    public function test_search_by_filters_with_archived_true(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        Planning::factory()->create([
            'user_id' => $user->uuid,
            'deleted_at' => now(),
        ]);

        $response = $this->postJson("/api/planning/search/{$user->uuid}", [
            'archived' => true,
        ]);

        $response->assertStatus(200);
    }

    public function test_create_returns_planning_from_ai(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output' => [
                    null,
                    [
                        'content' => [
                            ['text' => 'Generated lesson plan content'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->disableMiddleware();

        $response = $this->postJson('/api/planning/create', [
            'prompt' => 'Create a lesson plan for Math',
        ]);

        $response->assertStatus(200);
    }
}
