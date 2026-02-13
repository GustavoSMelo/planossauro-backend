<?php

namespace App\Http\Middleware\Subscription;

use Closure;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateStripeCustomerID
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->input('customer');
        $authToken = $request->header('Authorizaton');
        $token = explode(' ', $authToken)[1];
        $user = PersonalAccessToken::findToken($token)->tokenable;
        $subscription = Subscription::query()->where('user_id', '=', $user);

        if ($subscription->stripe_user === $customer) {
            return $next($request);
        }

        return response()->json([
            'Error' => 'You do not have permission to see this route'
        ], 401);
    }
}
