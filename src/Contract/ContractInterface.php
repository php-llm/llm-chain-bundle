<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Contract;

/**
 * Interface for custom contract implementations that can be injected into Platform instances.
 * 
 * Implementing classes should provide the contract logic specific to their platform
 * (e.g., custom request/response handling, authentication, etc.).
 */
interface ContractInterface
{
    // Placeholder interface - the specific contract methods will depend on 
    // the actual Contract implementation in the llm-chain library.
    // This interface ensures type safety for the contract parameter.
}