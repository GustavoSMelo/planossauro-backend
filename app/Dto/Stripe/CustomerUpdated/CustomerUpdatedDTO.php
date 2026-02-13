<?php

namespace App\Dto\Stripe\CustomerUpdated;

class CustomerUpdatedDTO
{
    public string $id;
    public string $type;
    public CustomerUpdatedDataDTO $data;

    public function __construct(string $id, string $type, CustomerUpdatedDataDTO $data)
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
    }
}
