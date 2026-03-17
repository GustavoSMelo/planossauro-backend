<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Plans extends Authenticatable
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = "uuid";

    /**
     * @var string
     */
    protected $table = "plans";

    /**
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        "plan_name",
        "price",
        "amount_planning_day",
        "amounth_planning_week",
        "has_cloud_save",
    ];
}
