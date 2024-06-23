<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\ChatChain;
use PhpLlm\LlmChain\LlmChainInterface;
use PhpLlm\LlmChain\OpenAI\ChatModel;
use PhpLlm\LlmChain\OpenAI\Embeddings;
use PhpLlm\LlmChain\OpenAI\OpenAIClient;
use PhpLlm\LlmChain\ToolBox\ParameterAnalyzer;
use PhpLlm\LlmChain\ToolBox\Registry;
use PhpLlm\LlmChain\ToolBox\ToolAnalyzer;
use PhpLlm\LlmChain\ToolChain;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()

        // chains
        ->set(ToolChain::class)
        ->set(ChatChain::class)
        ->alias(LlmChainInterface::class, ChatChain::class)

        // openai
        ->set(ChatModel::class)
            ->args([
                '$model' => '%llm_chain.openai.model%',
                '$temperature' => '%llm_chain.openai.temperature%',
            ])
        ->set(Embeddings::class)
        ->set(OpenAIClient::class)
            ->args([
                '$apiKey' => '%llm_chain.openai.api_key%',
            ])

        // tools
        ->set(Registry::class)
            ->args([
                '$tools' => tagged_iterator('llm_chain.tool'),
            ])
        ->set(ToolAnalyzer::class)
        ->set(ParameterAnalyzer::class)
    ;
};
