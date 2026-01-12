<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'payment_history';

    /**
     * @var boolean
     */
    public $timestamps = true;

    protected $fillable = ['payment_date', 'description', 'card_brand', 'last_four_digits', 'price', 'status', 'plan_id'];
}
