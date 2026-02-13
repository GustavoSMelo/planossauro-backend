<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

class SubscriptionItemsData
{
    public string $id; // Subscription item ID
    public int $current_period_end;
    public PlanDTO $plan;
    public string $subscription;

    public function __construct(string $id, int $current_period_end, PlanDTO $plan, string $subscription)
    {
        $this->id = $id;
        $this->current_period_end = $current_period_end;
        $this->plan = $plan;
        $this->subscription = $subscription;
    }
}
