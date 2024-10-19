<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Bridge\OpenAI\Embeddings\ModelClient as EmbeddingsModelClient;
use PhpLlm\LlmChain\Bridge\OpenAI\Embeddings\ResponseConverter as EmbeddingsResponseConverter;
use PhpLlm\LlmChain\Bridge\OpenAI\GPT\ModelClient as GPTModelClient;
use PhpLlm\LlmChain\Bridge\OpenAI\GPT\ResponseConverter as GPTResponseConverter;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->set(EmbeddingsModelClient::class)
        ->args([
            '$apiKey' => abstract_arg('OpenAI API Key'),
        ])
        ->set(EmbeddingsResponseConverter::class)
        ->set(GPTModelClient::class)
        ->args([
            '$apiKey' => abstract_arg('OpenAI API Key'),
        ])
        ->set(GPTResponseConverter::class)
    ;
};
