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
                ->arrayNode('runtimes')
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
                            ->scalarNode('runtime')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('embeddings')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('runtime')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
