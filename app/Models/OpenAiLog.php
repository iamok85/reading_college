<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenAiLog extends Model
{
    protected $table = 'openai_logs';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
