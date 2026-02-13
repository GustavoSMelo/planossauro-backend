<?php

namespace App\Dto\Stripe\SubscriptionDeleted;

class SubscriptionDeletedDTO
{
    public string $id;
    public string $type;
    public SubscriptionDeletedDataDTO $data;

    public function __construct(string $id, string $type, SubscriptionDeletedDataDTO $data)
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
    }
}
