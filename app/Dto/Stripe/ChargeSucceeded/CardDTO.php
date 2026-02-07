<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class CardDTO
{
    public string $brand;
    public string $last4;
    public CardChecksDTO $checks;

    public function __construct(string $brand, string $last4, CardChecksDTO $checks)
    {
        $this->brand = $brand;
        $this->last4 = $last4;
        $this->checks = $checks;
    }
}
