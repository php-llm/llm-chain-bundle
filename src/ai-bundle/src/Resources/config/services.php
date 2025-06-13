<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Chain\StructuredOutput\ChainProcessor as StructureOutputProcessor;
use PhpLlm\LlmChain\Chain\StructuredOutput\ResponseFormatFactory;
use PhpLlm\LlmChain\Chain\StructuredOutput\ResponseFormatFactoryInterface;
use PhpLlm\LlmChain\Chain\Toolbox\ChainProcessor as ToolProcessor;
use PhpLlm\LlmChain\Chain\Toolbox\Toolbox;
use PhpLlm\LlmChain\Chain\Toolbox\ToolboxInterface;
use PhpLlm\LlmChain\Chain\Toolbox\ToolFactory\ReflectionToolFactory;
use PhpLlm\LlmChain\Chain\Toolbox\ToolFactoryInterface;
use PhpLlm\LlmChainBundle\Profiler\DataCollector;
use PhpLlm\LlmChainBundle\Profiler\TraceableToolbox;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()

        // structured output
        ->set(ResponseFormatFactory::class)
            ->alias(ResponseFormatFactoryInterface::class, ResponseFormatFactory::class)
        ->set(StructureOutputProcessor::class)
            ->tag('llm_chain.chain.input_processor')
            ->tag('llm_chain.chain.output_processor')

        // tools
        ->set('llm_chain.toolbox.abstract')
            ->class(Toolbox::class)
            ->autowire()
            ->abstract()
            ->args([
                '$toolFactory' => service(ToolFactoryInterface::class),
                '$tools' => abstract_arg('Collection of tools'),
            ])
        ->set(Toolbox::class)
            ->parent('llm_chain.toolbox.abstract')
            ->args([
                '$tools' => tagged_iterator('llm_chain.tool'),
            ])
            ->alias(ToolboxInterface::class, Toolbox::class)
        ->set(ReflectionToolFactory::class)
            ->alias(ToolFactoryInterface::class, ReflectionToolFactory::class)
        ->set('llm_chain.tool.chain_processor.abstract')
            ->class(ToolProcessor::class)
            ->abstract()
            ->args([
                '$toolbox' => abstract_arg('Toolbox'),
            ])
        ->set(ToolProcessor::class)
            ->parent('llm_chain.tool.chain_processor.abstract')
            ->tag('llm_chain.chain.input_processor')
            ->tag('llm_chain.chain.output_processor')
            ->args([
                '$toolbox' => service(ToolboxInterface::class),
                '$eventDispatcher' => service('event_dispatcher')->nullOnInvalid(),
            ])

        // profiler
        ->set(DataCollector::class)
            ->tag('data_collector')
        ->set(TraceableToolbox::class)
            ->decorate(ToolboxInterface::class)
            ->tag('llm_chain.traceable_toolbox')
    ;
};
