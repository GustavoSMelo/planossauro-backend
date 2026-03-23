<?php

namespace App\Dto\Stripe;

class StripeCache
{
    public ?string $subscription_id;
    public ?string $customer_id;
    public ?string $invoice_id;
    public ?string $product_id;
    public ?string $invoice_pdf;
    public ?int $period_end;
    public ?int $amount_paid;
    public ?string $status;
    public ?string $card_brand;
    public ?string $last4;
    public ?string $price_id;
    public ?string $customer_email;
    public ?int $paid_at;
    public ?string $plan_uuid;
    public ?string $description;
    public ?string $subscription_item;

    public function __construct(
        ?string $subscription_id,
        ?string $customer_id,
        ?string $invoice_id,
        ?string $product_id,
        ?string $invoice_pdf,
        ?int $period_end,
        ?int $amount_paid,
        ?string $status,
        ?string $card_brand,
        ?string $last4,
        ?string $price_id,
        ?string $customer_email,
        ?int $paid_at,
        ?string $plan_uuid,
        ?string $description,
        ?string $subscription_item,
    ) {
        $this->subscription_id = $subscription_id;
        $this->customer_id = $customer_id;
        $this->invoice_id = $invoice_id;
        $this->product_id = $product_id;
        $this->invoice_pdf = $invoice_pdf;
        $this->invoice_id = $invoice_id;
        $this->period_end = $period_end;
        $this->amount_paid = $amount_paid;
        $this->status = $status;
        $this->card_brand = $card_brand;
        $this->last4 = $last4;
        $this->price_id = $price_id;
        $this->customer_email = $customer_email;
        $this->paid_at = $paid_at;
        $this->plan_uuid = $plan_uuid;
        $this->description = $description;
        $this->subscription_item = $subscription_item;
    }
}
