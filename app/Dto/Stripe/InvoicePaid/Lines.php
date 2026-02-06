<?php

namespace App\Dto\Stripe\InvoicePaid;

class Lines
{
    /**
     * @var LinesData[]
     */
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
