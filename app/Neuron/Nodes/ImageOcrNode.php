<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Neuron\Agents\ImageOcrAgent;
use App\Neuron\Events\RetrieveImageOcr;
use App\Support\OpenAiLogger;
use NeuronAI\Chat\Attachments\Image;
use NeuronAI\Chat\Enums\AttachmentContentType;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class ImageOcrNode extends Node
{
    public function __invoke(RetrieveImageOcr $event, WorkflowState $state): RetrieveImageOcr
    {
        $path = $event->path;
        $text = null;

        if (is_file($path)) {
            $base64 = base64_encode((string) file_get_contents($path));
            $message = new UserMessage('Extract all text from this image. Return only the text, preserving line breaks.');
            $message->addAttachment(new Image($base64, AttachmentContentType::BASE64, 'image/png'));

            $response = ImageOcrAgent::make()->chat($message);
            $value = trim((string) $response->getContent());
            $text = $value !== '' ? $value : null;
            OpenAiLogger::log('image_ocr', $message->getContent(), $text);
        }

        $state->set('image_ocr', $text);

        return new RetrieveImageOcr($path);
    }
}
