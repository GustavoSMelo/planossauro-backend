<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'plans';

    /**
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'plan_name',
        'price',
        'amount_planning_day',
        'amount_planning_week',
        'has_cloud_save',
    ];
}
