<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle;

use PhpLlm\LlmChain\Response\ToolCall;
use PhpLlm\LlmChain\ToolBox\ToolBox;
use PhpLlm\LlmChain\ToolBox\ToolBoxInterface;

/**
 * @phpstan-type ToolCallData array{
 *     call: ToolCall,
 *     result: string,
 * }
 */
final class TraceableToolBox implements ToolBoxInterface
{
    /**
     * @var list<ToolCallData>
     */
    public array $calls = [];

    public function __construct(
        private ToolBox $toolRegistry,
    ) {
    }

    public function getMap(): array
    {
        return $this->toolRegistry->getMap();
    }

    public function execute(ToolCall $toolCall): string
    {
        $result = $this->toolRegistry->execute($toolCall);

        $this->calls[] = [
            'call' => $toolCall,
            'result' => $result,
        ];

        return $result;
    }
}
