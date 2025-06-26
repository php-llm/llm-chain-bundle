<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Tests\DependencyInjection;

use PhpLlm\LlmChainBundle\Contract\ContractInterface;
use PhpLlm\LlmChainBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    #[Test]
    public function platformContractConfigurationIsOptional(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        // Test that contract is optional and defaults to null
        $config = $processor->processConfiguration($configuration, [
            'llm_chain' => [
                'platform' => [
                    'openai' => [
                        'api_key' => 'test-key',
                    ],
                ],
            ],
        ]);

        self::assertArrayHasKey('platform', $config);
        self::assertArrayHasKey('openai', $config['platform']);
        self::assertArrayHasKey('contract', $config['platform']['openai']);
        self::assertNull($config['platform']['openai']['contract']);
    }

    #[Test]
    public function platformContractCanBeConfigured(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        // Test that contract can be set
        $config = $processor->processConfiguration($configuration, [
            'llm_chain' => [
                'platform' => [
                    'openai' => [
                        'api_key' => 'test-key',
                        'contract' => '@my_custom_contract_service',
                    ],
                ],
            ],
        ]);

        self::assertArrayHasKey('platform', $config);
        self::assertArrayHasKey('openai', $config['platform']);
        self::assertArrayHasKey('contract', $config['platform']['openai']);
        self::assertSame('@my_custom_contract_service', $config['platform']['openai']['contract']);
    }

    #[Test]
    public function allPlatformTypesAcceptContractConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, [
            'llm_chain' => [
                'platform' => [
                    'anthropic' => [
                        'api_key' => 'test-key',
                        'contract' => '@anthropic_contract',
                    ],
                    'google' => [
                        'api_key' => 'test-key',
                        'contract' => '@google_contract',
                    ],
                    'openai' => [
                        'api_key' => 'test-key',
                        'contract' => '@openai_contract',
                    ],
                    'mistral' => [
                        'api_key' => 'test-key',
                        'contract' => '@mistral_contract',
                    ],
                    'openrouter' => [
                        'api_key' => 'test-key',
                        'contract' => '@openrouter_contract',
                    ],
                    'azure' => [
                        'gpt_deployment' => [
                            'api_key' => 'test-key',
                            'base_url' => 'https://test.openai.azure.com',
                            'deployment' => 'gpt-4',
                            'api_version' => '2023-03-15-preview',
                            'contract' => '@azure_contract',
                        ],
                    ],
                ],
            ],
        ]);

        // Check all platforms have the contract key configured
        self::assertSame('@anthropic_contract', $config['platform']['anthropic']['contract']);
        self::assertSame('@google_contract', $config['platform']['google']['contract']);
        self::assertSame('@openai_contract', $config['platform']['openai']['contract']);
        self::assertSame('@mistral_contract', $config['platform']['mistral']['contract']);
        self::assertSame('@openrouter_contract', $config['platform']['openrouter']['contract']);
        self::assertSame('@azure_contract', $config['platform']['azure']['gpt_deployment']['contract']);
    }
}