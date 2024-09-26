<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle;

use PhpLlm\LlmChain\LanguageModel;
use PhpLlm\LlmChain\Message\MessageBag;
use PhpLlm\LlmChain\Response\Response;

/**
 * @phpstan-type LlmCallData array{
 *     messages: MessageBag,
 *     options: array<string, mixed>,
 *     response: Response,
 * }
 */
final class TraceableLanguageModel implements LanguageModel
{
    /**
     * @var list<LlmCallData>
     */
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

    public function supportsToolCalling(): bool
    {
        return $this->llm->supportsToolCalling();
    }

    public function supportsImageInput(): bool
    {
        return $this->llm->supportsImageInput();
    }

    public function supportsStructuredOutput(): bool
    {
        return $this->llm->supportsStructuredOutput();
    }

    public function getName(): string
    {
        return $this->name;
    }
}
