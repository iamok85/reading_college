<?php

namespace App\Neuron\Nodes;

use App\Models\EssayAnalysis;
use App\Models\EssaySubmission;
use App\Neuron\Agents\ResearchAgent;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Support\OpenAiLogger;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\StopEvent;
use NeuronAI\Workflow\WorkflowState;

class EssayAnalysisNode extends Node
{
    public function __invoke(RetrieveEssayAnalysis $event, WorkflowState $state): RetrieveEssayAnalysis|StopEvent
    {
        $prompt = "You are an educational psychologist assistant. Based on the last {$event->essayCount} essays, "
            . "provide a brief psychological and learning analysis. Focus on strengths, challenges, emotional tone, "
            . "topics of interest, and supportive suggestions. Avoid medical diagnosis. "
            . "Format as short bullets and a final summary paragraph. Plain text only.\n\n"
            . "Essays:\n{$event->essayText}";

        $response = ResearchAgent::make()
            ->setInstructions(
                'You are an educational psychologist assistant. Provide brief psychological and learning analysis. '
                . 'Focus on strengths, challenges, emotional tone, topics of interest, and supportive suggestions. '
                . 'Avoid medical diagnosis. Format as short bullets and a final summary paragraph. Plain text only.'
            )
            ->chat(new UserMessage($prompt));

        $content = $response->getContent();
        OpenAiLogger::log('essay_analysis', $prompt, $content);

        $state->set('essay_analysis', $content);

        if ($state->get('pipeline_mode')) {
            $submissionId = $state->get('essay_submission_id');
            if ($submissionId) {
                EssaySubmission::where('id', $submissionId)->update([
                    'analysis_text' => $content,
                ]);
            }
            return new StopEvent($content);
        }

        return new RetrieveEssayAnalysis(
            essayText: $event->essayText,
            essayCount: $event->essayCount
        );
    }
}
