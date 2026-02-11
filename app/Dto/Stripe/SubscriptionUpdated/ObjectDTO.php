<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

class ObjectDTO
{
    public string $id; // subscriptionId
    public string $customer;
    public SubscriptionItems $items;

    public function __construct(string $id, string $customer, SubscriptionItems $items)
    {
        $this->id = $id;
        $this->customer = $customer;
        $this->items = $items;
    }
}
