<?php

namespace App\Dto\Stripe\SubscriptionDeleted;

class SubscriptionDeletedPlanDTO
{
    public string $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }
}
