<?php

use App\Models\Subscription;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $subscriptions = Subscription::where('next_billing', '>=', date('Y-m-d'))->get();

    foreach ($subscriptions as $subscription) {
        $subscription->daily_plans_used = 0;
        $subscription->weekly_plans_used = 0;
        $subscription->next_billing = date('Y-m-d', strtotime('+1 month'));

        $subscription->save();
    }
})
    ->everyMinute()
    ->timezone('America/Sao_Paulo')
    ->name('reset_subscription_tokens')
    ->withoutOverlapping();
