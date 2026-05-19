<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
}