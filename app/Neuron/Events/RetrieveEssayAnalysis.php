<?php

namespace App\Neuron\Events;

use NeuronAI\Workflow\Event;

class RetrieveEssayAnalysis implements Event
{
    public function __construct(
        public string $essayText,
        public int $essayCount,
    ) {
    }
}
