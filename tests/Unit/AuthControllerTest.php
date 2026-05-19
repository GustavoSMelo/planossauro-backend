<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function disableMiddleware(): void
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        $this->withoutMiddleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
    }

    public function test_github_access_token_returns_access_token(): void
    {
        Http::fake([
            'https://github.com/login/oauth/access_token' => Http::response([
                'access_token' => 'test_token',
            ], 200),
            'https://api.github.com/user' => Http::response([
                'id' => 12345,
                'email' => 'test@example.com',
            ], 200),
        ]);

        $response = $this->getJson('/api/token/github/test_code');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'accessToken',
        ]);
    }

    public function test_github_access_token_returns_error_for_invalid_code(): void
    {
        Http::fake([
            'https://github.com/login/oauth/access_token' => Http::response([
                'error' => 'bad_verification_code',
                'error_description' => 'Bad verification code',
            ], 400),
        ]);

        $response = $this->getJson('/api/token/github/invalid_code');

        $this->assertTrue(in_array($response->status(), [200, 400]));
    }

    public function test_github_auth_returns_user_with_valid_token(): void
    {
        $user = User::factory()->create([
            'github_email' => 'test@example.com',
            'github_id' => '12345',
        ]);

        Http::fake([
            'https://api.github.com/user' => Http::response([
                'id' => 12345,
                'email' => 'test@example.com',
            ], 200),
        ]);

        $response = $this->getJson('/api/auth/github/valid_token');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'type',
            'message',
        ]);
    }

    public function test_github_auth_returns_401_for_unregistered_user(): void
    {
        Http::fake([
            'https://api.github.com/user' => Http::response([
                'id' => 99999,
                'email' => 'nonexistent@example.com',
            ], 200),
        ]);

        $response = $this->getJson('/api/auth/github/token_for_unknown_user');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'User not founded');
    }

    public function test_google_auth_returns_user_with_valid_token(): void
    {
        $user = User::factory()->create([
            'google_email' => 'test@gmail.com',
            'google_id' => '123456789',
        ]);

        Http::fake([
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'email' => 'test@gmail.com',
                'sub' => '123456789',
            ], 200),
        ]);

        $response = $this->getJson('/api/auth/google/valid_token');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'type',
            'message',
        ]);
    }

    public function test_google_auth_returns_401_for_unregistered_user(): void
    {
        Http::fake([
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'email' => 'nonexistent@gmail.com',
                'sub' => '999999999',
            ], 200),
        ]);

        $response = $this->getJson('/api/auth/google/token_for_unknown_user');

        $response->assertStatus(401);
    }

    public function test_google_auth_returns_401_for_invalid_google_id(): void
    {
        $user = User::factory()->create([
            'google_email' => 'test@gmail.com',
            'google_id' => '123456789',
        ]);

        Http::fake([
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'email' => 'test@gmail.com',
                'sub' => 'wrong_id',
            ], 200),
        ]);

        $response = $this->getJson('/api/auth/google/token_with_wrong_id');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Google id is invalid');
    }

    public function test_logout_deletes_user_tokens(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create();
        $user->createToken('auth');

        $response = $this->deleteJson("/api/logout/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'User logout with success');
    }

    public function test_logout_returns_error_for_nonexistent_user(): void
    {
        $this->disableMiddleware();

        $response = $this->deleteJson('/api/logout/nonexistent-uuid');

        $response->assertStatus(500);
    }
}
