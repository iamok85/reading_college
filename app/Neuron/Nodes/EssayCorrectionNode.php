<?php

namespace App\Neuron\Nodes;

use App\Models\EssaySubmission;
use App\Neuron\Agents\ResearchAgent;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Neuron\Events\RetrieveEssayCorrection;
use App\Neuron\Events\RetrieveEssayImages;
use App\Support\OpenAiLogger;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class EssayCorrectionNode extends Node
{
    public static function parseEssayCorrection(string $response): array
    {
        $spelling = null;
        $grammar = null;
        $corrected = null;
        $original = null;
        $normalized = str_replace('**', '', $response);

        if (preg_match('/^(?:\\d+\\)\\s*)?Original Writ(?:ing|ting):\\s*(.*?)^(?:\\d+\\)\\s*)?Spelling [Mm]istakes:\\s*(.*?)^(?:\\d+\\)\\s*)?Grammar [Mm]istakes:\\s*(.*?)^(?:\\d+\\)\\s*)?Corrected version:\\s*(.*)$/sm', $normalized, $matches)) {
            $original = trim($matches[1]);
            $spelling = trim($matches[2]);
            $grammar = trim($matches[3]);
            $corrected = trim($matches[4]);
        } elseif (preg_match('/^(?:\\d+\\)\\s*)?Spelling [Mm]istakes:\\s*(.*?)^(?:\\d+\\)\\s*)?Grammar [Mm]istakes:\\s*(.*?)^(?:\\d+\\)\\s*)?Corrected version:\\s*(.*)$/sm', $normalized, $matches)) {
            $spelling = trim($matches[1]);
            $grammar = trim($matches[2]);
            $corrected = trim($matches[3]);
        }

        if ($original !== null) {
            $original = preg_replace('/^\\s*-\\s*/m', '', $original);
            $original = preg_replace('/\\s+$/m', '', $original);
        }
        if ($spelling !== null) {
            $spelling = preg_replace('/^\\s*-\\s*/m', '', $spelling);
            $spelling = preg_replace('/\\s+$/m', '', $spelling);
        }
        if ($grammar !== null) {
            $grammar = preg_replace('/^\\s*-\\s*/m', '', $grammar);
            $grammar = preg_replace('/\\s+$/m', '', $grammar);
        }
        if ($corrected !== null) {
            $corrected = preg_replace('/\\s+$/m', '', $corrected);
        }

        return [
            'original_writing' => $original,
            'spelling_mistakes' => $spelling,
            'grammar_mistakes' => $grammar,
            'corrected_version' => $corrected,
        ];
    }

    public function __invoke(RetrieveEssayCorrection $event, WorkflowState $state): RetrieveEssayCorrection|RetrieveEssayAnalysis|RetrieveEssayImages
    {
        try {
            OpenAiLogger::log('essay_correction', $event->essay, null, [
                'phase' => 'request',
            ]);
            $response = ResearchAgent::make()
                ->setInstructions(
                    'You are a writing assistant. For every response, do all of the following in plain text:\n'
                    . '1) Spelling mistakes: list each mistake and its correction. If none, say "None".\n'
                    . '2) Grammar mistakes: list each mistake and its correction. If none, say "None".\n'
                    . '3) Corrected version: provide the corrected writing.'
                )
                ->chat(new UserMessage($event->essay));
        } catch (\Throwable $exception) {
            OpenAiLogger::log('essay_correction', $event->essay, null, [
                'error' => $exception->getMessage(),
            ]);
            throw new \RuntimeException('Timed out while calling OpenAI for essay correction.', 0, $exception);
        }

        $content = $response->getContent();
        OpenAiLogger::log('essay_correction', $event->essay, $content);

        $state->set('essay_correction', $content);
        $state->set('essay_text', $event->essay);

        if ($state->get('pipeline_mode')) {
            $parts = self::parseEssayCorrection((string) $content);
            $imagePaths = array_merge(
                (array) $state->get('image_paths'),
                (array) $state->get('pdf_paths')
            );
            $submission = EssaySubmission::create([
                'user_id' => $state->get('user_id') ?: null,
                'child_id' => $state->get('child_id') ?: null,
                'image_paths' => array_values($imagePaths),
                'uploaded_at' => now(),
                'ocr_text' => $state->get('ocr_text') ?: $event->essay,
                'original_writing' => $parts['original_writing'],
                'spelling_mistakes' => $parts['spelling_mistakes'],
                'grammar_mistakes' => $parts['grammar_mistakes'],
                'corrected_version' => $parts['corrected_version'],
                'response_text' => $content,
            ]);
            $state->set('essay_submission_id', $submission->id);
            $state->set('corrected_version', $parts['corrected_version']);

            $analysisText = trim((string) ($state->get('analysis_text') ?? $event->essay));
            if ($analysisText === '') {
                $analysisText = $event->essay;
            }
            $essayCount = (int) ($state->get('essay_count') ?? 1);
            return new RetrieveEssayImages(
                essayId: $submission->id,
                correctedEssay: $parts['corrected_version'] ?: $analysisText
            );
        }

        return new RetrieveEssayCorrection($event->essay);
    }
}
