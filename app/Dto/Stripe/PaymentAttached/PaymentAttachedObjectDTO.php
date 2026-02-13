<?php

namespace App\Dto\Stripe\PaymentAttached;

class PaymentAttachedObjectDTO
{
    public string $customer;

    public function __construct(string $customer)
    {
        $this->customer = $customer;
    }
}
