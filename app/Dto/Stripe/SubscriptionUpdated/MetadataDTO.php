<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

class MetadataDTO
{
    public string $plan_uuid;

    public function __construct(string $plan_uuid)
    {
        $this->plan_uuid = $plan_uuid;
    }
}
