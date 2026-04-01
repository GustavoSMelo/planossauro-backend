<?php

namespace App\Http\Controllers;

use App\Dto\Stripe\ChargeSucceeded\BillingDetailsDTO;
use App\Dto\Stripe\ChargeSucceeded\CardChecksDTO;
use App\Dto\Stripe\ChargeSucceeded\CardDTO;
use App\Dto\Stripe\ChargeSucceeded\DataDTO;
use App\Dto\Stripe\ChargeSucceeded\DataObjectDTO;
use App\Dto\Stripe\ChargeSucceeded\PaymentMethodDetailsDTO;
use App\Dto\Stripe\ChargeSucceeded\StripeChargeSucceededDTO;
use App\Dto\Stripe\ChargeSucceeded\WalletDTO;
use App\Dto\Stripe\CheckoutSessionCompleted\CheckoutSessionCompletedDataDTO;
use App\Dto\Stripe\CheckoutSessionCompleted\CheckoutSessionCompletedDTO;
use App\Dto\Stripe\CheckoutSessionCompleted\CheckoutSessionCompletedMetadataDTO;
use App\Dto\Stripe\CheckoutSessionCompleted\CheckoutSessionCompletedObjectDTO;
use App\Dto\Stripe\CustomerUpdated\CustomerUpdatedDataDTO;
use App\Dto\Stripe\CustomerUpdated\CustomerUpdatedDTO;
use App\Dto\Stripe\CustomerUpdated\CustomerUpdatedObjectDTO;
use App\Dto\Stripe\CustomerUpdated\CustomerUpdatedPreviousAttributesDTO;
use App\Dto\Stripe\InvoicePaid\LineParentSubscriptionItemDetails;
use App\Dto\Stripe\InvoicePaid\Lines;
use App\Dto\Stripe\InvoicePaid\LinesData;
use App\Dto\Stripe\InvoicePaid\LinesParent;
use App\Dto\Stripe\InvoicePaid\PriceDetails;
use App\Dto\Stripe\InvoicePaid\Princing;
use App\Dto\Stripe\InvoicePaid\StatusTransitions;
use App\Dto\Stripe\InvoicePaid\StripeInvoicePaidDataDTO;
use App\Dto\Stripe\InvoicePaid\StripeInvoicePaidDTO;
use App\Dto\Stripe\InvoicePaid\StripeInvoicePaidObjectDTO;
use App\Dto\Stripe\PaymentAttached\PaymentAttachedDataDTO;
use App\Dto\Stripe\PaymentAttached\PaymentAttachedDTO;
use App\Dto\Stripe\PaymentAttached\PaymentAttachedObjectDTO;
use App\Dto\Stripe\StripeCache;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedDataDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedItemsDataDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedItemsDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedObjectDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedPlanDTO;
use App\Dto\Stripe\SubscriptionUpdated\DataDTO as SubscriptionUpdatedDataDTO;
use App\Dto\Stripe\SubscriptionUpdated\MetadataDTO as SubscriptionUpdatedMetadataDTO;
use App\Dto\Stripe\SubscriptionUpdated\ObjectDTO;
use App\Dto\Stripe\SubscriptionUpdated\PlanDTO;
use App\Dto\Stripe\SubscriptionUpdated\SubscriptionItems;
use App\Dto\Stripe\SubscriptionUpdated\SubscriptionItemsData;
use App\Dto\Stripe\SubscriptionUpdated\SubscriptionUpdatedDTO;
use App\Enums\PlanStatus;
use App\Models\PaymentHistory;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripeController extends Controller
{
    private function isStripeObjectFull(StripeCache $stripeCache): bool
    {
        if (
            $stripeCache->amount_paid &&
            $stripeCache->card_brand &&
            $stripeCache->customer_id &&
            $stripeCache->period_end &&
            $stripeCache->invoice_id &&
            $stripeCache->invoice_pdf &&
            $stripeCache->last4 &&
            $stripeCache->price_id &&
            $stripeCache->product_id &&
            $stripeCache->status &&
            $stripeCache->subscription_id &&
            $stripeCache->paid_at &&
            $stripeCache->plan_uuid &&
            $stripeCache->description &&
            $stripeCache->customer_email &&
            $stripeCache->subscription_item
        ) {
            return true;
        }

        return false;
    }

    private function saveStripeInformationsAndUpdatePlan(
        StripeCache $stripeCache,
    ) {
        $user = User::query()
            ->where('google_email', '=', $stripeCache->customer_email)
            ->orWhere('github_email', '=', $stripeCache->customer_email)
            ->first();

        if (! $user) {
            Log::error(
                'User not found for email: '.$stripeCache->customer_email,
            );

            return;
        }

        $subscription = Subscription::where(
            'user_id',
            '=',
            $user->uuid,
        )->first();

        if (! $subscription) {
            Log::error('Subscription not found for user: '.$user->uuid);

            return;
        }

        $plan = Plans::query()
            ->where('uuid', '=', $stripeCache->plan_uuid)
            ->first();

        $subscription->stripe_subscription = $stripeCache->subscription_id;
        $subscription->stripe_user = $stripeCache->customer_id;
        $subscription->stripe_price = $stripeCache->price_id;
        $subscription->stripe_product = $stripeCache->product_id;
        $subscription->stripe_subscription_item =
            $stripeCache->subscription_item;
        $subscription->next_billing = gmdate('Y-m-d', $stripeCache->period_end);
        $subscription->status = $stripeCache->status;
        $subscription->card_brand = $stripeCache->card_brand;
        $subscription->last_four_digits = $stripeCache->last4;
        $subscription->date_verified = date('Y-m-d');
        $subscription->price = floor($stripeCache->amount_paid / 100);

        if ($stripeCache->plan_uuid === '') {
            $subscription->plans_id = '91842dba-9965-42c9-af2a-07fef464b315';
        } else {
            $subscription->plans_id = $plan->uuid;
        }

        $subscription->save();

        $paymentHistory = new PaymentHistory;
        $paymentHistory->payment_date = gmdate('Y-m-d', $stripeCache->paid_at);
        $paymentHistory->description = $stripeCache->description;
        $paymentHistory->card_brand = $stripeCache->card_brand;
        $paymentHistory->price = floor($stripeCache->amount_paid / 100);
        $paymentHistory->status = $stripeCache->status;
        $paymentHistory->user_id = $user->uuid;
        $paymentHistory->last_four_digits = $stripeCache->last4;
        $paymentHistory->NFe = $stripeCache->invoice_pdf;
        $paymentHistory->stripe_invoice = $stripeCache->invoice_id;
        $paymentHistory->stripe_product = $stripeCache->product_id;
        $paymentHistory->stripe_subscription = $stripeCache->subscription_id;
        $paymentHistory->subscription_id = $subscription->uuid;

        if ($stripeCache->plan_uuid === '') {
            $subscription->plans_id = '91842dba-9965-42c9-af2a-07fef464b315';
            $paymentHistory->plan_id = '91842dba-9965-42c9-af2a-07fef464b315';
        } else {
            $subscription->plans_id = $plan->uuid;
            $paymentHistory->plan_id =
                $plan?->uuid ?? '91842dba-9965-42c9-af2a-07fef464b315';
        }

        $paymentHistory->save();

        Cache::forget('stripeCache-'.$stripeCache->customer_id);

        Log::info('Stripe success: Subscription and payment history saved for customer '.$stripeCache->customer_id);
    }

    private function changePaymentMethodSubscription(
        string $customerId,
        ?string $customerEmail = null,
    ): bool {
        $stripe = new StripeClient(config('services.stripe.secret'));
        $customer = $stripe->customers->retrieve($customerId);
        $defaultPaymentId = $customer->invoice_settings->default_payment_method;
        if (! $defaultPaymentId || $defaultPaymentId === null) {
            return false;
        }

        $defaultPayment = $stripe->paymentMethods->retrieve($defaultPaymentId);

        $brand = $defaultPayment->card->brand;
        $last4 = $defaultPayment->card->last4;

        $useruuid = '';
        $customerEmailHelper = '';

        if (strlen($customerEmail)) {
            $customerEmailHelper = $customerEmail;
        }

        $user = User::query()
            ->where('google_email', '=', $customerEmailHelper)
            ->orWhere('github_email', '=', $customerEmailHelper)
            ->first();

        if ($user && $user->uuid) {
            $useruuid = $user->uuid;
        }

        $subscriptionQuery = Subscription::query()->where(
            'stripe_user',
            '=',
            $customerId,
        );

        if ($useruuid !== '') {
            $subscriptionQuery->orWhere('user_id', '=', $useruuid);
        }

        $subscription = $subscriptionQuery->first();

        if (! $subscription) {
            return false;
        }

        $subscription->card_brand = $brand;
        $subscription->last_four_digits = $last4;

        $subscription->save();

        Log::info('Stripe success: Payment method changed for customer '.$customerId);

        return true;
    }

    public function handler(Request $request)
    {
        $body = $request->all();

        switch ($body['type']) {
            case 'charge.succeeded':
                $walletHelper =
                    $body['data']['object']['payment_method_details']['card'][
                        'wallet'
                    ];
                $wallet = null;

                if ($walletHelper !== null) {
                    $wallet = new WalletDTO(
                        $body['data']['object']['card']['wallet']['type'] ??
                            null,
                    );
                }

                $cardCheckHelper = '';

                if (
                    $wallet &&
                    strlen($wallet->type) > 0 &&
                    $wallet->type === 'google_pay'
                ) {
                    $cardCheckHelper = 'pass';
                } else {
                    $cardCheckHelper =
                        $body['data']['object']['payment_method_details'][
                            'card'
                        ]['checks']['cvc_check'];
                }

                $cardCheck = new CardChecksDTO($cardCheckHelper);
                $card = new CardDTO(
                    $body['data']['object']['payment_method_details']['card'][
                        'brand'
                    ],
                    $body['data']['object']['payment_method_details']['card'][
                        'last4'
                    ],
                    $cardCheck,
                    $wallet,
                );
                $paymentMethodDetails = new PaymentMethodDetailsDTO($card);
                $billingDetails = new BillingDetailsDTO(
                    $body['data']['object']['billing_details']['email'],
                );
                $dataObject = new DataObjectDTO(
                    $body['data']['object']['id'],
                    $paymentMethodDetails,
                    $body['data']['object']['customer'],
                    $billingDetails,
                );
                $dataDTO = new DataDTO($dataObject);
                $stripeChargeSucceeded = new StripeChargeSucceededDTO(
                    $dataDTO,
                    $body['type'],
                    $body['id'],
                );

                /**
                 * @var StripeCache | null
                 */
                $stripeCache = Cache::get(
                    'stripeCache-'.
                        $stripeChargeSucceeded->data->object->customer,
                );

                if (
                    $stripeChargeSucceeded->data->object->payment_method_details
                        ->card->checks->cvc_check !== 'check' &&
                    $stripeChargeSucceeded->data->object->payment_method_details
                        ->card->checks->cvc_check !== 'pass'
                ) {
                    Log::error(
                        'CVC invalid for customer'.
                            $stripeChargeSucceeded->data->object->customer,
                    );
                    break;
                }

                if (! $stripeCache) {
                    $stripeCacheHelper = new StripeCache(
                        null,
                        $stripeChargeSucceeded->data->object->customer,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $stripeChargeSucceeded->data->object->payment_method_details->card->brand,
                        $stripeChargeSucceeded->data->object->payment_method_details->card->last4,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                    );

                    Cache::put(
                        'stripeCache-'.$stripeCacheHelper->customer_id,
                        $stripeCacheHelper,
                    );

                    Log::info('Stripe success: Charge succeeded, cache created for customer '.$stripeCacheHelper->customer_id);

                    return response()->json(['received' => true]);
                }

                $stripeCache->customer_id =
                    $stripeChargeSucceeded->data->object->customer;
                $stripeCache->last4 =
                    $stripeChargeSucceeded->data->object->payment_method_details->card->last4;
                $stripeCache->card_brand =
                    $stripeChargeSucceeded->data->object->payment_method_details->card->brand;

                Cache::put(
                    'stripeCache-'.$stripeCache->customer_id,
                    $stripeCache,
                );

                if ($this->isStripeObjectFull($stripeCache)) {
                    return $this->saveStripeInformationsAndUpdatePlan(
                        $stripeCache,
                    );
                }

                break;
            case 'invoice.paid':
                $lineDatas = [];

                foreach (
                    $body['data']['object']['lines']['data'] as $index => $lineData
                ) {
                    $subscriptionItemDetails = new LineParentSubscriptionItemDetails(
                        $lineData['parent']['subscription_item_details'][
                            'subscription'
                        ],
                        $lineData['parent']['subscription_item_details'][
                            'subscription_item'
                        ],
                    );
                    $parent = new LinesParent($subscriptionItemDetails);
                    $priceDetails = new PriceDetails(
                        $lineData['pricing']['price_details']['price'],
                        $lineData['pricing']['price_details']['product'],
                    );
                    $pricing = new Princing($priceDetails);
                    $periodEnd = $lineData['period']['end'];

                    $lineDataHelper = new LinesData(
                        $lineData['id'],
                        $lineData['description'],
                        $lineData['invoice'],
                        $parent,
                        $pricing,
                        $periodEnd,
                    );

                    array_push($lineDatas, $lineDataHelper);
                }
                $lines = new Lines($lineDatas);
                $statusTransitions = new StatusTransitions(
                    $body['data']['object']['status_transitions']['paid_at'],
                );
                $object = new StripeInvoicePaidDataDTO(
                    $body['data']['object']['id'],
                    $body['data']['object']['amount_paid'],
                    $body['data']['object']['customer'],
                    $body['data']['object']['customer_email'],
                    $body['data']['object']['effective_at'],
                    $body['data']['object']['invoice_pdf'],
                    $lines,
                    $body['data']['object']['number'],
                    $body['data']['object']['status'],
                    $statusTransitions,
                );
                $data = new StripeInvoicePaidObjectDTO($object);
                $stripeInvoicePaidDTO = new StripeInvoicePaidDTO(
                    $body['id'],
                    $body['object'],
                    $body['type'],
                    $data,
                );

                /**
                 * @var StripeCache | null
                 */
                $stripeCache = Cache::get(
                    'stripeCache-'.
                        $stripeInvoicePaidDTO->data->object->customer,
                );

                if (! $stripeCache) {
                    $stripeCacheHelper = new StripeCache(
                        $stripeInvoicePaidDTO->data->object->lines->data[0]->parent->subscription_item_details->subscription,
                        $stripeInvoicePaidDTO->data->object->customer,
                        $stripeInvoicePaidDTO->data->object->lines->data[0]->invoice,
                        $stripeInvoicePaidDTO->data->object->lines->data[0]->princing->priceDetails->product,
                        $stripeInvoicePaidDTO->data->object->invoice_pdf,
                        $stripeInvoicePaidDTO->data->object->effective_at,
                        $stripeInvoicePaidDTO->data->object->amount_paid,
                        $stripeInvoicePaidDTO->data->object->status,
                        null,
                        null,
                        $stripeInvoicePaidDTO->data->object->lines->data[0]->princing->priceDetails->price,
                        $stripeInvoicePaidDTO->data->object->customer_email,
                        $stripeInvoicePaidDTO->data->object->status_transitions->paid_at,
                        null,
                        $stripeInvoicePaidDTO->data->object->lines->data[0]->description,
                        $stripeInvoicePaidDTO->data->object->lines->data[0]->parent->subscription_item_details->subscription_item,
                    );

                    Cache::set(
                        'stripeCache-'.$stripeCacheHelper->customer_id,
                        $stripeCacheHelper,
                    );

                    Log::info('Stripe success: Invoice paid, cache created for customer '.$stripeCacheHelper->customer_id);

                    return response()->json(['received' => true]);
                }

                $stripeCache->subscription_id =
                    $stripeInvoicePaidDTO->data->object->lines->data[0]->parent->subscription_item_details->subscription;
                $stripeCache->customer_id =
                    $stripeInvoicePaidDTO->data->object->customer;
                $stripeCache->invoice_id =
                    $stripeInvoicePaidDTO->data->object->lines->data[0]->invoice;
                $stripeCache->product_id =
                    $stripeInvoicePaidDTO->data->object->lines->data[0]->princing->priceDetails->product;
                $stripeCache->invoice_pdf =
                    $stripeInvoicePaidDTO->data->object->invoice_pdf;
                $stripeCache->period_end =
                    $stripeInvoicePaidDTO->data->object->lines->data[0]->period_end;
                $stripeCache->amount_paid =
                    $stripeInvoicePaidDTO->data->object->amount_paid;
                $stripeCache->status =
                    $stripeInvoicePaidDTO->data->object->status;
                $stripeCache->price_id =
                    $stripeInvoicePaidDTO->data->object->lines->data[0]->princing->priceDetails->price;
                $stripeCache->customer_email =
                    $stripeInvoicePaidDTO->data->object->customer_email;
                $stripeCache->paid_at =
                    $stripeInvoicePaidDTO->data->object->status_transitions->paid_at;
                $stripeCache->description =
                    $stripeInvoicePaidDTO->data->object->lines->data[0]->description;
                $stripeCache->subscription_item =
                    $stripeInvoicePaidDTO->data->object->lines->data[0]->parent->subscription_item_details->subscription_item;

                Cache::set(
                    'stripeCache-'.$stripeCache->customer_id,
                    $stripeCache,
                );
                if ($this->isStripeObjectFull($stripeCache)) {
                    return $this->saveStripeInformationsAndUpdatePlan(
                        $stripeCache,
                    );
                }

                break;
            case 'checkout.session.completed':
                $metadataUUID =
                    $body['data']['object']['metadata']['plan_uuid'];

                if (
                    ! $metadataUUID ||
                    $metadataUUID === null ||
                    $metadataUUID === ''
                ) {
                    $metadataUUID = '';
                }

                $checkoutSessionMetadata = new CheckoutSessionCompletedMetadataDTO(
                    $metadataUUID,
                );

                $checkoutSessionObject = new CheckoutSessionCompletedObjectDTO(
                    $checkoutSessionMetadata,
                    $body['data']['object']['customer'],
                );
                $checkoutSessionData = new CheckoutSessionCompletedDataDTO(
                    $checkoutSessionObject,
                );
                $checkoutSession = new CheckoutSessionCompletedDTO(
                    $body['id'],
                    $body['type'],
                    $checkoutSessionData,
                );

                /**
                 * @var StripeCache
                 */
                $stripeCache = Cache::get(
                    'stripeCache-'.$checkoutSession->data->object->customer,
                );

                if ($stripeCache) {
                    $stripeCache->plan_uuid =
                        $checkoutSession->data->object->metadata->plan_uuid;
                    $stripeCache->customer_id =
                        $checkoutSession->data->object->customer;

                    if ($this->isStripeObjectFull($stripeCache)) {
                        return $this->saveStripeInformationsAndUpdatePlan(
                            $stripeCache,
                        );
                    }

                    Cache::put(
                        'stripeCache-'.$stripeCache->customer_id,
                        $stripeCache,
                    );
                    break;
                }

                $stripeCacheHelper = new StripeCache(
                    null,
                    $checkoutSession->data->object->customer,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $checkoutSession->data->object->metadata->plan_uuid,
                    null,
                    null,
                );

                Cache::put(
                    'stripeCache-'.$stripeCacheHelper->customer_id,
                    $stripeCacheHelper,
                );

                Log::info('Stripe success: Checkout session completed, cache created for customer '.$stripeCacheHelper->customer_id);

                break;
            case 'customer.subscription.deleted':
                $subscriptionDeletedPlan = new SubscriptionDeletedPlanDTO(
                    $body['data']['object']['status'],
                );
                $subscriptionDeletedItemsDataDTO = new SubscriptionDeletedItemsDataDTO(
                    $body['data']['object']['items']['data'][0][
                        'current_period_end'
                    ],
                );
                $subscriptionDeletedItemsDTO = new SubscriptionDeletedItemsDTO([
                    $subscriptionDeletedItemsDataDTO,
                ]);

                $subscriptionDeletedObject = new SubscriptionDeletedObjectDTO(
                    $subscriptionDeletedPlan,
                    $body['data']['object']['customer'],
                    $body['data']['object']['id'],
                    $body['data']['object']['ended_at'],
                    $subscriptionDeletedItemsDTO,
                );
                $subscriptionDeletedData = new SubscriptionDeletedDataDTO(
                    $subscriptionDeletedObject,
                );
                $subscriptionDeleted = new SubscriptionDeletedDTO(
                    $body['id'],
                    $body['type'],
                    $subscriptionDeletedData,
                );

                if (
                    $subscriptionDeleted->data->object->plan->status !==
                    PlanStatus::CANCELED->value
                ) {
                    break;
                }

                $subscription = Subscription::query()
                    ->where(
                        'stripe_user',
                        '=',
                        $subscriptionDeleted->data->object->customer,
                    )
                    ->first();

                if (! $subscription) {
                    break;
                }

                $subscription->daily_plans_used = 0;
                $subscription->weekly_plans_used = 0;
                $subscription->date_verified = date('Y-m-d');
                $subscription->next_billing = gmdate(
                    'Y-m-d',
                    $subscriptionDeleted->data->object->items->data[0]
                        ->current_period_end,
                );
                $subscription->status = PlanStatus::CANCELED->value;
                $subscription->last_four_digits = null;
                $subscription->card_brand = null;
                $subscription->stripe_subscription = null;
                $subscription->stripe_user = null;
                $subscription->stripe_price = null;
                $subscription->stripe_product = null;
                $subscription->plans_id =
                    '91842dba-9965-42c9-af2a-07fef464b315';
                $subscription->price = 0;
                $subscription->stripe_subscription_item = '';

                $subscription->save();

                Log::info('Stripe success: Subscription deleted for customer '.$subscriptionDeleted->data->object->customer);

                break;
            case 'customer.subscription.updated':
                $metadata = new SubscriptionUpdatedMetadataDTO(
                    $body['data']['object']['items']['data'][0]['plan'][
                        'metadata'
                    ]['plan_uuid'],
                );

                $planDTO = new PlanDTO(
                    $body['data']['object']['items']['data'][0]['plan']['id'],
                    $body['data']['object']['items']['data'][0]['plan'][
                        'amount'
                    ],
                    $body['data']['object']['items']['data'][0]['plan'][
                        'product'
                    ],
                    $metadata,
                );

                $subscriptionItemsData = new SubscriptionItemsData(
                    $body['data']['object']['items']['data'][0]['id'],
                    $body['data']['object']['items']['data'][0][
                        'current_period_end'
                    ],
                    $planDTO,
                    $body['data']['object']['items']['data'][0]['subscription'],
                );
                $subscriptionItems = new SubscriptionItems([
                    $subscriptionItemsData,
                ]);
                $objectDataDTO = new ObjectDTO(
                    $body['data']['object']['id'],
                    $body['data']['object']['customer'],
                    $subscriptionItems,
                    $body['data']['object']['status'],
                );

                $dataDTO = new SubscriptionUpdatedDataDTO($objectDataDTO, null);
                $subscriptionUpdatedDTO = new SubscriptionUpdatedDTO(
                    $body['id'],
                    $body['type'],
                    $dataDTO,
                );

                $defaultPaymentMethodExists = array_key_exists(
                    'default_payment_method',
                    $body['data']['previous_attributes'],
                );
                $defaultPaymentMethodPrevious = '';

                if ($defaultPaymentMethodExists) {
                    $defaultPaymentMethodPrevious =
                        $body['data']['previous_attributes'][
                            'default_payment_method'
                        ];
                }

                if (strlen($defaultPaymentMethodPrevious) > 0) {
                    $this->changePaymentMethodSubscription(
                        $subscriptionUpdatedDTO->data->object->customer,
                    );
                    break;
                }

                $subscription = Subscription::query()
                    ->where(
                        'stripe_user',
                        '=',
                        $subscriptionUpdatedDTO->data->object->customer,
                    )
                    ->first();

                if (! $subscription) {
                    break;
                }

                $plan = Plans::query()
                    ->where(
                        'uuid',
                        '=',
                        $subscriptionUpdatedDTO->data->object->items->data[0]
                            ->plan->metadata->plan_uuid,
                    )
                    ->first();

                if (! $plan) {
                    $plan = Plans::query()
                        ->where(
                            'price',
                            '=',
                            $subscriptionUpdatedDTO->data->object->items
                                ->data[0]->plan->amount / 100,
                        )
                        ->first();
                }

                $subscription->plans_id =
                    $plan?->uuid ?? '91842dba-9965-42c9-af2a-07fef464b315';
                $subscription->next_billing = gmdate(
                    'Y-m-d',
                    $subscriptionUpdatedDTO->data->object->items->data[0]
                        ->current_period_end,
                );
                $subscription->stripe_price =
                    $subscriptionUpdatedDTO->data->object->items->data[0]->plan->id;
                $subscription->stripe_product =
                    $subscriptionUpdatedDTO->data->object->items->data[0]->plan->product;
                $subscription->stripe_subscription_item =
                    $subscriptionUpdatedDTO->data->object->items->data[0]->id;
                $subscription->price =
                    $subscriptionUpdatedDTO->data->object->items->data[0]->plan
                        ->amount / 100;
                $subscription->date_verified = date('Y-m-d');
                $subscription->status =
                    $subscriptionUpdatedDTO->data->object->status;

                $subscription->save();

                Log::info('Stripe success: Subscription updated for customer '.$subscriptionUpdatedDTO->data->object->customer);

                break;
            case 'payment_method.attached':
                $walletHelper = $body['data']['object']['card']['wallet'];
                $walletType = '';

                if ($walletHelper !== null) {
                    $walletType =
                        $body['data']['object']['card']['wallet']['type'];
                }

                $wallet = new WalletDTO($walletType);
                $cvcChecks =
                    $body['data']['object']['card']['checks']['cvc_check'];

                if (! $cvcChecks || $cvcChecks === null) {
                    $cvcChecks = '';
                }

                $checks = new CardChecksDTO($cvcChecks);

                if (
                    ! $wallet->type ||
                    $wallet->type === null ||
                    $wallet->type === ''
                ) {
                    $wallet = null;
                }
                $card = new CardDTO(
                    $body['data']['object']['card']['brand'],
                    $body['data']['object']['card']['last4'],
                    $checks,
                    $wallet,
                );

                if ($card->wallet && $card->wallet->type === 'google_pay') {
                    $customer = $body['data']['object']['customer'];
                    $email =
                        $body['data']['object']['billing_details']['email'];

                    $user = User::query()
                        ->where('google_email', '=', $email)
                        ->orWhere('github_email', '=', $email)
                        ->first();

                    $useruuid = $user?->uuid ?? '';

                    $subscriptionQuery = Subscription::query()->where(
                        'stripe_user',
                        '=',
                        $customer,
                    );

                    if ($useruuid !== '') {
                        $subscriptionQuery->orWhere('user_id', '=', $useruuid);
                    }

                    $subscription = $subscriptionQuery->first();

                    if (! $subscription) {
                        $stripeCache = new StripeCache(
                            null,
                            $customer,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            $card->brand,
                            $card->last4,
                            null,
                            $email,
                            null,
                            null,
                            null,
                            null,
                            null,
                        );

                        Cache::put('stripeCache-'.$customer, $stripeCache);
                        break;
                    }

                    $subscription->card_brand = $card->brand;
                    $subscription->last_four_digits = $card->last4;
                    $subscription->save();

                    Log::info('Stripe success: Payment method attached for customer '.$customer);

                    break;
                }

                $paymentAttachedObject = new PaymentAttachedObjectDTO(
                    $body['data']['object']['customer'],
                );
                $paymentAttachedData = new PaymentAttachedDataDTO(
                    $paymentAttachedObject,
                );
                $paymentAttached = new PaymentAttachedDTO(
                    $paymentAttachedData,
                    $body['id'],
                    $body['type'],
                );

                $this->changePaymentMethodSubscription(
                    $paymentAttached->data->object->customer,
                    null,
                );

                break;
            case 'customer.updated':
                $customerUpdatedDataObject = new CustomerUpdatedObjectDTO(
                    $body['data']['object']['id'],
                    $body['data']['object']['email'],
                );

                $hasNoPreviousAttribute = $body['data']['previous_attributes'];

                if (
                    ! array_key_exists(
                        'invoice_settings',
                        $hasNoPreviousAttribute,
                    )
                ) {
                    break;
                }

                $previousDefaultPayment =
                    $body['data']['previous_attributes']['invoice_settings'][
                        'default_payment_method'
                    ];

                if (
                    ! $previousDefaultPayment ||
                    $previousDefaultPayment === null
                ) {
                    $previousDefaultPayment = '';
                }

                $previousAttributeDTO = new CustomerUpdatedPreviousAttributesDTO(
                    $previousDefaultPayment,
                );
                $customerUpdatedDataDTO = new CustomerUpdatedDataDTO(
                    $customerUpdatedDataObject,
                    $previousAttributeDTO,
                );
                $customerUpdated = new CustomerUpdatedDTO(
                    $body['id'],
                    $body['type'],
                    $customerUpdatedDataDTO,
                );

                if ($previousDefaultPayment) {
                    $this->changePaymentMethodSubscription(
                        $customerUpdated->data->object->id,
                        $customerUpdated->data->object->email,
                    );
                    break;
                }

            default:
                Log::info(
                    'Method not founded for this event: '.$body['type'],
                );
                break;
        }
    }
}
