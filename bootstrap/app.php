<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(commands: __DIR__ . "/../routes/console.php", health: "/up")
    ->withRouting(api: __DIR__ . "/../routes/api.php", apiPrefix: "/api")
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            fn() => response()->json(["message" => "Unauthenticated"], 401),
        );
        $middleware->trustProxies(at: "*");
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            Request $request,
        ) {
            return response()->json(
                [
                    "message" => "Unauthenticated.",
                    "status" => "error",
                ],
                401,
            );
        });
    })
    ->create();
