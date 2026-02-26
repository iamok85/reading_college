<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Neuron\Events\RetrieveEssayCorrection;
use App\Neuron\Events\RetrieveImageOcr;
use App\Neuron\Events\RetrievePdfOcr;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Workflow\StopEvent;
use NeuronAI\Workflow\WorkflowState;

class EssayPipelineStartNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): RetrieveImageOcr|RetrievePdfOcr|RetrieveEssayCorrection|StopEvent
    {
        $imageQueue = array_values((array) $state->get('image_paths'));
        $pdfQueue = array_values((array) $state->get('pdf_paths'));

        $state->set('image_queue', $imageQueue);
        $state->set('pdf_queue', $pdfQueue);
        $state->set('ocr_chunks', []);

        if (!empty($imageQueue)) {
            $next = array_shift($imageQueue);
            $state->set('image_queue', $imageQueue);
            $state->set('current_image_path', $next);
            return new RetrieveImageOcr($next);
        }

        if (!empty($pdfQueue)) {
            $next = array_shift($pdfQueue);
            $state->set('pdf_queue', $pdfQueue);
            $state->set('current_pdf_path', $next);
            return new RetrievePdfOcr($next);
        }

        $inputText = trim((string) $state->get('input_text'));
        if ($inputText === ''&&empty($imageQueue)&&empty($pdfQueue)) {
            return new StopEvent();
        }

        return new RetrieveEssayCorrection($inputText);
    }
}
