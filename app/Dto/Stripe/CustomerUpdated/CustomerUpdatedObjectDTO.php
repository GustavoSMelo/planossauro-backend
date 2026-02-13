<?php

namespace App\Dto\Stripe\CustomerUpdated;

class CustomerUpdatedObjectDTO
{
    public string $id; // customer id
    public string $email;

    public function __construct(string $id, string $email)
    {
        $this->id = $id;
        $this->email = $email;
    }
}
