<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use NeuronAI\Agent;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use App\Neuron\Providers\OpenAIVideoProvider;
use Illuminate\Support\Facades\Http;

class EssayVideoAgent extends Agent
{
    /**
     * @param Message|array<int, Message> $messages
     */
    public function chat(Message|array $messages): Message
    {
        if (app()->environment('testing') && !empty($_ENV['FAKE_OPENAI'])) {
            return new Message(
                MessageRole::ASSISTANT,
                (string) ($_ENV['FAKE_OPENAI_TEXT'] ?? '')
            );
        }

        return parent::chat($messages);
    }

    protected function provider(): AIProviderInterface
    {
        if (!empty($_ENV['OPENAI_API_KEY'])) {
            return new OpenAIVideoProvider(
                $_ENV['OPENAI_API_KEY'],
                $_ENV['OPENAI_VIDEO_MODEL'] ?? 'sora-2',
                [
                    'seconds' => (int) ($_ENV['OPENAI_VIDEO_SECONDS'] ?? 12),
                    'size' => (string) ($_ENV['OPENAI_VIDEO_SIZE'] ?? '1280x720'),
                ],
                httpOptions: new HttpClientOptions(timeout: 60, connectTimeout: 10)
            );
        }

        throw new \Exception('You need a valid OPENAI_API_KEY to use Video Assistant.');
    }

    public function fetchStatus(string $jobId): ?array
    {
        $apiKey = (string) ($_ENV['OPENAI_API_KEY'] ?? '');
        if ($apiKey === '') {
            return null;
        }

        try {
            $base = rtrim((string) ($_ENV['OPENAI_API_BASE'] ?? 'https://api.openai.com/v1'), '/');
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->get($base . '/videos/' . $jobId);
        } catch (\Throwable $exception) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }
}
