<?php

namespace App\Neuron\Nodes;

use App\Neuron\Agents\ResearchAgent;
use App\Neuron\Events\RetrieveReadingRecommendations;
use App\Support\OpenAiLogger;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class ReadingRecommendationsNode extends Node
{
    public function __invoke(RetrieveReadingRecommendations $event, WorkflowState $state): RetrieveReadingRecommendations
    {
        $childContext = [];
        if ($event->childName) {
            $childContext[] = "Child name: {$event->childName}.";
        }
        if ($event->childAge !== null) {
            $childContext[] = "Child age: {$event->childAge}.";
        }
        if ($event->childGender) {
            $childContext[] = "Child gender: {$event->childGender}.";
        }

        $prompt = "You are a kids reading coach. Based on the child's essays and profile, "
            . "return a JSON object with a key \"items\" containing an array of 5 recommendations "
            . "(books and movies). For each item include: type (\"Book\" or \"Movie\"), title, "
            . "and paragraph. Each paragraph must be a funny story that teaches knowledge and has "
            . "approximately {$event->targetWords} words (+/- 15%). "
            . "Use age-appropriate vocabulary. Do not include markdown or extra keys.\n\n"
            . implode(' ', $childContext) . "\n\n"
            . "Essays:\n{$event->essayText}";

        $response = ResearchAgent::make()
            ->setInstructions(
                'You are a kids reading coach. Return a JSON object with key "items" containing an array of 5 '
                . 'recommendations (books and movies). Each item includes: type ("Book" or "Movie"), title, '
                . 'and paragraph. Each paragraph must be a funny story that teaches knowledge and matches the target '
                . 'word count (+/- 15%). Use age-appropriate vocabulary. Do not include markdown or extra keys.'
            )
            ->chat(new UserMessage($prompt));

        $content = $response->getContent();
        OpenAiLogger::log('reading_recommendations', $prompt, $content);

        $state->set('reading_recommendations', $content);

        return new RetrieveReadingRecommendations(
            essayText: $event->essayText,
            targetWords: $event->targetWords,
            childName: $event->childName,
            childAge: $event->childAge,
            childGender: $event->childGender
        );
    }
}
