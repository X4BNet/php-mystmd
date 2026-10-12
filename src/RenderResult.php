<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** HTML and the document/diagnostics which produced it. */
final readonly class RenderResult implements \Stringable, \JsonSerializable
{
    /** @var array<string, mixed> */
    public array $ast;
    /** @var list<Diagnostic> */
    public array $diagnostics;

    /** Construct a render result without discarding parse diagnostics. */
    public function __construct(public string $html, public Document $document)
    {
        $this->ast = $document->ast;
        $this->diagnostics = $document->diagnostics;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return ['html' => $this->html, 'ast' => $this->ast, 'diagnostics' => $this->diagnostics];
    }

    /** Return the rendered HTML. */
    public function __toString(): string
    {
        return $this->html;
    }
}
