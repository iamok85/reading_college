<?php

namespace App\Neuron\Nodes;

use App\Models\EssaySubmission;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Neuron\Events\RetrieveEssayImages;
use App\Support\OpenAiLogger;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class EssayImageNode extends Node
{
    public function __invoke(RetrieveEssayImages $event, WorkflowState $state): RetrieveEssayImages|RetrieveEssayAnalysis
    {
        $prompt = $this->buildPrompt($event->correctedEssay);
        OpenAiLogger::log('essay_images', $prompt, null, [
            'phase' => 'request',
            'essay_id' => $event->essayId,
            'payload' => [
                'model' => 'gpt-image-1.5',
                'n' => 1,
                'size' => '1024x1024',
            ],
        ]);
        $imagePaths = $this->generateImages($event->essayId, $prompt);

        if (!empty($imagePaths)) {
            EssaySubmission::whereKey($event->essayId)->update([
                'generated_image_paths' => $imagePaths,
            ]);
        }

        $state->set('generated_image_paths', $imagePaths);

        if ($state->get('pipeline_mode')) {
            $analysisText = trim((string) ($state->get('analysis_text') ?? $event->correctedEssay));
            if ($analysisText === '') {
                $analysisText = $event->correctedEssay;
            }
            $essayCount = (int) ($state->get('essay_count') ?? 1);
            return new RetrieveEssayAnalysis($analysisText, max(1, $essayCount));
        }

        return new RetrieveEssayImages($event->essayId, $event->correctedEssay);
    }

    private function buildPrompt(string $correctedEssay): string
    {
        $trimmed = trim($correctedEssay);
        return "Create 1 child-friendly illustration in a four-panel grid (2x2). "
            . "Each panel should depict a different part of the story in order (beginning, middle, end, plus one key detail). "
            . "Use a bright, warm, cartoon storybook style with clean outlines and soft shading, similar to a kid-friendly picture book. "
            . "No text overlays. Keep it classroom-appropriate.\n\n"
            . $trimmed;
    }

    private function generateImages(int $essayId, string $prompt): array
    {
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? null;
        if (!$apiKey) {
            return [];
        }

        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 60,
            'connect_timeout' => 10,
        ]);

        try {
            $response = $client->post('images/generations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-image-1.5',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024',
                ],
            ]);
        } catch (\Throwable $exception) {
            OpenAiLogger::log('essay_images', $prompt, null, [
                'error' => $exception->getMessage(),
            ]);
            return [];
        }

        $payload = json_decode((string) $response->getBody(), true);
        $images = $payload['data'] ?? [];
        if (!is_array($images) || empty($images)) {
            OpenAiLogger::log('essay_images', $prompt, json_encode($payload), [
                'error' => 'No images returned.',
            ]);
            return [];
        }

        $paths = [];
        foreach (array_slice($images, 0, 1) as $index => $image) {
            $base64 = $image['b64_json'] ?? null;
            if (!$base64) {
                continue;
            }

            $binary = base64_decode($base64, true);
            if ($binary === false) {
                continue;
            }

            $filename = 'image-' . ($index + 1) . '.png';
            $relativePath = 'generated_images/' . $essayId . '/' . $filename;
            Storage::disk('public')->put($relativePath, $binary);
            $paths[] = $relativePath;
        }

        OpenAiLogger::log('essay_images', $prompt, json_encode([
            'count' => count($paths),
            'paths' => $paths,
        ]), [
            'model' => 'gpt-image-1.5',
        ]);

        return $paths;
    }
}
