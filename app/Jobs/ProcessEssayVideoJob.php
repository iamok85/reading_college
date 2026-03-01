<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EssaySubmission;
use App\Services\EssayVideoService;
use App\Support\OpenAiLogger;
use App\Neuron\Agents\EssayVideoAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use DateTimeInterface;

class ProcessEssayVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(
        public int $essayId,
        public string $jobId,
        public string $prompt,
    ) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(20);
    }

    public function handle(EssayVideoService $service): void
    {
        try {
            Log::channel('openai')->info('essay_video.job_start', [
                'essay_id' => $this->essayId,
                'job_id' => $this->jobId,
            ]);

            $essay = EssaySubmission::find($this->essayId);
            if (!$essay) {
                Log::channel('openai')->warning('essay_video.job_missing_essay', [
                    'essay_id' => $this->essayId,
                ]);
                return;
            }

            $data = EssayVideoAgent::make()->fetchStatus($this->jobId);
            if ($data === null) {
                Log::channel('openai')->warning('essay_video.job_poll_failed', [
                    'essay_id' => $this->essayId,
                    'job_id' => $this->jobId,
                ]);
                $this->release((int) ($_ENV['OPENAI_VIDEO_POLL_INTERVAL'] ?? 10));
                return;
            }
            OpenAiLogger::log('essay_video_poll', $this->prompt, json_encode($data), [
                'essay_id' => $this->essayId,
                'job_id' => $this->jobId,
            ]);

            $status = $data['status'] ?? $data['data']['status'] ?? null;
            $progress = (int) ($data['progress'] ?? $data['data']['progress'] ?? 0);
            $videoUrl = $this->extractVideoUrl($data);

            $essay->update([
                'video_status' => $status ?: $essay->video_status,
                'video_progress' => $progress ?: $essay->video_progress,
                'video_url' => $videoUrl ?: $essay->video_url,
                'video_error' => $data['error']['message'] ?? $essay->video_error,
            ]);
            Log::channel('openai')->info('essay_video.job_status', [
                'essay_id' => $this->essayId,
                'job_id' => $this->jobId,
                'status' => $status,
                'progress' => $progress,
                'video_url' => $videoUrl,
            ]);

            if ($status === 'failed') {
                OpenAiLogger::log('essay_video', $this->prompt, json_encode($data), [
                    'error' => 'Video generation failed.',
                ]);
                Log::channel('openai')->warning('essay_video.job_failed', [
                    'essay_id' => $this->essayId,
                    'job_id' => $this->jobId,
                ]);
                return;
            }

            if ($status === 'completed' && !$videoUrl) {
                $essay->update([
                    'video_error' => 'Video completed but URL missing. Check OpenAI response.',
                    'video_progress' => 95,
                ]);
                OpenAiLogger::log('essay_video', $this->prompt, json_encode($data), [
                    'error' => 'Video completed but URL missing.',
                ]);
                Log::channel('openai')->warning('essay_video.job_missing_url', [
                    'essay_id' => $this->essayId,
                    'job_id' => $this->jobId,
                ]);
                return;
            }

            if (!$videoUrl) {
                Log::channel('openai')->info('essay_video.job_pending', [
                    'essay_id' => $this->essayId,
                    'job_id' => $this->jobId,
                ]);
                $this->release((int) ($_ENV['OPENAI_VIDEO_POLL_INTERVAL'] ?? 10));
                return;
            }

            $videoBinary = $service->downloadBinary($videoUrl);
            if ($videoBinary === null) {
                Log::channel('openai')->warning('essay_video.job_download_failed', [
                    'essay_id' => $this->essayId,
                    'job_id' => $this->jobId,
                    'video_url' => $videoUrl,
                ]);
                $this->release((int) ($_ENV['OPENAI_VIDEO_POLL_INTERVAL'] ?? 10));
                return;
            }

            $videoPath = $service->storeVideo($this->essayId, $videoBinary);
            $audioPath = $service->resolveSongPath($this->essayId);

            if ($audioPath) {
                $merged = $service->mergeAudioWithVideo($this->essayId, $videoPath, $audioPath);
                if ($merged) {
                    $videoPath = $merged;
                }
            }

            $essay->update([
                'generated_video_path' => $videoPath,
                'video_status' => 'ready',
                'video_progress' => 100,
            ]);
            Log::channel('openai')->info('essay_video.job_complete', [
                'essay_id' => $this->essayId,
                'job_id' => $this->jobId,
                'video_path' => $videoPath,
            ]);
        } catch (Throwable $exception) {
            $essay = EssaySubmission::find($this->essayId);
            if ($essay) {
                $essay->update([
                    'video_error' => $exception->getMessage(),
                ]);
            }
            Log::channel('openai')->error('essay_video.job_exception', [
                'essay_id' => $this->essayId,
                'job_id' => $this->jobId,
                'error' => $exception->getMessage(),
            ]);
            $this->release((int) ($_ENV['OPENAI_VIDEO_POLL_INTERVAL'] ?? 10));
        }
    }

    private function extractVideoUrl(array $data): ?string
    {
        $candidates = [
            $data['video_url'] ?? null,
            $data['url'] ?? null,
            $data['data'][0]['url'] ?? null,
            $data['data'][0]['video_url'] ?? null,
            $data['data']['url'] ?? null,
            $data['data']['video_url'] ?? null,
            $data['output'][0]['url'] ?? null,
            $data['output'][0]['video_url'] ?? null,
            $data['data']['output'][0]['url'] ?? null,
            $data['data']['output'][0]['video_url'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }
}
