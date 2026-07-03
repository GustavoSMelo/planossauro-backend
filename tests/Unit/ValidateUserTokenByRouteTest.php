<?php

namespace Tests\Unit;

use App\Http\Middleware\ValidateUserTokenByRoute;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ValidateUserTokenByRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_route_uuid_matches_token_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create("/test/{$user->uuid}", 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $request->setRouteResolver(function () use ($user) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{userUUID}', []);
            $route->bind($request);
            $route->setParameter('userUUID', $user->uuid);
            return $route;
        });

        $middleware = new ValidateUserTokenByRoute();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_rejects_when_route_uuid_does_not_match(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create("/test/{$otherUser->uuid}", 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $request->setRouteResolver(function () use ($otherUser) {
            $route = new \Illuminate\Routing\Route('GET', '/test/{userUUID}', []);
            $route->bind($request);
            $route->setParameter('userUUID', $otherUser->uuid);
            return $route;
        });

        $middleware = new ValidateUserTokenByRoute();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertFalse($called);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
