<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EssaySong extends Model
{
    protected $fillable = [
        'essay_submission_id',
        'user_id',
        'child_id',
        'status',
        'song_name',
        'song_path',
        'provider',
        'provider_song_id',
        'error_message',
    ];

    public function essaySubmission(): BelongsTo
    {
        return $this->belongsTo(EssaySubmission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
