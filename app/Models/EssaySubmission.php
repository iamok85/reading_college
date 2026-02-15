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
        'uploaded_at',
        'ocr_text',
        'response_text',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'uploaded_at' => 'datetime',
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
