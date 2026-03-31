<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ReadingRecommendation;
use App\Neuron\Events\RetrieveReadingRecommendations;
use App\Neuron\Nodes\ReadingRecommendationsNode;
use App\Neuron\Workflows\ReadingRecommendationPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NeuronAI\Workflow\WorkflowState;
use Throwable;

class ProcessReadingRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 240;
    public bool $failOnTimeout = true;

    public function __construct(
        public int $recommendationId,
        public string $essayText,
        public int $targetWords,
        public ?string $childName,
        public ?int $childAge,
        public ?string $childGender,
        public int $essayCount,
        public ?string $latestSubmissionAt
    ) {}

    public function handle(): void
    {
        $recommendation = ReadingRecommendation::find($this->recommendationId);
        if (!$recommendation) {
            return;
        }

        try {
            $recommendation->update([
                'processing_status' => 'processing',
                'processing_error' => null,
            ]);

            $event = new RetrieveReadingRecommendations(
                essayText: $this->essayText,
                targetWords: $this->targetWords,
                childName: $this->childName,
                childAge: $this->childAge,
                childGender: $this->childGender
            );

            $state = new WorkflowState();
            (new ReadingRecommendationsNode())($event, $state);

            $raw = $state->get('reading_recommendations');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded) || !isset($decoded['items']) || !is_array($decoded['items'])) {
                throw new \RuntimeException('Failed to refresh readings.');
            }

            $recommendationLinks = array_map(function (array $item): array {
                return [
                    'title' => (string) ($item['title'] ?? ''),
                    'type' => (string) ($item['type'] ?? 'Book'),
                    'paragraph' => (string) ($item['paragraph'] ?? ''),
                ];
            }, $decoded['items']);

            $recommendationLinks = array_slice($recommendationLinks, 0, 5);

            $recommendation->update([
                'essay_count' => $this->essayCount,
                'last_submission_at' => $this->latestSubmissionAt,
                'items' => $recommendationLinks,
                'processing_status' => 'processing_images',
                'processing_error' => null,
            ]);

            $pipeline = new ReadingRecommendationPipeline($recommendation->id, $recommendationLinks);
            foreach ($pipeline->run() as $workflowEvent) {
                // Drain workflow
            }

            $recommendation->refresh();
            $recommendation->update([
                'processing_status' => 'completed',
                'processing_error' => null,
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($recommendation, $exception->getMessage());
        }
    }

    public function failed(?Throwable $exception): void
    {
        $recommendation = ReadingRecommendation::find($this->recommendationId);
        if (!$recommendation) {
            return;
        }

        $this->markFailed($recommendation, $exception?->getMessage() ?: 'Reading recommendation refresh failed.');
    }

    private function markFailed(ReadingRecommendation $recommendation, string $message): void
    {
        $recommendation->update([
            'processing_status' => 'failed',
            'processing_error' => $message,
        ]);
    }
}
