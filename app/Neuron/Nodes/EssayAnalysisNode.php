<?php

namespace App\Neuron\Nodes;

use App\Neuron\Agents\EssayAnalysisAgent;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Support\OpenAiLogger;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class EssayAnalysisNode extends Node
{
    public function __invoke(RetrieveEssayAnalysis $event, WorkflowState $state): RetrieveEssayAnalysis
    {
        $prompt = "You are an educational psychologist assistant. Based on the last {$event->essayCount} essays, "
            . "provide a brief psychological and learning analysis. Focus on strengths, challenges, emotional tone, "
            . "topics of interest, and supportive suggestions. Avoid medical diagnosis. "
            . "Format as short bullets and a final summary paragraph. Plain text only.\n\n"
            . "Essays:\n{$event->essayText}";

        $response = EssayAnalysisAgent::make()
            ->chat(new UserMessage($prompt));

        $content = $response->getContent();
        OpenAiLogger::log('essay_analysis', $prompt, $content);

        $state->set('essay_analysis', $content);

        return new RetrieveEssayAnalysis(
            essayText: $event->essayText,
            essayCount: $event->essayCount
        );
    }
}
