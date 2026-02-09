<?php

namespace App\Http\Controllers;

use App\Dto\Stripe\ChargeSucceeded\BillingDetailsDTO;
use App\Dto\Stripe\ChargeSucceeded\CardChecksDTO;
use App\Dto\Stripe\ChargeSucceeded\CardDTO;
use App\Dto\Stripe\ChargeSucceeded\DataDTO;
use App\Dto\Stripe\ChargeSucceeded\DataObjectDTO;
use App\Dto\Stripe\ChargeSucceeded\PaymentMethodDetailsDTO;
use App\Dto\Stripe\InvoicePaid\LineParentSubscriptionItemDetails;
use App\Dto\Stripe\InvoicePaid\Lines;
use App\Dto\Stripe\InvoicePaid\LinesData;
use App\Dto\Stripe\InvoicePaid\LinesParent;
use App\Dto\Stripe\InvoicePaid\PriceDetails;
use App\Dto\Stripe\InvoicePaid\Princing;
use App\Dto\Stripe\InvoicePaid\StripeInvoicePaidDataDTO;
use App\Dto\Stripe\InvoicePaid\StripeInvoicePaidDTO;
use App\Dto\Stripe\InvoicePaid\StripeInvoicePaidObjectDTO;
use App\Dto\Stripe\StripeCache;
use App\Dto\Stripe\ChargeSucceeded\StripeChargeSucceededDTO;
use App\Dto\Stripe\CheckoutSessionCompleted\CheckoutSessionCompletedDataDTO;
use App\Dto\Stripe\CheckoutSessionCompleted\CheckoutSessionCompletedDTO;
use App\Dto\Stripe\CheckoutSessionCompleted\CheckoutSessionCompletedMetadataDTO;
use App\Dto\Stripe\CheckoutSessionCompleted\CheckoutSessionCompletedObjectDTO;
use App\Dto\Stripe\InvoicePaid\StatusTransitions;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedDataDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedItemsDataDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedItemsDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedObjectDTO;
use App\Dto\Stripe\SubscriptionDeleted\SubscriptionDeletedPlanDTO;
use App\Enums\PlanStatus;
use App\Models\PaymentHistory;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    private function isStripeObjectFull(StripeCache $stripeCache): bool
    {
        if (
            $stripeCache->amount_paid &&
            $stripeCache->card_brand &&
            $stripeCache->customer_id &&
            $stripeCache->effect_at &&
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
            $stripeCache->customer_email
        ) return true;

        return false;
    }

    private function saveStripeInformationsAndUpdatePlan(StripeCache $stripeCache)
    {
        $user = User::query()
            ->where('google_email', '=', $stripeCache->customer_email)
            ->orWhere('github_email', '=', $stripeCache->customer_email)
            ->first();

        $subscription = Subscription::where('user_id', '=', $user->uuid)->first();
        $plan = Plans::query()->where('uuid', '=', $stripeCache->plan_uuid)->first();

        $subscription->stripe_subscription = $stripeCache->subscription_id;
        $subscription->stripe_user = $stripeCache->customer_id;
        $subscription->stripe_price = floor($stripeCache->amount_paid / 100);
        $subscription->stripe_product = $stripeCache->product_id;
        $subscription->next_billing = gmdate('Y/m/d', $stripeCache->effect_at);
        $subscription->status = $stripeCache->status;
        $subscription->card_brand = $stripeCache->card_brand;
        $subscription->last_four_digits = $stripeCache->last4;
        $subscription->date_verified = date('Y/m/d');

        if ($stripeCache->plan_uuid === '') {
            $subscription->daily_plans_used = 3;
            $subscription->weekly_plans_used = 3;
            $subscription->plans_id = '91842dba-9965-42c9-af2a-07fef464b315';
        } else {
            $subscription->daily_plans_used = $plan->amount_planning_day;
            $subscription->weekly_plans_used = $plan->amount_planning_week;
            $subscription->plans_id = $plan->uuid;
        }

        $subscription->save();

        $paymentHistory = new PaymentHistory();
        $paymentHistory->payment_date = gmdate('Y/m/d', $stripeCache->paid_at);
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

        if ($stripeCache->plan_uuid === '')
            $subscription->plans_id = '91842dba-9965-42c9-af2a-07fef464b315';
        else
            $paymentHistory->plan_id = $plan->uuid;


        $paymentHistory->save();

        Cache::forget('stripeCache-' . $stripeCache->customer_id);
    }

    public function handler(Request $request)
    {
        $body = $request->all();

        switch ($body['type']) {
            case "charge.succeeded":
                $cardCheck = new CardChecksDTO($body['data']['object']['payment_method_details']['card']['checks']['cvc_check']);
                $card = new CardDTO(
                    $body['data']['object']['payment_method_details']['card']['brand'],
                    $body['data']['object']['payment_method_details']['card']['last4'],
                    $cardCheck
                );
                $paymentMethodDetails = new PaymentMethodDetailsDTO($card);
                $billingDetails = new BillingDetailsDTO($body['data']['object']['billing_details']['email']);
                $dataObject = new DataObjectDTO(
                    $body['data']['object']['id'],
                    $paymentMethodDetails,
                    $body['data']['object']['customer'],
                    $billingDetails
                );
                $dataDTO = new DataDTO($dataObject);
                $stripeChargeSucceeded = new StripeChargeSucceededDTO(
                    $dataDTO,
                    $body['type'],
                    $body['id']
                );

                /**
                 * @var StripeCache | null
                 */
                $stripeCache = Cache::get('stripeCache-' . $stripeChargeSucceeded->data->object->customer);

                if (
                    $stripeChargeSucceeded->data->object->payment_method_details->card->checks->cvc_check !== "check" &&
                    $stripeChargeSucceeded->data->object->payment_method_details->card->checks->cvc_check !== "pass"
                ) {
                    Log::error('CVC invalid for customer' . $stripeChargeSucceeded->data->object->customer);
                }

                if (!$stripeCache || $stripeCache === null) {
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
                        null
                    );

                    Cache::put('stripeCache-' . $stripeCacheHelper->customer_id, $stripeCacheHelper);
                    return;
                }

                $stripeCache->customer_id = $stripeChargeSucceeded->data->object->customer;
                $stripeCache->last4 = $stripeChargeSucceeded->data->object->payment_method_details->card->last4;
                $stripeCache->card_brand = $stripeChargeSucceeded->data->object->payment_method_details->card->brand;

                Cache::put('stripeCache-' . $stripeCache->customer_id, $stripeCache);

                if ($this->isStripeObjectFull($stripeCache)) {
                    return $this->saveStripeInformationsAndUpdatePlan($stripeCache);
                }

                break;
            case "invoice.paid":
                $lineDatas = [];

                foreach ($body['data']['object']['lines']['data'] as $index => $lineData) {

                    $subscriptionItemDetails = new LineParentSubscriptionItemDetails(
                        $lineData['parent']['subscription_item_details']['subscription'],
                        $lineData['parent']['subscription_item_details']['subscription_item']
                    );
                    $parent = new LinesParent($subscriptionItemDetails);
                    $priceDetails = new PriceDetails($lineData['pricing']['price_details']['price'], $lineData['pricing']['price_details']['product']);
                    $pricing = new Princing($priceDetails);
                    $lineDataHelper = new LinesData(
                        $lineData['id'],
                        $lineData['description'],
                        $lineData['invoice'],
                        $parent,
                        $pricing
                    );

                    array_push($lineDatas, $lineDataHelper);
                }
                $lines = new Lines($lineDatas);
                $statusTransitions = new StatusTransitions($body['data']['object']['status_transitions']['paid_at']);
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
                    $statusTransitions
                );
                $data = new StripeInvoicePaidObjectDTO($object);
                $stripeInvoicePaidDTO = new StripeInvoicePaidDTO($body['id'], $body['object'], $body['type'], $data);

                /**
                 * @var StripeCache | null
                 */
                $stripeCache = Cache::get('stripeCache-' . $stripeInvoicePaidDTO->data->object->customer);

                if (!$stripeCache || $stripeCache === null) {
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
                    );

                    Cache::set('stripeCache-' . $stripeCacheHelper->customer_id, $stripeCacheHelper);
                    return;
                }

                $stripeCache->subscription_id = $stripeInvoicePaidDTO->data->object->lines->data[0]->parent->subscription_item_details->subscription;
                $stripeCache->customer_id = $stripeInvoicePaidDTO->data->object->customer;
                $stripeCache->invoice_id = $stripeInvoicePaidDTO->data->object->lines->data[0]->invoice;
                $stripeCache->product_id = $stripeInvoicePaidDTO->data->object->lines->data[0]->princing->priceDetails->product;
                $stripeCache->invoice_pdf = $stripeInvoicePaidDTO->data->object->invoice_pdf;
                $stripeCache->effect_at = $stripeInvoicePaidDTO->data->object->effective_at;
                $stripeCache->amount_paid = $stripeInvoicePaidDTO->data->object->amount_paid;
                $stripeCache->status = $stripeInvoicePaidDTO->data->object->status;
                $stripeCache->price_id = $stripeInvoicePaidDTO->data->object->lines->data[0]->princing->priceDetails->price;
                $stripeCache->customer_email = $stripeInvoicePaidDTO->data->object->customer_email;
                $stripeCache->paid_at = $stripeInvoicePaidDTO->data->object->status_transitions->paid_at;
                $stripeCache->description = $stripeInvoicePaidDTO->data->object->lines->data[0]->description;

                Cache::set('stripeCache-' . $stripeCache->customer_id, $stripeCache);
                if ($this->isStripeObjectFull($stripeCache)) {
                    return $this->saveStripeInformationsAndUpdatePlan($stripeCache);
                }

                break;
            case "checkout.session.completed":
                $metadataUUID = $body['data']['object']['metadata']['uuid_plan'];

                if (!$metadataUUID || $metadataUUID === null || $metadataUUID === '')
                    $metadataUUID = '';

                $checkoutSessionMetadata = new CheckoutSessionCompletedMetadataDTO(
                    $metadataUUID
                );

                $checkoutSessionObject = new CheckoutSessionCompletedObjectDTO(
                    $checkoutSessionMetadata,
                    $body['data']['object']['customer']
                );
                $checkoutSessionData = new CheckoutSessionCompletedDataDTO($checkoutSessionObject);
                $checkoutSession = new CheckoutSessionCompletedDTO(
                    $body['id'],
                    $body['type'],
                    $checkoutSessionData
                );

                /**
                 * @var StripeCache
                 */
                $stripeCache = Cache::get('stripeCache-' . $checkoutSession->data->object->customer);

                $stripeCache->plan_uuid = $checkoutSession->data->object->metadata->uuid_plan;
                $stripeCache->customer_id = $checkoutSession->data->object->customer;


                if ($this->isStripeObjectFull($stripeCache)) {
                    return $this->saveStripeInformationsAndUpdatePlan($stripeCache);
                }

                Cache::put('stripeCache-' . $stripeCache->customer_id, $stripeCache);

                break;
            case "customer.subscription.deleted":
                $subscriptionDeletedPlan = new SubscriptionDeletedPlanDTO($body['data']['object']['plan']['status']);
                $subscriptionDeletedItemsDataDTO = new SubscriptionDeletedItemsDataDTO($body['data']['object']['items']['data'][0]['current_period_end']);
                $subscriptionDeletedItemsDTO = new SubscriptionDeletedItemsDTO([$subscriptionDeletedItemsDataDTO]);

                $subscriptionDeletedObject = new SubscriptionDeletedObjectDTO(
                    $subscriptionDeletedPlan,
                    $body['data']['object']['customer'],
                    $body['data']['object']['id'],
                    $body['data']['object']['ended_at'],
                    $subscriptionDeletedItemsDTO
                );

                $subscriptionDeletedData = new SubscriptionDeletedDataDTO($subscriptionDeletedObject);

                $subscriptionDeleted = new SubscriptionDeletedDTO(
                    $body['id'],
                    $body['status'],
                    $subscriptionDeletedData
                );

                if ($subscriptionDeleted->data->object->plan->status !== PlanStatus::CANCELED) {
                    return;
                }

                $subscription = Subscription::query()
                    ->where(
                        'stripe_subscription',
                        '=',
                        $subscriptionDeleted
                            ->data
                            ->object
                            ->customer
                    )->first();

                $subscription->daily_plans_used = 3;
                $subscription->weekly_plans_used = 3;
                $subscription->date_verified = date('Y-m-d');
                $subscription->next_billing = $subscriptionDeleted->data->object->items->data[0]->current_period_end; // <-
                $subscription->status = PlanStatus::ACTIVE;
                $subscription->last_four_digits = null;
                $subscription->card_brand = null;
                $subscription->stripe_subscription = null;
                $subscription->stripe_user = null;
                $subscription->stripe_price = null;
                $subscription->stripe_product = null;
                $subscription->plans_id = '91842dba-9965-42c9-af2a-07fef464b315';

                $subscription->save();

                break;
        }
    }
}
