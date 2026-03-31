<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EssaySubmission;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Neuron\Events\RetrieveEssayCorrection;
use App\Neuron\Events\RetrieveEssayImages;
use App\Neuron\Events\RetrieveImageOcr;
use App\Neuron\Events\RetrievePdfOcr;
use App\Neuron\Nodes\EssayAnalysisNode;
use App\Neuron\Nodes\EssayCorrectionNode;
use App\Neuron\Nodes\EssayImageNode;
use App\Neuron\Nodes\EssayPipelineStartNode;
use App\Neuron\Nodes\ImageOcrNode;
use App\Neuron\Nodes\PdfOcrNode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Workflow\WorkflowState;
use Throwable;

class ProcessEssaySubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 240;
    public bool $failOnTimeout = true;

    public function __construct(
        public int $essaySubmissionId,
        public string $inputText
    ) {}

    public function handle(): void
    {
        $submission = EssaySubmission::find($this->essaySubmissionId);
        if (!$submission) {
            return;
        }

        try {
            $submission->update([
                'processing_status' => 'processing',
                'processing_error' => null,
                'processing_completed_at' => null,
            ]);

            $composedInput = trim($this->inputText);
            $storedPaths = $this->resolveStoredPaths($submission->image_paths);
            $imagePaths = [];
            $pdfPaths = [];

            foreach ($storedPaths as $path) {
                if (Str::endsWith(Str::lower($path), '.pdf')) {
                    $pdfPaths[] = $path;
                    continue;
                }
                $imagePaths[] = $path;
            }

            if (!empty($imagePaths) || !empty($pdfPaths)) {
                $submission->update([
                    'processing_status' => 'processing_ocr',
                ]);

                $ocrState = new WorkflowState([
                    'input_text' => $composedInput,
                    'image_paths' => $imagePaths,
                    'pdf_paths' => $pdfPaths,
                ]);

                $event = (new EssayPipelineStartNode())(new StartEvent(), $ocrState);
                while ($event instanceof RetrieveImageOcr || $event instanceof RetrievePdfOcr) {
                    if ($event instanceof RetrieveImageOcr) {
                        $event = (new ImageOcrNode())($event, $ocrState);
                        continue;
                    }

                    if ($event instanceof RetrievePdfOcr) {
                        $event = (new PdfOcrNode())($event, $ocrState);
                    }
                }

                $ocrText = trim((string) ($ocrState->get('ocr_text') ?? ''));
                if ($ocrText === '') {
                    throw new \RuntimeException('Unable to extract text from the uploaded files.');
                }

                $submission->update([
                    'ocr_text' => $ocrText,
                ]);
                $composedInput = $ocrText;
            } elseif ($composedInput === '') {
                throw new \RuntimeException('No essay text was provided.');
            }

            $submission->update([
                'processing_status' => 'processing_correction',
            ]);

            $correctionState = new WorkflowState([
                'pipeline_mode' => false,
                'user_id' => (int) $submission->user_id,
                'child_id' => (int) ($submission->child_id ?? 0),
                'essay_submission_id' => (int) $submission->id,
            ]);

            (new EssayCorrectionNode())(new RetrieveEssayCorrection($composedInput), $correctionState);
            $correction = trim((string) ($correctionState->get('essay_correction') ?? ''));

            if ($correction === '') {
                throw new \RuntimeException('Essay correction failed.');
            }

            $parts = EssayCorrectionNode::parseEssayCorrection($correction);
            $correctedEssay = trim((string) ($parts['corrected_version'] ?? ''));
            if ($correctedEssay === '') {
                $correctedEssay = $composedInput;
            }

            $submission->update([
                'ocr_text' => $submission->ocr_text ?: $composedInput,
                'original_writing' => $parts['original_writing'],
                'spelling_mistakes' => $parts['spelling_mistakes'],
                'grammar_mistakes' => $parts['grammar_mistakes'],
                'corrected_version' => $parts['corrected_version'],
                'response_text' => $correction,
            ]);

            $submission->update([
                'processing_status' => 'processing_images',
            ]);

            $this->generateImages($submission, $correctedEssay);

            $submission->update([
                'processing_status' => 'processing_analysis',
            ]);

            $analysisState = new WorkflowState([
                'pipeline_mode' => false,
                'user_id' => (int) $submission->user_id,
                'child_id' => (int) ($submission->child_id ?? 0),
                'essay_submission_id' => (int) $submission->id,
            ]);
            (new EssayAnalysisNode())(new RetrieveEssayAnalysis($correctedEssay, 1), $analysisState);

            $analysis = trim((string) ($analysisState->get('essay_analysis') ?? ''));
            if ($analysis === '') {
                throw new \RuntimeException('Essay analysis failed.');
            }

            $submission->update([
                'analysis_text' => $analysis,
                'processing_status' => 'completed',
                'processing_error' => null,
                'processing_completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($submission, $exception->getMessage());
        }
    }

    public function failed(?Throwable $exception): void
    {
        $submission = EssaySubmission::find($this->essaySubmissionId);
        if (!$submission) {
            return;
        }

        $message = $exception?->getMessage() ?: 'Essay processing failed.';
        $this->markFailed($submission, $message);
    }

    private function resolveStoredPaths(mixed $paths): array
    {
        if (is_array($paths)) {
            return array_values(array_filter($paths, fn ($item) => is_string($item) && $item !== ''));
        }

        if (is_string($paths) && $paths !== '') {
            $decoded = json_decode($paths, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($item) => is_string($item) && $item !== ''));
            }
        }

        return [];
    }

    private function markFailed(EssaySubmission $submission, string $message): void
    {
        $submission->update([
            'processing_status' => 'failed',
            'processing_error' => $message,
            'processing_completed_at' => null,
        ]);
    }

    private function generateImages(EssaySubmission $submission, string $correctedEssay): void
    {
        try {
            $imageState = new WorkflowState([
                'pipeline_mode' => false,
            ]);

            (new EssayImageNode())(
                new RetrieveEssayImages((int) $submission->id, $correctedEssay),
                $imageState
            );
        } catch (Throwable) {
            // Image generation should not fail the full essay pipeline.
        }
    }
}
