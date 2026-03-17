<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Subscription extends Authenticatable
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = "uuid";

    /**
     * @var string
     */
    protected $table = "subscription";

    /**
     * @var boolean
     */
    public $timestamps = true;

    protected $fillable = [
        "daily_plans_used",
        "weekly_plans_used",
        "date_verified",
        "next_billing",
        "status",
        "last_four_digits",
        "user_id",
        "plans_id",
        "card_brand",
        "price",
        "stripe_subscription",
        "stripe_user",
        "stripe_price",
        "stripe_product",
        "stripe_subscription_item",
    ];

    protected $hidden = [
        "stripe_subscription",
        "stripe_user",
        "stripe_price",
        "stripe_product",
        "stripe_subscription_item",
    ];
}
