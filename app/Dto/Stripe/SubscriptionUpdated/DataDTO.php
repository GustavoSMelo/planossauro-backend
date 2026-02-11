<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

class DataDTO
{
    public ObjectDTO $object;
    public PreviousAttributes $previous_attributes;

    public function __construct(ObjectDTO $object, PreviousAttributes $previous_attributes)
    {
        $this->object = $object;
        $this->previous_attributes = $previous_attributes;
    }
}
