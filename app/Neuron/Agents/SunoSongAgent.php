<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use App\Support\SunoLogger;
use Illuminate\Support\Facades\Http;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Suno\Suno;

class SunoSongAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        $apiKey = $_ENV['SUNO_API_KEY'] ?? '';
        $baseUrl = $_ENV['SUNO_API_URL'] ?? '';

        if ($apiKey !== '' && $baseUrl !== '') {
            return new Suno($apiKey, $baseUrl);
        }

        throw new \Exception('You need a valid SUNO_API_KEY and SUNO_API_URL to use Suno.');
    }


    /**
     * @return array{audio_url?:string,title?:string,provider_song_id?:string,task_id?:string}
     */
    public function generate(string $title, string $lyrics): array
    {
        $apiUrl = $_ENV['SUNO_API_URL'];
        $apiKey = $_ENV['SUNO_API_KEY'];

        if (! $apiUrl || ! $apiKey) {
            throw new \RuntimeException('Missing Suno API configuration.');
        }

        $payload = [
            'prompt' => $lyrics,
            'customMode' => false,
            'instrumental' => false,
            'model' => 'V4_5ALL',
        ];

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post($apiUrl, $payload);

        if (! $response->successful()) {
            SunoLogger::log('suno_generate', $lyrics, $response->body(), [
                'model' => $payload['model'],
                'status' => $response->status(),
            ]);
            throw new \RuntimeException('Suno request failed.');
        }

        $data = $response->json();
        $audioUrl = data_get($data, 'audio_url')
            ?? data_get($data, 'data.0.audio_url')
            ?? data_get($data, 'song.audio_url')
            ?? null;
        $taskId = data_get($data, 'data.taskId') ?? data_get($data, 'taskId');

        $result = [
            'audio_url' => $audioUrl,
            'title' => data_get($data, 'title') ?? data_get($data, 'data.0.title') ?? $title,
            'provider_song_id' => (string) ($taskId ?: (data_get($data, 'id') ?? data_get($data, 'data.0.id') ?? '')),
            'task_id' => $taskId ? (string) $taskId : null,
        ];

        SunoLogger::log('suno_generate', $lyrics, json_encode($data), [
            'model' => $payload['model'],
            'task_id' => $taskId,
        ]);

        return $result;
    }
}
