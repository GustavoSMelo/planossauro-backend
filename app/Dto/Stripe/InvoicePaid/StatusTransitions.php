<?php

namespace App\Dto\Stripe\InvoicePaid;

class StatusTransitions
{
    public int $paid_at;

    public function __construct(int $paid_at)
    {
        $this->paid_at = $paid_at;
    }
}
