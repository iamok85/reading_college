<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedEssay extends Model
{
    protected $fillable = [
        'essay_submission_id',
        'user_id',
        'child_id',
        'child_name',
        'child_age',
        'corrected_text',
        'image_path',
        'shared_at',
    ];

    protected $casts = [
        'shared_at' => 'datetime',
    ];

    public function essaySubmission(): BelongsTo
    {
        return $this->belongsTo(EssaySubmission::class);
    }
}
