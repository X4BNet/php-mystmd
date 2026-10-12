<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

use Splitice\Mystmd\Extension\ExtensionInterface;

/** Convenient, reusable parse-and-render facade. */
final class Converter
{
    private Parser $parser;
    private HtmlRenderer $renderer;

    /**
     *
     * @param array<string, mixed> $options
     * @param list<ExtensionInterface> $extensions
     */
    public function __construct(array $options = [], array $extensions = [], private readonly ?Resolver\ResolverSet $resolvers = null)
    {
        $registry = new Registry();
        foreach ($extensions as $extension) {
            $extension->register($registry);
        }
        $this->parser = new Parser($options, $registry);
        $this->renderer = new HtmlRenderer($this->parser->options['html'] ?? [], $registry);
    }

    /** Convert Markdown while retaining its AST and diagnostics. */
    public function convert(string $source, ?string $origin = null): RenderResult
    {
        return $this->render($this->parse($source, $origin));
    }
    /** Parse independently of rendering, preserving diagnostics and source origin. */
    public function parse(string $source, ?string $origin = null): Document
    {
        $parsed = $this->parser->parse($source);
        return new Document($parsed->ast, $parsed->diagnostics, $source, $origin);
    }

    /** Render a parsed document using this converter's extensions and resolvers. */
    public function render(Document $document): RenderResult
    {
        if ($this->resolvers !== null) {
            $document = (new Resolver\ResolverPipeline($this->resolvers, $this->parser))->resolve($document);
        }
        return $this->renderer->render($document);
    }
}
