<?php

namespace Tests\Unit;

use App\Http\Middleware\ValidateUserTokenByRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UserControllerTest extends TestCase
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
        $this->withoutMiddleware(ValidateUserTokenByRoute::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByBody::class);
    }

    public function test_store_creates_user_with_github_email(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cellphone_number' => '11999999999',
            'github_email' => 'john@example.com',
            'github_id' => '12345',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ];

        $response = $this->postJson('/api/user', $userData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => ['uuid', 'full_name', 'github_email'],
        ]);

        $this->assertDatabaseHas('user', [
            'full_name' => 'John Doe',
            'github_email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('planning_hour', [
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ]);
    }

    public function test_store_creates_user_with_google_email(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cellphone_number' => '11999999999',
            'google_email' => 'john@gmail.com',
            'google_id' => '67890',
            'initial_hour' => '14:00',
            'interval_between_classes' => '01:00',
        ];

        $response = $this->postJson('/api/user', $userData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => ['uuid', 'full_name', 'google_email'],
        ]);

        $this->assertDatabaseHas('user', [
            'full_name' => 'John Doe',
            'google_email' => 'john@gmail.com',
        ]);
    }

    public function test_store_fails_without_email(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cellphone_number' => '11999999999',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ];

        $response = $this->postJson('/api/user', $userData);

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'Github and Google email is null, please, provide at least one of them');
    }

    public function test_store_fails_with_github_email_without_github_id(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cellphone_number' => '11999999999',
            'github_email' => 'john@example.com',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ];

        $response = $this->postJson('/api/user', $userData);

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'Github id not provided');
    }

    public function test_store_validation_fails_with_short_name(): void
    {
        $userData = [
            'full_name' => 'Jo',
            'cellphone_number' => '11999999999',
            'github_email' => 'john@example.com',
            'github_id' => '12345',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ];

        $response = $this->postJson('/api/user', $userData);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Error on validation code');
    }

    public function test_store_validation_fails_with_short_phone(): void
    {
        $userData = [
            'full_name' => 'John Doe',
            'cellphone_number' => '1199999',
            'github_email' => 'john@example.com',
            'github_id' => '12345',
            'initial_hour' => '12:00',
            'interval_between_classes' => '00:30',
        ];

        $response = $this->postJson('/api/user', $userData);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Error on validation code');
    }

    public function test_show_returns_user_by_uuid(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create();

        $response = $this->getJson("/api/user/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('uuid', (string) $user->uuid);
    }

    public function test_show_returns_500_for_nonexistent_user(): void
    {
        $this->disableMiddleware();
        $response = $this->getJson('/api/user/nonexistent-uuid');

        $response->assertStatus(500);
    }

    public function test_update_modifies_user_data(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create([
            'github_email' => 'original@example.com',
            'github_is_validated' => true,
        ]);

        $updateData = [
            'full_name' => 'Updated Name',
            'cellphone_number' => '11988888888',
            'github_email' => 'original@example.com',
        ];

        $response = $this->putJson("/api/user/{$user->uuid}", $updateData);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'User updated with success');

        $this->assertDatabaseHas('user', [
            'uuid' => $user->uuid,
            'full_name' => 'Updated Name',
        ]);
    }

    public function test_update_returns_400_for_nonexistent_user(): void
    {
        $this->disableMiddleware();
        $updateData = [
            'full_name' => 'Updated Name',
            'cellphone_number' => '11988888888',
        ];

        $response = $this->putJson('/api/user/nonexistent-uuid', $updateData);

        $response->assertStatus(400);
    }

    public function test_destroy_fails_without_subscription(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create([
            'github_is_validated' => true,
        ]);

        $response = $this->deleteJson("/api/user/{$user->uuid}");

        $response->assertStatus(400);
    }

    public function test_destroy_fails_for_user_without_validated_account(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create([
            'github_is_validated' => false,
            'google_is_validated' => false,
        ]);

        $response = $this->deleteJson("/api/user/{$user->uuid}");

        $response->assertStatus(401);
        $response->assertJsonPath('error', 'To delete this account, you first need to have a google or github account validated');
    }

    public function test_destroy_fails_for_nonexistent_user(): void
    {
        $this->disableMiddleware();
        $response = $this->deleteJson('/api/user/nonexistent-uuid');

        $response->assertStatus(404);
        $response->assertJsonPath('error', 'User with this uuid was not founded');
    }

    public function test_find_by_github_email_returns_user(): void
    {
        $user = User::factory()->create([
            'github_email' => 'github@example.com',
        ]);

        $response = $this->getJson("/api/user/github/{$user->github_email}");

        $response->assertStatus(200);
        $response->assertJsonPath('github_email', 'github@example.com');
    }

    public function test_find_by_google_email_returns_user(): void
    {
        $user = User::factory()->create([
            'google_email' => 'google@example.com',
        ]);

        $response = $this->getJson("/api/user/google/{$user->google_email}");

        $response->assertStatus(200);
        $response->assertJsonPath('google_email', 'google@example.com');
    }

    public function test_validate_with_incorrect_code(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create([
            'github_validation_code' => 12345,
        ]);

        $response = $this->patchJson("/api/user/validate/{$user->uuid}", [
            'loginType' => 'github',
            'validationCode' => '99999',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'validation code is invalid');
    }

    public function test_validate_requires_valid_login_type(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create();

        $response = $this->patchJson("/api/user/validate/{$user->uuid}", [
            'loginType' => 'invalid',
            'validationCode' => '12345',
        ]);

        $response->assertStatus(400);
    }

    public function test_remove_soft_delete_user_restores_user(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create([
            'deleted_at' => now(),
        ]);

        $response = $this->patchJson("/api/user/restore/{$user->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'User restored with success');

        $user->refresh();
        $this->assertNull($user->deleted_at);
    }

    public function test_remove_soft_delete_fails_for_nonexistent_user(): void
    {
        $this->disableMiddleware();
        $response = $this->patchJson('/api/user/restore/nonexistent-uuid');

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'user with this uuid was not founded');
    }

    public function test_unlink_github_account(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create([
            'github_email' => 'github@example.com',
            'google_email' => 'google@example.com',
        ]);

        $response = $this->patchJson("/api/user/unlink/{$user->uuid}", [
            'unlink' => 'github',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Github unlinked with success');

        $user->refresh();
        $this->assertNull($user->github_email);
    }

    public function test_unlink_google_account(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create([
            'github_email' => 'github@example.com',
            'google_email' => 'google@example.com',
        ]);

        $response = $this->patchJson("/api/user/unlink/{$user->uuid}", [
            'unlink' => 'google',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Google unlinked with success');

        $user->refresh();
        $this->assertNull($user->google_email);
    }

    public function test_unlink_fails_with_invalid_type(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create();

        $response = $this->patchJson("/api/user/unlink/{$user->uuid}", [
            'unlink' => 'invalid',
        ]);

        $response->assertStatus(200);
    }

    public function test_unlink_fails_when_only_github_account(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create([
            'github_email' => 'github@example.com',
            'google_email' => null,
        ]);

        $response = $this->patchJson("/api/user/unlink/{$user->uuid}", [
            'unlink' => 'google',
        ]);

        $response->assertStatus(200);
    }

    public function test_unlink_fails_when_only_google_account(): void
    {
        $this->disableMiddleware();
        $user = User::factory()->create([
            'github_email' => null,
            'google_email' => 'google@example.com',
        ]);

        $response = $this->patchJson("/api/user/unlink/{$user->uuid}", [
            'unlink' => 'github',
        ]);

        $response->assertStatus(200);
    }

    public function test_resend_email_fails_for_nonexistent_user(): void
    {
        $this->disableMiddleware();
        $response = $this->postJson('/api/user/resend/validationcode', [
            'uuid' => 'invalid-uuid',
            'loginType' => 'github',
        ]);

        $response->assertStatus(500);
    }
}
