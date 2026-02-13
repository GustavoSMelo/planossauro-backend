<?php

namespace App\Dto\Stripe\SubscriptionDeleted;

class SubscriptionDeletedItemsDataDTO
{
    public int $current_period_end;

    public function __construct(int $current_period_end)
    {
        $this->current_period_end = $current_period_end;
    }
}
