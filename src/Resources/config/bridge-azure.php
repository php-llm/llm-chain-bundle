<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Bridge\Azure\OpenAI\EmbeddingsModelClient;
use PhpLlm\LlmChain\Bridge\Azure\OpenAI\GPTModelClient;
use PhpLlm\LlmChain\Bridge\Azure\Store\SearchStore;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->set(EmbeddingsModelClient::class)
        ->args([
            '$baseUrl' => abstract_arg('Base URL for Azure API'),
            '$deployment' => abstract_arg('Deployment for Azure API'),
            '$apiVersion' => abstract_arg('API version for Azure API'),
            '$apiKey' => abstract_arg('API key for Azure API'),
        ])
        ->set(GPTModelClient::class)
        ->args([
            '$baseUrl' => abstract_arg('Base URL for Azure API'),
            '$deployment' => abstract_arg('Deployment for Azure API'),
            '$apiVersion' => abstract_arg('API version for Azure API'),
            '$apiKey' => abstract_arg('API key for Azure API'),
        ])
        ->set(SearchStore::class)
        ->args([
            '$endpointUrl' => abstract_arg('Endpoint URL for Azure AI Search API'),
            '$apiKey' => abstract_arg('API key for Azure AI Search API'),
            '$indexName' => abstract_arg('Name of Azure AI Search index'),
            '$apiVersion' => abstract_arg('API version for Azure AI Search API'),
        ])
    ;
};
