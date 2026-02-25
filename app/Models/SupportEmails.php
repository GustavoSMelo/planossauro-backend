<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportEmails extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var boolean
     */
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var string
     */
    protected $table = 'support_emails';

    /**
     * @var boolean
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
        'user_id'
    ];
}
