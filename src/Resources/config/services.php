<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Chain;
use PhpLlm\LlmChain\Chain\StructuredOutput\ChainProcessor as StructureOutputProcessor;
use PhpLlm\LlmChain\Chain\StructuredOutput\ResponseFormatFactory;
use PhpLlm\LlmChain\Chain\StructuredOutput\ResponseFormatFactoryInterface;
use PhpLlm\LlmChain\Chain\StructuredOutput\SchemaFactory;
use PhpLlm\LlmChain\Chain\ToolBox\ChainProcessor as ToolProcessor;
use PhpLlm\LlmChain\Chain\ToolBox\ParameterAnalyzer;
use PhpLlm\LlmChain\Chain\ToolBox\ToolAnalyzer;
use PhpLlm\LlmChain\Chain\ToolBox\ToolBox;
use PhpLlm\LlmChain\Chain\ToolBox\ToolBoxInterface;
use PhpLlm\LlmChain\Embedder;
use PhpLlm\LlmChain\PlatformInterface;
use PhpLlm\LlmChainBundle\Profiler\DataCollector;
use PhpLlm\LlmChainBundle\Profiler\TraceableToolBox;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
            ->autowire()

        // high level feature
        ->set('llm_chain.chain.abstract', Chain::class)
            ->abstract()
            ->args([
                '$platform' => service(PlatformInterface::class),
                '$llm' => abstract_arg('Language model'),
                '$inputProcessor' => tagged_iterator('llm_chain.chain.input_processor'),
                '$outputProcessor' => tagged_iterator('llm_chain.chain.output_processor'),
            ])
        ->set('llm_chain.embedder.abstract', Embedder::class)
            ->abstract()
            ->args([
                '$embeddings' => abstract_arg('Embeddings model'),
            ])

        // structured output
        ->set(ResponseFormatFactory::class)
            ->alias(ResponseFormatFactoryInterface::class, ResponseFormatFactory::class)
        ->set(SchemaFactory::class)
        ->set(StructureOutputProcessor::class)

        // tools
        ->set('llm_chain.toolbox.abstract')
            ->class(ToolBox::class)
            ->abstract()
            ->args([
                '$tools' => abstract_arg('Collection of tools'),
            ])
        ->set(ToolBox::class)
            ->parent('llm_chain.toolbox.abstract')
            ->args([
                '$tools' => tagged_iterator('llm_chain.tool'),
            ])
            ->alias(ToolBoxInterface::class, ToolBox::class)
        ->set(ToolAnalyzer::class)
        ->set(ParameterAnalyzer::class)
        ->set('llm_chain.tool.chain_processor.abstract')
            ->class(ToolProcessor::class)
            ->abstract()
            ->args([
                '$toolBox' => abstract_arg('Tool box'),
            ])
        ->set(ToolProcessor::class)
            ->parent('llm_chain.tool.chain_processor.abstract')
            ->args([
                '$toolBox' => service(ToolBoxInterface::class),
            ])

        // profiler
        ->set(DataCollector::class)
            ->tag('data_collector')
        ->set(TraceableToolBox::class)
            ->decorate(ToolBoxInterface::class)
            ->tag('llm_chain.traceable_toolbox')
    ;
};
