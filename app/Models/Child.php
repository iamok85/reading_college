<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Child extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'age',
        'birth_year',
        'gender',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function essaySubmissions(): HasMany
    {
        return $this->hasMany(EssaySubmission::class);
    }

    public function essaySongs(): HasMany
    {
        return $this->hasMany(EssaySong::class);
    }

    public function readingRecommendations(): HasMany
    {
        return $this->hasMany(ReadingRecommendation::class);
    }

    public function essayAnalyses(): HasMany
    {
        return $this->hasMany(EssayAnalysis::class);
    }
}
