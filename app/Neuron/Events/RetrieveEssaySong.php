<?php

namespace App\Neuron\Events;

use NeuronAI\Workflow\Event;

class RetrieveEssaySong implements Event
{
    public function __construct(
        public int $essayId,
        public string $title,
        public string $lyrics,
    ) {
    }
}
