<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\DependencyInjection;

use PhpLlm\LlmChain\EmbeddingModel;
use PhpLlm\LlmChain\LanguageModel;
use PhpLlm\LlmChain\OpenAI\Model\Embeddings;
use PhpLlm\LlmChain\OpenAI\Model\Gpt;
use PhpLlm\LlmChain\OpenAI\Runtime;
use PhpLlm\LlmChain\OpenAI\Runtime\Azure as AzureRuntime;
use PhpLlm\LlmChain\OpenAI\Runtime\OpenAI as OpenAIRuntime;
use PhpLlm\LlmChain\Store\Azure\SearchStore as AzureSearchStore;
use PhpLlm\LlmChain\Store\ChromaDb\Store as ChromaDbStore;
use PhpLlm\LlmChain\Store\StoreInterface;
use PhpLlm\LlmChain\Store\VectorStoreInterface;
use PhpLlm\LlmChain\ToolBox\AsTool;
use PhpLlm\LlmChainBundle\DataCollector;
use PhpLlm\LlmChainBundle\TraceableLanguageModel;
use PhpLlm\LlmChainBundle\TraceableToolRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class LlmChainExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config['runtimes'] as $name => $runtime) {
            $this->processRuntimeConfig($name, $runtime, $container);
        }
        if (1 === count($config['runtimes'])) {
            $container->setAlias(Runtime::class, 'llm_chain.runtime.'.$name);
        }

        foreach ($config['llms'] as $name => $llm) {
            $this->processLlmConfig($name, $llm, $container);
        }
        if (1 === count($config['llms'])) {
            $container->setAlias(LanguageModel::class, 'llm_chain.llm.'.$name);
        }

        foreach ($config['embeddings'] as $name => $embeddings) {
            $this->processEmbeddingsConfig($name, $embeddings, $container);
        }
        if (1 === count($config['embeddings'])) {
            $container->setAlias(EmbeddingModel::class, 'llm_chain.embeddings.'.$name);
        }

        foreach ($config['stores'] as $name => $store) {
            $this->processStoreConfig($name, $store, $container);
        }
        if (1 === count($config['stores'])) {
            $container->setAlias(VectorStoreInterface::class, 'llm_chain.store.'.$name);
            $container->setAlias(StoreInterface::class, 'llm_chain.store.'.$name);
        }

        $container->registerAttributeForAutoconfiguration(AsTool::class, static function (ChildDefinition $definition, AsTool $attribute): void {
            $definition->addTag('llm_chain.tool', [
                'name' => $attribute->name,
                'description' => $attribute->description,
                'method' => $attribute->method,
            ]);
        });

        if (false === $container->getParameter('kernel.debug')) {
            $container->removeDefinition(DataCollector::class);
            $container->removeDefinition(TraceableToolRegistry::class);
        }
    }

    private function processRuntimeConfig(string $name, array $runtime, ContainerBuilder $container): void
    {
        if ('openai' === $runtime['type']) {
            $definition = new ChildDefinition(OpenAIRuntime::class);
            $definition
                ->replaceArgument('$apiKey', $runtime['api_key']);

            $container->setDefinition('llm_chain.runtime.'.$name, $definition);

            return;
        }

        if ('azure' === $runtime['type']) {
            $definition = new ChildDefinition(AzureRuntime::class);
            $definition
                ->replaceArgument('$baseUrl', $runtime['base_url'])
                ->replaceArgument('$deployment', $runtime['deployment'])
                ->replaceArgument('$key', $runtime['api_key'])
                ->replaceArgument('$apiVersion', $runtime['version']);

            $container->setDefinition('llm_chain.runtime.'.$name, $definition);
        }
    }

    private function processLlmConfig(string $name, array $llm, ContainerBuilder $container): void
    {
        $runtime = isset($llm['runtime']) ? 'llm_chain.runtime.'.$llm['runtime'] : Runtime::class;

        $definition = new ChildDefinition(Gpt::class);
        $definition->replaceArgument('$runtime', new Reference($runtime));

        $container->setDefinition('llm_chain.llm.'.$name, $definition);

        if ($container->getParameter('kernel.debug')) {
            $traceable = new Definition(TraceableLanguageModel::class);
            $traceable->setDecoratedService('llm_chain.llm.'.$name);
            $traceable->addTag('llm_chain.traceable_llm');
            $traceable->setArgument('$llm', new Reference('llm_chain.llm.'.$name.'.debug.inner'));
            $traceable->setArgument('$name', $name);
            $container->setDefinition('llm_chain.llm.'.$name.'.debug', $traceable);
        }
    }

    private function processEmbeddingsConfig(string $name, mixed $embeddings, ContainerBuilder $container): void
    {
        $runtime = isset($embeddings['runtime']) ? 'llm_chain.runtime.'.$embeddings['runtime'] : Runtime::class;

        $definition = new ChildDefinition(Embeddings::class);
        $definition->replaceArgument('$runtime', new Reference($runtime));

        $container->setDefinition('llm_chain.embeddings.'.$name, $definition);
    }

    private function processStoreConfig(string $name, mixed $stores, ContainerBuilder $container): void
    {
        if ('chroma-db' === $stores['engine']) {
            $definition = new ChildDefinition(ChromaDbStore::class);
            $definition->replaceArgument('$collectionName', $stores['collection_name']);

            $container->setDefinition('llm_chain.store.'.$name, $definition);
        }

        if ('azure-search' === $stores['engine']) {
            $definition = new ChildDefinition(AzureSearchStore::class);
            $definition
                ->replaceArgument('$endpointUrl', $stores['endpoint'])
                ->replaceArgument('$apiKey', $stores['api_key'])
                ->replaceArgument('$indexName', $stores['index_name'])
                ->replaceArgument('$apiVersion', $stores['api_version']);

            $container->setDefinition('llm_chain.store.'.$name, $definition);
        }
    }
}
