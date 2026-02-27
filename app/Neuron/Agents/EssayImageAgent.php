<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class EssayImageAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        if (!empty($_ENV['OPENAI_API_KEY'])) {
            return new OpenAI(
                $_ENV['OPENAI_API_KEY'],
                $_ENV['OPENAI_CHAT_MODEL'] ?? 'gpt-image-1.5',
                httpOptions: new HttpClientOptions(timeout: 60, connectTimeout: 10)
            );
        }

        throw new \Exception('You need a valid OPENAI_API_KEY to use Chat Assistant.');
    }
}
