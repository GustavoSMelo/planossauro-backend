<?php

namespace App\Dto\Stripe\InvoicePaid;

class LineParentSubscriptionItemDetails
{
    public string $subscription;
    public string $subscription_item;

    public function __construct(string $subscription, string $subscription_item)
    {
        $this->subscription = $subscription;
        $this->subscription_item = $subscription_item;
    }
}
