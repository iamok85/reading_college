<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EssayAnalysis extends Model
{
    protected $fillable = [
        'user_id',
        'child_id',
        'essay_count',
        'last_submission_at',
        'analysis_text',
    ];

    protected $casts = [
        'last_submission_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
