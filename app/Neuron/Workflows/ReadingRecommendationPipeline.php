<?php

declare(strict_types=1);

namespace App\Neuron\Workflows;

use App\Neuron\Nodes\ReadingRecommendationStartNode;
use App\Neuron\Nodes\ReadingRecommendationImageNode;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;

class ReadingRecommendationPipeline extends Workflow
{
    public function __construct(int $recommendationId, array $items)
    {
        $state = new WorkflowState([
            'recommendation_id' => $recommendationId,
            'recommendation_items' => array_values($items),
        ]);

        parent::__construct($state);
    }

    protected function nodes(): array
    {
        return [
            new ReadingRecommendationStartNode(),
            new ReadingRecommendationImageNode(),
        ];
    }
}
