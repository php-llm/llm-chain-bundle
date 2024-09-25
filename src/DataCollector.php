<?php

declare(strict_types=1);

/*
 * This file is part of php-llm/llm-chain-bundle.
 *
 * (c) Christopher Hertel <mail@christopher-hertel.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpLlm\LlmChainBundle;

use PhpLlm\LlmChain\ToolBox\Metadata;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type LlmCallData from TraceableLanguageModel
 * @phpstan-import-type ToolCallData from TraceableToolBox
 */
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
        private TraceableToolBox $toolBox,
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
            'tools' => $this->toolBox->getMap(),
            'llm_calls' => $llmCalls,
            'tool_calls' => $this->toolBox->calls,
        ];
    }

    public static function getTemplate(): string
    {
        return '@LlmChain/data_collector.html.twig';
    }

    /**
     * @return list<LlmCallData>
     */
    public function getLlmCalls(): array
    {
        return $this->data['llm_calls'] ?? [];
    }

    public function getLlmCallCount(): int
    {
        return array_sum(array_map('count', $this->data['llm_calls'] ?? []));
    }

    /**
     * @return Metadata[]
     */
    public function getTools(): array
    {
        return $this->data['tools'] ?? [];
    }

    /**
     * @return list<ToolCallData>
     */
    public function getToolCalls(): array
    {
        return $this->data['tool_calls'] ?? [];
    }
}
