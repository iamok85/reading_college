<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Neuron\Agents\ImageOcrAgent;
use App\Neuron\Events\RetrieveImageOcr;
use App\Neuron\Events\RetrievePdfOcr;
use App\Neuron\Events\RetrieveEssayCorrection;
use App\Support\OpenAiLogger;
use NeuronAI\Chat\Attachments\Image;
use NeuronAI\Chat\Enums\AttachmentContentType;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class ImageOcrNode extends Node
{
    public function __invoke(RetrieveImageOcr $event, WorkflowState $state): RetrieveImageOcr|RetrievePdfOcr|RetrieveEssayCorrection
    {
        $path = (string) ($state->get('current_image_path') ?? $event->path);
        $resolvedPath = $path;
        if ($resolvedPath !== '' && !str_starts_with($resolvedPath, DIRECTORY_SEPARATOR)) {
            $resolvedPath = \Illuminate\Support\Facades\Storage::disk('public')->path($resolvedPath);
        }
        $text = null;
    
        if (is_file($resolvedPath)) {
            $base64 = base64_encode((string) file_get_contents($path));
            $message = new UserMessage('Extract all text from this image. Return only the text, preserving line breaks.');
            $message->addAttachment(new Image($base64, AttachmentContentType::BASE64, 'image/png'));

            $response = ImageOcrAgent::make()->chat($message);
            $value = trim((string) $response->getContent());
            $text = $value !== '' ? $value : null;
            OpenAiLogger::log('image_ocr', $message->getContent(), $text);
        }

        $state->set('image_ocr', $text);
        $this->appendOcrChunk($state, $text);

        $nextImage = $this->shiftQueue($state, 'image_queue');
        if ($nextImage !== null) {
            return new RetrieveImageOcr($nextImage);
        }

        $nextPdf = $this->shiftQueue($state, 'pdf_queue');
        if ($nextPdf !== null) {
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
