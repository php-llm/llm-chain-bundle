<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Profiler;

use PhpLlm\LlmChain\Chain\ToolBox\ToolBoxInterface;
use PhpLlm\LlmChain\Model\Response\ToolCall;

/**
 * @phpstan-type ToolCallData array{
 *     call: ToolCall,
 *     result: string,
 * }
 */
final class TraceableToolBox implements ToolBoxInterface
{
    /**
     * @var ToolCallData[]
     */
    public array $calls = [];

    public function __construct(
        private ToolBoxInterface $toolBox,
    ) {
    }

    public function getMap(): array
    {
        return $this->toolBox->getMap();
    }

    public function execute(ToolCall $toolCall): string
    {
        $result = $this->toolBox->execute($toolCall);

        $this->calls[] = [
            'call' => $toolCall,
            'result' => $result,
        ];

        return $result;
    }
}
