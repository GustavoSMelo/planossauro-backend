<?php

namespace App\Dto\Stripe\InvoicePaid;

class StripeInvoicePaidDataDTO
{
    public string $id;
    public int $amount_paid;
    public string $customer;
    public ?string $customer_email;
    public int $effective_at;
    public ?string $invoice_pdf;
    public Lines $lines;
    public ?string $number;
    public string $status;
    public BillingDetails $billing_details;
    public StatusTransitions $status_transitions;

    public function __construct(
        string $id,
        int $amount_paid,
        string $customer,
        string $customer_email,
        int $effective_at,
        string $invoice_pdf,
        Lines $lines,
        string $number,
        string $status,
        BillingDetails $billing_details,
        StatusTransitions $status_transitions
    ) {
        $this->id = $id;
        $this->amount_paid = $amount_paid;
        $this->customer = $customer;
        $this->customer_email = $customer_email;
        $this->effective_at = $effective_at;
        $this->invoice_pdf = $invoice_pdf;
        $this->lines = $lines;
        $this->number = $number;
        $this->status = $status;
        $this->billing_details = $billing_details;
        $this->status_transitions = $status_transitions;
    }
}
