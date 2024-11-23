<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\DependencyInjection;

use PhpLlm\LlmChain\Bridge\Anthropic\Claude;
use PhpLlm\LlmChain\Bridge\Azure\Store\SearchStore as AzureSearchStore;
use PhpLlm\LlmChain\Bridge\ChromaDB\Store as ChromaDBStore;
use PhpLlm\LlmChain\Bridge\Meta\Llama;
use PhpLlm\LlmChain\Bridge\MongoDB\Store as MongoDBStore;
use PhpLlm\LlmChain\Bridge\OpenAI\Embeddings;
use PhpLlm\LlmChain\Bridge\OpenAI\Embeddings\ModelClient as OpenAIEmbeddingsModelClient;
use PhpLlm\LlmChain\Bridge\OpenAI\GPT;
use PhpLlm\LlmChain\Bridge\OpenAI\GPT\ModelClient as OpenAIGPTModelClient;
use PhpLlm\LlmChain\Bridge\Pinecone\Store as PineconeStore;
use PhpLlm\LlmChain\Bridge\Voyage\Voyage;
use PhpLlm\LlmChain\Chain\InputProcessor;
use PhpLlm\LlmChain\Chain\OutputProcessor;
use PhpLlm\LlmChain\Chain\ToolBox\Attribute\AsTool;
use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Embedder;
use PhpLlm\LlmChain\Platform\ModelClient;
use PhpLlm\LlmChain\Platform\ResponseConverter;
use PhpLlm\LlmChain\Store\StoreInterface;
use PhpLlm\LlmChain\Store\VectorStoreInterface;
use PhpLlm\LlmChainBundle\Profiler\DataCollector;
use PhpLlm\LlmChainBundle\Profiler\TraceableToolBox;
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

        if (isset($config['platform'])) {
            if (1 !== count($config['platform'])) {
                throw new \InvalidArgumentException('Only one platform at a time is supported.');
            }

            $type = array_keys($config['platform']);
            $this->processPlatformConfig($type[0], $config['platform'][$type[0]], $container);
        }

        foreach ($config['chains'] as $chainName => $chain) {
            $this->processChainConfig($chainName, $chain, $container);
        }
        if (1 === count($config['chains']) && isset($chainName)) {
            $container->setAlias(ChainInterface::class, 'llm_chain.chain.'.$chainName);
        }

        foreach ($config['embedder'] as $embedderName => $embedder) {
            $this->processEmbedderConfig($embedderName, $embedder, $container);
        }
        if (1 === count($config['embedder']) && isset($embedderName)) {
            $container->setAlias(Embedder::class, 'llm_chain.embedder.'.$embedderName);
        }

        foreach ($config['stores'] as $storeName => $store) {
            $this->processStoreConfig($storeName, $store, $container);
        }
        if (1 === count($config['stores']) && isset($storeName)) {
            $container->setAlias(VectorStoreInterface::class, 'llm_chain.store.'.$storeName);
            $container->setAlias(StoreInterface::class, 'llm_chain.store.'.$storeName);
        }

        $container->registerAttributeForAutoconfiguration(AsTool::class, static function (ChildDefinition $definition, AsTool $attribute): void {
            $definition->addTag('llm_chain.tool', [
                'name' => $attribute->name,
                'description' => $attribute->description,
                'method' => $attribute->method,
            ]);
        });

        $container->registerForAutoconfiguration(InputProcessor::class)
            ->addTag('llm_chain.chain.input_processor');
        $container->registerForAutoconfiguration(OutputProcessor::class)
            ->addTag('llm_chain.chain.output_processor');
        $container->registerForAutoconfiguration(ModelClient::class)
            ->addTag('llm_chain.platform.model_client');
        $container->registerForAutoconfiguration(ResponseConverter::class)
            ->addTag('llm_chain.platform.response_converter');

        if (false === $container->getParameter('kernel.debug')) {
            $container->removeDefinition(DataCollector::class);
            $container->removeDefinition(TraceableToolBox::class);
        }
    }

    /**
     * @param array<string, mixed> $platform
     */
    private function processPlatformConfig(string $type, array $platform, ContainerBuilder $container): void
    {
        if ('openai' === $type) {
            $this->loadBridge($container, 'openai');
            $container->getDefinition(OpenAIEmbeddingsModelClient::class)
                ->replaceArgument('$apiKey', $platform['api_key']);

            $container->getDefinition(OpenAIGPTModelClient::class)
                ->replaceArgument('$apiKey', $platform['api_key']);

            return;
        }

        // TODO: Azure, Replicate, Ollama, etc.
        throw new \InvalidArgumentException(sprintf('Platform "%s" is not supported for configuration via bundle at this point.', $type));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processChainConfig(string $name, array $config, ContainerBuilder $container): void
    {
        ['name' => $modelName, 'version' => $version, 'options' => $options] = $config['model'];

        $llmClass = match (strtolower($modelName)) {
            'gpt' => GPT::class,
            'claude' => Claude::class,
            'llama' => Llama::class,
            default => throw new \InvalidArgumentException(sprintf('Model "%s" is not supported.', $modelName)),
        };
        $llmDefinition = (new Definition($llmClass))
            ->setArguments([
                '$version' => $version,
                '$options' => $options,
            ]);
        $container->setDefinition('llm_chain.chain.'.$name.'.llm', $llmDefinition);

        $definition = (new ChildDefinition('llm_chain.chain.abstract'))
            ->replaceArgument('$llm', new Reference('llm_chain.chain.'.$name.'.llm'));

        $container->setDefinition('llm_chain.chain.'.$name, $definition);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processEmbedderConfig(int|string $name, array $config, ContainerBuilder $container): void
    {
        ['name' => $modelName, 'version' => $version, 'options' => $options] = $config['model'];

        $modelClass = match (strtolower($modelName)) {
            'openai' => Embeddings::class,
            'voyage' => Voyage::class,
            default => throw new \InvalidArgumentException(sprintf('Model "%s" is not supported.', $modelName)),
        };
        $modelDefinition = (new Definition($modelClass))
            ->setArguments([
                '$version' => $version,
                '$options' => $options,
            ]);
        $container->setDefinition('llm_chain.embedder.'.$name.'.embeddings', $modelDefinition);

        $definition = (new ChildDefinition('llm_chain.embedder.abstract'))
            ->replaceArgument('$embeddings', new Reference('llm_chain.embedder.'.$name.'.embeddings'));

        $container->setDefinition('llm_chain.embedder.'.$name, $definition);
    }

    /**
     * @param array<string, mixed> $stores
     */
    private function processStoreConfig(string $name, array $stores, ContainerBuilder $container): void
    {
        if ('azure-search' === $stores['engine']) {
            $this->loadBridge($container, 'azure');
            $definition = new ChildDefinition(AzureSearchStore::class);
            $definition
                ->replaceArgument('$endpointUrl', $stores['endpoint'])
                ->replaceArgument('$apiKey', $stores['api_key'])
                ->replaceArgument('$indexName', $stores['index_name'])
                ->replaceArgument('$apiVersion', $stores['api_version']);

            $container->setDefinition('llm_chain.store.'.$name, $definition);
        }

        if ('chroma-db' === $stores['engine']) {
            $this->loadBridge($container, 'chromadb');
            $definition = new ChildDefinition(ChromaDBStore::class);
            $definition->replaceArgument('$collectionName', $stores['collection_name']);

            $container->setDefinition('llm_chain.store.'.$name, $definition);
        }

        if ('mongodb' === $stores['engine']) {
            $this->loadBridge($container, 'mongodb');
            $definition = new ChildDefinition(MongoDBStore::class);
            $definition
                ->replaceArgument('$databaseName', $stores['database_name'])
                ->replaceArgument('$collectionName', $stores['collection_name'])
                ->replaceArgument('$indexName', $stores['index_name'])
                ->replaceArgument('$vectorFieldName', $stores['vector_field_name'])
                ->replaceArgument('$bulkWrite', $stores['bulk_write']);

            $container->setDefinition('llm_chain.store.'.$name, $definition);
        }

        if ('pinecone' === $stores['engine']) {
            $this->loadBridge($container, 'pinecone');
            $definition = new ChildDefinition(PineconeStore::class);
            $definition
                ->replaceArgument('$namespace', $stores['namespace'] ?? null)
                ->replaceArgument('$filter', $stores['filter'] ?? [])
                ->replaceArgument('$topK', $stores['top_k'] ?? 3);

            $container->setDefinition('llm_chain.store.'.$name, $definition);
        }
    }

    private function loadBridge(ContainerBuilder $container, string $name): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('bridge-'.$name.'.php');
    }
}
