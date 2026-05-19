<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportEmails extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var bool
     */
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var string
     */
    protected $table = 'support_emails';

    /**
     * @var bool
     */
    protected $timestaps = true;

    /**
     * @var array
     */
    protected $fillable = [
        'ticketId',
        'title',
        'description',
        'category',
        'user_id',
    ];
}
