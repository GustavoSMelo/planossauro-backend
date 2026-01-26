<?php

namespace App\Http\Middleware\PaymentHistory;

use App\Models\PaymentHistory;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaymentHistoryID
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $paymentId = $request->route('paymentId');

        $authToken = $request->header('Authorization');
        $token = explode(' ', $authToken)[1];
        $user = PersonalAccessToken::findToken($token);
        $payment = PaymentHistory::query()
            ->where('uuid', '=', $paymentId)
            ->first();

        if ($user->uuid === $payment->user_id) {
            return $next($request);
        }

        return response()->json([
            'Error' => 'You do not have permission to see this route or informations'
        ], 401);
    }
}
