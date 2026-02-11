<?php

namespace App\Http\Controllers;

use App\Enums\PlanStatus;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Stripe\StripeClient;

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
                'status' => PlanStatus::PAID->value,
                'last_four_digits' => null,
                'card_brand' => null,
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
            'next_billing' => date('Y-m-d', strtotime('+1 month')),
            'status' => PlanStatus::PAID->value,
            'last_four_digits' => null,
            'card_brand' => null,
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
            'card_brand' => ['required', 'string'],
            'next_billing' => ['required', 'date', 'after_or_equal:today'],
            'date_verified' => ['required', 'date', 'after_or_equal:today'],
            'card_brand' => ['nullable', 'string'],
            'stripe_user' => ['nullable', 'string'],
            'stripe_price' => ['nullable', 'string'],
            'stripe_product' => ['nullable', 'string'],
            'stripe_subscription' => ['nullable', 'string']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error in validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $plansId = $request->input('plans_id');
        $lastFourDigits = $request->input('last_four_digits');
        $nextBilling = $request->input('next_billing');
        $dateVerified = $request->input('date_verified');
        $stripeUser = $request->input('stripe_user');
        $stripeSubscription = $request->input('stripe_subscription');
        $stripePrice = $request->input('stripe_price');
        $stripeProduct = $request->input('stripe_product');
        $cardBrand = $request->input('card_brand');

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
                'status' => PlanStatus::PAID->value,
                'stripe_user' => $stripeUser,
                'stripe_subscription' => $stripeSubscription,
                'stripe_price' => $stripePrice,
                'stripe_product' => $stripeProduct,
                'card_brand' => $cardBrand
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
            'status' => PlanStatus::PAID->value,
            'stripe_user' => $stripeUser,
            'stripe_subscription' => $stripeSubscription,
            'stripe_price' => $stripePrice,
            'stripe_product' => $stripeProduct,
            'card_brand' => $cardBrand
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
            'card_brand' => ['card_brand', 'string'],
            'date_verified' => ['required', 'date', 'after_or_equal:today'],
            'card_brand' => ['nullable', 'string'],
            'stripe_user' => ['nullable', 'string'],
            'stripe_price' => ['nullable', 'string'],
            'stripe_product' => ['nullable', 'string'],
            'stripe_subscription' => ['nullable', 'string']
        ]);

        $plansId = $request->input('plans_id');
        $lastFourDigits = $request->input('last_four_digits');
        $nextBilling = $request->input('next_billing');
        $dateVerified = $request->input('date_verified');
        $stripeUser = $request->input('stripe_user');
        $stripeSubscription = $request->input('stripe_subscription');
        $stripePrice = $request->input('stripe_price');
        $stripeProduct = $request->input('stripe_product');
        $cardBrand = $request->input('card_brand');

        $subscription->update([
            'next_billing' => $nextBilling,
            'date_verified' => $dateVerified,
            'last_four_digits' => $lastFourDigits,
            'plans_id' => $plansId,
            'user_id' => $userId,
            'daily_plans_used' => 0,
            'weekly_plans_used' => 0,
            'status' => PlanStatus::PAID->value,
            'stripe_user' => $stripeUser,
            'stripe_subscription' => $stripeSubscription,
            'stripe_price' => $stripePrice,
            'stripe_product' => $stripeProduct,
            'card_brand' => $cardBrand
        ]);
        $subscription->save();

        return response()->json(['message' => 'subscription updated with success']);
    }

    public function patchPlanStatus(string $subscriptionId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['string', 'required', Rule::enum(PlanStatus::class)]
        ]);

        if ($validator->failed()) {
            return response()->json([
                'message' => 'Status validation failed',
                'error' => $validator->errors()
            ]);
        }

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
        $plan = Plans::query('uuid', '=', $subscription->plans_id)->first();

        return response()->json(['subscription' => $subscription, 'plan' => $plan]);
    }

    public function dashboard(string $userUUID)
    {
        $subscription = Subscription::query()->where('user_id', '=', $userUUID)->first();
        $plan = Plans::query()->where('uuid', '=', $subscription->plans_id)->first();

        return response()->json([
            'max_amount_planning_week' => $plan->amount_planning_week,
            'max_amount_planning_daily' => $plan->amount_planning_day,
            'used_weekly_planning' => $subscription->weekly_plans_used,
            'used_daily_planning' => $subscription->daily_plans_used,
            'current_plan' => $plan->plan_name,
            'subscription_id' => $subscription->uuid,
            'plan_id' => $plan->uuid
        ]);
    }

    /**
     * @param "week" | "daily" $planningType
     * @param string $subscriptionId
     */
    public function addPlanningUsedOnSubscription(string $planningType, string $subscriptionId)
    {
        if ($planningType !== 'week' && $planningType !== 'daily')
            return response()->json([
                'message' => 'Planning type invalid'
            ], 400);

        $subscription = Subscription::query()->where('uuid', '=', $subscriptionId)->first();
        $plan = Plans::query()->where('uuid', '=', $subscription->plans_id)->first();

        if ($planningType === 'week') {
            if ($subscription->weekly_plans_used >= $plan->amount_planning_week)
                return response()->json([
                    'message' => 'Used all weekly token'
                ], 403);

            $plansUsed = $subscription->weekly_plans_used + 1;
            $subscription->weekly_plans_used = $plansUsed;
            $subscription->save();

            return response()->json([
                'message' => "Subscription week tokens used updated with success"
            ]);
        }

        if ($subscription->daily_plans_used >= $plan->amount_planning_day)
            return response()->json([
                'message' => 'Used all weekly token'
            ], 403);

        $plansUsed = $subscription->daily_plans_used + 1;
        $subscription->weekly_plans_used = $plansUsed;
        $subscription->save();

        return response()->json([
            'message' => "Subscription week tokens used updated with success"
        ]);
    }

    public function changePaymentMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'return_url' => ['required', 'string', 'url'],
            'customer' => ['required', 'string', 'exists:subscription,stripe_user']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'Error in validation',
                'errors' => $validator->errors()
            ]);
        }

        $customer = $request->input('customer');
        $return_url = $request->input('return_url');

        $stripe = new StripeClient(config('services.stripe.secret'));
        $session = $stripe->billingPortal->sessions->create([
            'customer' => $customer,
            'return_url' => $return_url,
            'flow_data' => [
                'type' => 'payment_method_update'
            ]
        ]);

        return response()->json([$session]);
    }
}
