<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

use Splitice\Mystmd\Diagnostic;
use Splitice\Mystmd\Document;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Internal\RenderPreparation;
use Splitice\Mystmd\Transforms;

/** Applies caller capabilities to a copy of a document, preserving parse results. */
final class ResolverPipeline
{
    /** Bind immutable capabilities and a parser with the same extension registry. */
    public function __construct(private readonly ResolverSet $resolvers, private readonly Parser $parser)
    {
    }

    /** Resolve includes before labels, then external references and resources. */
    public function resolve(Document $document): Document
    {
        $resolved = $this->expandIncludes($document);
        RenderPreparation::prepare($resolved, ($this->parser->options['html']['profile'] ?? 'upstream') === 'semantic');
        $this->resolveExternal($resolved);
        return $resolved;
    }

    /** Expand includes on a copy before the renderer prepares local targets. */
    public function expandIncludes(Document $document): Document
    {
        $resolved = $document->copy();
        $context = new ResolutionContext($resolved, $document->origin, $document->origin !== null ? [$document->origin] : []);
        $this->includes($resolved->ast, $context, $resolved);
        return $resolved;
    }

    /** Apply external capabilities only after local references have been resolved. */
    public function resolveExternal(Document $document): void
    {
        $context = new ResolutionContext($document, $document->origin, $document->origin !== null ? [$document->origin] : []);
        $this->references($document->ast, $context, $document);
    }

    /** Resolve descendants using the origin of the include that introduced them.
     * @param array<string, mixed> $node
     */
    private function references(array &$node, ResolutionContext $context, Document $resolved): void
    {
        $includeContext = $node['_includeContext'] ?? null;
        if (is_array($includeContext)) {
            $context = new ResolutionContext($resolved, $includeContext[0], $includeContext[1]);
            unset($node['_includeContext']);
        }
        foreach ($node['children'] ?? [] as $index => $child) {
            $this->references($child, $context, $resolved);
            $node['children'][$index] = $child;
        }
        try {
            if ($node['type'] === 'crossReference' && !($node['resolved'] ?? false) && $this->resolvers->references !== null) {
                $reference = $this->resolvers->references->resolveReference($node['identifier'] ?? $node['label'] ?? '', $node['kind'] ?? 'ref', $context);
                if ($reference === null) {
                    throw new \RuntimeException('External reference was not found');
                }
                $node = array_replace($node, $reference, ['resolved' => true]);
                $node['children'] ??= [['type' => 'text', 'value' => $reference['title'] ?? $node['label'] ?? $node['identifier'] ?? '']];
            } elseif ($node['type'] === 'cite' && $this->resolvers->citations !== null) {
                $citation = $this->resolvers->citations->resolveCitation($node, $context);
                if ($citation === null) {
                    throw new \RuntimeException('Citation was not found');
                }
                $node = $citation + array_intersect_key($node, ['position' => true]);
            } elseif (in_array($node['type'], ['image', 'iframe'], true) && $this->resolvers->resources !== null) {
                $key = $node['type'] === 'image' ? 'url' : 'src';
                $resource = $this->resolvers->resources->resolveResource($node[$key], $context);
                if ($resource === null) {
                    throw new \RuntimeException('Resource was not found');
                }
                $node[$key] = $resource;
            }
        } catch (\Throwable $exception) {
            $resolved->diagnostics[] = new Diagnostic($exception->getMessage(), 'resolver-failed', 'error', $node['position'] ?? null);
        }
    }

    /**
     * @param array<string, mixed> $tree
     */
    private function includes(array &$tree, ResolutionContext $context, Document $output): void
    {
        Transforms::walk($tree, function (array &$node) use ($context, $output): void {
            if ($node['type'] !== 'include' || $this->resolvers->includes === null) {
                return;
            }
            try {
                if (count($context->ancestry) >= $this->resolvers->maxIncludeDepth) {
                    throw new \RuntimeException('Maximum include depth exceeded');
                }
                $included = $this->resolvers->includes->resolveInclude($node['file'], $context);
                if ($included === null) {
                    throw new \RuntimeException('Include was not found: ' . $node['file']);
                }
                if (in_array($included->identity, $context->ancestry, true)) {
                    throw new \RuntimeException('Include cycle: ' . $included->identity);
                }
                $beforeWarnings = count($this->parser->diagnostics());
                $filtered = IncludeFilter::apply($node['filter'] ?? null, $included->source, $this->parser);
                array_push($output->diagnostics, ...array_slice($this->parser->diagnostics(), $beforeWarnings));
                $source = $filtered['content'];
                if (($node['startingLineNumber'] ?? null) === 'match') {
                    $node['startingLineNumber'] = $filtered['startingLineNumber'] ?? 1;
                }
                if ($node['literal'] ?? false) {
                    $code = ['type' => 'code', 'value' => $source] + array_intersect_key($node, array_flip(['lang', 'identifier', 'label', 'html_id', 'class', 'showLineNumbers', 'startingLineNumber', 'emphasizeLines', 'filename', 'position']));
                    if (isset($node['caption'])) {
                        $target = array_intersect_key($code, array_flip(['identifier', 'label', 'html_id']));
                        $code = array_diff_key($code, $target);
                        $node = ['type' => 'container', 'kind' => 'code', 'children' => [$code, ['type' => 'caption', 'children' => [['type' => 'paragraph', 'children' => $node['caption']]]]]] + $target + array_intersect_key($node, ['position' => true]);
                    } else {
                        $node = $code;
                    }
                } else {
                    $nested = $this->parser->parse($source);
                    $nestedContext = new ResolutionContext($nested, $included->identity, [...$context->ancestry, $included->identity]);
                    $this->includes($nested->ast, $nestedContext, $output);
                    array_push($output->diagnostics, ...$nested->diagnostics);
                    $node = ['type' => 'root', 'children' => $nested->ast['children'], '_includeContext' => [$included->identity, $nestedContext->ancestry]] + array_intersect_key($node, ['position' => true]);
                }
            } catch (\Throwable $exception) {
                $output->diagnostics[] = new Diagnostic($exception->getMessage(), 'include-resolve', 'error', $node['position'] ?? null);
            }
        });
    }
}
