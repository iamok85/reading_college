<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SharedEssay;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EssayMagazine
{
    private const MAX_IMAGE_BYTES = 2 * 1024 * 1024;
    private const MAX_IMAGE_DIMENSION = 1200;

    public function download(Request $request)
    {
        $data = $request->validate([
            'selected' => ['nullable', 'array'],
            'selected.*' => ['integer'],
            'child_name' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        if (!empty($data['selected'])) {
            $items = SharedEssay::whereIn('id', $data['selected'])
                ->orderByDesc('shared_at')
                ->get();
        } else {
            $query = SharedEssay::query()->orderByDesc('shared_at');

            if (!empty($data['child_name'])) {
                $name = trim((string) $data['child_name']);
                if ($name !== '') {
                    $query->where('child_name', 'like', '%' . $name . '%');
                }
            }

            if (!empty($data['date_from'])) {
                $query->whereDate('shared_at', '>=', $data['date_from']);
            }

            if (!empty($data['date_to'])) {
                $query->whereDate('shared_at', '<=', $data['date_to']);
            }

            $items = $query->get();
        }

        if ($items->isEmpty()) {
            return back()->withErrors([
                'selected' => 'No matching shared essays found.',
            ]);
        }

        $maxItems = 8;
        $totalItems = $items->count();
        $items = $items->take($maxItems);

        $magazineItems = $items->map(function (SharedEssay $item): array {
            $imageData = null;
            if ($item->image_path) {
                $fullPath = Storage::disk('public')->path($item->image_path);
                if (is_file($fullPath)) {
                    $imageData = $this->encodeImage($fullPath);
                }
            }

            return [
                'child_name' => $item->child_name ?? 'Child',
                'child_age' => $item->child_age,
                'shared_at' => $item->shared_at,
                'corrected_text' => $item->corrected_text ?? '',
                'image_data' => $imageData,
            ];
        });

        try {
            $pdf = Pdf::loadView('pdf.feeds-magazine', [
                'items' => $magazineItems,
                'generatedAt' => now(),
                'totalItems' => $totalItems,
                'maxItems' => $maxItems,
            ])->setPaper('a4');
        } catch (\Throwable $exception) {
            Log::error('feeds.magazine.download.failed', [
                'error' => $exception->getMessage(),
            ]);
            abort(500, 'Unable to generate magazine PDF.');
        }

        $filename = 'feeds-magazine-' . now()->format('YmdHis') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    private function encodeImage(string $fullPath): ?string
    {
        $raw = file_get_contents($fullPath);
        if ($raw === false) {
            return null;
        }

        $fileSize = strlen($raw);
        if ($fileSize <= self::MAX_IMAGE_BYTES) {
            $mime = mime_content_type($fullPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        if (!function_exists('imagecreatefromstring')) {
            $mime = mime_content_type($fullPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        $source = @imagecreatefromstring($raw);
        if ($source === false) {
            $mime = mime_content_type($fullPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxDim = max($width, $height);
        $scale = $maxDim > self::MAX_IMAGE_DIMENSION ? self::MAX_IMAGE_DIMENSION / $maxDim : 1;
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($resized === false) {
            imagedestroy($source);
            $mime = mime_content_type($fullPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($source);

        ob_start();
        imagejpeg($resized, null, 82);
        $jpeg = ob_get_clean();
        imagedestroy($resized);

        if ($jpeg === false) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode($jpeg);
    }
}
