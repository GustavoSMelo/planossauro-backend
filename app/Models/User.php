<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class User extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'user';

    /**
     * @var bool
     */
    protected $timestaps = false;

    /**
     * @var array<string, string, string, string>
     */
    protected $fillable = [
        'uuid',
        'google_email',
        'github_email',
        'github_id',
        'github_validation_code',
        'github_is_validated',
        'full_name',
        'cellphone_number',
        'sms_validation_code',
        'sms_is_validated',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
