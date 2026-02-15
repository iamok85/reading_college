<?php

namespace App\Neuron\Nodes;

use App\Neuron\Agents\EssayCorrectionAgent;
use App\Neuron\Events\RetrieveEssayCorrection;
use App\Support\OpenAiLogger;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class EssayCorrection extends Node
{
    public function __invoke(RetrieveEssayCorrection $event, WorkflowState $state): RetrieveEssayCorrection
    {
        $response = EssayCorrectionAgent::make()
            ->chat(new UserMessage($event->essay));

        $content = $response->getContent();
        OpenAiLogger::log('essay_correction', $event->essay, $content);

        $state->set('essay_correction', $content);

        return new RetrieveEssayCorrection($event->essay);
    }
}
