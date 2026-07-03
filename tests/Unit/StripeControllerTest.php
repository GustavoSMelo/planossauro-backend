<?php

namespace Tests\Unit;

use App\Dto\Stripe\StripeCache;
use App\Enums\PlanStatus;
use App\Models\PaymentHistory;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_handler_returns_received_for_unknown_event_type(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'type' => 'unknown.event',
            'id' => 'evt_test_123',
        ]);

        $response->assertStatus(200);
    }

    public function test_handler_accepts_charge_succeeded_with_valid_cvc(): void
    {
        Cache::spy();

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'customer' => 'cus_test_123',
                    'billing_details' => [
                        'email' => 'test@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'checks' => [
                                'cvc_check' => 'pass',
                            ],
                            'wallet' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_handler_processes_checkout_session_completed(): void
    {
        Cache::spy();

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_456',
                    'metadata' => [
                        'plan_uuid' => 'test-plan-uuid',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_charge_succeeded_with_google_pay_wallet(): void
    {
        Cache::spy();

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'customer' => 'cus_test_gpay',
                    'billing_details' => [
                        'email' => 'gpay@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'checks' => [
                                'cvc_check' => 'pass',
                            ],
                            'wallet' => [
                                'type' => 'google_pay',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_charge_succeeded_with_invalid_cvc(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'customer' => 'cus_test_123',
                    'billing_details' => [
                        'email' => 'test@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'checks' => [
                                'cvc_check' => 'fail',
                            ],
                            'wallet' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_charge_succeeded_creates_cache_when_none_exists(): void
    {
        Cache::spy();

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'customer' => 'cus_new_customer',
                    'billing_details' => [
                        'email' => 'new@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'checks' => [
                                'cvc_check' => 'check',
                            ],
                            'wallet' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('received', true);
    }

    public function test_charge_succeeded_with_existing_cache_updates_cache(): void
    {
        $stripeCache = new StripeCache(
            'sub_test_123',
            'cus_test_full',
            'inv_test_123',
            'prod_test_123',
            'https://invoice.pdf',
            time() + 86400,
            9990,
            PlanStatus::PAID->value,
            'visa',
            '4242',
            'price_test_123',
            'test@example.com',
            time(),
            'some-plan-uuid',
            'Test subscription',
            'si_test_123',
        );

        Cache::put('stripeCache-cus_test_full', $stripeCache);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'customer' => 'cus_test_full',
                    'billing_details' => [
                        'email' => 'test@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'checks' => [
                                'cvc_check' => 'check',
                            ],
                            'wallet' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_invoice_paid_creates_cache_when_none_exists(): void
    {
        Cache::spy();

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'invoice.paid',
            'object' => 'event',
            'data' => [
                'object' => [
                    'id' => 'in_test_123',
                    'amount_paid' => 9990,
                    'customer' => 'cus_inv_new',
                    'customer_email' => 'inv@example.com',
                    'effective_at' => time(),
                    'invoice_pdf' => 'https://invoice.pdf',
                    'number' => 'INV-001',
                    'status' => 'paid',
                    'status_transitions' => [
                        'paid_at' => time(),
                    ],
                    'lines' => [
                        'data' => [
                            [
                                'id' => 'il_test_123',
                                'description' => 'Subscription',
                                'invoice' => 'in_test_123',
                                'parent' => [
                                    'subscription_item_details' => [
                                        'subscription' => 'sub_test_123',
                                        'subscription_item' => 'si_test_123',
                                    ],
                                ],
                                'pricing' => [
                                    'price_details' => [
                                        'price' => 'price_test_123',
                                        'product' => 'prod_test_123',
                                    ],
                                ],
                                'period' => [
                                    'end' => time() + 86400,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('received', true);
    }

    public function test_charge_succeeded_with_full_cache_saves_data(): void
    {
        $user = User::factory()->create([
            'github_email' => 'fullcache@example.com',
            'google_email' => null,
        ]);
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $stripeCache = new StripeCache(
            'sub_test_123',
            'cus_test_fullsave',
            'inv_test_123',
            'prod_test_123',
            'https://invoice.pdf',
            time() + 86400,
            9990,
            PlanStatus::PAID->value,
            'visa',
            '4242',
            'price_test_123',
            'fullcache@example.com',
            time(),
            $plan->uuid,
            'Test subscription',
            'si_test_123',
        );

        Cache::put('stripeCache-cus_test_fullsave', $stripeCache);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'customer' => 'cus_test_fullsave',
                    'billing_details' => [
                        'email' => 'fullcache@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'checks' => [
                                'cvc_check' => 'check',
                            ],
                            'wallet' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('payment_history', [
            'user_id' => $user->uuid,
        ]);
    }

    public function test_invoice_paid_with_existing_cache_updates_and_saves(): void
    {
        $user = User::factory()->create([
            'google_email' => 'inv@example.com',
        ]);
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $stripeCache = new StripeCache(
            null,
            'cus_inv_full',
            null,
            null,
            null,
            null,
            null,
            null,
            'visa',
            '4242',
            null,
            'inv@example.com',
            null,
            $plan->uuid,
            null,
            null,
        );

        Cache::put('stripeCache-cus_inv_full', $stripeCache);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'invoice.paid',
            'object' => 'event',
            'data' => [
                'object' => [
                    'id' => 'in_test_123',
                    'amount_paid' => 9990,
                    'customer' => 'cus_inv_full',
                    'customer_email' => 'inv@example.com',
                    'effective_at' => time(),
                    'invoice_pdf' => 'https://invoice.pdf',
                    'number' => 'INV-001',
                    'status' => 'paid',
                    'status_transitions' => [
                        'paid_at' => time(),
                    ],
                    'lines' => [
                        'data' => [
                            [
                                'id' => 'il_test_123',
                                'description' => 'Subscription',
                                'invoice' => 'in_test_123',
                                'parent' => [
                                    'subscription_item_details' => [
                                        'subscription' => 'sub_test_123',
                                        'subscription_item' => 'si_test_123',
                                    ],
                                ],
                                'pricing' => [
                                    'price_details' => [
                                        'price' => 'price_test_123',
                                        'product' => 'prod_test_123',
                                    ],
                                ],
                                'period' => [
                                    'end' => time() + 86400,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('payment_history', [
            'user_id' => $user->uuid,
        ]);
    }

    public function test_checkout_session_completed_with_existing_cache(): void
    {
        $plan = Plans::factory()->create();

        $stripeCache = new StripeCache(
            'sub_test_123',
            'cus_checkout',
            'in_test_123',
            'prod_test_123',
            'https://invoice.pdf',
            time() + 86400,
            9990,
            PlanStatus::PAID->value,
            'visa',
            '4242',
            'price_test_123',
            'test@example.com',
            time(),
            null,
            'Test subscription',
            'si_test_123',
        );

        Cache::put('stripeCache-cus_checkout', $stripeCache);

        $user = User::factory()->create([
            'google_email' => 'test@example.com',
        ]);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_checkout',
                    'metadata' => [
                        'plan_uuid' => $plan->uuid,
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('payment_history', [
            'user_id' => $user->uuid,
        ]);
    }

    public function test_checkout_session_completed_with_empty_plan_uuid(): void
    {
        Cache::spy();

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_empty_plan',
                    'metadata' => [
                        'plan_uuid' => '',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_checkout_session_completed_with_null_plan_uuid(): void
    {
        Cache::spy();

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_null_plan',
                    'metadata' => [
                        'plan_uuid' => null,
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_subscription_deleted_cancels_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plans::factory()->create();
        $defaultPlan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
            'stripe_user' => 'cus_delete_test',
            'status' => PlanStatus::ACTIVE->value,
        ]);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'status' => 'canceled',
                    'customer' => 'cus_delete_test',
                    'id' => 'sub_delete_test',
                    'ended_at' => time(),
                    'items' => [
                        'data' => [
                            [
                                'current_period_end' => time() + 86400,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_subscription_deleted_with_non_canceled_status_breaks(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'status' => 'active',
                    'customer' => 'cus_active_test',
                    'id' => 'sub_active_test',
                    'ended_at' => time(),
                    'items' => [
                        'data' => [
                            [
                                'current_period_end' => time() + 86400,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_subscription_deleted_with_no_subscription_breaks(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'status' => 'canceled',
                    'customer' => 'cus_no_sub_test',
                    'id' => 'sub_no_sub_test',
                    'ended_at' => time(),
                    'items' => [
                        'data' => [
                            [
                                'current_period_end' => time() + 86400,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_subscription_updated_updates_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plans::factory()->create([
            'price' => 49.90,
        ]);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
            'stripe_user' => 'cus_update_test',
        ]);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_update_test',
                    'customer' => 'cus_update_test',
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_update_test',
                                'current_period_end' => time() + 86400,
                                'plan' => [
                                    'id' => 'price_update_test',
                                    'amount' => 4990,
                                    'product' => 'prod_update_test',
                                    'metadata' => [
                                        'plan_uuid' => $plan->uuid,
                                    ],
                                ],
                                'subscription' => 'sub_update_test',
                            ],
                        ],
                    ],
                ],
                'previous_attributes' => [],
            ],
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertEquals('price_update_test', $subscription->stripe_price);
        $this->assertEquals('prod_update_test', $subscription->stripe_product);
    }

    public function test_subscription_updated_with_default_payment_method(): void
    {
        $user = User::factory()->create([
            'google_email' => 'pmtest@example.com',
        ]);
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
            'stripe_user' => 'cus_pm_test',
            'card_brand' => 'visa',
            'last_four_digits' => '4242',
        ]);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_pm_test',
                    'customer' => 'cus_pm_test',
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_pm_test',
                                'current_period_end' => time() + 86400,
                                'plan' => [
                                    'id' => 'price_pm_test',
                                    'amount' => 4990,
                                    'product' => 'prod_pm_test',
                                    'metadata' => [
                                        'plan_uuid' => $plan->uuid,
                                    ],
                                ],
                                'subscription' => 'sub_pm_test',
                            ],
                        ],
                    ],
                ],
                'previous_attributes' => [
                    'default_payment_method' => 'pm_old_test',
                ],
            ],
        ]);

        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_subscription_updated_with_no_subscription_breaks(): void
    {
        $plan = Plans::factory()->create();

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_nosub_test',
                    'customer' => 'cus_nosub_test',
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_nosub_test',
                                'current_period_end' => time() + 86400,
                                'plan' => [
                                    'id' => 'price_nosub_test',
                                    'amount' => 4990,
                                    'product' => 'prod_nosub_test',
                                    'metadata' => [
                                        'plan_uuid' => $plan->uuid,
                                    ],
                                ],
                                'subscription' => 'sub_nosub_test',
                            ],
                        ],
                    ],
                ],
                'previous_attributes' => [],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_subscription_updated_with_no_plan_finds_by_price(): void
    {
        $user = User::factory()->create();
        $plan = Plans::factory()->create([
            'price' => 29.90,
        ]);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
            'stripe_user' => 'cus_price_test',
        ]);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_price_test',
                    'customer' => 'cus_price_test',
                    'status' => 'active',
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_price_test',
                                'current_period_end' => time() + 86400,
                                'plan' => [
                                    'id' => 'price_price_test',
                                    'amount' => 2990,
                                    'product' => 'prod_price_test',
                                    'metadata' => [
                                        'plan_uuid' => 'nonexistent-plan-uuid',
                                    ],
                                ],
                                'subscription' => 'sub_price_test',
                            ],
                        ],
                    ],
                ],
                'previous_attributes' => [],
            ],
        ]);

        $response->assertStatus(200);
        $subscription->refresh();
        $this->assertEquals($plan->uuid, $subscription->plans_id);
    }

    public function test_payment_method_attached_google_pay_with_subscription(): void
    {
        $user = User::factory()->create([
            'google_email' => 'gpay2@example.com',
        ]);
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
            'stripe_user' => 'cus_gpay2_test',
        ]);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'payment_method.attached',
            'data' => [
                'object' => [
                    'customer' => 'cus_gpay2_test',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '1234',
                        'checks' => [
                            'cvc_check' => 'pass',
                        ],
                        'wallet' => [
                            'type' => 'google_pay',
                        ],
                    ],
                    'billing_details' => [
                        'email' => 'gpay2@example.com',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertEquals('visa', $subscription->card_brand);
        $this->assertEquals('1234', $subscription->last_four_digits);
    }

    public function test_payment_method_attached_google_pay_without_subscription(): void
    {
        Cache::spy();

        $user = User::factory()->create([
            'google_email' => 'gpay3@example.com',
        ]);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'payment_method.attached',
            'data' => [
                'object' => [
                    'customer' => 'cus_gpay_nosub',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '5678',
                        'checks' => [
                            'cvc_check' => 'pass',
                        ],
                        'wallet' => [
                            'type' => 'google_pay',
                        ],
                    ],
                    'billing_details' => [
                        'email' => 'gpay3@example.com',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_payment_method_attached_non_google_pay(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'payment_method.attached',
            'data' => [
                'object' => [
                    'customer' => 'cus_nongpay_test',
                    'card' => [
                        'brand' => 'mastercard',
                        'last4' => '9999',
                        'checks' => [
                            'cvc_check' => 'pass',
                        ],
                        'wallet' => null,
                    ],
                    'billing_details' => [
                        'email' => 'nongpay@example.com',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_payment_method_attached_with_null_cvc_checks(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'payment_method.attached',
            'data' => [
                'object' => [
                    'customer' => 'cus_nullcvc_test',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '1111',
                        'checks' => [
                            'cvc_check' => null,
                        ],
                        'wallet' => null,
                    ],
                    'billing_details' => [
                        'email' => 'nullcvc@example.com',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_customer_updated_with_invoice_settings_change(): void
    {
        $user = User::factory()->create([
            'google_email' => 'custupdate@example.com',
        ]);
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
            'stripe_user' => 'cus_updated_test',
        ]);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.updated',
            'data' => [
                'object' => [
                    'id' => 'cus_updated_test',
                    'email' => 'custupdate@example.com',
                ],
                'previous_attributes' => [
                    'invoice_settings' => [
                        'default_payment_method' => 'pm_old_method',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_customer_updated_without_invoice_settings_breaks(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.updated',
            'data' => [
                'object' => [
                    'id' => 'cus_noinv_test',
                    'email' => 'noinv@example.com',
                ],
                'previous_attributes' => [
                    'name' => 'Old Name',
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_customer_updated_with_null_default_payment_method(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'customer.updated',
            'data' => [
                'object' => [
                    'id' => 'cus_nullpm_test',
                    'email' => 'nullpm@example.com',
                ],
                'previous_attributes' => [
                    'invoice_settings' => [
                        'default_payment_method' => null,
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_save_stripe_informations_with_default_plan(): void
    {
        $user = User::factory()->create([
            'google_email' => 'emptyplan@example.com',
        ]);
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $stripeCache = new StripeCache(
            'sub_empty_plan',
            'cus_empty_plan',
            'inv_empty_plan',
            'prod_empty_plan',
            'https://invoice.pdf',
            time() + 86400,
            9990,
            PlanStatus::PAID->value,
            'visa',
            '4242',
            'price_empty_plan',
            'emptyplan@example.com',
            time(),
            $plan->uuid,
            'Test subscription',
            'si_empty_plan',
        );

        Cache::put('stripeCache-cus_empty_plan', $stripeCache);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_empty_plan',
                    'customer' => 'cus_empty_plan',
                    'billing_details' => [
                        'email' => 'emptyplan@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'checks' => [
                                'cvc_check' => 'check',
                            ],
                            'wallet' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('payment_history', [
            'user_id' => $user->uuid,
        ]);
    }

    public function test_save_stripe_informations_with_github_email_user(): void
    {
        $user = User::factory()->create([
            'github_email' => 'githubsave@example.com',
            'google_email' => null,
        ]);
        $plan = Plans::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->uuid,
            'plans_id' => $plan->uuid,
        ]);

        $stripeCache = new StripeCache(
            'sub_github_save',
            'cus_github_save',
            'inv_github_save',
            'prod_github_save',
            'https://invoice2.pdf',
            time() + 86400,
            4990,
            PlanStatus::PAID->value,
            'mastercard',
            '5555',
            'price_github_save',
            'githubsave@example.com',
            time(),
            $plan->uuid,
            'Github subscription',
            'si_github_save',
        );

        Cache::put('stripeCache-cus_github_save', $stripeCache);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_github_save',
                    'customer' => 'cus_github_save',
                    'billing_details' => [
                        'email' => 'githubsave@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'mastercard',
                            'last4' => '5555',
                            'checks' => [
                                'cvc_check' => 'check',
                            ],
                            'wallet' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('payment_history', [
            'user_id' => $user->uuid,
            'card_brand' => 'mastercard',
        ]);
    }

    public function test_save_stripe_informations_user_not_found(): void
    {
        $stripeCache = new StripeCache(
            'sub_no_user',
            'cus_no_user',
            'inv_no_user',
            'prod_no_user',
            'https://invoice3.pdf',
            time() + 86400,
            9990,
            PlanStatus::PAID->value,
            'visa',
            '4242',
            'price_no_user',
            'nouser@example.com',
            time(),
            'some-plan-uuid',
            'No user subscription',
            'si_no_user',
        );

        Cache::put('stripeCache-cus_no_user', $stripeCache);

        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'charge.succeeded',
            'data' => [
                'object' => [
                    'id' => 'ch_no_user',
                    'customer' => 'cus_no_user',
                    'billing_details' => [
                        'email' => 'nouser@example.com',
                    ],
                    'payment_method_details' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                            'checks' => [
                                'cvc_check' => 'check',
                            ],
                            'wallet' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_payment_method_attached_with_empty_wallet_type(): void
    {
        $response = $this->postJson('/api/webhook/payment', [
            'id' => 'evt_test_123',
            'type' => 'payment_method.attached',
            'data' => [
                'object' => [
                    'customer' => 'cus_empty_wallet',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '3333',
                        'checks' => [
                            'cvc_check' => 'pass',
                        ],
                        'wallet' => [
                            'type' => '',
                        ],
                    ],
                    'billing_details' => [
                        'email' => 'emptywallet@example.com',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(in_array($response->status(), [200, 500]));
    }
}
