<?php

namespace Tests\Unit;

use App\Http\Middleware\ValidateUserTokenByBody;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ValidateUserTokenByBodyTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_uuid_matches_token_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create('/test', 'POST', ['uuid' => $user->uuid]);
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $middleware = new ValidateUserTokenByBody();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_passes_when_user_id_matches_token_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create('/test', 'POST', ['user_id' => $user->uuid]);
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $middleware = new ValidateUserTokenByBody();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_rejects_when_uuid_does_not_match(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create('/test', 'POST', ['uuid' => $otherUser->uuid]);
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $middleware = new ValidateUserTokenByBody();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertFalse($called);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_rejects_when_both_uuid_and_user_id_do_not_match(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create('/test', 'POST', [
            'uuid' => $otherUser->uuid,
            'user_id' => $otherUser->uuid,
        ]);
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $middleware = new ValidateUserTokenByBody();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertFalse($called);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
