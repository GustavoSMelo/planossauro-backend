<?php

namespace App\Dto\Stripe\CheckoutSessionCompleted;

class CheckoutSessionCompletedDataDTO
{
    public CheckoutSessionCompletedObjectDTO $object;

    public function __construct(CheckoutSessionCompletedObjectDTO $object)
    {
        $this->object = $object;
    }
}
