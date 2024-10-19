<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Chain;
use PhpLlm\LlmChain\Chain\InputProcessor;
use PhpLlm\LlmChain\Chain\OutputProcessor;
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
use PhpLlm\LlmChain\Platform;
use PhpLlm\LlmChain\Platform\ModelClient;
use PhpLlm\LlmChain\Platform\ResponseConverter;
use PhpLlm\LlmChain\PlatformInterface;
use PhpLlm\LlmChainBundle\Profiler\DataCollector;
use PhpLlm\LlmChainBundle\Profiler\TraceablePlatform;
use PhpLlm\LlmChainBundle\Profiler\TraceableToolBox;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->instanceof(InputProcessor::class)
            ->tag('llm_chain.chain.input_processor')
        ->instanceof(OutputProcessor::class)
            ->tag('llm_chain.chain.output_processor')
        ->instanceof(ModelClient::class)
            ->tag('llm_chain.platform.model_client')
        ->instanceof(ResponseConverter::class)
            ->tag('llm_chain.platform.response_converter')

        // high level feature
        ->set('llm_chain.chain.abstract', Chain::class)
            ->abstract()
            ->args([
                '$llm' => abstract_arg('Language model'),
                '$inputProcessor' => tagged_iterator('llm_chain.chain.input_processor'),
                '$outputProcessor' => tagged_iterator('llm_chain.chain.output_processor'),
            ])
        ->set('llm_chain.embedder.abstract', Embedder::class)
            ->abstract()
            ->args([
                '$embeddings' => abstract_arg('Embeddings model'),
            ])
        ->set(Platform::class)
            ->args([
                '$modelClients' => tagged_iterator('llm_chain.platform.model_client'),
                '$responseConverter' => tagged_iterator('llm_chain.platform.response_converter'),
            ])
            ->alias(PlatformInterface::class, Platform::class)

        // structured output
        ->set(ResponseFormatFactory::class)
            ->alias(ResponseFormatFactoryInterface::class, ResponseFormatFactory::class)
        ->set(SchemaFactory::class)
        ->set(StructureOutputProcessor::class)

        // tools
        ->set(ToolBox::class)
            ->args([
                '$tools' => tagged_iterator('llm_chain.tool'),
            ])
            ->alias(ToolBoxInterface::class, ToolBox::class)
        ->set(ToolAnalyzer::class)
        ->set(ParameterAnalyzer::class)
        ->set(ToolProcessor::class)

        // profiler
        ->set(DataCollector::class)
        ->set(TraceablePlatform::class)
            ->decorate(Platform::class)
        ->set(TraceableToolBox::class)
            ->decorate(ToolBox::class)
    ;
};
