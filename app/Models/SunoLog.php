<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunoLog extends Model
{
    protected $fillable = [
        'user_id',
        'context',
        'model',
        'prompt',
        'response',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
