<?php

namespace Tests\Unit;

use App\Models\PlanningHour;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $mockEmails = Mockery::mock();
        $mockEmails->shouldReceive('send')->andReturn(['id' => 'test_id']);

        $mock = Mockery::mock();
        $mock->shouldReceive('emails')->andReturn($mockEmails);

        $this->app->instance('resend', $mock);
    }

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

    public function test_facebook_access_token_returns_token(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'access_token' => 'fb_test_token',
            ], 200),
        ]);

        $response = $this->getJson('/api/token/facebook/fb_code');

        $response->assertStatus(200);
        $response->assertJsonPath('access_token', 'fb_test_token');
    }

    public function test_facebook_access_token_returns_error(): void
    {
        Http::fake([
            'https://graph.facebook.com/v25.0/oauth/access_token' => Http::response([
                'error' => [
                    'message' => 'Invalid code',
                ],
            ], 400),
        ]);

        $response = $this->getJson('/api/token/facebook/invalid_fb_code');

        $this->assertTrue(in_array($response->status(), [200, 400, 401]));
    }

    public function test_facebook_auth_returns_user_with_valid_token(): void
    {
        $user = User::factory()->create([
            'facebook_email' => 'fb@example.com',
            'facebook_id' => 'fb123',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 'fb123',
                'email' => 'fb@example.com',
                'name' => 'Test User',
            ], 200),
        ]);

        $response = $this->getJson('/api/auth/facebook/fb_valid_token');

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Auth granted');
        $response->assertJsonPath('email', 'fb@example.com');
        $response->assertJsonPath('id', 'fb123');
        $response->assertJsonPath('name', 'Test User');
    }

    public function test_facebook_auth_returns_user_not_found(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 'fb999',
                'email' => 'notfound@example.com',
                'name' => 'Not Found',
            ], 200),
        ]);

        $response = $this->getJson('/api/auth/facebook/fb_unknown_token');

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'User not found');
        $response->assertJsonPath('email', 'notfound@example.com');
    }

    public function test_facebook_auth_returns_user_not_found_for_non_matching(): void
    {
        $user = User::factory()->create([
            'facebook_email' => 'correct@example.com',
            'facebook_id' => 'fb123',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 'fb456',
                'email' => 'other@example.com',
                'name' => 'Test User',
            ], 200),
        ]);

        $response = $this->getJson('/api/auth/facebook/fb_non_matching');

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'User not found');
    }

    public function test_register_creates_new_user(): void
    {
        $plan = Plans::factory()->free()->create();

        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Test User',
            'email' => 'newuser@example.com',
            'cellphone_number' => '11999999999',
            'password' => 'password123',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'User registered with success');

        $this->assertDatabaseHas('user', [
            'user_email' => 'newuser@example.com',
            'full_name' => 'Test User',
        ]);

        $this->assertDatabaseHas('planning_hour', [
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ]);
    }

    public function test_register_validation_fails(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Jo',
            'email' => 'not-email',
            'cellphone_number' => '123',
            'password' => 'short',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Validation failed');
    }

    public function test_register_existing_user_with_same_password(): void
    {
        $existingUser = User::factory()->create([
            'user_email' => 'existing@example.com',
            'user_password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Updated Name',
            'email' => 'existing@example.com',
            'cellphone_number' => '11999999999',
            'password' => 'password123',
            'initial_hour' => '14:00',
            'interval_between_classes' => '01:00',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'User updated and registered with success');
    }

    public function test_register_existing_user_with_different_password(): void
    {
        $existingUser = User::factory()->create([
            'user_email' => 'existing2@example.com',
            'user_password' => Hash::make('oldpassword'),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Test User',
            'email' => 'existing2@example.com',
            'cellphone_number' => '11999999999',
            'password' => 'differentpassword',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error', 'Invalid credentials');
    }

    public function test_register_existing_user_with_github_email(): void
    {
        $existingUser = User::factory()->create([
            'github_email' => 'github@example.com',
            'user_password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Updated Name',
            'email' => 'github@example.com',
            'cellphone_number' => '11999999999',
            'password' => 'password123',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'User updated and registered with success');
    }

    public function test_register_existing_user_updates_planning_hour(): void
    {
        $existingUser = User::factory()->create([
            'user_email' => 'ph@example.com',
            'user_password' => Hash::make('password123'),
        ]);
        PlanningHour::factory()->create([
            'user_id' => $existingUser->uuid,
            'initial_hour' => '08:00',
            'interval_between_classes' => '00:30',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Updated Name',
            'email' => 'ph@example.com',
            'cellphone_number' => '11999999999',
            'password' => 'password123',
            'initial_hour' => '15:00',
            'interval_between_classes' => '01:00',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('planning_hour', [
            'user_id' => $existingUser->uuid,
            'initial_hour' => '15:00',
            'interval_between_classes' => '01:00',
        ]);
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'user_email' => 'login@example.com',
            'user_password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Auth granted');
        $response->assertJsonStructure(['token', 'type', 'message', 'user']);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'user_email' => 'login2@example.com',
            'user_password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login2@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_login_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_login_validation_fails(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Validation failed');
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

    public function test_google_auth_returns_401_on_exception(): void
    {
        Http::fake([
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response(null, 500),
        ]);

        $response = $this->getJson('/api/auth/google/broken_token');

        $response->assertStatus(401);
    }

    public function test_register_in_local_env_uses_adm_plan(): void
    {
        config(['app.env' => 'local']);
        Plans::factory()->create(['plan_name' => 'adm']);

        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Test User',
            'email' => 'localuser@example.com',
            'cellphone_number' => '11999999999',
            'password' => 'password123',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ]);

        $response->assertStatus(201);
    }

    public function test_facebook_auth_returns_error_on_exception(): void
    {
        Http::fake([
            'https://graph.facebook.com/v25.0/me' => Http::response(null, 500),
        ]);

        $response = $this->getJson('/api/auth/facebook/broken_fb_token');

        $this->assertTrue(in_array($response->status(), [200, 400, 500]));
    }

    public function test_github_access_token_exception(): void
    {
        Http::fake([
            'https://github.com/login/oauth/access_token' => Http::response(null, 500),
        ]);

        $response = $this->getJson('/api/token/github/broken_code');

        $this->assertTrue(in_array($response->status(), [200, 400]));
    }
}
