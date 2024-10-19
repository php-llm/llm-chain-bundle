<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Bridge\Meta\LlamaPromptConverter;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->set(LlamaPromptConverter::class)
    ;
};
