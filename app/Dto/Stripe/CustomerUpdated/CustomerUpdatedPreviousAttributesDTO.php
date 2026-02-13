<?php

namespace App\Dto\Stripe\CustomerUpdated;

class CustomerUpdatedPreviousAttributesDTO
{
    public string $default_payment_method;

    public function __construct(string $default_payment_method)
    {
        $this->default_payment_method = $default_payment_method;
    }
}
