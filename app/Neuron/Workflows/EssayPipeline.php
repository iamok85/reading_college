<?php

declare(strict_types=1);

namespace App\Neuron\Workflows;

use App\Neuron\Nodes\EssayAnalysisNode;
use App\Neuron\Nodes\EssayCorrectionNode;
use App\Neuron\Nodes\EssayPipelineStartNode;
use App\Neuron\Nodes\ImageOcrNode;
use App\Neuron\Nodes\PdfOcrNode;
use App\Neuron\Observers\EssayPipelineLogger;
use Illuminate\Support\Str;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Workflow\Persistence\FilePersistence;

class EssayPipeline extends Workflow
{
    public function __construct(
        protected string $inputText,
        protected array $imagePaths,
        protected array $pdfPaths,
        protected int $userId,
        protected int $childId = 0
    ) {
        $storageDir = storage_path('ai');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $state = new WorkflowState([
            'input_text' => $this->inputText,
            'image_paths' => array_values($this->imagePaths),
            'pdf_paths' => array_values($this->pdfPaths),
            'essay_count' => 1,
            'pipeline_mode' => true,
            'user_id' => $this->userId,
            'child_id' => $this->childId,
        ]);

        $workflowId = 'essay_pipeline_' . $this->userId . '_' . Str::uuid()->toString();
        $persistence = new FilePersistence($storageDir, 'essay_pipeline_');

        parent::__construct($state, $persistence, $workflowId);

        $this->observe(new EssayPipelineLogger(
            $workflowId,
            $this->userId,
            array_values($this->imagePaths),
            array_values($this->pdfPaths)
        ));
    }

    protected function nodes(): array
    {
        return [
            new EssayPipelineStartNode(),
            new ImageOcrNode(),
            new PdfOcrNode(),
            new EssayCorrectionNode(),
            new EssayAnalysisNode(),
        ];
    }
}
