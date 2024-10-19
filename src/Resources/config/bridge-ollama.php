<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Bridge\Ollama\LlamaModelHandler;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->set(LlamaModelHandler::class)
        ->args([
            '$hostUrl' => abstract_arg('Base URL for Ollama API'),
        ])
    ;
};
