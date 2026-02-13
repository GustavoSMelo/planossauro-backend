<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

use PlanDTO\PlanDTO;

class SubscriptionItems
{
    /**
     * @var SubscriptionItemsData[]
     */
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
