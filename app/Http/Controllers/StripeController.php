<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function handler(Request $request)
    {
        $body = $request->all();

        Log::info($body);
        Log::info('-------------------');

        switch ($body['type']) {
            case "charge.succeeded":
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
                $object = new StripeInvoicePaidDataDTO(
                    $body['data']['object']['id'],
                    $body['data']['object']['amount_paid'],
                    $body['data']['object']['customer'],
                    $body['data']['object']['customer_email'],
                    $body['data']['object']['effective_at'],
                    $body['data']['object']['invoice_pdf'],
                    $lines,
                    $body['data']['object']['number'],
                    $body['data']['object']['status']
                );
                $data = new StripeInvoicePaidObjectDTO($object);
                $stripeInvoicePaidDTO = new StripeInvoicePaidDTO($body['id'], $body['object'], $body['type'], $data);

                Cache::put('qwert', $stripeInvoicePaidDTO->id, now()->plus(minutes: 10));
                /**
                 * @var StripeCache | null
                 */
                $stripeCache = Cache::get('stripeCache-' . $stripeInvoicePaidDTO->data->object->customer);

                if (!$stripeCache) {
                    // $stripeCacheHelper = new StripeCache(
                    //     $stripeInvoicePaidDTO->data->object->lines->data[0]->parent->subscription_item_details->subscription,
                    //     $stripeInvoicePaidDTO->data->object->customer,
                    //     $stripeInvoicePaidDTO->data->object->lines->data[0]->invoice
                    //     );
                    Cache::set();
                }

                Log::info('CacheTeste: ' . $stripeCache);

                break;
        }

        // $user = User::query()->where('github_email', '=', $body->data->billing_details->email);
    }
}
