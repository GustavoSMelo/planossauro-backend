<?php

namespace App\Http\Controllers;

use App\Interfaces\IStripeWebhook;
use App\Models\User;
use Illuminate\Http\Request;

class StripeController extends Controller
{
    public function handler(Request $request) {
        /**
         * @var IStripeWebhook
         */
        $body = $request->all();

        $user = User::query()->where('github_email', '=', $body->data->billing_details->email);
    }
}
