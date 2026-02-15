<?php

namespace App\Support;

use App\Models\OpenAiLog;
use Illuminate\Support\Facades\Log;

class OpenAiLogger
{
    public static function log(string $context, ?string $prompt, ?string $response, array $meta = []): void
    {
        OpenAiLog::create([
            'user_id' => auth()->id(),
            'context' => $context,
            'model' => $_ENV['OPENAI_CHAT_MODEL'] ?? null,
            'prompt' => $prompt,
            'response' => $response,
            'meta' => $meta,
        ]);

        Log::channel('openai')->info('openai.call', [
            'user_id' => auth()->id(),
            'context' => $context,
            'model' => $_ENV['OPENAI_CHAT_MODEL'] ?? null,
            'prompt' => $prompt,
            'response' => $response,
            'meta' => $meta,
        ]);
    }
}
