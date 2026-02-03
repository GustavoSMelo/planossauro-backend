<?php

namespace App\Enums;

enum StripeWebhookTypes: string {
    case ChargeSucceeded = 'charge.succeeded';
    case CustomerUpdate = 'customer.update';
    case SubscriptionCreated = 'customer.subscription.created';
    case CheckoutCompleted = 'checkout.session.completed';
}
