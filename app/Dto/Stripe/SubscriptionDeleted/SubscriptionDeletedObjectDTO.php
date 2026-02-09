<?php

namespace App\Dto\Stripe\SubscriptionDeleted;

class SubscriptionDeletedObjectDTO
{
    public SubscriptionDeletedPlanDTO $plan;
    public string $customer;
    public string $id;
    public int $ended_at;
    public SubscriptionDeletedItemsDTO $items;

    public function __construct(SubscriptionDeletedPlanDTO $plan, string $customer, string $id, int $ended_at, SubscriptionDeletedItemsDTO $items)
    {
        $this->customer = $customer;
        $this->plan = $plan;
        $this->id = $id;
        $this->ended_at = $ended_at;
        $this->items = $items;
    }
}
