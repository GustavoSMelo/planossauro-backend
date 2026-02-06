<?php

namespace App\Dto\Stripe\InvoicePaid;

class StripeInvoicePaidObjectDTO
{
    public StripeInvoicePaidDataDTO $object;

    public function __construct(StripeInvoicePaidDataDTO $object)
    {
        $this->object = $object;
    }
}
