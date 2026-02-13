<?php

namespace App\Dto\Stripe\PaymentAttached;

class PaymentAttachedDataDTO
{
    public PaymentAttachedObjectDTO $object;

    public function __construct(PaymentAttachedObjectDTO $object)
    {
        $this->object = $object;
    }
}
