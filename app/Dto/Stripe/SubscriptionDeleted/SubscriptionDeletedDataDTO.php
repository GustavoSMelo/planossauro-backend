<?php

namespace App\Dto\Stripe\SubscriptionDeleted;

class SubscriptionDeletedDataDTO
{
    public SubscriptionDeletedObjectDTO $object;

    public function __construct(SubscriptionDeletedObjectDTO $object)
    {
        $this->object = $object;
    }
}
