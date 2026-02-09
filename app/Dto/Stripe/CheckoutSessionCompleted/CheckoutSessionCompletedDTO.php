<?php

namespace App\Dto\Stripe\CheckoutSessionCompleted;

class CheckoutSessionCompletedDTO
{
    public string $id;
    public string $type;
    public CheckoutSessionCompletedDataDTO $data;

    public function __construct(string $id, string $type, CheckoutSessionCompletedDataDTO $data)
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
    }
}
