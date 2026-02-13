<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class WalletDTO
{
    public ?string $type;

    public function __construct(?string $type)
    {
        $this->type = $type;
    }
}
