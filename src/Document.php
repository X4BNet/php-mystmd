<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Parsed MyST document, independent of the underlying tokenizer. */
final class Document implements \JsonSerializable
{
    /**
     *
     * @param array<string, mixed> $ast
     * @param list<Diagnostic> $diagnostics
     */
    public function __construct(public array $ast, public array $diagnostics = [], public readonly string $source = '', public readonly ?string $origin = null)
    {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->ast;
    }
}
