<?php

declare(strict_types=1);

namespace PhpLlm\LlmChainBundle\Tests\Profiler;

use PhpLlm\LlmChain\Chain\Toolbox\ToolboxInterface;
use PhpLlm\LlmChain\Platform\Response\ToolCall;
use PhpLlm\LlmChain\Platform\Tool\ExecutionReference;
use PhpLlm\LlmChain\Platform\Tool\Tool;
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
        $metadata = new Tool(new ExecutionReference('Foo\Bar'), 'bar', 'description', null);
        $toolbox = $this->createToolbox(['tool' => $metadata]);
        $traceableToolbox = new TraceableToolbox($toolbox);

        $map = $traceableToolbox->getTools();

        self::assertSame(['tool' => $metadata], $map);
    }

    #[Test]
    public function execute(): void
    {
        $metadata = new Tool(new ExecutionReference('Foo\Bar'), 'bar', 'description', null);
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
     * @param Tool[] $tools
     */
    private function createToolbox(array $tools): ToolboxInterface
    {
        return new class($tools) implements ToolboxInterface {
            public function __construct(
                private readonly array $tools,
            ) {
            }

            public function getTools(): array
            {
                return $this->tools;
            }

            public function execute(ToolCall $toolCall): string
            {
                return 'tool_result';
            }
        };
    }
}
