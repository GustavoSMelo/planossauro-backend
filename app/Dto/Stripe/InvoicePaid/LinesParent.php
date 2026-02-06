<?php

namespace App\Dto\Stripe\InvoicePaid;

class LinesParent
{
    public LineParentSubscriptionItemDetails $subscription_item_details;

    public function __construct(LineParentSubscriptionItemDetails $subscription_item_details)
    {
        $this->subscription_item_details = $subscription_item_details;
    }
}
