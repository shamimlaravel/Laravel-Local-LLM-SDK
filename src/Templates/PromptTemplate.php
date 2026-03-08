<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Templates;

use InvalidArgumentException;

final class PromptTemplate
{
    /**
     * @var array<string, mixed>
     */
    private array $variables = [];

    public function __construct(
        private string $template,
    ) {}

    public static function make(string $template): self
    {
        return new self($template);
    }

    public function with(string $key, mixed $value): self
    {
        $this->variables[$key] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function withVariables(array $variables): self
    {
        foreach ($variables as $key => $value) {
            $this->variables[$key] = $value;
        }

        return $this;
    }

    public function render(): string
    {
        $result = $this->template;

        foreach ($this->variables as $key => $value) {
            $placeholder = '{{ ' . $key . ' }}';

            if (str_contains($result, $placeholder)) {
                $result = str_replace($placeholder, (string) $value, $result);
            }

            $placeholder2 = '{{' . $key . '}}';
            if (str_contains($result, $placeholder2)) {
                $result = str_replace($placeholder2, (string) $value, $result);
            }
        }

        $result = preg_replace('/\{\{\s*[\w.]+\s*\}\}/', '', $result) ?? $result;

        return trim($result);
    }

    public function renderJson(): string
    {
        $result = $this->render();

        return json_encode($result, JSON_THROW_ON_ERROR);
    }

    public static function systemPrompt(string $role): self
    {
        return new self("You are {{role}}. {{#if context}}Context: {{context}}{{/if}}");
    }

    public static function qaPrompt(): self
    {
        return new self(<<<'PROMPT'
You are a helpful AI assistant.

{{#if system_prompt}}
System: {{system_prompt}}
{{/if}}

Context:
{{context}}

User Question: {{question}}

Instructions: {{#if instructions}}{{instructions}}{{else}}Answer the question based on the provided context.{{/if}}

Answer:
PROMPT
        );
    }

    public static function extractionPrompt(string $entityType): self
    {
        return new self(<<<'PROMPT'
Extract {{entity_type}} information from the following text.

{{#if schema}}
Use this schema:
{{schema}}
{{/if}}

Text:
{{text}}

{{#if examples}}
Examples:
{{examples}}
{{/if}}

Extract and return the information in JSON format:
PROMPT
        );
    }

    public static function summarizationPrompt(): self
    {
        return new self(<<<'PROMPT'
Summarize the following text.

{{#if style}}
Style: {{style}}
{{/if}}

{{#if max_length}}
Maximum length: {{max_length}} words
{{/if}}

Text to summarize:
{{text}}

Summary:
PROMPT
        );
    }

    public static function classificationPrompt(): self
    {
        return new self(<<<'PROMPT'
Classify the following input into one of the provided categories.

Categories:
{{categories}}

{{#if multi_class}}
(Multiple classifications allowed)
{{/if}}

Input:
{{input}}

{{#if context}}
Context: {{context}}
{{/if}}

Classification:
PROMPT
        );
    }

    public static function codeReviewPrompt(): self
    {
        return new self(<<<'PROMPT'
You are a code reviewer. Review the following code for:
- Bugs and errors
- Security vulnerabilities
- Performance issues
- Code style and best practices

Language: {{language}}

Code:
```{{language}}
{{code}}
```

{{#if focus}}
Focus areas: {{focus}}
{{/if}}

Review:
PROMPT
        );
    }

    public static function conversationPrompt(): self
    {
        return new self(<<<'PROMPT'
{{#each messages}}
{{#if (eq role "system")}}
System: {{content}}
{{else if (eq role "user")}}
User: {{content}}
{{else if (eq role "assistant")}}
Assistant: {{content}}
{{/if}}
{{/each}}

{{#if context}}
Context: {{context}}
{{/if}}
PROMPT
        );
    }
}
