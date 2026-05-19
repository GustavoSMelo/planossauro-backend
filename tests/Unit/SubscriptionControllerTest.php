<?php

namespace Tests\Unit;

use App\Enums\PlanStatus;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function disableMiddleware(): void
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        $this->withoutMiddleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByRoute::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByBody::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByBodyUserID::class);
        $this->withoutMiddleware(\App\Http\Middleware\Subscription\ValidateSubscriptionID::class);
    }

    public function test_assign_free_plan_to_user_creates_subscription(): void
    {
        config(['app.env' => 'local']);

        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create(['plan_name' => 'adm']);

        $response = $this->postJson("/api/subscription/assign/free/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJson(['Plan assigned with success']);

        $this->assertDatabaseHas('subscription', [
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);
    }

    public function test_assign_free_plan_returns_404_for_nonexistent_user(): void
    {
        $this->disableMiddleware();

        $response = $this->postJson('/api/subscription/assign/free/nonexistent-uuid');

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'User with this is does not exists');
    }

    public function test_assign_free_plan_downgrades_existing_subscription(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->free()->create();
        $premiumPlan = Plans::factory()->premium()->create();
        Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $premiumPlan->uuid,
            'stripe_subscription' => 'sub_test123',
        ]);

        $response = $this->postJson("/api/subscription/assign/free/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'User already has a subscription');
    }

    public function test_assign_plan_to_user_creates_subscription(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();

        $subscriptionData = [
            'plans_id' => $plan->uuid,
            'last_four_digits' => '1234',
            'card_brand' => 'visa',
            'next_billing' => Carbon::now()->addMonth()->format('Y-m-d'),
            'date_verified' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->postJson("/api/subscription/assign/{$user->uuid}", $subscriptionData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Plan assigned with success');

        $this->assertDatabaseHas('subscription', [
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);
    }

    public function test_assign_plan_to_user_updates_existing_subscription(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        Subscription::factory()->create([
            'user_id' => $user->uuid,
        ]);

        $subscriptionData = [
            'plans_id' => $plan->uuid,
            'last_four_digits' => '5678',
            'card_brand' => 'mastercard',
            'next_billing' => Carbon::now()->addMonth()->format('Y-m-d'),
            'date_verified' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->putJson("/api/subscription/{$user->uuid}", $subscriptionData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Plan updated with success');
    }

    public function test_assign_plan_fails_with_invalid_data(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $response = $this->postJson("/api/subscription/assign/{$user->uuid}", []);

        $response->assertStatus(422);
    }

    public function test_patch_plan_status_updates_status(): void
    {
        $this->disableMiddleware();

        $subscription = Subscription::factory()->create();

        $response = $this->patchJson("/api/subscription/status/update/{$subscription->uuid}", [
            'status' => PlanStatus::CANCELED->value,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'subscription status updated with success');

        $subscription->refresh();
        $this->assertEquals(PlanStatus::CANCELED->value, $subscription->status);
    }

    public function test_patch_plan_status_fails_with_invalid_status(): void
    {
        $this->disableMiddleware();

        $subscription = Subscription::factory()->create();

        $response = $this->patchJson("/api/subscription/status/update/{$subscription->uuid}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }

    public function test_patch_plan_status_returns_422_for_nonexistent_subscription(): void
    {
        $this->disableMiddleware();

        $response = $this->patchJson('/api/subscription/status/update/nonexistent-uuid', [
            'status' => PlanStatus::CANCELED->value,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Subscription not found');
    }

    public function test_show_returns_subscription_for_user(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $response = $this->getJson("/api/subscription/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'subscription',
            'plan',
        ]);
    }

    public function test_dashboard_returns_planning_usage(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create([
            'amount_planning_week' => 10,
            'amount_planning_day' => 5,
        ]);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
            'weekly_plans_used' => 3,
            'daily_plans_used' => 2,
        ]);

        $response = $this->getJson("/api/subscription/dashboard/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'max_amount_planning_week',
            'max_amount_planning_daily',
            'used_weekly_planning',
            'used_daily_planning',
            'current_plan',
            'subscription_id',
            'plan_id',
        ]);
        $response->assertJsonPath('max_amount_planning_week', 10);
        $response->assertJsonPath('used_weekly_planning', 3);
    }

    public function test_add_planning_used_increments_weekly_count(): void
    {
        $this->disableMiddleware();

        $plan = Plans::factory()->create([
            'amount_planning_week' => 10,
        ]);
        $subscription = Subscription::factory()->create([
            'plans_id' => $plan->uuid,
            'weekly_plans_used' => 5,
        ]);

        $response = $this->patchJson("/api/subscription/week/{$subscription->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Subscription week tokens used updated with success');

        $subscription->refresh();
        $this->assertEquals(6, $subscription->weekly_plans_used);
    }

    public function test_add_planning_used_increments_daily_count(): void
    {
        $this->disableMiddleware();

        $plan = Plans::factory()->create([
            'amount_planning_day' => 5,
        ]);
        $subscription = Subscription::factory()->create([
            'plans_id' => $plan->uuid,
            'daily_plans_used' => 2,
        ]);

        $response = $this->patchJson("/api/subscription/daily/{$subscription->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Subscription daily tokens used updated with success');

        $subscription->refresh();
        $this->assertEquals(3, $subscription->daily_plans_used);
    }

    public function test_add_planning_used_returns_403_when_weekly_limit_reached(): void
    {
        $this->disableMiddleware();

        $plan = Plans::factory()->create([
            'amount_planning_week' => 10,
        ]);
        $subscription = Subscription::factory()->create([
            'plans_id' => $plan->uuid,
            'weekly_plans_used' => 10,
        ]);

        $response = $this->patchJson("/api/subscription/week/{$subscription->uuid}");

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Used all weekly token');
    }

    public function test_add_planning_used_returns_403_when_daily_limit_reached(): void
    {
        $this->disableMiddleware();

        $plan = Plans::factory()->create([
            'amount_planning_day' => 5,
        ]);
        $subscription = Subscription::factory()->create([
            'plans_id' => $plan->uuid,
            'daily_plans_used' => 5,
        ]);

        $response = $this->patchJson("/api/subscription/daily/{$subscription->uuid}");

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Used all weekly token');
    }

    public function test_add_planning_used_returns_400_for_invalid_type(): void
    {
        $this->disableMiddleware();

        $subscription = Subscription::factory()->create();

        $response = $this->patchJson("/api/subscription/invalid/{$subscription->uuid}");

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Planning type invalid');
    }

    public function test_cancel_subscription_returns_400_for_empty_id(): void
    {
        $this->disableMiddleware();

        $response = $this->deleteJson('/api/subscription/cancel/nonexistent-uuid');

        $response->assertStatus(400);
    }

    public function test_cancel_subscription_returns_400_for_nonexistent_subscription(): void
    {
        $this->disableMiddleware();

        $response = $this->deleteJson('/api/subscription/cancel/nonexistent-uuid');

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'subscription not founded');
    }
}
