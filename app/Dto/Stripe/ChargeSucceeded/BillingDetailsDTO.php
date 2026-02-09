<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class BillingDetailsDTO
{
    public string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }
}
