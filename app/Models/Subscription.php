<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'subscription';

    /**
     * @var boolean
     */
    public $timestamps = true;

    protected $fillable = [
        'daily_plans_used',
        'weekly_plans_used',
        'date_verified',
        'next_billing',
        'status',
        'last_four_digits',
        'user_id',
        'plans_id',
        'card_brand',
        'stripe_subscription',
        'stripe_user',
        'stripe_price',
        'stripe_product'
    ];

    protected $hidden = [
        'stripe_subscription',
        'stripe_user',
        'stripe_price',
        'stripe_product'
    ];
}
