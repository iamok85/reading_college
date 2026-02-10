<?php

namespace App\Neuron\Events;

use NeuronAI\Workflow\Event;

class RetrieveImageOcr implements Event
{
    public function __construct(public string $path)
    {
    }
}
