<?php

namespace App\Dto\Stripe\InvoicePaid;

class StripeInvoicePaidDTO
{
    public string $id;
    public string $object;
    public string $type;
    public StripeInvoicePaidObjectDTO $data;

    public function __construct(string $id, string $object, string $type, StripeInvoicePaidObjectDTO $data)
    {
        $this->id = $id;
        $this->object = $object;
        $this->type = $type;
        $this->data = $data;
    }
}
