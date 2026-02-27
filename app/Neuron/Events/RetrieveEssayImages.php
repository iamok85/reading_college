<?php

namespace App\Neuron\Events;

use NeuronAI\Workflow\Event;

class RetrieveEssayImages implements Event
{
    public function __construct(
        public int $essayId,
        public string $correctedEssay,
    ) {
    }
}
