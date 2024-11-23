<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Bridge\ChromaDB\Store;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->set(Store::class)
        ->args([
            '$collectionName' => abstract_arg('Name of ChromaDB collection'),
        ])
    ;
};
