<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Tests\Profiler;

use PhpLlm\LlmChain\Chain\Toolbox\ExecutionReference;
use PhpLlm\LlmChain\Chain\Toolbox\Metadata;
use PhpLlm\LlmChain\Chain\Toolbox\ToolboxInterface;
use PhpLlm\LlmChain\Model\Response\ToolCall;
use PhpLlm\LlmChainBundle\Profiler\TraceableToolbox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceableToolbox::class)]
#[Small]
final class TraceableToolboxTest extends TestCase
{
    #[Test]
    public function getMap(): void
    {
        $metadata = new Metadata(new ExecutionReference('Foo\Bar'), 'bar', 'description', null);
        $toolbox = $this->createToolbox(['tool' => $metadata]);
        $traceableToolbox = new TraceableToolbox($toolbox);

        $map = $traceableToolbox->getMap();

        self::assertSame(['tool' => $metadata], $map);
    }

    #[Test]
    public function execute(): void
    {
        $metadata = new Metadata(new ExecutionReference('Foo\Bar'), 'bar', 'description', null);
        $toolbox = $this->createToolbox(['tool' => $metadata]);
        $traceableToolbox = new TraceableToolbox($toolbox);
        $toolCall = new ToolCall('foo', '__invoke');

        $result = $traceableToolbox->execute($toolCall);

        self::assertSame('tool_result', $result);
        self::assertCount(1, $traceableToolbox->calls);
        self::assertSame($toolCall, $traceableToolbox->calls[0]['call']);
        self::assertSame('tool_result', $traceableToolbox->calls[0]['result']);
    }

    /**
     * @param Metadata[] $metadata
     */
    private function createToolbox(array $metadata): ToolboxInterface
    {
        return new class($metadata) implements ToolboxInterface {
            public function __construct(
                private readonly array $metadata,
            ) {
            }

            public function getMap(): array
            {
                return $this->metadata;
            }

            public function execute(ToolCall $toolCall): string
            {
                return 'tool_result';
            }
        };
    }
}
