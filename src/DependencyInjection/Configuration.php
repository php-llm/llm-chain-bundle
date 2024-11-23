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
                ->arrayNode('platform')
                    ->children()
                        ->arrayNode('openai')
                            ->children()
                                ->scalarNode('api_key')->isRequired()->end()
                            ->end()
                        ->end()
                        ->arrayNode('azure')
                            ->children()
                                ->scalarNode('api_key')->isRequired()->end()
                                ->scalarNode('base_url')->isRequired()->end()
                                ->scalarNode('deployment')->isRequired()->end()
                                ->scalarNode('version')->info('The used API version')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('chains')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('model')
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('version')->defaultNull()->end()
                                    ->arrayNode('options')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('embedder')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('model')
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('version')->defaultNull()->end()
                                    ->arrayNode('options')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('stores')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->enumNode('engine')->values(['azure-search', 'chroma-db', 'mongodb', 'pinecone'])->isRequired()->end()
                            // Azure AI Search & MongoDB
                            ->scalarNode('index_name')->end()
                            // Azure AI Search
                            ->scalarNode('api_key')->end()
                            ->scalarNode('api_version')->end()
                            ->scalarNode('endpoint')->end()
                            // ChromaDB & MongoDB
                            ->scalarNode('collection_name')->end()
                            // MongoDB
                            ->scalarNode('database_name')->end()
                            ->scalarNode('vector_field_name')->defaultValue('vector')->end()
                            ->booleanNode('bulk_write')->defaultValue(false)->end()
                            // Pinecone
                            ->arrayNode('filter')->end()
                            ->scalarNode('namespace')->end()
                            ->scalarNode('top_k')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
