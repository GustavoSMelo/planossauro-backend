<?php

namespace App\Http\Middleware\Planning;

use App\Models\Planning;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidatePlanningID
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->route('uuid');

        $authToken = $request->header('Authorization');
        $token = explode(' ', $authToken)[1];
        $user = PersonalAccessToken::findToken($token);
        $planning = Planning::query()->where('uuid', '=', $uuid)->first();

        if ($user->uuid === $planning->user_id) {
            return $next($request);
        }

        return response()->json([
            'Error' => 'You do not have permission to see this route or informations'
        ], 401);
    }
}
