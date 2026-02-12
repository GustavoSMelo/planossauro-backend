<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

class PlanDTO
{
    public string $id; // price id
    public int $amount;
    public string $product;
    public MetadataDTO $metadata;

    public function __construct(string $id, int $amount, string $product, MetadataDTO $metadata)
    {
        $this->id = $id;
        $this->amount = $amount;
        $this->product = $product;
        $this->metadata = $metadata;
    }
}
