<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SupportEmailsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $mockEmails = Mockery::mock();
        $mockEmails->shouldReceive('send')->andReturn(['id' => 'test_email_id']);

        $mock = Mockery::mock();
        $mock->shouldReceive('emails')->andReturn($mockEmails);

        $this->app->instance('resend', $mock);
    }

    protected function disableMiddleware(): void
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        $this->withoutMiddleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        $this->withoutMiddleware(\App\Http\Middleware\ValidateUserTokenByRoute::class);
    }

    public function test_create_and_send_email_creates_support_email(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $emailData = [
            'title' => 'Test Support Request',
            'description' => 'This is a test description',
            'category' => 'technical',
            'ticketId' => 'TICKET-123',
        ];

        $response = $this->postJson("/api/support/email/{$user->uuid}", $emailData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('support_emails', [
            'title' => 'Test Support Request',
            'description' => 'This is a test description',
            'category' => 'technical',
            'ticketId' => 'TICKET-123',
            'user_id' => $user->uuid,
        ]);
    }

    public function test_create_and_send_email_fails_with_validation_error(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $emailData = [
            'title' => '',
        ];

        $response = $this->postJson("/api/support/email/{$user->uuid}", $emailData);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Error on validation');
    }

    public function test_create_and_send_email_fails_for_nonexistent_user(): void
    {
        $this->disableMiddleware();

        $emailData = [
            'title' => 'Test Support Request',
            'description' => 'This is a test description',
            'category' => 'technical',
            'ticketId' => 'TICKET-123',
        ];

        $response = $this->postJson('/api/support/email/nonexistent-uuid', $emailData);

        $response->assertStatus(200);
        $response->assertJsonPath('error', 'user not found');
    }

    public function test_create_and_send_email_stores_user_github_email(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create([
            'github_email' => 'github@example.com',
            'google_email' => null,
        ]);

        $emailData = [
            'title' => 'Test Support Request',
            'description' => 'This is a test description',
            'category' => 'technical',
            'ticketId' => 'TICKET-123',
        ];

        $response = $this->postJson("/api/support/email/{$user->uuid}", $emailData);

        $response->assertStatus(200);
    }

    public function test_create_and_send_email_stores_user_google_email(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create([
            'google_email' => 'google@example.com',
            'github_email' => null,
        ]);

        $emailData = [
            'title' => 'Test Support Request',
            'description' => 'This is a test description',
            'category' => 'technical',
            'ticketId' => 'TICKET-123',
        ];

        $response = $this->postJson("/api/support/email/{$user->uuid}", $emailData);

        $response->assertStatus(200);
    }

    public function test_create_and_send_email_includes_user_cellphone(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create([
            'cellphone_number' => '11999999999',
        ]);

        $emailData = [
            'title' => 'Test Support Request',
            'description' => 'This is a test description',
            'category' => 'technical',
            'ticketId' => 'TICKET-123',
        ];

        $response = $this->postJson("/api/support/email/{$user->uuid}", $emailData);

        $response->assertStatus(200);
    }

    public function test_create_and_send_email_creates_support_email_record(): void
    {
        $this->disableMiddleware();

        $user = User::factory()->create();

        $emailData = [
            'title' => 'Help Request',
            'description' => 'Need help with my account',
            'category' => 'account',
            'ticketId' => 'TICKET-456',
        ];

        $this->postJson("/api/support/email/{$user->uuid}", $emailData);

        $this->assertDatabaseHas('support_emails', [
            'user_id' => $user->uuid,
            'ticketId' => 'TICKET-456',
        ]);
    }
}
