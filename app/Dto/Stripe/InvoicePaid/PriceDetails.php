<?php

namespace App\Dto\Stripe\InvoicePaid;

class PriceDetails
{
    public string $price;
    public string $product;

    public function __construct(string $price, string $product)
    {
        $this->price = $price;
        $this->product = $product;
    }
}
