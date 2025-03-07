<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Profiler;

use PhpLlm\LlmChain\Chain\ToolBox\Metadata;
use PhpLlm\LlmChain\Chain\ToolBox\ToolBoxInterface;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type PlatformCallData from TraceablePlatform
 * @phpstan-import-type ToolCallData from TraceableToolBox
 */
final class DataCollector extends AbstractDataCollector
{
    /**
     * @var TraceablePlatform[]
     */
    private readonly array $platforms;

    /**
     * @var TraceableToolBox[]
     */
    private readonly array $toolBoxes;

    /**
     * @param TraceablePlatform[] $platforms
     * @param TraceableToolBox[]  $toolBoxes
     */
    public function __construct(
        #[TaggedIterator('llm_chain.traceable_platform')]
        iterable $platforms,
        private readonly ToolBoxInterface $defaultToolBox,
        #[TaggedIterator('llm_chain.traceable_toolbox')]
        iterable $toolBoxes,
    ) {
        $this->platforms = $platforms instanceof \Traversable ? iterator_to_array($platforms) : $platforms;
        $this->toolBoxes = $toolBoxes instanceof \Traversable ? iterator_to_array($toolBoxes) : $toolBoxes;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = [
            'tools' => $this->defaultToolBox->getMap(),
            'platform_calls' => array_merge(...array_map(fn (TraceablePlatform $platform) => $platform->calls, $this->platforms)),
            'tool_calls' => array_merge(...array_map(fn (TraceableToolBox $toolBox) => $toolBox->calls, $this->toolBoxes)),
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
