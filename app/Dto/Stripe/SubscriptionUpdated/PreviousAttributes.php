<?php

namespace App\Dto\Stripe\SubscriptionUpdated;

class PreviousAttributes
{
    public ?string $default_payment_method;
    public ?SubscriptionItems $items;

    public function __construct(?string $default_payment_method, ?SubscriptionItems $items)
    {
        $this->default_payment_method = $default_payment_method;
        $this->items = $items;
    }
}
