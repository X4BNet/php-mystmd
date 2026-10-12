<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Component;

/** An explicitly registered, trusted rendering callback. */
final readonly class Component
{
    /**
     * @param \Closure $render
     * @phpstan-param \Closure(array<string, string>): string $render
     */
    public function __construct(public string $name, public \Closure $render, public OutputKind $output = OutputKind::Block)
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $name)) {
            throw new \InvalidArgumentException('Invalid component name');
        }
    }
}
