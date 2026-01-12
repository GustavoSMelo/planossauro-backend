<?php

namespace App\Http\Controllers;

use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use App\PlanStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function assignFreePlanToUser(string $userId)
    {
        $user = User::query()->where('uuid', '=', $userId)->get();
        if (!$user) return response()->json(['message' => 'User with this is does not exists'], 404);

        $plans = Plans::query()->where('price', '=', 0)->get();

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

    public function assignPlanToUser(string $userId, Request $request)
    {
        $request->validate([
            'plans_id' => ['string', 'required', 'uuid', Rule::unique('plans', 'uuid')],
            'last_four_digits' => ['required', 'numeric', 'min:4', 'max:4'],
            'next_billing' => ['required', 'date', 'after_or_equal:today'],
            'date_verified' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $plansId = $request->input('plans_id');
        $lastFourDigits = $request->input('last_four_digits');
        $nextBilling = $request->input('next_billing');
        $dateVerified = $request->input('date_verified');

        Subscription::create([
            'next_billing' => $nextBilling,
            'date_verified' => $dateVerified,
            'last_four_digits' => $lastFourDigits,
            'plans_id' => $plansId,
            'user_id' => $userId,
            'daily_plans_used' => 0,
            'weekly_plans_used' => 0,
            'status' => PlanStatus::PROCESSING
        ]);

        return response()->json([
            'message' => 'Plan assigned with success'
        ]);
    }

    public function updatePlan(string $subscription, string $userId, Request $request)
    {
        $subscription = Subscription::query()->where('uuid', '=', $subscription);
        $request->validate([
            'plans_id' => ['string', 'required', 'uuid', Rule::unique('plans', 'uuid')],
            'last_four_digits' => ['required', 'numeric', 'min:4', 'max:4'],
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

    public function patchPlanStatus(string $userPlanId, Request $request)
    {
        $request->validate([
            'status' => ['string', 'required', Rule::enum(PlanStatus::class)]
        ]);

        $subscription = Subscription::query()->find('uuid', '=', $userPlanId);
        $subscription->update(['status' => $request->input('status')]);
        $subscription->save();

        return response()->json(['message' => 'subscription status updated with success']);
    }
}
