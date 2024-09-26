<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('llm_chain');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('platforms')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->enumNode('type')->values(['openai', 'azure'])->isRequired()->end()
                            ->scalarNode('api_key')->isRequired()->end()
                            ->scalarNode('base_url')->end()
                            ->scalarNode('deployment')->end()
                            ->scalarNode('version')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('llms')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('platform')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('embeddings')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('platform')->end()
                        ->end()
                    ->end()
                ->end()
            ->arrayNode('stores')
                ->children()
                    ->arrayNode('chroma_db')
                        ->children()
                            ->scalarNode('engine')->defaultValue('chroma-db')->end()
                            ->scalarNode('collection_name')->isRequired()->end()
                        ->end()
                    ->end()
                    ->arrayNode('azure_search')
                        ->children()
                            ->scalarNode('engine')->defaultValue('azure-search')->end()
                            ->scalarNode('api_key')->isRequired()->end()
                            ->scalarNode('endpoint')->isRequired()->end()
                            ->scalarNode('index_name')->isRequired()->end()
                            ->scalarNode('api_version')->defaultValue('2024-07-01')->end()
                            ->scalarNode('vector_field_name')->defaultValue('vector')->end()
                        ->end()
                    ->end()
//                    ->arrayNode('mongodb')
//                        ->children()
//                            ->scalarNode('engine')->defaultValue('mongodb')->end()
//                            ->scalarNode('uri')->isRequired()->end()
//                            ->scalarNode('database_name')->isRequired()->end()
//                            ->scalarNode('collection_name')->isRequired()->end()
//                            ->scalarNode('index_name')->end()
//                            ->scalarNode('vector_field_name')->defaultValue('vector')->end()
//                            ->booleanNode('bulk_write')->defaultFalse()->end()
//                        ->end()
//                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
