<?php

namespace Tests\Unit;

use App\Neuron\Events\RetrievePdfOcr;
use App\Neuron\Nodes\PdfOcrNode;
use NeuronAI\Workflow\WorkflowState;
use Tests\TestCase;

class PdfOcrNodeTest extends TestCase
{
    public function test_pdf_ocr_reads_text_from_fixture(): void
    {
        if (empty($_ENV['OPENAI_API_KEY'])) {
            $this->markTestSkipped('OPENAI_API_KEY is not set.');
        }

        $path = base_path('tests/test-ocr.pdf');
        if (!is_file($path)) {
            $this->markTestSkipped('tests/test-ocr.pdf not found.');
        }

        $state = new WorkflowState([
            'current_pdf_path' => $path,
            'pdf_queue' => [],
            'ocr_chunks' => [],
            'input_text' => '',
        ]);

        $node = new PdfOcrNode();
        $node(new RetrievePdfOcr($path), $state);

        $text = $state->get('pdf_ocr');
        $this->assertIsString($text);
        $this->assertNotSame('', trim((string) $text));

        fwrite(STDOUT, "\n--- OCR OUTPUT ---\n" . trim((string) $text) . "\n");
    }
}
