<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Bridge\Pinecone\Store;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->set(Store::class)
        ->args([
            '$namespace' => abstract_arg('Namespace of index'),
            '$filter' => abstract_arg('Filter for metadata'),
            '$topK' => abstract_arg('Number of results to return'),
        ])
    ;
};
