<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Tests\DependencyInjection;

use PhpLlm\LlmChainBundle\Contract\ContractInterface;
use PhpLlm\LlmChainBundle\DependencyInjection\LlmChainExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class LlmChainExtensionTest extends TestCase
{
    #[Test]
    public function contractInterfaceIsRegisteredForAutoconfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $extension = new LlmChainExtension();

        $extension->load([], $container);

        $autoconfiguredInterfaces = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(ContractInterface::class, $autoconfiguredInterfaces);
        
        $contractConfig = $autoconfiguredInterfaces[ContractInterface::class];
        $tags = $contractConfig->getTags();
        self::assertArrayHasKey('llm_chain.platform.contract', $tags);
    }

    #[Test]
    public function platformWithoutContractWorksAsNormal(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $extension = new LlmChainExtension();

        $config = [
            'llm_chain' => [
                'platform' => [
                    'openai' => [
                        'api_key' => 'test-key',
                    ],
                ],
            ],
        ];

        $extension->load([$config['llm_chain']], $container);

        // Platform should be registered
        self::assertTrue($container->hasDefinition('llm_chain.platform.openai'));
        
        $definition = $container->getDefinition('llm_chain.platform.openai');
        $arguments = $definition->getArguments();
        
        // Should have the API key argument
        self::assertArrayHasKey('$apiKey', $arguments);
        self::assertSame('test-key', $arguments['$apiKey']);
    }

    #[Test]
    public function platformWithContractInjectsContractService(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        
        // Register a mock contract service
        $container->register('my_custom_contract', ContractInterface::class);
        
        $extension = new LlmChainExtension();

        $config = [
            'llm_chain' => [
                'platform' => [
                    'openai' => [
                        'api_key' => 'test-key',
                        'contract' => 'my_custom_contract',
                    ],
                ],
            ],
        ];

        $extension->load([$config['llm_chain']], $container);

        // Platform should be registered
        self::assertTrue($container->hasDefinition('llm_chain.platform.openai'));
        
        $definition = $container->getDefinition('llm_chain.platform.openai');
        $arguments = $definition->getArguments();
        
        // Should have the API key argument
        self::assertArrayHasKey('$apiKey', $arguments);
        self::assertSame('test-key', $arguments['$apiKey']);
        
        // Should have the contract argument as a service reference
        self::assertArrayHasKey('$contract', $arguments);
        self::assertInstanceOf(Reference::class, $arguments['$contract']);
        self::assertSame('my_custom_contract', (string) $arguments['$contract']);
    }

    #[Test]
    public function azurePlatformWithContractInjectsContractService(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        
        // Register a mock contract service
        $container->register('my_azure_contract', ContractInterface::class);
        
        $extension = new LlmChainExtension();

        $config = [
            'llm_chain' => [
                'platform' => [
                    'azure' => [
                        'gpt_deployment' => [
                            'api_key' => 'test-key',
                            'base_url' => 'https://test.openai.azure.com',
                            'deployment' => 'gpt-4',
                            'api_version' => '2023-03-15-preview',
                            'contract' => 'my_azure_contract',
                        ],
                    ],
                ],
            ],
        ];

        $extension->load([$config['llm_chain']], $container);

        // Platform should be registered
        self::assertTrue($container->hasDefinition('llm_chain.platform.azure.gpt_deployment'));
        
        $definition = $container->getDefinition('llm_chain.platform.azure.gpt_deployment');
        $arguments = $definition->getArguments();
        
        // Should have the contract argument as a service reference
        self::assertArrayHasKey('$contract', $arguments);
        self::assertInstanceOf(Reference::class, $arguments['$contract']);
        self::assertSame('my_azure_contract', (string) $arguments['$contract']);
    }
}