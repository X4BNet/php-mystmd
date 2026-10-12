<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Syntax;

/** A single-line or fenced block recognized by an application parser. */
final readonly class BlockMatch
{
    /**
     * @param array<string, mixed> $node
     * @param \Closure|null $bodyParser
     * @phpstan-param (\Closure(string, \Splitice\Mystmd\Parser): array<string, mixed>)|null $bodyParser
     */
    public function __construct(public array $node, public ?string $fence = null, public ?\Closure $bodyParser = null)
    {
    }
}
