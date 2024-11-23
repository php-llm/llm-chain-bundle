<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Profiler;

use PhpLlm\LlmChain\Chain\ToolBox\Metadata;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type PlatformCallData from TraceablePlatform
 * @phpstan-import-type ToolCallData from TraceableToolBox
 */
final class DataCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly TraceablePlatform $platform,
        private readonly TraceableToolBox $toolBox,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = [
            'tools' => $this->toolBox->getMap(),
            'platform_calls' => $this->platform->calls,
            'tool_calls' => $this->toolBox->calls,
        ];
    }

    public static function getTemplate(): string
    {
        return '@LlmChain/data_collector.html.twig';
    }

    /**
     * @return PlatformCallData[]
     */
    public function getPlatformCalls(): array
    {
        return $this->data['platform_calls'] ?? [];
    }

    /**
     * @return Metadata[]
     */
    public function getTools(): array
    {
        return $this->data['tools'] ?? [];
    }

    /**
     * @return ToolCallData[]
     */
    public function getToolCalls(): array
    {
        return $this->data['tool_calls'] ?? [];
    }
}
