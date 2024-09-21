<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle;

use PhpLlm\LlmChain\LanguageModel;
use PhpLlm\LlmChain\Message\MessageBag;

final class TraceableLanguageModel implements LanguageModel
{
    public array $calls = [];

    public function __construct(
        private LanguageModel $llm,
        private string $name,
    ) {
    }

    public function call(MessageBag $messages, array $options = []): array
    {
        $response = $this->llm->call($messages, $options);

        $this->calls[] = [
            'messages' => clone $messages,
            'options' => $options,
            'response' => $response,
        ];

        return $response;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
