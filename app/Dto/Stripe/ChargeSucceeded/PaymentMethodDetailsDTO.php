<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class PaymentMethodDetailsDTO
{
    public CardDTO $card;

    public function __construct(CardDTO $card)
    {
        $this->card = $card;
    }
}
