<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle;

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

    public function execute(string $name, string $arguments): string
    {
        $response = $this->toolRegistry->execute($name, $arguments);

        $this->calls[] = [
            'name' => $name,
            'arguments' => $arguments,
            'response' => $response,
        ];

        return $response;
    }
}
