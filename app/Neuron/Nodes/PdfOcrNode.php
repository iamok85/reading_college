<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Neuron\Agents\PdfOcrAgent;
use App\Neuron\Events\RetrievePdfOcr;
use NeuronAI\Chat\Attachments\Document;
use NeuronAI\Chat\Enums\AttachmentContentType;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class PdfOcrNode extends Node
{
    public function __invoke(RetrievePdfOcr $event, WorkflowState $state): RetrievePdfOcr
    {
        $path = $event->path;
        $text = null;

        if (is_file($path)) {
            $base64 = base64_encode((string) file_get_contents($path));
            $message = new UserMessage('Extract all text from this PDF. Return only the text, preserving line breaks.');
            $message->addAttachment(new Document($base64, AttachmentContentType::BASE64, 'application/pdf'));

            $response = PdfOcrAgent::make()->chat($message);
            $value = trim((string) $response->getContent());
            $text = $value !== '' ? $value : null;
        }

        $state->set('pdf_ocr', $text);

        return new RetrievePdfOcr($path);
    }
}
