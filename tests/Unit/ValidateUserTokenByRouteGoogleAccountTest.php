<?php

namespace Tests\Unit;

use App\Http\Middleware\ValidateUserTokenByRouteGoogleAccount;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ValidateUserTokenByRouteGoogleAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_google_email_matches(): void
    {
        $user = User::factory()->create([
            'google_email' => 'test@gmail.com',
            'google_id' => '12345',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create('/test/test@gmail.com', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/test/{googleEmail}', []);
            $request = Request::create('/test/test@gmail.com', 'GET');
            $route->bind($request);
            $route->setParameter('googleEmail', 'test@gmail.com');
            return $route;
        });

        $middleware = new ValidateUserTokenByRouteGoogleAccount();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_rejects_when_google_email_does_not_match(): void
    {
        $user = User::factory()->create([
            'google_email' => 'test@gmail.com',
            'google_id' => '12345',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create('/test/other@gmail.com', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/test/{googleEmail}', []);
            $request = Request::create('/test/other@gmail.com', 'GET');
            $route->bind($request);
            $route->setParameter('googleEmail', 'other@gmail.com');
            return $route;
        });

        $middleware = new ValidateUserTokenByRouteGoogleAccount();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertFalse($called);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
