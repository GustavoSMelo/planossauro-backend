<?php

namespace Tests\Unit;

use App\Http\Middleware\ValidateUserTokenByBodyGithubAccount;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ValidateUserTokenByRouteGithubAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_github_email_matches(): void
    {
        $user = User::factory()->create([
            'github_email' => 'test@example.com',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create('/test/test@example.com', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/test/{githubEmail}', []);
            $request = Request::create('/test/test@example.com', 'GET');
            $route->bind($request);
            $route->setParameter('githubEmail', 'test@example.com');
            return $route;
        });

        $middleware = new ValidateUserTokenByBodyGithubAccount();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($called);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_rejects_when_github_email_does_not_match(): void
    {
        $user = User::factory()->create([
            'github_email' => 'test@example.com',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $request = Request::create('/test/other@example.com', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/test/{githubEmail}', []);
            $request = Request::create('/test/other@example.com', 'GET');
            $route->bind($request);
            $route->setParameter('githubEmail', 'other@example.com');
            return $route;
        });

        $middleware = new ValidateUserTokenByBodyGithubAccount();
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            return response()->json(['ok' => true]);
        });

        $this->assertFalse($called);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
