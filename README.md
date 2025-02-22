# LLM Chain Bundle

Symfony integration bundle for [php-llm/llm-chain](https://github.com/php-llm/llm-chain) library.

## Installation

```bash
composer require php-llm/llm-chain-bundle
```

## Configuration

### Simple Example with OpenAI

```yaml
# config/packages/llm_chain.yaml
llm_chain:
    platform:
        openai:
            api_key: '%env(OPENAI_API_KEY)%'
    chain:
        default:
            model:
                name: 'GPT'
```

### Advanced Example with Anthropic, Azure, Google and multiple chains
```yaml
# config/packages/llm_chain.yaml
llm_chain:
    platform:
        anthropic:
            api_key: '%env(ANTHROPIC_API_KEY)%'
        azure:
            # multiple deployments possible
            gpt_deployment:
                base_url: '%env(AZURE_OPENAI_BASEURL)%'
                deployment: '%env(AZURE_OPENAI_GPT)%'
                api_key: '%env(AZURE_OPENAI_KEY)%'
                api_version: '%env(AZURE_GPT_VERSION)%'
        google:
            api_key: '%env(GOOGLE_API_KEY)%'
    chain:
        rag:
            platform: 'llm_chain.platform.azure.gpt_deployment'
            structured_output: false # Disables support for "output_structure" option, default is true
            model:
                name: 'GPT'
                version: 'gpt-4o-mini'
            system_prompt: 'You are a helpful assistant that can answer questions.' # The default system prompt of the chain
            tools:
                - 'PhpLlm\LlmChain\Chain\ToolBox\Tool\SimilaritySearch'
        research:
            platform: 'llm_chain.platform.anthropic'
            model:
                name: 'Claude'
            tools: # If undefined, all tools are injected into the chain, use "tools: false" to disable tools.
                - 'PhpLlm\LlmChain\Chain\ToolBox\Tool\Wikipedia'
            fault_tolerant_toolbox: false # Disables fault tolerant toolbox, default is true
    store:
        # also azure_search, mongodb and pinecone are supported as store type
        chroma_db:
            # multiple collections possible per type
            default:
                collection: 'my_collection'
    embedder:
        default:
            # platform: 'llm_chain.platform.anthropic'
            # store: 'llm_chain.store.chroma_db.default'
            model:
                name: 'Embeddings'
                version: 'text-embedding-ada-002'
```

## Usage

### Chain Service

Use the `Chain` service to leverage GPT:
```php
use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Model\Message\Message;
use PhpLlm\LlmChain\Model\Message\MessageBag;

final readonly class MyService
{
    public function __construct(
        private ChainInterface $chain,
    ) {
    }
    
    public function submit(string $message): string
    {
        $messages = new MessageBag(
            Message::forSystem('Speak like a pirate.'),
            Message::ofUser($message),
        );

        return $this->chain->call($messages);
    }
}
```

### Register Tools

To use existing tools, you can register them as a service:
```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    PhpLlm\LlmChain\Chain\ToolBox\Tool\Clock: ~
    PhpLlm\LlmChain\Chain\ToolBox\Tool\OpenMeteo: ~
    PhpLlm\LlmChain\Chain\ToolBox\Tool\SerpApi:
        $apiKey: '%env(SERP_API_KEY)%'
    PhpLlm\LlmChain\Chain\ToolBox\Tool\SimilaritySearch: ~
    PhpLlm\LlmChain\Chain\ToolBox\Tool\Tavily:
      $apiKey: '%env(TAVILY_API_KEY)%'
    PhpLlm\LlmChain\Chain\ToolBox\Tool\Wikipedia: ~
    PhpLlm\LlmChain\Chain\ToolBox\Tool\YouTubeTranscriber: ~
```

Custom tools can be registered by using the `#[AsTool]` attribute:
```php
use PhpLlm\LlmChain\Chain\ToolBox\Attribute\AsTool;

#[AsTool('company_name', 'Provides the name of your company')]
final class CompanyName
{
    public function __invoke(): string
    {
        return 'ACME Corp.'
    }
}
```

The chain configuration by default will inject all known tools into the chain.

To disable this behavior, set the `tools` option to `false`:
```yaml
llm_chain:
    chain:
        my_chain:
            tools: false
```

To inject only specific tools, list them in the configuration:
```yaml
llm_chain:
    chain:
        my_chain:
            tools:
                - 'PhpLlm\LlmChain\Chain\ToolBox\Tool\SimilaritySearch'
```

### Profiler

The profiler panel provides insights into the chain's execution:

![Profiler](./profiler.png)
