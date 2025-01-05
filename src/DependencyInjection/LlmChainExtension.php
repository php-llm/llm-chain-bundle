<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\DependencyInjection;

use PhpLlm\LlmChain\Bridge\Anthropic\Claude;
use PhpLlm\LlmChain\Bridge\Anthropic\PlatformFactory as AnthropicPlatformFactory;
use PhpLlm\LlmChain\Bridge\Azure\OpenAI\PlatformFactory as AzureOpenAIPlatformFactory;
use PhpLlm\LlmChain\Bridge\Azure\Store\SearchStore as AzureSearchStore;
use PhpLlm\LlmChain\Bridge\ChromaDB\Store as ChromaDBStore;
use PhpLlm\LlmChain\Bridge\Meta\Llama;
use PhpLlm\LlmChain\Bridge\MongoDB\Store as MongoDBStore;
use PhpLlm\LlmChain\Bridge\OpenAI\Embeddings;
use PhpLlm\LlmChain\Bridge\OpenAI\GPT;
use PhpLlm\LlmChain\Bridge\OpenAI\PlatformFactory as OpenAIPlatformFactory;
use PhpLlm\LlmChain\Bridge\Pinecone\Store as PineconeStore;
use PhpLlm\LlmChain\Bridge\Voyage\Voyage;
use PhpLlm\LlmChain\Chain;
use PhpLlm\LlmChain\Chain\InputProcessor;
use PhpLlm\LlmChain\Chain\InputProcessor\SystemPromptInputProcessor;
use PhpLlm\LlmChain\Chain\OutputProcessor;
use PhpLlm\LlmChain\Chain\StructuredOutput\ChainProcessor as StructureOutputProcessor;
use PhpLlm\LlmChain\Chain\ToolBox\Attribute\AsTool;
use PhpLlm\LlmChain\Chain\ToolBox\ChainProcessor as ToolProcessor;
use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Embedder;
use PhpLlm\LlmChain\Model\EmbeddingsModel;
use PhpLlm\LlmChain\Model\LanguageModel;
use PhpLlm\LlmChain\Platform;
use PhpLlm\LlmChain\Platform\ModelClient;
use PhpLlm\LlmChain\Platform\ResponseConverter;
use PhpLlm\LlmChain\PlatformInterface;
use PhpLlm\LlmChain\Store\StoreInterface;
use PhpLlm\LlmChain\Store\VectorStoreInterface;
use PhpLlm\LlmChainBundle\Profiler\DataCollector;
use PhpLlm\LlmChainBundle\Profiler\TraceablePlatform;
use PhpLlm\LlmChainBundle\Profiler\TraceableToolBox;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

use function Symfony\Component\String\u;

final class LlmChainExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        foreach ($config['platform'] ?? [] as $type => $platform) {
            $this->processPlatformConfig($type, $platform, $container);
        }
        $platforms = array_keys($container->findTaggedServiceIds('llm_chain.platform'));
        if (1 === count($platforms)) {
            $container->setAlias(PlatformInterface::class, reset($platforms));
        }
        if ($container->getParameter('kernel.debug')) {
            foreach ($platforms as $platform) {
                $traceablePlatformDefinition = (new Definition(TraceablePlatform::class))
                    ->setDecoratedService($platform)
                    ->setAutowired(true)
                    ->addTag('llm_chain.traceable_platform');
                $suffix = u($platform)->afterLast('.')->toString();
                $container->setDefinition('llm_chain.traceable_platform.'.$suffix, $traceablePlatformDefinition);
            }
        }

        foreach ($config['chain'] as $chainName => $chain) {
            $this->processChainConfig($chainName, $chain, $container);
        }
        if (1 === count($config['chain']) && isset($chainName)) {
            $container->setAlias(ChainInterface::class, 'llm_chain.chain.'.$chainName);
        }
        $llms = array_keys($container->findTaggedServiceIds('llm_chain.model.language_model'));
        if (1 === count($llms)) {
            $container->setAlias(LanguageModel::class, reset($llms));
        }

        foreach ($config['store'] ?? [] as $type => $store) {
            $this->processStoreConfig($type, $store, $container);
        }
        $stores = array_keys($container->findTaggedServiceIds('llm_chain.store'));
        if (1 === count($stores)) {
            $container->setAlias(VectorStoreInterface::class, reset($stores));
            $container->setAlias(StoreInterface::class, reset($stores));
        }

        foreach ($config['embedder'] as $embedderName => $embedder) {
            $this->processEmbedderConfig($embedderName, $embedder, $container);
        }
        if (1 === count($config['embedder']) && isset($embedderName)) {
            $container->setAlias(Embedder::class, 'llm_chain.embedder.'.$embedderName);
        }
        $embeddings = array_keys($container->findTaggedServiceIds('llm_chain.model.embeddings_model'));
        if (1 === count($embeddings)) {
            $container->setAlias(EmbeddingsModel::class, reset($embeddings));
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
            $platformId = 'llm_chain.platform.openai';
            $definition = (new Definition(Platform::class))
                ->setFactory(OpenAIPlatformFactory::class.'::create')
                ->setAutowired(true)
                ->setLazy(true)
                ->addTag('proxy', ['interface' => PlatformInterface::class])
                ->setArguments(['$apiKey' => $platform['api_key']])
                ->addTag('llm_chain.platform');

            $container->setDefinition($platformId, $definition);

            return;
        }

        if ('azure' === $type) {
            foreach ($platform as $name => $config) {
                $platformId = 'llm_chain.platform.azure.'.$name;
                $definition = (new Definition(Platform::class))
                    ->setFactory(AzureOpenAIPlatformFactory::class.'::create')
                    ->setAutowired(true)
                    ->setLazy(true)
                    ->addTag('proxy', ['interface' => PlatformInterface::class])
                    ->setArguments([
                        '$baseUrl' => $config['base_url'],
                        '$deployment' => $config['deployment'],
                        '$apiVersion' => $config['api_version'],
                        '$apiKey' => $config['api_key'],
                    ])
                    ->addTag('llm_chain.platform');

                $container->setDefinition($platformId, $definition);
            }

            return;
        }

        if ('anthropic' === $type) {
            $platformId = 'llm_chain.platform.anthropic';
            $definition = (new Definition(Platform::class))
                ->setFactory(AnthropicPlatformFactory::class.'::create')
                ->setAutowired(true)
                ->setLazy(true)
                ->addTag('proxy', ['interface' => PlatformInterface::class])
                ->setArguments([
                    '$apiKey' => $platform['api_key'],
                ])
                ->addTag('llm_chain.platform');

            if (isset($platform['version'])) {
                $definition->replaceArgument('$version', $platform['version']);
            }

            $container->setDefinition($platformId, $definition);

            return;
        }

        throw new \InvalidArgumentException(sprintf('Platform "%s" is not supported for configuration via bundle at this point.', $type));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processChainConfig(string $name, array $config, ContainerBuilder $container): void
    {
        // MODEL
        ['name' => $modelName, 'version' => $version, 'options' => $options] = $config['model'];

        $llmClass = match (strtolower($modelName)) {
            'gpt' => GPT::class,
            'claude' => Claude::class,
            'llama' => Llama::class,
            default => throw new \InvalidArgumentException(sprintf('Model "%s" is not supported.', $modelName)),
        };
        $llmDefinition = new Definition($llmClass);
        if (null !== $version) {
            $llmDefinition->setArgument('$version', $version);
        }
        if (0 !== count($options)) {
            $llmDefinition->setArgument('$options', $options);
        }
        $llmDefinition->addTag('llm_chain.model.language_model');
        $container->setDefinition('llm_chain.chain.'.$name.'.llm', $llmDefinition);

        // CHAIN
        $chainDefinition = (new Definition(Chain::class))
            ->setArgument('$platform', new Reference($config['platform']))
            ->setArgument('$llm', new Reference('llm_chain.chain.'.$name.'.llm'));

        $inputProcessors = [];
        $outputProcessors = [];

        // TOOL & PROCESSOR
        if ($config['tools']['enabled']) {
            // Create specific tool box and process if tools are explicitly defined
            if (0 !== count($config['tools']['services'])) {
                $tools = array_map(static fn (string $tool) => new Reference($tool), $config['tools']['services']);
                $toolboxDefinition = (new ChildDefinition('llm_chain.toolbox.abstract'))
                    ->replaceArgument('$tools', $tools);
                $container->setDefinition('llm_chain.toolbox.'.$name, $toolboxDefinition);

                if ($container->getParameter('kernel.debug')) {
                    $traceableToolboxDefinition = (new Definition('llm_chain.traceable_toolbox.'.$name))
                        ->setClass(TraceableToolBox::class)
                        ->setAutowired(true)
                        ->setDecoratedService('llm_chain.toolbox.'.$name)
                        ->addTag('llm_chain.traceable_toolbox');
                    $container->setDefinition('llm_chain.traceable_toolbox.'.$name, $traceableToolboxDefinition);
                }

                $toolProcessorDefinition = (new ChildDefinition('llm_chain.tool.chain_processor.abstract'))
                    ->replaceArgument('$toolBox', new Reference('llm_chain.toolbox.'.$name));
                $container->setDefinition('llm_chain.tool.chain_processor.'.$name, $toolProcessorDefinition);

                $inputProcessors[] = new Reference('llm_chain.tool.chain_processor.'.$name);
                $outputProcessors[] = new Reference('llm_chain.tool.chain_processor.'.$name);
            } else {
                $inputProcessors[] = new Reference(ToolProcessor::class);
                $outputProcessors[] = new Reference(ToolProcessor::class);
            }
        }

        // STRUCTURED OUTPUT
        if ($config['structured_output']) {
            $inputProcessors[] = new Reference(StructureOutputProcessor::class);
            $outputProcessors[] = new Reference(StructureOutputProcessor::class);
        }

        // SYSTEM PROMPT
        if (is_string($config['system_prompt'])) {
            $systemPromptInputProcessorDefinition = new Definition(SystemPromptInputProcessor::class);
            $systemPromptInputProcessorDefinition
                ->setAutowired(true)
                ->setArgument('$systemPrompt', $config['system_prompt']);

            $inputProcessors[] = $systemPromptInputProcessorDefinition;
        }

        $chainDefinition
            ->setArgument('$inputProcessors', $inputProcessors)
            ->setArgument('$outputProcessors', $outputProcessors);

        $container->setDefinition('llm_chain.chain.'.$name, $chainDefinition);
    }

    /**
     * @param array<string, mixed> $stores
     */
    private function processStoreConfig(string $type, array $stores, ContainerBuilder $container): void
    {
        if ('azure_search' === $type) {
            foreach ($stores as $name => $store) {
                $arguments = [
                    '$endpointUrl' => $store['endpoint'],
                    '$apiKey' => $store['api_key'],
                    '$indexName' => $store['index_name'],
                    '$apiVersion' => $store['api_version'],
                ];

                if (array_key_exists('vector_field', $store)) {
                    $arguments['$vectorFieldName'] = $store['vector_field'];
                }

                $definition = new Definition(AzureSearchStore::class);
                $definition
                    ->setAutowired(true)
                    ->addTag('llm_chain.store')
                    ->setArguments($arguments);

                $container->setDefinition('llm_chain.store.'.$type.'.'.$name, $definition);
            }
        }

        if ('chroma_db' === $type) {
            foreach ($stores as $name => $store) {
                $definition = new Definition(ChromaDBStore::class);
                $definition
                    ->setAutowired(true)
                    ->setArgument('$collectionName', $store['collection'])
                    ->addTag('llm_chain.store');

                $container->setDefinition('llm_chain.store.'.$type.'.'.$name, $definition);
            }
        }

        if ('mongodb' === $type) {
            foreach ($stores as $name => $store) {
                $arguments = [
                    '$databaseName' => $store['database'],
                    '$collectionName' => $store['collection'],
                    '$indexName' => $store['index_name'],
                ];

                if (array_key_exists('vector_field', $store)) {
                    $arguments['$vectorFieldName'] = $store['vector_field'];
                }

                if (array_key_exists('bulk_write', $store)) {
                    $arguments['$bulkWrite'] = $store['bulk_write'];
                }

                $definition = new Definition(MongoDBStore::class);
                $definition
                    ->setAutowired(true)
                    ->addTag('llm_chain.store')
                    ->setArguments($arguments);

                $container->setDefinition('llm_chain.store.'.$type.'.'.$name, $definition);
            }
        }

        if ('pinecone' === $type) {
            foreach ($stores as $name => $store) {
                $arguments = [
                    '$namespace' => $store['namespace'],
                ];

                if (array_key_exists('filter', $store)) {
                    $arguments['$filter'] = $store['filter'];
                }

                if (array_key_exists('top_k', $store)) {
                    $arguments['$topK'] = $store['top_k'];
                }

                $definition = new Definition(PineconeStore::class);
                $definition
                    ->setAutowired(true)
                    ->addTag('llm_chain.store')
                    ->setArguments($arguments);

                $container->setDefinition('llm_chain.store.'.$type.'.'.$name, $definition);
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processEmbedderConfig(int|string $name, array $config, ContainerBuilder $container): void
    {
        ['name' => $modelName, 'version' => $version, 'options' => $options] = $config['model'];

        $modelClass = match (strtolower($modelName)) {
            'embeddings' => Embeddings::class,
            'voyage' => Voyage::class,
            default => throw new \InvalidArgumentException(sprintf('Model "%s" is not supported.', $modelName)),
        };
        $modelDefinition = (new Definition($modelClass));
        if (null !== $version) {
            $modelDefinition->setArgument('$version', $version);
        }
        if (0 !== count($options)) {
            $modelDefinition->setArgument('$options', $options);
        }
        $modelDefinition->addTag('llm_chain.model.embeddings_model');
        $container->setDefinition('llm_chain.embedder.'.$name.'.embeddings', $modelDefinition);

        $definition = (new ChildDefinition('llm_chain.embedder.abstract'))
            ->replaceArgument('$embeddings', new Reference('llm_chain.embedder.'.$name.'.embeddings'));

        $container->setDefinition('llm_chain.embedder.'.$name, $definition);
    }
}
