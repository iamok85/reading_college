<?php

declare(strict_types=1);

namespace App\Neuron\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\OpenAI\MessageMapper;
use NeuronAI\Providers\OpenAI\ToolPayloadMapper;
use NeuronAI\Providers\ToolPayloadMapperInterface;
use NeuronAI\Tools\ToolInterface;
use RuntimeException;
use Generator;

class OpenAIVideoProvider implements AIProviderInterface
{
    protected string $baseUri = 'https://api.openai.com/v1';
    protected ?string $system = null;
    protected Client $client;

    protected MessageMapperInterface $messageMapper;
    protected ToolPayloadMapperInterface $toolPayloadMapper;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        protected string $key,
        protected string $model,
        protected array $parameters = [],
        protected ?HttpClientOptions $httpOptions = null,
    ) {
        if (!empty($_ENV['OPENAI_API_BASE'])) {
            $this->baseUri = rtrim((string) $_ENV['OPENAI_API_BASE'], '/');
        }
        $config = [
            'base_uri' => rtrim($this->baseUri, '/') . '/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->key,
            ],
        ];

        if ($this->httpOptions instanceof HttpClientOptions) {
            if ($this->httpOptions->timeout !== null) {
                $config['timeout'] = $this->httpOptions->timeout;
            }
            if ($this->httpOptions->connectTimeout !== null) {
                $config['connect_timeout'] = $this->httpOptions->connectTimeout;
            }
        }

        $this->client = new Client($config);
    }

    public function systemPrompt(?string $prompt): AIProviderInterface
    {
        $this->system = $prompt;
        return $this;
    }

    /**
     * @param ToolInterface[] $tools
     */
    public function setTools(array $tools): AIProviderInterface
    {
        return $this;
    }

    public function messageMapper(): MessageMapperInterface
    {
        return $this->messageMapper ?? $this->messageMapper = new MessageMapper();
    }

    public function toolPayloadMapper(): ToolPayloadMapperInterface
    {
        return $this->toolPayloadMapper ?? $this->toolPayloadMapper = new ToolPayloadMapper();
    }

    /**
     * @param Message[] $messages
     */
    public function chat(array $messages): Message
    {
        return $this->chatAsync($messages)->wait();
    }

    /**
     * @param Message[] $messages
     */
    public function chatAsync(array $messages): PromiseInterface
    {
        $prompt = $this->extractPrompt($messages);

        try {
            $response = $this->client->post('videos', [
                'json' => [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'seconds' => (string) ($this->parameters['seconds'] ?? '12'),
                    'size' => $this->parameters['size'] ?? '1280x720',
                ],
            ]);
        } catch (\Throwable $exception) {
            return Create::rejectionFor($exception);
        }

        $payload = (string) $response->getBody();

        return Create::promiseFor(new Message(MessageRole::ASSISTANT, $payload));
    }

    public function stream(array|string $messages, callable $executeToolsCallback): Generator
    {
        throw new RuntimeException('Streaming is not supported for video generation.');
    }

    public function structured(array $messages, string $class, array $response_schema): Message
    {
        throw new RuntimeException('Structured responses are not supported for video generation.');
    }

    public function setClient(Client $client): AIProviderInterface
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param Message[] $messages
     */
    private function extractPrompt(array $messages): string
    {
        $last = end($messages);
        if ($last instanceof Message) {
            $content = $last->getContent();
            return is_string($content) ? $content : json_encode($content);
        }

        return '';
    }
}
