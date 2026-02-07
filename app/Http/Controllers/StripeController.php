<?php

namespace App\Http\Controllers;

use App\Dto\Stripe\ChargeSucceeded\BillingDetails;
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
use App\Dto\Stripe\InvoicePaid\BillingDetails as InvoicePaidBillingDetails;
use App\Dto\Stripe\InvoicePaid\StatusTransitions;
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
            $stripeCache->subscription_id
        ) return true;

        return false;
    }

    private function saveStripeInformationsAndUpdatePlan(StripeCache $stripeCache)
    {
        $user = User::query()->where('email', '=', $stripeCache->customer_email)->first();
        $subscription = Subscription::where('user_id', '=', $user->uuid)->first();
        $paymentHistory = Subscription::where('user');
    }

    public function handler(Request $request)
    {
        $body = $request->all();

        Log::info($body);
        Log::info('-------------------');

        switch ($body['type']) {
            case "charge.succeeded":

                $cardCheck = new CardChecksDTO($body['data']['object']['payment_method_details']['card']['checks']['cvc_check']);
                $card = new CardDTO(
                    $body['data']['object']['payment_method_details']['card']['brand'],
                    $body['data']['object']['payment_method_details']['card']['last4'],
                    $cardCheck
                );
                $paymentMethodDetails = new PaymentMethodDetailsDTO($card);
                $billingDetails = new BillingDetails($body['data']['object']['billing_details']['email']);
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
                    $this->saveStripeInformationsAndUpdatePlan($stripeCache);
                }

                break;
            case "invoice.paid":
                $lineDatas = [];

                foreach ($body['data']['object']['lines']['data'] as $index => $lineData) {

                    $billingDetail = new InvoicePaidBillingDetails($body['data']['object']['billing_details']['email']);
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
                    $billingDetail,
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
                    );

                    Cache::set('stripeCache-' . $stripeCacheHelper->customer_id, $stripeCacheHelper);
                    return;
                }

                if ($this->isStripeObjectFull($stripeCache)) {
                    $this->saveStripeInformationsAndUpdatePlan($stripeCache);
                }

                break;
        }

        // $user = User::query()->where('github_email', '=', $body->data->billing_details->email);
    }
}
