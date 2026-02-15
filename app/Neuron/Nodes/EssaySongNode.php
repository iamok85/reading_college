<?php

namespace App\Neuron\Nodes;

use App\Neuron\Agents\SunoSongAgent;
use App\Neuron\Events\RetrieveEssaySong;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class EssaySongNode extends Node
{
    public function __invoke(RetrieveEssaySong $event, WorkflowState $state): RetrieveEssaySong
    {
        $payload = SunoSongAgent::make()->generate($event->title, $event->lyrics);

        $state->set('song_payload', $payload);

        return new RetrieveEssaySong(
            essayId: $event->essayId,
            title: $event->title,
            lyrics: $event->lyrics,
        );
    }
}
