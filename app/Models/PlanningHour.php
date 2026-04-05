<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlanningHour extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'planning_hour';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'interval_between_classes',
        'initial_hour',
        'user_id',
    ];

    /**
     * @var bool
     */
    public $timestamps = true;
}
