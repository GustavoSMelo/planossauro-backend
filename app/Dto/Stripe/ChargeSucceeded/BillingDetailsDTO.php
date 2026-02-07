<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class BillingDetails
{
    public string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }
}
