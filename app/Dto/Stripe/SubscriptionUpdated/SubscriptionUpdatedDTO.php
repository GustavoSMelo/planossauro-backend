<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

class SubscriptionUpdatedDTO
{
    public string $id;
    public string $type;
    public DataDTO $data;

    public function __construct(string $id, string $type, DataDTO $data)
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
    }
}
