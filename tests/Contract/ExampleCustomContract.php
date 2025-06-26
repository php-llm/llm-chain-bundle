<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Tests\Contract;

use PhpLlm\LlmChainBundle\Contract\ContractInterface;

/**
 * Example custom contract implementation for testing purposes.
 * 
 * This demonstrates how to implement a custom contract service that can be
 * injected into platform configurations via the bundle config.
 */
class ExampleCustomContract implements ContractInterface
{
    public function __construct(
        private readonly string $customSetting = 'default'
    ) {
    }

    public function getCustomSetting(): string
    {
        return $this->customSetting;
    }

    /**
     * Example method that could be used for custom contract logic.
     * In a real implementation, this might handle custom normalizers,
     * authentication, or request/response processing.
     */
    public function processCustomLogic(array $data): array
    {
        // Custom processing logic would go here
        return array_merge($data, ['processed_by' => self::class]);
    }
}