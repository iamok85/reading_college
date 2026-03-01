<?php

declare(strict_types=1);

namespace App\Neuron\Events;

use NeuronAI\Workflow\Event;

class RetrieveEssayVideo implements Event
{
    public function __construct(
        public int $essayId,
        public string $correctedEssay,
    ) {}
}
