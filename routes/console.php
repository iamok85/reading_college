<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Livewire\Chat;
use App\Models\EssaySubmission;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('essays:backfill-corrections {--dry-run}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $total = 0;
    $updated = 0;

    EssaySubmission::query()
        ->whereNotNull('response_text')
        ->where(function ($query) {
            $query->whereNull('spelling_mistakes')
                ->orWhereNull('grammar_mistakes')
                ->orWhereNull('corrected_version');
        })
        ->orderBy('id')
        ->chunkById(100, function ($essays) use ($dryRun, &$total, &$updated) {
            foreach ($essays as $essay) {
                $total++;
                $response = (string) $essay->response_text;

                $parts = Chat::parseEssayCorrection($response);
                $spelling = $parts['spelling_mistakes'];
                $grammar = $parts['grammar_mistakes'];
                $corrected = $parts['corrected_version'];

                if ($dryRun) {
                    continue;
                }

                $essay->update([
                    'spelling_mistakes' => $essay->spelling_mistakes ?? $spelling,
                    'grammar_mistakes' => $essay->grammar_mistakes ?? $grammar,
                    'corrected_version' => $essay->corrected_version ?? $corrected,
                ]);
                $updated++;
            }
        });

    if ($dryRun) {
        $this->info("Dry run complete. {$total} essays would be processed.");
        return;
    }

    $this->info("Backfill complete. {$updated} essays updated.");
})->purpose('Backfill spelling/grammar/corrected columns from response_text');

Artisan::command('essays:migrate-generated-images {--dry-run}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $total = 0;
    $migrated = 0;
    $skipped = 0;

    EssaySubmission::query()
        ->whereNotNull('generated_image_paths')
        ->orderBy('id')
        ->chunkById(100, function ($essays) use ($dryRun, &$total, &$migrated, &$skipped) {
            foreach ($essays as $essay) {
                $total++;
                $paths = $essay->generated_image_paths ?? [];
                $paths = is_array($paths) ? $paths : (json_decode((string) $paths, true) ?: []);

                if (empty($paths)) {
                    $skipped++;
                    continue;
                }

                $updatedPaths = [];
                $changed = false;

                foreach ($paths as $path) {
                    $path = (string) $path;
                    if ($path === '') {
                        continue;
                    }

                    if (str_starts_with($path, 'generated_images/')) {
                        $updatedPaths[] = $path;
                        continue;
                    }

                    $filename = basename($path);
                    $oldPath = $path;
                    $newPath = 'generated_images/' . $essay->id . '/' . $filename;
                    $updatedPaths[] = $newPath;

                    if ($dryRun) {
                        $changed = true;
                        continue;
                    }

                    if (Storage::disk('public')->exists($oldPath)) {
                        if (!Storage::disk('public')->exists($newPath)) {
                            Storage::disk('public')->move($oldPath, $newPath);
                        } else {
                            Storage::disk('public')->delete($oldPath);
                        }
                        $changed = true;
                    }
                }

                if ($changed) {
                    if (!$dryRun) {
                        $essay->update([
                            'generated_image_paths' => array_values($updatedPaths),
                        ]);
                    }
                    $migrated++;
                } else {
                    $skipped++;
                }
            }
        });

    if ($dryRun) {
        $this->info("Dry run complete. {$total} essays checked, {$migrated} would be updated.");
        return;
    }

    $this->info("Migration complete. {$migrated} essays updated, {$skipped} skipped.");
})->purpose('Move generated image files under generated_images/{essayId} and update stored paths');
