<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_returns_api_and_database_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'api' => [
                'status',
                'message',
            ],
            'database' => [
                'status',
                'message',
            ],
        ]);
    }

    public function test_check_returns_200_for_api_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonPath('api.status', 200);
        $response->assertJsonPath('api.message', 'ok');
    }

    public function test_check_returns_200_for_database_when_connected(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonPath('database.status', 200);
        $response->assertJsonPath('database.message', 'ok');
    }

    public function test_check_root_endpoint_returns_same_response(): void
    {
        $response = $this->getJson('/api');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'api' => [
                'status',
                'message',
            ],
            'database' => [
                'status',
                'message',
            ],
        ]);
    }
}
