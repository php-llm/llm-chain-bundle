# LLM Chain Bundle

Symfony integration bundle for [php-llm/llm-chain](https://github.com/php-llm/llm-chain) library.

## Installation

```bash
composer require php-llm/llm-chain-bundle:@dev php-llm/llm-chain:@dev
```

## Example Configuration

```yaml
# config/packages/llm_chain.yaml
llm_chain:
    openai:
        api_key: '%env(OPENAI_API_KEY)%'
        model: 'gpt-4o'
        temperature: 1.0
```

## Usage

Use the tool chain service to leverage tool calling with GPT:
```php
use PhpLlm\LlmChain\Message\Message;
use PhpLlm\LlmChain\Message\MessageBag;
use PhpLlm\LlmChain\ToolChain;


final class MyService
{
    public function __construct(
        private ToolChain $toolChain
    ) {
    }
    
    public function processMessage(string $message): void
    {
        $messages = $this->loadMessageBad();
        $message = Message::ofUser($message);

        $response = $this->toolChain->call($message, $messages);

        $messages[] = $message;
        $messages[] = Message::ofAssistant($response);

        $this->saveMessages($messages);
    }
}
```
Extend the tool chain service to add your own tools:
```php
use PhpLlm\LlmChain\ToolBox\AsTool;
use Symfony\Component\Clock\ClockInterface;

#[AsTool('clock', 'Provides the current date and time')]
final class Clock
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
```
