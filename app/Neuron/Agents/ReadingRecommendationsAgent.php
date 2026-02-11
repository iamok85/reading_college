<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;

class ReadingRecommendationsAgent extends Agent
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
}
