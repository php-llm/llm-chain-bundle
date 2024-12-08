<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\DependencyInjection;

use PhpLlm\LlmChain\PlatformInterface;
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
                        ->arrayNode('anthropic')
                            ->children()
                                ->scalarNode('api_key')->isRequired()->end()
                                ->scalarNode('version')->defaultNull()->end()
                            ->end()
                        ->end()
                        ->arrayNode('azure')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('api_key')->isRequired()->end()
                                    ->scalarNode('base_url')->isRequired()->end()
                                    ->scalarNode('deployment')->isRequired()->end()
                                    ->scalarNode('api_version')->info('The used API version')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('openai')
                            ->children()
                                ->scalarNode('api_key')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('chain')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('platform')->defaultValue(PlatformInterface::class)->end()
                            ->arrayNode('model')
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('version')->defaultNull()->end()
                                    ->arrayNode('options')
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('tools')
                                ->scalarPrototype()->end()
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
                ->arrayNode('store')
                    ->children()
                        ->arrayNode('chroma_db')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('host')->isRequired()->end()
                                    ->scalarNode('collection')->isRequired()->end()
                                ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
