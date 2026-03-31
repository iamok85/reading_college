<?php

declare(strict_types=1);

namespace App\Neuron\Providers;

use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Providers\HttpClientOptions;

class OpenAIProxyProvider extends OpenAI
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        protected string $key,
        protected string $model,
        protected array $parameters = [],
        protected bool $strict_response = false,
        protected ?HttpClientOptions $httpOptions = null,
        ?string $baseUri = null,
    ) {
        if ($baseUri) {
            $this->baseUri = rtrim($baseUri, '/');
        }

        parent::__construct(
            $this->key,
            $this->model,
            $this->parameters,
            $this->strict_response,
            $this->httpOptions
        );
    }
}
