<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    use HasUuids;

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'planning';

    /**
     * @var array<string, string, string, string, string>
     */
    protected $fillable = ['document_b64', 'start_plan', 'end_plan', 'school_name', 'class_name'];

    /**
     * @var bool
     */
    public $timestamps = true;
}
