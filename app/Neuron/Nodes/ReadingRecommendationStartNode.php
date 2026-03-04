<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Neuron\Events\RetrieveReadingRecommendationImages;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Workflow\StopEvent;
use NeuronAI\Workflow\WorkflowState;

class ReadingRecommendationStartNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): RetrieveReadingRecommendationImages|StopEvent
    {
        $recommendationId = (int) ($state->get('recommendation_id') ?? 0);
        $items = $state->get('recommendation_items');
        $items = is_array($items) ? $items : [];

        if (!$recommendationId || empty($items)) {
            return new StopEvent();
        }

        return new RetrieveReadingRecommendationImages($recommendationId, $items);
    }
}
