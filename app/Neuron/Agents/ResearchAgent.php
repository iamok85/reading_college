<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use NeuronAI\Agent;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;

class ResearchAgent extends Agent
{
    /**
     * @param Message|array<int, Message> $messages
     */
    public function chat(Message|array $messages): Message
    {
        if (app()->environment('testing') && !empty($_ENV['FAKE_OPENAI'])) {
            return new Message(
                MessageRole::ASSISTANT,
                (string) ($_ENV['FAKE_OPENAI_TEXT'] ?? '')
            );
        }

        return parent::chat($messages);
    }

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
