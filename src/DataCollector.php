<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DataCollector extends AbstractDataCollector
{
    /**
     * @var list<TraceableLanguageModel>
     */
    private readonly array $llms;

    /**
     * @param iterable<TraceableLanguageModel> $llms
     */
    public function __construct(
        #[AutowireIterator('llm_chain.traceable_llm')]
        iterable $llms,
        private TraceableToolRegistry $toolRegistry,
    ) {
        $this->llms = $llms instanceof \Traversable ? iterator_to_array($llms) : $llms;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $llmCalls = [];
        foreach ($this->llms as $llm) {
            $llmCalls[$llm->getName()] = $llm->calls;
        }

        $this->data = [
            'tools' => $this->toolRegistry->getMap(),
            'llm_calls' => $llmCalls,
            'tool_calls' => $this->toolRegistry->calls,
        ];
    }

    public static function getTemplate(): string
    {
        return '@LlmChain/data_collector.html.twig';
    }


    public function getLlmCalls(): array
    {
        return $this->data['llm_calls'] ?? [];
    }

    public function getLlmCallCount(): int
    {
        return array_sum(array_map('count', $this->data['llm_calls'] ?? []));
    }

    public function getTools(): array
    {
        return $this->data['tools'] ?? [];
    }

    public function getToolCalls(): array
    {
        return $this->data['tool_calls'] ?? [];
    }
}
