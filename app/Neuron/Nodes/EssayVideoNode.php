<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Models\EssaySubmission;
use App\Neuron\Events\RetrieveEssayVideo;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Support\OpenAiLogger;
use App\Neuron\Agents\EssayVideoAgent;
use App\Jobs\ProcessEssayVideoJob;
use App\Services\EssayVideoService;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Chat\Messages\UserMessage;

class EssayVideoNode extends Node
{
    private const DEFAULT_SECONDS = 12;
    private const DEFAULT_SIZE = '1280x720';
    private const POLL_INTERVAL = 5;

    public function __invoke(RetrieveEssayVideo $event, WorkflowState $state): RetrieveEssayVideo|RetrieveEssayAnalysis
    {
        $prompt = $this->buildPrompt($event->correctedEssay);
        OpenAiLogger::log('essay_video', $prompt, null, [
            'phase' => 'request',
            'essay_id' => $event->essayId,
            'payload' => [
                'model' => $this->model(),
                'seconds' => self::DEFAULT_SECONDS,
                'size' => self::DEFAULT_SIZE,
            ],
        ]);

        $videoPath = $this->generateVideo($event->essayId, $prompt);
        if ($videoPath) {
            EssaySubmission::whereKey($event->essayId)->update([
                'generated_video_path' => $videoPath,
            ]);
        }

        $state->set('generated_video_path', $videoPath);

        if ($state->get('pipeline_mode')) {
            $analysisText = trim((string) ($state->get('analysis_text') ?? $event->correctedEssay));
            if ($analysisText === '') {
                $analysisText = $event->correctedEssay;
            }
            $essayCount = (int) ($state->get('essay_count') ?? 1);
            return new RetrieveEssayAnalysis($analysisText, max(1, $essayCount));
        }

        return new RetrieveEssayVideo($event->essayId, $event->correctedEssay);
    }

    private function buildPrompt(string $correctedEssay): string
    {
        $trimmed = trim($correctedEssay);
        return "Create a 12-second animated story in a warm, child-friendly illustration style. "
            . "No real people or celebrity likenesses. No text overlays. "
            . "Use soft, bright colors, gentle motion, and cozy classroom-appropriate themes.\n\n"
            . "Scenes (3 seconds each):\n"
            . "1) Beginning of the story.\n"
            . "2) Middle event.\n"
            . "3) Key action moment.\n"
            . "4) Ending or takeaway.\n\n"
            . "Story summary:\n" . $trimmed;
    }

    private function generateVideo(int $essayId, string $prompt): ?string
    {
        try {
            $response = EssayVideoAgent::make()->chat(new UserMessage($prompt));
        } catch (\Throwable $exception) {
            OpenAiLogger::log('essay_video', $prompt, null, [
                'error' => $exception->getMessage(),
            ]);
            return null;
        }

        $payload = $response->getContent();
        $data = is_string($payload) ? (json_decode($payload, true) ?? []) : [];
        $videoUrl = $this->extractVideoUrl($data);
        $jobId = $data['id'] ?? $data['data']['id'] ?? null;

        EssaySubmission::whereKey($essayId)->update([
            'video_job_id' => $jobId ? (string) $jobId : null,
            'video_status' => $data['status'] ?? 'queued',
            'video_progress' => (int) ($data['progress'] ?? 0),
            'video_error' => null,
            'video_url' => $videoUrl,
        ]);

        if (! $videoUrl && $jobId) {
            ProcessEssayVideoJob::dispatch($essayId, (string) $jobId, $prompt)
                ->delay(now()->addSeconds((int) ($_ENV['OPENAI_VIDEO_POLL_INTERVAL'] ?? 10)));
            return null;
        }

        if (! $videoUrl) {
            OpenAiLogger::log('essay_video', $prompt, json_encode($data), [
                'error' => 'No video URL returned.',
            ]);
            return null;
        }

        $service = new EssayVideoService();
        $videoBinary = $service->downloadBinary($videoUrl);
        if ($videoBinary === null) {
            OpenAiLogger::log('essay_video', $prompt, $videoUrl, [
                'error' => 'Unable to download video.',
            ]);
            return null;
        }

        $videoPath = $service->storeVideo($essayId, $videoBinary);
        $audioPath = $service->resolveSongPath($essayId);

        if ($audioPath) {
            $merged = $service->mergeAudioWithVideo($essayId, $videoPath, $audioPath);
            if ($merged) {
                $videoPath = $merged;
            }
        }

        OpenAiLogger::log('essay_video', $prompt, json_encode([
            'video_path' => $videoPath,
            'audio_path' => $audioPath,
        ]), [
            'model' => $this->model(),
        ]);

        return $videoPath;
    }

    private function extractVideoUrl(array $data): ?string
    {
        $candidates = [
            $data['video_url'] ?? null,
            $data['url'] ?? null,
            $data['data'][0]['url'] ?? null,
            $data['data'][0]['video_url'] ?? null,
            $data['output'][0]['url'] ?? null,
            $data['output'][0]['video_url'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }


    private function model(): string
    {
        return (string) ($_ENV['OPENAI_VIDEO_MODEL'] ?? 'sora-2');
    }
}
