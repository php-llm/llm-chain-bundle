<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Bridge\Anthropic\ModelHandler;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->set(ModelHandler::class)
        ->args([
            '$apiKey' => abstract_arg('API key for Anthropic API'),
            '$version' => abstract_arg('API version for Anthropic API'),
        ])
    ;
};
