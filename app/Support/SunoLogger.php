<?php

namespace App\Support;

use App\Models\SunoLog;
use Illuminate\Support\Facades\Log;

class SunoLogger
{
    public static function log(string $context, ?string $prompt, ?string $response, array $meta = []): void
    {
        SunoLog::create([
            'user_id' => auth()->id(),
            'context' => $context,
            'model' => $meta['model'] ?? null,
            'prompt' => $prompt,
            'response' => $response,
            'meta' => $meta,
        ]);

        Log::channel('suno')->info('suno.call', [
            'user_id' => auth()->id(),
            'context' => $context,
            'model' => $meta['model'] ?? null,
            'prompt' => $prompt,
            'response' => $response,
            'meta' => $meta,
        ]);
    }
}
