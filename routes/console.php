<?php

use App\Enums\PlanStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $subscriptions = Subscription::where('next_billing', '>=', date('Y-m-d'))->get();

    foreach ($subscriptions as $subscription) {
        if ($subscription->status === PlanStatus::PAID->value || $subscription->status === PlanStatus::ACTIVE->value) {
            $subscription->daily_plans_used = 0;
            $subscription->weekly_plans_used = 0;

            if ($subscription->status === PlanStatus::ACTIVE->value)
                $subscription->next_billing = date('Y-m-d', strtotime('+1 month'));
            $subscription->save();
        }
    }
})
    ->daily()
    ->timezone('America/Sao_Paulo')
    ->name('reset_subscription_tokens')
    ->withoutOverlapping();

Schedule::call(function () {
    $users = User::query()->where('deleted_at', '>=', date('Y-m-d'))->get();

    foreach ($users as $user) {
        $user->delete();
    }
})
    ->daily()
    ->timezone('America/Sao_Paulo')
    ->name('delete_user_accounts')
    ->withoutOverlapping();
