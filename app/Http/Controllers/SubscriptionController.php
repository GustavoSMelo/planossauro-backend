<?php

namespace App\Http\Controllers;

use App\Enums\PlanStatus;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function assignFreePlanToUser(string $userUUID)
    {
        $user = User::query()->where('uuid', '=', $userUUID)->first();
        if (!$user) return response()->json(['message' => 'User with this is does not exists'], 404);

        $plans = Plans::query()->where('price', '=', 0)->first();
        $subscription = Subscription::query()->where('user_id', '=', $userUUID)->first();

        if ($subscription) {
            $subscription->update([
                'daily_plans_used' => 0,
                'weekly_plans_used' => 0,
                'date_verified' => null,
                'next_billing' => null,
                'status' => PlanStatus::ACTIVE,
                'last_four_digits' => null,
                'user_id' => $user->uuid,
                'plans_id' => $plans->uuid
            ]);
            $subscription->save();

            return response()->json(['message' => 'Plan downgraded with success']);
        }

        Subscription::create([
            'daily_plans_used' => 0,
            'weekly_plans_used' => 0,
            'date_verified' => null,
            'next_billing' => null,
            'status' => PlanStatus::ACTIVE,
            'last_four_digits' => null,
            'user_id' => $user->uuid,
            'plans_id' => $plans->uuid
        ]);

        return response()->json(['Plan assigned with success']);
    }

    public function assignPlanToUser(string $userUUID, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plans_id' => ['string', 'required', 'uuid', 'exists:plans,uuid'],
            'last_four_digits' => ['required', 'numeric'],
            'next_billing' => ['required', 'date', 'after_or_equal:today'],
            'date_verified' => ['required', 'date', 'after_or_equal:today'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in validation',
                'errors' => $validator->errors()
            ],422);
        }

        $plansId = $request->input('plans_id');
        $lastFourDigits = $request->input('last_four_digits');
        $nextBilling = $request->input('next_billing');
        $dateVerified = $request->input('date_verified');

        $subscription = Subscription::query()->where('user_id', '=', $userUUID)->first();

        if ($subscription) {
            $subscription->update([
                'next_billing' => $nextBilling,
                'date_verified' => $dateVerified,
                'last_four_digits' => $lastFourDigits,
                'plans_id' => $plansId,
                'user_id' => $userUUID,
                'daily_plans_used' => 0,
                'weekly_plans_used' => 0,
                'status' => PlanStatus::PROCESSING
            ]);

            $subscription->save();
            return response()->json([
                'message' => 'Plan updated with success'
            ]);
        }

        Subscription::create([
            'next_billing' => $nextBilling,
            'date_verified' => $dateVerified,
            'last_four_digits' => $lastFourDigits,
            'plans_id' => $plansId,
            'user_id' => $userUUID,
            'daily_plans_used' => 0,
            'weekly_plans_used' => 0,
            'status' => PlanStatus::PROCESSING
        ]);

        return response()->json([
            'message' => 'Plan assigned with success'
        ]);
    }

    public function updatePlan(string $subscriptionId, string $userId, Request $request)
    {
        $subscription = Subscription::query()->where('uuid', '=', $subscriptionId)->first();
        $request->validate([
            'plans_id' => ['string', 'required', 'uuid', 'exists:plans,uuid'],
            'last_four_digits' => ['required', 'numeric'],
            'next_billing' => ['required', 'date', 'after_or_equal:today'],
            'date_verified' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $plansId = $request->input('plans_id');
        $lastFourDigits = $request->input('last_four_digits');
        $nextBilling = $request->input('next_billing');
        $dateVerified = $request->input('date_verified');

        $subscription->update([
            'next_billing' => $nextBilling,
            'date_verified' => $dateVerified,
            'last_four_digits' => $lastFourDigits,
            'plans_id' => $plansId,
            'user_id' => $userId,
            'daily_plans_used' => 0,
            'weekly_plans_used' => 0,
            'status' => PlanStatus::PROCESSING
        ]);
        $subscription->save();

        return response()->json(['message' => 'subscription updated with success']);
    }

    public function patchPlanStatus(string $subscriptionId, Request $request)
    {
        $request->validate([
            'status' => ['string', 'required', Rule::enum(PlanStatus::class)]
        ]);

        $subscription = Subscription::query()->where('uuid', '=', $subscriptionId)->first();

        if (!$subscription) return response()->json([
            'error' => 'Subscription not found'
        ], 422);

        $subscription->update(['status' => $request->input('status')]);
        $subscription->save();

        return response()->json(['message' => 'subscription status updated with success']);
    }

    public function show(string $userUUID)
    {
        $subscription = Subscription::query()->where('user_id', '=', $userUUID)->first();

        return response()->json(['subscription' => $subscription]);
    }
}
