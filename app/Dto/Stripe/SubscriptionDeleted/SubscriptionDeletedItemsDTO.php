<?php

namespace App\Dto\Stripe\SubscriptionDeleted;

class SubscriptionDeletedItemsDTO
{
    /**
     * @var SubscriptionDeletedItemsDataDTO[]
     */
    public array $data;


    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
