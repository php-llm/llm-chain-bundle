<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Profiler;

use PhpLlm\LlmChain\Model\Model;
use PhpLlm\LlmChain\Model\Response\AsyncResponse;
use PhpLlm\LlmChain\Model\Response\ResponseInterface;
use PhpLlm\LlmChain\PlatformInterface;

/**
 * @phpstan-type PlatformCallData array{
 *     model: Model,
 *     input: array<mixed>|string|object,
 *     options: array<string, mixed>,
 *     response: ResponseInterface,
 * }
 */
final class TraceablePlatform implements PlatformInterface
{
    /**
     * @var PlatformCallData[]
     */
    public array $calls = [];

    public function __construct(
        private readonly PlatformInterface $platform,
    ) {
    }

    public function request(Model $model, array|string|object $input, array $options = []): ResponseInterface
    {
        $response = $this->platform->request($model, $input, $options);

        $this->calls[] = [
            'model' => $model,
            'input' => is_object($input) ? clone $input : $input,
            'options' => $options,
            'response' => $response instanceof AsyncResponse ? $response->unwrap() : $response,
        ];

        return $response;
    }
}
