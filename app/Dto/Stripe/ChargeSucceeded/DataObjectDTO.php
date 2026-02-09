<?php

namespace App\Dto\Stripe\ChargeSucceeded;

class DataObjectDTO
{
    public string $id;
    public PaymentMethodDetailsDTO $payment_method_details;
    public string $customer;
    public BillingDetailsDTO $billing_details;

    public function __construct(
        string $id,
        PaymentMethodDetailsDTO $payment_method_details,
        string $customer,
        BillingDetailsDTO $billing_details
    )
    {
        $this->id = $id;
        $this->payment_method_details = $payment_method_details;
        $this->customer = $customer;
        $this->billing_details = $billing_details;
    }
}
