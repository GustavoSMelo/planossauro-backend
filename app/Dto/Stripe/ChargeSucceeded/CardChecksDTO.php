<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class CardChecksDTO
{
    public string $cvc_check;

    public function __construct(string $cvc_check)
    {
        $this->cvc_check = $cvc_check;
    }
}
