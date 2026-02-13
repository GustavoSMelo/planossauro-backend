<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

class ObjectDTO
{
    public string $id; // subscriptionId
    public string $customer;
    public SubscriptionItems $items;
    public string $status;

    public function __construct(string $id, string $customer, SubscriptionItems $items, string $status)
    {
        $this->id = $id;
        $this->customer = $customer;
        $this->items = $items;
        $this->status = $status;
    }
}
