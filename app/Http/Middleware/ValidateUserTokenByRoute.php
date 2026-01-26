<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateUserTokenByRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tokenWithBearer = $request->header('Authorization');

        $token = explode(' ', $tokenWithBearer)[1];
        $user = PersonalAccessToken::findToken($token)->tokenable;

        $requestUUID = $request->route('userUUID');

        if ($requestUUID === $user->uuid) {
            return $next($request);
        }

        return response()->json([
            'Error' => 'You do not have permission to see this route or informations'
        ], 401);
    }
}
