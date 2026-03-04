<?php

declare(strict_types=1);

namespace App\Neuron\Events;

use NeuronAI\Workflow\Event;

class RetrieveReadingRecommendationImages implements Event
{
    /**
     * @param array<int, array<string, string>> $items
     */
    public function __construct(
        public int $recommendationId,
        public array $items,
    ) {}
}
