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
                ->arrayNode('openai')
                    ->children()
                        ->scalarNode('api_key')->isRequired()->end()
                        ->scalarNode('model')->isRequired()->end()
                        ->floatNode('temperature')->defaultValue(0.5)->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
