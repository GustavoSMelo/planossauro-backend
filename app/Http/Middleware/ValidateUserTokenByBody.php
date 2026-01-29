<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateUserTokenByBody
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uuidBody = $request->input('uuid');
        $uuidUserBody = $request->input('user_id');

        $authToken = $request->header('Authorization');
        $token = explode(' ', $authToken)[1];

        $user = PersonalAccessToken::findToken($token)->tokenable;

        if ($user->uuid === $uuidBody || $user->uuid === $uuidUserBody) return $next($request);

        return response()->json([
            'Error' => 'You do not have permission to see this route or informations'
        ], 401);
    }
}
