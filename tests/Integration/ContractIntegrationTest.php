<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Tests\Integration;

use PhpLlm\LlmChainBundle\Contract\ContractInterface;
use PhpLlm\LlmChainBundle\DependencyInjection\Configuration;
use PhpLlm\LlmChainBundle\DependencyInjection\LlmChainExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Integration tests that validate the full contract injection workflow.
 */
final class ContractIntegrationTest extends TestCase
{
    #[Test]
    public function fullConfigurationWorkflowWithContracts(): void
    {
        // 1. Test Configuration Processing
        $configuration = new Configuration();
        $processor = new Processor();
        
        $yamlConfig = [
            'llm_chain' => [
                'platform' => [
                    'openai' => [
                        'api_key' => 'sk-test123',
                        'contract' => 'my_openai_contract',
                    ],
                    'anthropic' => [
                        'api_key' => 'claude-test456',
                        'version' => '2023-06-01',
                        'contract' => 'my_anthropic_contract',
                    ],
                    'azure' => [
                        'main' => [
                            'api_key' => 'azure-test789',
                            'base_url' => 'https://test.openai.azure.com',
                            'deployment' => 'gpt-4',
                            'api_version' => '2023-03-15-preview',
                            'contract' => 'my_azure_contract',
                        ],
                    ],
                    'google' => [
                        'api_key' => 'google-test',
                        // No contract specified - should default to null
                    ],
                ],
            ],
        ];

        $processedConfig = $processor->processConfiguration($configuration, [$yamlConfig['llm_chain']]);
        
        // Verify configuration processing
        self::assertSame('my_openai_contract', $processedConfig['platform']['openai']['contract']);
        self::assertSame('my_anthropic_contract', $processedConfig['platform']['anthropic']['contract']);
        self::assertSame('my_azure_contract', $processedConfig['platform']['azure']['main']['contract']);
        self::assertNull($processedConfig['platform']['google']['contract']);

        // 2. Test Extension Processing
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        
        // Register mock contract services
        $container->register('my_openai_contract', ContractInterface::class);
        $container->register('my_anthropic_contract', ContractInterface::class);
        $container->register('my_azure_contract', ContractInterface::class);
        
        $extension = new LlmChainExtension();
        $extension->load([$processedConfig], $container);

        // 3. Verify Platform Service Definitions
        
        // OpenAI platform with contract
        self::assertTrue($container->hasDefinition('llm_chain.platform.openai'));
        $openaiDef = $container->getDefinition('llm_chain.platform.openai');
        $openaiArgs = $openaiDef->getArguments();
        self::assertArrayHasKey('$apiKey', $openaiArgs);
        self::assertSame('sk-test123', $openaiArgs['$apiKey']);
        self::assertArrayHasKey('$contract', $openaiArgs);
        self::assertInstanceOf(Reference::class, $openaiArgs['$contract']);
        self::assertSame('my_openai_contract', (string) $openaiArgs['$contract']);

        // Anthropic platform with contract and version
        self::assertTrue($container->hasDefinition('llm_chain.platform.anthropic'));
        $anthropicDef = $container->getDefinition('llm_chain.platform.anthropic');
        $anthropicArgs = $anthropicDef->getArguments();
        self::assertArrayHasKey('$apiKey', $anthropicArgs);
        self::assertSame('claude-test456', $anthropicArgs['$apiKey']);
        self::assertArrayHasKey('$version', $anthropicArgs);
        self::assertSame('2023-06-01', $anthropicArgs['$version']);
        self::assertArrayHasKey('$contract', $anthropicArgs);
        self::assertInstanceOf(Reference::class, $anthropicArgs['$contract']);
        self::assertSame('my_anthropic_contract', (string) $anthropicArgs['$contract']);

        // Azure platform with contract
        self::assertTrue($container->hasDefinition('llm_chain.platform.azure.main'));
        $azureDef = $container->getDefinition('llm_chain.platform.azure.main');
        $azureArgs = $azureDef->getArguments();
        self::assertArrayHasKey('$baseUrl', $azureArgs);
        self::assertArrayHasKey('$deployment', $azureArgs);
        self::assertArrayHasKey('$apiVersion', $azureArgs);
        self::assertArrayHasKey('$apiKey', $azureArgs);
        self::assertArrayHasKey('$contract', $azureArgs);
        self::assertInstanceOf(Reference::class, $azureArgs['$contract']);
        self::assertSame('my_azure_contract', (string) $azureArgs['$contract']);

        // Google platform without contract
        self::assertTrue($container->hasDefinition('llm_chain.platform.google'));
        $googleDef = $container->getDefinition('llm_chain.platform.google');
        $googleArgs = $googleDef->getArguments();
        self::assertArrayHasKey('$apiKey', $googleArgs);
        self::assertSame('google-test', $googleArgs['$apiKey']);
        // Should not have contract argument when null
        self::assertArrayNotHasKey('$contract', $googleArgs);

        // 4. Verify ContractInterface Autoconfiguration
        $autoconfigured = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(ContractInterface::class, $autoconfigured);
        $contractConfig = $autoconfigured[ContractInterface::class];
        $tags = $contractConfig->getTags();
        self::assertArrayHasKey('llm_chain.platform.contract', $tags);
    }

    #[Test]
    public function backwardCompatibilityWithoutContracts(): void
    {
        // Test that existing configurations without contracts continue to work
        $configuration = new Configuration();
        $processor = new Processor();
        
        $yamlConfig = [
            'llm_chain' => [
                'platform' => [
                    'openai' => [
                        'api_key' => 'sk-test123',
                        // No contract specified
                    ],
                ],
            ],
        ];

        $processedConfig = $processor->processConfiguration($configuration, [$yamlConfig['llm_chain']]);
        
        // Contract should default to null
        self::assertNull($processedConfig['platform']['openai']['contract']);

        // Extension should work without contract injection
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        
        $extension = new LlmChainExtension();
        $extension->load([$processedConfig], $container);

        self::assertTrue($container->hasDefinition('llm_chain.platform.openai'));
        $definition = $container->getDefinition('llm_chain.platform.openai');
        $arguments = $definition->getArguments();
        
        // Should have API key but no contract argument
        self::assertArrayHasKey('$apiKey', $arguments);
        self::assertSame('sk-test123', $arguments['$apiKey']);
        self::assertArrayNotHasKey('$contract', $arguments);
    }
}