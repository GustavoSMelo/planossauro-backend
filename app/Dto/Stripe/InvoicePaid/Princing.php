<?php

namespace App\Dto\Stripe\InvoicePaid;

class Princing
{
    public PriceDetails $priceDetails;

    public function __construct(PriceDetails $priceDetails)
    {
        $this->priceDetails = $priceDetails;
    }
}
