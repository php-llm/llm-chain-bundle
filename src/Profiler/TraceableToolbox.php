<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Profiler;

use PhpLlm\LlmChain\Chain\Toolbox\ToolboxInterface;
use PhpLlm\LlmChain\Model\Response\ToolCall;

/**
 * @phpstan-type ToolCallData array{
 *     call: ToolCall,
 *     result: string,
 * }
 */
final class TraceableToolbox implements ToolboxInterface
{
    /**
     * @var ToolCallData[]
     */
    public array $calls = [];

    public function __construct(
        private readonly ToolboxInterface $toolbox,
    ) {
    }

    public function getMap(): array
    {
        return $this->toolbox->getMap();
    }

    public function execute(ToolCall $toolCall): mixed
    {
        $result = $this->toolbox->execute($toolCall);

        $this->calls[] = [
            'call' => $toolCall,
            'result' => $result,
        ];

        return $result;
    }
}
