<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EssaySubmission extends Model
{
    protected $fillable = [
        'user_id',
        'child_id',
        'image_paths',
        'generated_image_paths',
        'uploaded_at',
        'ocr_text',
        'original_writing',
        'spelling_mistakes',
        'grammar_mistakes',
        'corrected_version',
        'analysis_text',
        'response_text',
        'processing_status',
        'processing_error',
        'processing_completed_at',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'generated_image_paths' => 'array',
        'uploaded_at' => 'datetime',
        'processing_completed_at' => 'datetime',
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
