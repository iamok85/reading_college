<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;

class EssayCorrectionAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        if (!empty($_ENV['OPENAI_API_KEY'])) {
            return new OpenAI(
                $_ENV['OPENAI_API_KEY'],
                $_ENV['OPENAI_CHAT_MODEL'] ?? 'gpt-5.2-chat-latest'
            );
        }

        throw new \Exception('You need a valid OPENAI_API_KEY to use Chat Assistant.');
    }

    public function instructions(): string
    {
        return 'You are a writing assistant. For every response, do all of the following in plain text:\n'
            . '1) Spelling mistakes: list each mistake and its correction. If none, say "None".\n'
            . '2) Grammar mistakes: list each mistake and its correction. If none, say "None".\n'
            . '3) Corrected version: provide the corrected writing.';
    }
}
