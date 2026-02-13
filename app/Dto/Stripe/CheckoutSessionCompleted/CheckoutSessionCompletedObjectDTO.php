<?php

namespace App\Dto\Stripe\CheckoutSessionCompleted;

class CheckoutSessionCompletedObjectDTO
{
    public CheckoutSessionCompletedMetadataDTO $metadata;
    public string $customer;

    public function __construct(CheckoutSessionCompletedMetadataDTO $metadata, string $customer)
    {
        $this->metadata = $metadata;
        $this->customer = $customer;
    }
}
