<?php

namespace App\Http\Controllers;

use App\Enums\PlanStatus as EnumsPlanStatus;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentHistoryController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_date' => ['required', 'date'],
                'description' => ['required', 'string'],
                'card_brand' => ['required', 'string'],
                'price' => ['required', 'numeric'],
                'status' => ['required', 'string', Rule::enum(EnumsPlanStatus::class)],
                'plan_id' => ['required', 'string', 'exists:plans,uuid', 'uuid'],
                'user_id' => ['required', 'string', 'exists:user,uuid', 'uuid'],
                'last_four_digits' => ['required', 'numeric'],
                'NFe' => ['nullable', 'string']
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'error on validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $paymentDate = $request->input('payment_date');
            $description = $request->input('description');
            $cardBrand = $request->input('card_brand');
            $price = $request->input('price');
            $status = $request->input('status');
            $planId = $request->input('plan_id');
            $lastFourDigits = $request->input('last_four_digits');
            $userId = $request->input('user_id');
            $NFe = $request->input('NFe');

            PaymentHistory::create([
                'payment_date' => $paymentDate,
                'description' => $description,
                'card_brand' => $cardBrand,
                'last_four_digits' => $lastFourDigits,
                'price' => $price,
                'plan_id' => $planId,
                'user_id' => $userId,
                'status' => $status,
                'NFe' => $NFe
            ]);

            return response()->json([
                'message' => 'Payment registred with success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error',
                'error' => $e
            ]);
        }
    }

    public function show(string $userId)
    {
        $payments = PaymentHistory::query()->where('user_id', '=', $userId)->get();

        return response()->json([
            'payments' => $payments
        ]);
    }

    public function update(string $paymentId, Request $request)
    {
        $request->validate([
            'payment_date' => ['required', 'date', 'after_or_equal:today'],
            'description' => ['required', 'string'],
            'card_brand' => ['required', 'string'],
            'price' => ['required', 'numeric'],
            'status' => ['required', 'string', Rule::enum(EnumsPlanStatus::class)],
            'plan_id' => ['required', 'string', 'exists:plans,uuid', 'uuid'],
            'user_id' => ['required', 'string', 'exists:user,uuid', 'uuid'],
            'last_four_digits' => ['required', 'numeric'],
            'NFe' => ['nullable', 'string']
        ]);

        $paymentDate = $request->input('payment_date');
        $description = $request->input('description');
        $cardBrand = $request->input('card_brand');
        $price = $request->input('price');
        $status = $request->input('status');
        $planId = $request->input('plan_id');
        $lastFourDigits = $request->input('last_four_digits');
        $userId = $request->input('user_id');

        $payment = PaymentHistory::query()->where('uuid', '=', $paymentId)->first();

        if (!$payment) return response()->json([
            'message' => 'User not founded'
        ]);

        $payment->update([
            'payment_date' => $paymentDate,
            'description' => $description,
            'card_brand' => $cardBrand,
            'last_four_digits' => $lastFourDigits,
            'price' => $price,
            'plan_id' => $planId,
            'user_id' => $userId,
            'status' => $status,
        ]);
        $payment->save();

        return response()->json([
            'message' => 'payment updated with success'
        ]);
    }

    public function insertNFe(string $paymentId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'NFe' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error on validation',
                'errors' => $validator->errors()
            ]);
        }


        $payment = PaymentHistory::query()->where('uuid', '=', $paymentId)->first();

        if (!$payment) return response()->json(['message' => 'user not founded'], 422);

        $payment->update([
            'NFe' => $request->input('NFe')
        ]);
        $payment->save();

        return response()->json([
            'message' => 'NFe inserted with success'
        ]);
    }

    public function updatePaymentStatus(string $paymentUuid, Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'status' => ['required', 'string', Rule::enum(EnumsPlanStatus::class)]
            ],
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error on validation',
                'errors' => $validator->errors()
            ]);
        }

        $payment = PaymentHistory::query()->where('uuid', '=', $paymentUuid)->first();
        $payment->update([
            'status' => $request->input('status')
        ]);
        $payment->save();

        return response()->json([
            'message' => 'Payment status updated with success'
        ]);
    }
}
