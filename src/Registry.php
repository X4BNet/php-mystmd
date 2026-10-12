<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Registry scoped to a converter, with no process-global extension state. */
final class Registry
{
    /** @var array<string, mixed> */
    public array $defaults = [];
    /**
     * @var array<string, callable>
     * @phpstan-var array<string, callable(array<string, mixed>, Parser): array<string, mixed>>
     */
    public array $directives = [];
    /**
     * @var array<string, callable>
     * @phpstan-var array<string, callable(array<string, mixed>, Parser): array<string, mixed>>
     */
    public array $roles = [];
    /** @var array<string, callable(array<string, mixed>, HtmlRenderer): string> */
    public array $renderers = [];
    /** @var list<array{trigger: string, parse: \Closure}> */
    public array $inlineParsers = [];
    /** @var list<\Closure> */
    public array $blockParsers = [];
    /** @var list<callable(Document): void> */
    public array $transforms = [];

    /** Supply extension defaults, overridden by explicit converter options.
     * @param array<string, mixed> $options
     */
    public function configure(array $options): void
    {
        $this->defaults = array_replace_recursive($this->defaults, $options);
    }

    /**
     * @param callable $handler
     * @phpstan-param callable(array<string, mixed>, Parser): array<string, mixed> $handler
     */
    public function directive(string $name, callable $handler): void
    {
        $this->directives[$name] = $handler;
    }

    /**
     * @param callable $handler
     * @phpstan-param callable(array<string, mixed>, Parser): array<string, mixed> $handler
     */
    public function role(string $name, callable $handler): void
    {
        $this->roles[$name] = $handler;
    }

    /** Register a directive definition and each explicitly declared alias. */
    public function directiveDefinition(Definition $definition): void
    {
        foreach ([$definition->spec['name'], ...(array)($definition->spec['alias'] ?? [])] as $name) {
            $this->directive($name, $definition->apply(...));
        }
    }

    /** Register a role definition and each explicitly declared alias. */
    public function roleDefinition(Definition $definition): void
    {
        foreach ([$definition->spec['name'], ...(array)($definition->spec['alias'] ?? [])] as $name) {
            $this->role($name, $definition->apply(...));
        }
    }

    /** @param callable(array<string, mixed>, HtmlRenderer): string $handler */
    public function renderer(string $type, callable $handler): void
    {
        $this->renderers[$type] = $handler;
    }

    /** Register an inline parser using package-owned AST and match objects.
     * @param callable $parse
     * @phpstan-param callable(string, Parser): ?Syntax\InlineMatch $parse
     */
    public function inlineParser(string $trigger, callable $parse): void
    {
        if ($trigger === '') {
            throw new \InvalidArgumentException('Inline syntax triggers cannot be empty');
        }
        $this->inlineParsers[] = ['trigger' => $trigger, 'parse' => \Closure::fromCallable($parse)];
    }

    /** Register a single-line or fenced block grammar.
     * @param callable $parse
     * @phpstan-param callable(string, Parser): ?Syntax\BlockMatch $parse
     */
    public function blockParser(callable $parse): void
    {
        $this->blockParsers[] = \Closure::fromCallable($parse);
    }

    /** @param callable(Document): void $handler */
    public function transform(callable $handler): void
    {
        $this->transforms[] = $handler;
    }
}
