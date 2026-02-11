<?php

namespace App\Neuron\Events;

use NeuronAI\Workflow\Event;

class RetrieveReadingRecommendations implements Event
{
    public function __construct(
        public string $essayText,
        public int $targetWords,
        public ?string $childName = null,
        public ?int $childAge = null,
        public ?string $childGender = null,
    ) {
    }
}
