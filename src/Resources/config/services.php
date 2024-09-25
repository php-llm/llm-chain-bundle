<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Chain;
use PhpLlm\LlmChain\DocumentEmbedder;
use PhpLlm\LlmChain\OpenAI\Model\Embeddings;
use PhpLlm\LlmChain\OpenAI\Model\Gpt;
use PhpLlm\LlmChain\OpenAI\Platform;
use PhpLlm\LlmChain\OpenAI\Platform\Azure as AzurePlatform;
use PhpLlm\LlmChain\OpenAI\Platform\OpenAI as OpenAIPlatform;
use PhpLlm\LlmChain\Store\Azure\SearchStore as AzureSearchStore;
use PhpLlm\LlmChain\Store\ChromaDb\Store as ChromaDbStore;
use PhpLlm\LlmChain\ToolBox\ParameterAnalyzer;
use PhpLlm\LlmChain\ToolBox\ToolAnalyzer;
use PhpLlm\LlmChain\ToolBox\ToolBox;
use PhpLlm\LlmChain\ToolBox\ToolBoxInterface;
use PhpLlm\LlmChainBundle\DataCollector;
use PhpLlm\LlmChainBundle\TraceableToolBox;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()

        // high level feature
        ->set(Chain::class)
        ->set(DocumentEmbedder::class)

        // platforms
        ->set(AzurePlatform::class)
            ->abstract()
            ->args([
                '$baseUrl' => abstract_arg('Base URL for Azure API'),
                '$deployment' => abstract_arg('Deployment for Azure API'),
                '$apiVersion' => abstract_arg('API version for Azure API'),
                '$key' => abstract_arg('API key for Azure API'),
            ])
        ->set(OpenAIPlatform::class)
            ->abstract()
            ->args([
                '$apiKey' => abstract_arg('API key for OpenAI API'),
            ])

        // models
        ->set(Gpt::class)
            ->abstract()
            ->args([
                '$platform' => service(Platform::class),
            ])
        ->set(Embeddings::class)
            ->abstract()
            ->args([
                '$platform' => service(Platform::class),
            ])

        // stores
        ->set(AzureSearchStore::class)
            ->abstract()
            ->args([
                '$endpointUrl' => abstract_arg('Endpoint URL for Azure AI Search API'),
                '$apiKey' => abstract_arg('API key for Azure AI Search API'),
                '$indexName' => abstract_arg('Name of Azure AI Search index'),
                '$apiVersion' => abstract_arg('API version for Azure AI Search API'),
            ])
        ->set(ChromaDbStore::class)
            ->abstract()
            ->args([
                '$collectionName' => abstract_arg('Name of ChromaDB collection'),
            ])

        // tools
        ->set(ToolBox::class)
            ->args([
                '$tools' => tagged_iterator('llm_chain.tool'),
            ])
            ->alias(ToolBoxInterface::class, ToolBox::class)
        ->set(ToolAnalyzer::class)
        ->set(ParameterAnalyzer::class)

        // profiler
        ->set(DataCollector::class)
        ->set(TraceableToolBox::class)
            ->decorate(ToolBox::class)
    ;
};
