<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle;

use PhpLlm\LlmChain\Response\ToolCall;
use PhpLlm\LlmChain\ToolBox\Registry;
use PhpLlm\LlmChain\ToolBox\RegistryInterface;

final class TraceableToolRegistry implements RegistryInterface
{
    public array $calls = [];

    public function __construct(
        private Registry $toolRegistry,
    ) {
    }

    public function getMap(): array
    {
        return $this->toolRegistry->getMap();
    }

    public function execute(ToolCall $toolCall): string
    {
        $response = $this->toolRegistry->execute($toolCall);

        $this->calls[] = [
            'name' => $toolCall->name,
            'arguments' => $toolCall->arguments,
            'response' => $response,
        ];

        return $response;
    }
}
