<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'generated_video_path',
        'video_job_id',
        'video_status',
        'video_progress',
        'video_error',
        'video_url',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'generated_image_paths' => 'array',
        'uploaded_at' => 'datetime',
        'video_progress' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function song(): HasOne
    {
        return $this->hasOne(EssaySong::class);
    }
}
