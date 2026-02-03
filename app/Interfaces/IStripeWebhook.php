<?php

namespace App\Interfaces;

use App\Enums\StripeWebhookTypes;

interface ICard
{
    public string $brand;
    public string $last4;
}

interface IPaymentMethodDetails
{
    public string $type;
    public ICard $card;
}

interface IBillingDetails
{
    public string $email;
    public string $name;
}

interface IStripeData
{
    public string $id;
    public string $amount;
    public string $balance_transaction;
    public string $customer;
    public bool $paid;
    public string $receipt_url;
    public string $status;
    public IBillingDetails $billing_details;
    public IPaymentMethodDetails $payment_method_details;
}

interface IStripeWebhook
{
    public StripeWebhookTypes $type;
    public string $id;
    public IStripeData $data;
}
