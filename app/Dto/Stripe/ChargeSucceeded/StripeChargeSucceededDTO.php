<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class StripeChargeSucceededDTO {
    public string $id;
    public string $type;
    public DataDTO $data;

    public function __construct(DataDTO $data, string $type, string $id)
    {
        $this->data = $data;
        $this->type = $type;
        $this->id = $id;
    }
}
