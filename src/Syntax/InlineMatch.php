<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Syntax;

/** A matched inline node; length counts Unicode characters, not bytes. */
final readonly class InlineMatch
{
    /** @param array<string, mixed> $node */
    public function __construct(public array $node, public int $length)
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('An inline match must consume source characters');
        }
    }
}
