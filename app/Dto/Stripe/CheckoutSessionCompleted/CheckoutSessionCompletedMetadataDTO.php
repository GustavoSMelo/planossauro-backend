<?php

namespace App\Dto\Stripe\CheckoutSessionCompleted;

class CheckoutSessionCompletedMetadataDTO
{
    public string $uuid_plan;

    public function __construct(string $uuid_plan)
    {
        $this->uuid_plan = $uuid_plan;
    }
}
