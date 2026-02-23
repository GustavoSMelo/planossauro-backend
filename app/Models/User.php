<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasUuids, HasApiTokens, Notifiable;

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
    protected $timestaps = true;

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
        'google_validation_code',
        'google_is_validated',
        'google_id',
        'deleted_at'
    ];

    protected $hidden = [
        'github_validation_code',
        'google_validation_code',
        'sms_validation_code'
    ];

    protected $casts = [
        'google_id' => 'string'
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
