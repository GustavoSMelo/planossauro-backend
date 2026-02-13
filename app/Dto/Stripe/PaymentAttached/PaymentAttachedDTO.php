<?php

namespace App\Dto\Stripe\PaymentAttached;

class PaymentAttachedDTO
{
    public PaymentAttachedDataDTO $data;
    public string $id;
    public string $type;

    public function __construct(PaymentAttachedDataDTO $data, string $id, string $type)
    {
        $this->data = $data;
        $this->id = $id;
        $this->type = $type;
    }
}
