<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateUserTokenByBodyGithubAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $githubEmail = $request->route('githubEmail');

        $authToken = $request->header('Authorization');
        $token = explode(' ', $authToken)[1];

        $user = PersonalAccessToken::findToken($token);

        if ($user->github_email === $githubEmail) {
            return $next($request);
        }

        return response()->json([
            'Error' => 'You do not have permission to see this route or informations'
        ], 401);
    }
}
