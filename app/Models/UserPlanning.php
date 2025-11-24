<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserPlanning extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'user_planning';

    /**
     * @var boolean
     */
    public $timestamps = true;

    protected $fillabe = ['user_id', 'planning_id'];

    protected static function booted() {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
