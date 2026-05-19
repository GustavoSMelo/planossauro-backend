<?php

namespace Tests\Unit;

use App\Enums\PlanStatus;
use App\Models\PaymentHistory;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function disableMiddleware(): void
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        $this->withoutMiddleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByRoute::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByBody::class);
        $this->withoutMiddleware(\App\Http\Middleware\PaymentHistory\ValidatePaymentHistoryID::class);
    }

    public function test_store_creates_payment_history(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $paymentData = [
            'payment_date' => '2024-01-15',
            'description' => 'Monthly subscription',
            'card_brand' => 'visa',
            'price' => 49.90,
            'status' => PlanStatus::PAID->value,
            'plan_id' => $plan->uuid,
            'user_id' => $user->uuid,
            'last_four_digits' => '1234',
            'subscription_id' => $subscription->uuid,
        ];

        $response = $this->postJson('/api/payment/history/', $paymentData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Payment registred with success');

        $this->assertDatabaseHas('payment_history', [
            'description' => 'Monthly subscription',
            'price' => 49.90,
        ]);
    }

    public function test_store_fails_with_validation_error(): void
    {
        $this->disableMiddleware();

        $paymentData = [
            'payment_date' => 'invalid-date',
        ];

        $response = $this->postJson('/api/payment/history/', $paymentData);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'error on validation');
    }

    public function test_show_returns_payments_for_user(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        PaymentHistory::factory()->create([
            'user_id' => $user->uuid,
            'subscription_id' => $subscription->uuid,
        ]);

        $response = $this->getJson("/api/payment/history/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'payments',
        ]);
    }

    public function test_update_modifies_payment_history(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $payment = PaymentHistory::factory()->create([
            'user_id' => $user->uuid,
            'subscription_id' => $subscription->uuid,
        ]);

        $updateData = [
            'payment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'description' => 'Updated description',
            'card_brand' => 'mastercard',
            'price' => 99.90,
            'status' => PlanStatus::PAID->value,
            'plan_id' => $plan->uuid,
            'user_id' => $user->uuid,
            'last_four_digits' => '5678',
            'subscription_id' => $subscription->uuid,
        ];

        $response = $this->putJson("/api/payment/history/{$payment->uuid}", $updateData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'payment updated with success');

        $payment->refresh();
        $this->assertEquals('Updated description', $payment->description);
    }

    public function test_update_returns_error_for_nonexistent_payment(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $updateData = [
            'payment_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'description' => 'Updated description',
            'card_brand' => 'mastercard',
            'price' => 99.90,
            'status' => PlanStatus::PAID->value,
            'plan_id' => $plan->uuid,
            'user_id' => $user->uuid,
            'last_four_digits' => '5678',
            'subscription_id' => $subscription->uuid,
        ];

        $response = $this->putJson('/api/payment/history/nonexistent-uuid', $updateData);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'User not founded');
    }

    public function test_insert_nfe_updates_payment_nfe(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $payment = PaymentHistory::factory()->create([
            'user_id' => $user->uuid,
            'subscription_id' => $subscription->uuid,
        ]);

        $response = $this->patchJson("/api/payment/history/upload/nfe/{$payment->uuid}", [
            'NFe' => 'NFE-123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'NFe inserted with success');

        $payment->refresh();
        $this->assertEquals('NFE-123456', $payment->NFe);
    }

    public function test_insert_nfe_returns_error_for_nonexistent_payment(): void
    {
        $this->disableMiddleware();

        $response = $this->patchJson('/api/payment/history/upload/nfe/nonexistent-uuid', [
            'NFe' => 'NFE-123456',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'user not founded');
    }

    public function test_update_payment_status_changes_status(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $payment = PaymentHistory::factory()->create([
            'user_id' => $user->uuid,
            'subscription_id' => $subscription->uuid,
            'status' => PlanStatus::PAID->value,
        ]);

        $response = $this->patchJson("/api/payment/history/status/update/{$payment->uuid}", [
            'status' => PlanStatus::CANCELED->value,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Payment status updated with success');

        $payment->refresh();
        $this->assertEquals(PlanStatus::CANCELED->value, $payment->status);
    }

    public function test_update_payment_status_fails_with_invalid_status(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $payment = PaymentHistory::factory()->create([
            'user_id' => $user->uuid,
            'subscription_id' => $subscription->uuid,
        ]);

        $response = $this->patchJson("/api/payment/history/status/update/{$payment->uuid}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }
}
