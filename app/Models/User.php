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
    protected $fillable = ['uuid', 'google_email', 'github_email', 'full_name', 'cellphone_number'];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}
