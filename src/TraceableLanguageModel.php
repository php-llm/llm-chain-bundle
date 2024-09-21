<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle;

use PhpLlm\LlmChain\LanguageModel;
use PhpLlm\LlmChain\Message\MessageBag;
use PhpLlm\LlmChain\Response\Response;

final class TraceableLanguageModel implements LanguageModel
{
    public array $calls = [];

    public function __construct(
        private LanguageModel $llm,
        private string $name,
    ) {
    }

    public function call(MessageBag $messages, array $options = []): Response
    {
        $response = $this->llm->call($messages, $options);

        $this->calls[] = [
            'messages' => clone $messages,
            'options' => $options,
            'response' => $response,
        ];

        return $response;
    }

    public function hasToolSupport(): bool
    {
        return $this->llm->hasToolSupport();
    }

    public function hasStructuredOutputSupport(): bool
    {
        return $this->llm->hasStructuredOutputSupport();
    }

    public function getName(): string
    {
        return $this->name;
    }
}
