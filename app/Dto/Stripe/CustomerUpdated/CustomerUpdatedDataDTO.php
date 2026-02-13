<?php

namespace App\Dto\Stripe\CustomerUpdated;

class CustomerUpdatedDataDTO
{
    public CustomerUpdatedObjectDTO $object;
    public ?CustomerUpdatedPreviousAttributesDTO $previousAttributes = null;

    public function __construct(CustomerUpdatedObjectDTO $object, ?CustomerUpdatedPreviousAttributesDTO $previousAttributes)
    {
        $this->object = $object;
        $this->previousAttributes = $previousAttributes;
    }
}
