<?php

namespace App\Neuron\Events;

use NeuronAI\Workflow\Event;

class RetrieveEssayCorrection implements Event
{
    public function __construct(public string $essay)
    {
    }
}
