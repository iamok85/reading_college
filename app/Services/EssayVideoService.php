<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EssaySong;
use App\Models\EssaySubmission;
use App\Neuron\Events\RetrieveEssaySong;
use App\Neuron\Nodes\EssaySongNode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NeuronAI\Workflow\WorkflowState;

class EssayVideoService
{
    public function downloadBinary(string $url): ?string
    {
        try {
            $response = Http::timeout(60)->get($url);
        } catch (\Throwable $exception) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->body();
    }

    public function storeVideo(int $essayId, string $binary): string
    {
        $filename = 'video-' . now()->format('YmdHis') . '-' . Str::random(6) . '.mp4';
        $relativePath = 'generated_videos/' . $essayId . '/' . $filename;
        Storage::disk('public')->put($relativePath, $binary);

        return $relativePath;
    }

    public function resolveSongPath(int $essayId): ?string
    {
        $essay = EssaySubmission::find($essayId);
        if (! $essay) {
            return null;
        }

        $song = EssaySong::where('essay_submission_id', $essayId)->first();
        if ($song && $song->status === 'ready' && $song->song_path) {
            return $song->song_path;
        }

        $lyrics = trim((string) $essay->corrected_version);
        if ($lyrics === '') {
            return null;
        }

        $now = now();
        if (! $song) {
            $song = EssaySong::create([
                'essay_submission_id' => $essay->id,
                'user_id' => $essay->user_id ?? null,
                'child_id' => $essay->child_id ?? null,
                'status' => 'pending',
                'provider' => 'suno',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $song->update([
                'status' => 'pending',
                'error_message' => null,
                'updated_at' => $now,
            ]);
        }

        try {
            $event = new RetrieveEssaySong(
                essayId: (int) $essay->id,
                title: 'Essay Song #' . $essay->id,
                lyrics: $lyrics,
            );
            $state = new WorkflowState();
            (new EssaySongNode())($event, $state);

            $payload = $state->get('song_payload') ?? [];
            $audioUrl = $payload['audio_url'] ?? null;
            $providerSongId = $payload['provider_song_id'] ?? $payload['task_id'] ?? null;

            if (! $audioUrl) {
                EssaySong::where('essay_submission_id', $essay->id)
                    ->update([
                        'status' => 'pending',
                        'provider_song_id' => (string) ($providerSongId ?? ''),
                        'updated_at' => now(),
                    ]);

                return null;
            }

            $audioResponse = Http::timeout(60)->get($audioUrl);
            if (! $audioResponse->successful()) {
                throw new \RuntimeException('Failed to download song audio.');
            }

            $filename = 'songs/essay-' . $essay->id . '-' . Str::random(6) . '.mp3';
            Storage::disk('public')->put($filename, $audioResponse->body());

            $songName = $payload['title'] ?? ('Essay Song #' . $essay->id);

            EssaySong::where('essay_submission_id', $essay->id)
                ->update([
                    'status' => 'ready',
                    'song_name' => $songName,
                    'song_path' => $filename,
                    'provider_song_id' => (string) ($providerSongId ?? ''),
                    'updated_at' => now(),
                ]);

            return $filename;
        } catch (\Throwable $exception) {
            EssaySong::where('essay_submission_id', $essay->id)
                ->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'updated_at' => now(),
                ]);

            Log::error('essay_video.song_failed', [
                'essay_id' => $essay->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    public function mergeAudioWithVideo(int $essayId, string $videoPath, string $audioPath): ?string
    {
        $ffmpeg = trim((string) shell_exec('command -v ffmpeg'));
        if ($ffmpeg === '') {
            return null;
        }

        $videoFullPath = Storage::disk('public')->path($videoPath);
        $audioFullPath = Storage::disk('public')->path($audioPath);
        if (!is_file($videoFullPath) || !is_file($audioFullPath)) {
            return null;
        }

        $outputRelative = 'generated_videos/' . $essayId . '/video-with-audio-' . Str::random(6) . '.mp4';
        $outputFull = Storage::disk('public')->path($outputRelative);

        $command = sprintf(
            '%s -y -i %s -i %s -shortest -c:v copy -c:a aac %s',
            escapeshellcmd($ffmpeg),
            escapeshellarg($videoFullPath),
            escapeshellarg($audioFullPath),
            escapeshellarg($outputFull)
        );

        $result = shell_exec($command);
        if (!is_file($outputFull)) {
            Log::warning('essay_video.merge_failed', [
                'essay_id' => $essayId,
                'command' => $command,
                'output' => $result,
            ]);
            return null;
        }

        return $outputRelative;
    }
}
