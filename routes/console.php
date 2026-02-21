<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
