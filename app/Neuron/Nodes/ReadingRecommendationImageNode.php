<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Models\ReadingRecommendation;
use App\Neuron\Agents\EssayImageAgent;
use App\Neuron\Events\RetrieveReadingRecommendationImages;
use App\Support\OpenAiLogger;
use Illuminate\Support\Facades\Storage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\StopEvent;
use NeuronAI\Workflow\WorkflowState;

class ReadingRecommendationImageNode extends Node
{
    public function __invoke(RetrieveReadingRecommendationImages $event, WorkflowState $state): RetrieveReadingRecommendationImages|StopEvent
    {
        $recommendation = ReadingRecommendation::find($event->recommendationId);
        if (!$recommendation) {
            return $event;
        }

        $updated = $recommendation->items ?? [];
        if (!is_array($updated)) {
            $updated = [];
        }

        foreach ($event->items as $index => $item) {
            $prompt = $this->buildPrompt($item);
            \Illuminate\Support\Facades\Log::channel('essay_pipeline')->info('reading_recommendation.pipeline_request', [
                'recommendation_id' => $recommendation->id,
                'item_index' => $index,
                'prompt' => $prompt,
            ]);
            $path = $this->generateImage($recommendation->id, $index, $prompt);

            $item['image_path'] = $path;
            $updated[$index] = $item;

            $recommendation->update([
                'items' => $updated,
            ]);

            \Illuminate\Support\Facades\Log::channel('essay_pipeline')->info('reading_recommendation.pipeline_response', [
                'recommendation_id' => $recommendation->id,
                'item_index' => $index,
                'image_path' => $path,
            ]);
        }

        $state->set('reading_recommendation_items', $updated);

        return new StopEvent();
    }

    /**
     * @param array<string, string> $item
     */
    public function generateImageForItem(int $recommendationId, int $index, array $item): ?string
    {
        $prompt = $this->buildPrompt($item);
        return $this->generateImage($recommendationId, $index, $prompt);
    }

    /**
     * @param array<string, string> $item
     */
    private function buildPrompt(array $item): string
    {
        $title = trim((string) ($item['title'] ?? ''));
        $paragraph = trim((string) ($item['paragraph'] ?? ''));

        return "Create 1 child-friendly illustration in a four-panel grid (2x2). "
            . "Each panel should depict a key moment or idea from this reading recommendation. "
            . "Use a bright, warm, cartoon storybook style with clean outlines and soft shading. "
            . "No text overlays. Keep it classroom-appropriate.\n\n"
            . "Title: {$title}\n"
            . "Brief: {$paragraph}";
    }

    private function generateImage(int $recommendationId, int $index, string $prompt): ?string
    {
        try {
            OpenAiLogger::log('reading_recommendation_image', $prompt, null, [
                'phase' => 'request',
                'recommendation_id' => $recommendationId,
                'payload' => [
                    'model' => 'gpt-image-1.5',
                    'n' => 1,
                    'size' => '1024x1024',
                ],
            ]);

            $response = EssayImageAgent::make()->chat(new UserMessage($prompt));
            $payload = $response->getContent();
            $payload = is_string($payload) ? (json_decode($payload, true) ?? []) : [];
        } catch (\Throwable $exception) {
            OpenAiLogger::log('reading_recommendation_image', $prompt, null, [
                'error' => $exception->getMessage(),
            ]);
            return null;
        }

        $images = is_array($payload) ? ($payload['data'] ?? []) : [];
        if (!is_array($images) || empty($images)) {
            OpenAiLogger::log('reading_recommendation_image', $prompt, json_encode($payload), [
                'error' => 'No images returned.',
            ]);
            return null;
        }

        $base64 = $images[0]['b64_json'] ?? null;
        if (!$base64) {
            return null;
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return null;
        }

        $filename = 'item-' . ($index + 1) . '.png';
        $relativePath = 'reading_recommendations/' . $recommendationId . '/' . $filename;
        Storage::disk('public')->put($relativePath, $binary);

        OpenAiLogger::log('reading_recommendation_image', $prompt, json_encode([
            'path' => $relativePath,
        ]), [
            'model' => 'gpt-image-1.5',
        ]);

        return $relativePath;
    }
}
