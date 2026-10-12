<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Converter;
use Splitice\Mystmd\Resolver\CitationResolverInterface;
use Splitice\Mystmd\Resolver\IncludedSource;
use Splitice\Mystmd\Resolver\IncludeResolverInterface;
use Splitice\Mystmd\Resolver\ReferenceResolverInterface;
use Splitice\Mystmd\Resolver\ResolutionContext;
use Splitice\Mystmd\Resolver\ResolverSet;
use Splitice\Mystmd\Resolver\ResourceResolverInterface;

/** Resolver capability boundaries and conversion isolation. */
final class ResolverTest extends TestCase
{
    /** Includes are inert without an explicitly supplied capability. */
    public function testNoImplicitFileAccess(): void
    {
        $result = (new Converter())->convert("```{include} /etc/passwd\n```");
        self::assertSame('<div file="/etc/passwd"></div>', $result->html);
        self::assertStringNotContainsString('root:', $result->html);
    }

    /** Local and external references can be mixed without changing the parsed input. */
    public function testExplicitResolversAndRepeatedConversions(): void
    {
        $resolvers = new class implements IncludeResolverInterface, ReferenceResolverInterface, CitationResolverInterface, ResourceResolverInterface {
            /** Return only this test's named document. */
            public function resolveInclude(string $target, ResolutionContext $context): ?IncludedSource
            {
                return $target === 'intro' ? new IncludedSource("(intro)=\n# Introduction\n\nIncluded **content**", 'test:intro') : null;
            }

            /** @return array{url: string, title: string}|null */
            public function resolveReference(string $identifier, string $kind, ResolutionContext $context): ?array
            {
                return $identifier === 'external' ? ['url' => 'https://example.test/guide?a=1&b=2', 'title' => 'External guide'] : null;
            }

            /**
             * @param array<string, mixed> $citation
             * @return array<string, mixed>|null
             */
            public function resolveCitation(array $citation, ResolutionContext $context): ?array
            {
                return $citation['identifier'] === 'paper' ? ['type' => 'text', 'value' => '(Author, 2026)'] : null;
            }

            /** Rewrite one approved resource. */
            public function resolveResource(string $uri, ResolutionContext $context): ?string
            {
                return $uri === 'image.png' ? '/assets/image.png' : null;
            }
        };
        $converter = new Converter(resolvers: new ResolverSet($resolvers, $resolvers, $resolvers, $resolvers));
        $parsed = $converter->parse("```{include} intro\n```\n\n{ref}`intro` {ref}`external` @paper\n\n![Example](image.png)", 'test:main');
        $before = json_encode($parsed);
        $result = $converter->render($parsed);
        self::assertStringContainsString('Included <strong>content</strong>', $result->html);
        self::assertStringContainsString('<a href="#intro">Introduction</a>', $result->html);
        self::assertStringContainsString('href="https://example.test/guide?a=1&amp;b=2"', $result->html);
        self::assertStringContainsString('(Author, 2026)', $result->html);
        self::assertStringContainsString('src="/assets/image.png"', $result->html);
        self::assertSame([], $result->diagnostics);
        self::assertSame($before, json_encode($parsed));
        self::assertSame($result->html, $converter->render($parsed)->html);
        self::assertSame('<p>Ordinary text</p>', $converter->convert('Ordinary text')->html);
        self::assertSame($result->ast, $result->jsonSerialize()['ast']);
    }

    /** Failed and cyclic includes produce diagnostics without reading further sources. */
    public function testIncludeFailuresAreBoundedAndDoNotLeak(): void
    {
        $includes = new class implements IncludeResolverInterface {
            /** Provide a cycle, unavailable target, and explicit failure. */
            public function resolveInclude(string $target, ResolutionContext $context): ?IncludedSource
            {
                return match ($target) {
                    'cycle' => new IncludedSource("```{include} cycle\n```", 'test:cycle'),
                    'throw' => throw new \RuntimeException('Unavailable source'),
                    default => null,
                };
            }
        };
        $converter = new Converter(resolvers: new ResolverSet(includes: $includes));
        foreach (['cycle', 'throw', 'missing'] as $target) {
            $result = $converter->convert("```{include} $target\n```");
            self::assertCount(1, $result->diagnostics);
            self::assertSame('include-resolve', $result->diagnostics[0]->ruleId);
            self::assertSame('error', $result->diagnostics[0]->severity);
        }
        self::assertSame([], $converter->convert('No includes')->diagnostics);
    }

    /** Nested resources inherit their own source identity and selected code retains whitespace. */
    public function testNestedOriginsAndLiteralSelection(): void
    {
        $resolver = new class implements IncludeResolverInterface, ResourceResolverInterface {
            /** @var list<array{string, ?string}> */
            public array $calls = [];

            /** Return deterministic nested content. */
            public function resolveInclude(string $target, ResolutionContext $context): ?IncludedSource
            {
                $this->calls[] = [$target, $context->origin];
                return match ($target) {
                    'chapter' => new IncludedSource("![A](a.png)\n\n```{include} child\n```", '/book/chapter.md'),
                    'child' => new IncludedSource('![B](b.png)', '/book/child.md'),
                    'code' => new IncludedSource("discard\n  indented\n\twith tab\n\n", '/book/example.txt'),
                    default => null,
                };
            }

            /** Resolve relative to the current include. */
            public function resolveResource(string $uri, ResolutionContext $context): string
            {
                $this->calls[] = [$uri, $context->origin];
                return '/assets/' . basename($context->origin ?? '') . '/' . $uri;
            }
        };
        $converter = new Converter(resolvers: new ResolverSet(includes: $resolver, resources: $resolver));
        $result = $converter->convert("```{include} chapter\n```\n\n![Root](root.png)", '/main.md');
        self::assertSame([], $result->diagnostics);
        self::assertContains(['child', '/book/chapter.md'], $resolver->calls);
        self::assertContains(['a.png', '/book/chapter.md'], $resolver->calls);
        self::assertContains(['b.png', '/book/child.md'], $resolver->calls);
        self::assertContains(['root.png', '/main.md'], $resolver->calls);
        self::assertStringNotContainsString('_includeContext', json_encode($result, JSON_THROW_ON_ERROR));
        $literal = $converter->convert("```{literalinclude} code\n:start-line: 1\n:lineno-match:\n```");
        $code = $literal->ast['children'][0]['children'][0];
        self::assertSame("  indented\n\twith tab\n\n", $code['value']);
        self::assertSame(2, $code['startingLineNumber']);
        self::assertStringContainsString("  indented\n\twith tab\n\n\n</code>", $literal->html);
        $captioned = $converter->convert("```{literalinclude} code\n:caption: A **caption**\n:name: sample\n```");
        $container = $captioned->ast['children'][0]['children'][0];
        self::assertSame('container', $container['type']);
        self::assertSame('sample', $container['identifier']);
        self::assertArrayNotHasKey('identifier', $container['children'][0]);
        self::assertSame('caption', $container['children'][1]['type']);
        self::assertStringContainsString('<strong>caption</strong>', $captioned->html);
    }

    /** Null and throwing external capabilities become diagnostics on each conversion. */
    public function testExternalFailuresDoNotEscapeOrLeak(): void
    {
        $resolver = new class implements CitationResolverInterface, ResourceResolverInterface, ReferenceResolverInterface {
            /** @param array<string, mixed> $citation @return array<string, mixed>|null */
            public function resolveCitation(array $citation, ResolutionContext $context): ?array
            {
                throw new \RuntimeException('Citation unavailable');
            }

            /** No resource is available. */
            public function resolveResource(string $uri, ResolutionContext $context): ?string
            {
                return null;
            }

            /** @return array<string, mixed>|null */
            public function resolveReference(string $identifier, string $kind, ResolutionContext $context): ?array
            {
                return null;
            }
        };
        $converter = new Converter(resolvers: new ResolverSet(references: $resolver, citations: $resolver, resources: $resolver));
        $source = '{ref}`missing` @paper ![image](missing.png)';
        $result = $converter->convert($source);
        self::assertCount(3, $result->diagnostics);
        self::assertSame(array_fill(0, 3, 'resolver-failed'), array_column($result->diagnostics, 'ruleId'));
        self::assertSame($result->html, $converter->convert($source)->html);
        self::assertSame([], $converter->convert('Clean')->diagnostics);
    }
}
