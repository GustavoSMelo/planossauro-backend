<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class DataDTO
{
    public DataObjectDTO $object;

    public function __construct(DataObjectDTO $object)
    {
        $this->object = $object;
    }
}
