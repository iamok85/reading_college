<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;

class ImageOcrAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        if (!empty($_ENV['OPENAI_API_KEY'])) {
            return new OpenAI(
                $_ENV['OPENAI_API_KEY'],
                $_ENV['OPENAI_OCR_MODEL'] ?? 'gpt-5.2-chat-latest'
            );
        }

        throw new \Exception('You need a valid OPENAI_API_KEY to use Image OCR.');
    }

    public function instructions(): string
    {
        return 'You are an OCR assistant. Extract all text from the image and return only the text, preserving line breaks. Do not add any suggestions, explanations, or extra words.';
    }
}
