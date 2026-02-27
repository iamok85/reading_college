<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Neuron\Agents\ResearchAgent;
use App\Neuron\Events\RetrievePdfOcr;
use App\Neuron\Events\RetrieveEssayCorrection;
use App\Support\OpenAiLogger;
use NeuronAI\Chat\Attachments\Document;
use NeuronAI\Chat\Enums\AttachmentContentType;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class PdfOcrNode extends Node
{
    public function __invoke(RetrievePdfOcr $event, WorkflowState $state): RetrievePdfOcr|RetrieveEssayCorrection
    {
        $path = (string) ($state->get('current_pdf_path') ?? $event->path);
        $resolvedPath = $path;
        if ($resolvedPath !== '' && !str_starts_with($resolvedPath, DIRECTORY_SEPARATOR)) {
            $resolvedPath = \Illuminate\Support\Facades\Storage::disk('public')->path($resolvedPath);
        }
        $text = null;
    
        if (is_file($resolvedPath)) {
            $base64 = base64_encode((string) file_get_contents($resolvedPath));
            $message = new UserMessage('Extract all text from this PDF. Return only the text, preserving line breaks.');
            $message->addAttachment(new Document($base64, AttachmentContentType::BASE64, 'application/pdf'));
            try {
                OpenAiLogger::log('pdf_ocr', $message->getContent(), null, [
                    'phase' => 'request',
                ]);
                $response = ResearchAgent::make()->chat($message);
                $value = trim((string) $response->getContent());
                $text = $value !== '' ? $value : null;
    
                OpenAiLogger::log('pdf_ocr', $message->getContent(), $text);
            } catch (\Throwable $exception) {
                OpenAiLogger::log('pdf_ocr', $message->getContent(), null, [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $state->set('pdf_ocr', $text);
        $this->appendOcrChunk($state, $text);

        $nextPdf = $this->shiftQueue($state, 'pdf_queue');
        if ($nextPdf !== null) {
            $state->set('current_pdf_path', $nextPdf);
            return new RetrievePdfOcr($nextPdf);
        }

        $essayText = $this->resolveEssayText($state);
        return new RetrieveEssayCorrection($essayText);
    }

    private function appendOcrChunk(WorkflowState $state, ?string $text): void
    {
        $chunks = $state->get('ocr_chunks');
        $chunks = is_array($chunks) ? $chunks : [];

        if (is_string($text) && trim($text) !== '') {
            $chunks[] = trim($text);
        }

        $state->set('ocr_chunks', $chunks);
        $state->set('ocr_text', trim(implode("\n\n", $chunks)));
    }

    private function shiftQueue(WorkflowState $state, string $key): ?string
    {
        $queue = $state->get($key);
        $queue = is_array($queue) ? $queue : null;

        if (!$queue || empty($queue)) {
            return null;
        }

        $next = array_shift($queue);
        $state->set($key, $queue);

        return is_string($next) ? $next : null;
    }

    private function resolveEssayText(WorkflowState $state): string
    {
        $ocrText = trim((string) ($state->get('ocr_text') ?? ''));
        if ($ocrText !== '') {
            return $ocrText;
        }

        return trim((string) ($state->get('input_text') ?? ''));
    }
}
