<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    use HasUuids;

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

    protected $fillable = ['plan_name', 'price', 'amount_planning', 'has_cloud_save'];
}
