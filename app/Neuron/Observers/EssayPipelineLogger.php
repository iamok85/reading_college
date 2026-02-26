<?php

declare(strict_types=1);

namespace App\Neuron\Observers;

use Illuminate\Support\Facades\Log;
use NeuronAI\Observability\Events\AgentError;
use NeuronAI\Observability\Events\WorkflowEnd;
use NeuronAI\Observability\Events\WorkflowNodeEnd;
use NeuronAI\Observability\Events\WorkflowNodeStart;
use NeuronAI\Observability\Events\WorkflowStart;
use NeuronAI\Workflow\WorkflowInterrupt;
use SplObserver;
use SplSubject;

class EssayPipelineLogger implements SplObserver
{
    public function __construct(
        private readonly string $workflowId,
        private readonly int $userId,
        private readonly array $imagePaths = [],
        private readonly array $pdfPaths = []
    ) {
    }

    public function update(SplSubject $subject, string $event = '*', mixed $data = null): void
    {
        $context = [
            'workflow_id' => $this->workflowId,
            'user_id' => $this->userId,
            'event' => $event,
        ];

        if ($data instanceof WorkflowNodeStart || $data instanceof WorkflowNodeEnd) {
            $context['node'] = $data->node;
        }

        if ($data instanceof WorkflowInterrupt) {
            $context['interrupt'] = $data->getData();
        }

        if ($data instanceof AgentError) {
            $context['error'] = $data->exception->getMessage();
            $context['exception'] = $data->exception::class;
            Log::channel('essay_pipeline')->error('workflow.error', $context);
            return;
        }

        if ($data instanceof WorkflowStart) {
            $context['image_paths'] = $this->imagePaths;
            $context['pdf_paths'] = $this->pdfPaths;
            Log::channel('essay_pipeline')->info('workflow.start', $context);
            return;
        }

        if ($data instanceof WorkflowEnd) {
            Log::channel('essay_pipeline')->info('workflow.end', $context);
            return;
        }

        if ($data instanceof WorkflowNodeStart) {
            Log::channel('essay_pipeline')->info('workflow.node_start', $context);
            return;
        }

        if ($data instanceof WorkflowNodeEnd) {
            if ($data->node === 'App\\Neuron\\Nodes\\ImageOcrNode') {
                $context['image_ocr'] = $data->state->get('image_ocr');
                $context['ocr_text'] = $data->state->get('ocr_text');
            }
            if ($data->node === 'App\\Neuron\\Nodes\\PdfOcrNode') {
                $context['pdf_ocr'] = $data->state->get('pdf_ocr');
                $context['ocr_text'] = $data->state->get('ocr_text');
            }
            if ($data->node === 'App\\Neuron\\Nodes\\EssayCorrectionNode') {
                $context['essay_correction'] = $data->state->get('essay_correction');
            }
            Log::channel('essay_pipeline')->info('workflow.node_end', $context);
            return;
        }

        if ($data instanceof WorkflowInterrupt) {
            Log::channel('essay_pipeline')->warning('workflow.interrupt', $context);
            return;
        }

        Log::channel('essay_pipeline')->info('workflow.event', $context);
    }
}
