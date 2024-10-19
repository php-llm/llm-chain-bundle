<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PhpLlm\LlmChain\Bridge\MongoDB\Store;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->set(Store::class)
        ->args([
            '$databaseName' => abstract_arg('The name of the database'),
            '$collectionName' => abstract_arg('The name of the collection'),
            '$indexName' => abstract_arg('The name of the Atlas Search index'),
            '$vectorFieldName' => abstract_arg('The name of the field int the index that contains the vector'),
            '$bulkWrite' => abstract_arg('Use bulk write operations'),
        ])
    ;
};
