<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Tests\Profiler;

use PhpLlm\LlmChain\Chain\ToolBox\Metadata;
use PhpLlm\LlmChain\Chain\ToolBox\ToolBoxInterface;
use PhpLlm\LlmChain\Model\Response\ToolCall;
use PhpLlm\LlmChainBundle\Profiler\TraceableToolBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceableToolBox::class)]
#[Small]
final class TraceableToolBoxTest extends TestCase
{
    #[Test]
    public function getMap(): void
    {
        $metadata = new Metadata('Foo\Bar', 'bar', 'description', '__invoke', null);
        $toolBox = $this->createToolBox(['tool' => $metadata]);
        $traceableToolBox = new TraceableToolBox($toolBox);

        $map = $traceableToolBox->getMap();

        self::assertSame(['tool' => $metadata], $map);
    }

    #[Test]
    public function execute(): void
    {
        $metadata = new Metadata('Foo\Bar', 'bar', 'description', '__invoke', null);
        $toolBox = $this->createToolBox(['tool' => $metadata]);
        $traceableToolBox = new TraceableToolBox($toolBox);
        $toolCall = new ToolCall('foo', '__invoke');

        $result = $traceableToolBox->execute($toolCall);

        self::assertSame('tool_result', $result);
        self::assertCount(1, $traceableToolBox->calls);
        self::assertSame($toolCall, $traceableToolBox->calls[0]['call']);
        self::assertSame('tool_result', $traceableToolBox->calls[0]['result']);
    }

    /**
     * @param Metadata[] $metadata
     */
    private function createToolBox(array $metadata): ToolBoxInterface
    {
        return new class($metadata) implements ToolBoxInterface {
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
