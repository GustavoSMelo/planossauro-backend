<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'payment_history';

    /**
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'payment_date',
        'description',
        'card_brand',
        'last_four_digits',
        'price',
        'status',
        'plan_id',
        'user_id',
        'NFe',
        'stripe_invoice',
        'stripe_product',
        'stripe_subscription',
        'subscription_id',
    ];

    protected $hidden = [
        'stripe_invoice',
        'stripe_product',
        'stripe_subscription',
    ];
}
