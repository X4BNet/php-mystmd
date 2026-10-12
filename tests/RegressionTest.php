<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Converter;
use Splitice\Mystmd\Definition;
use Splitice\Mystmd\HtmlRenderer;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Registry;
use Splitice\Mystmd\Resolver\ResolverSet;

/** Independent regression expectations and the complete public conversion contract. */
final class RegressionTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>}> */
    public static function upstreamCases(): iterable
    {
        $fixture = json_decode((string)file_get_contents(__DIR__ . '/Fixtures/reference-regressions.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($fixture['cases'] as $case) {
            yield $case['title'] => [$case];
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('upstreamCases')]
    public function testUpstreamRegression(array $case): void
    {
        $converter = new Converter();
        $parsed = $converter->parse($case['source'], 'memory:regression');
        $jsonAst = json_decode(json_encode($parsed->ast, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(UpstreamFixtureTest::canonical($case['ast']), UpstreamFixtureTest::canonical($jsonAst));
        self::assertEquals($case['diagnostics'], array_map(static fn ($d): array => ['message' => $d->message, 'ruleId' => $d->ruleId !== '' ? $d->ruleId : null, 'fatal' => $d->severity === 'error'], $parsed->diagnostics));
        $before = json_encode($parsed, JSON_THROW_ON_ERROR);
        $rendered = $converter->render($parsed);
        if ($case['title'] === 'Cyclic footnotes') {
            // Upstream builds A's backlinks before discovering B's reference to A.
            // Compare all other output, then independently require complete backlinks.
            $withoutBacklinks = static fn (string $html): string => preg_replace('~ <a[^>]+data-footnote-backref[^>]*>.*?</a>~s', '', $html) ?? $html;
            self::assertSame(UpstreamFixtureTest::normalizeHtml($withoutBacklinks($case['html'])), UpstreamFixtureTest::normalizeHtml($withoutBacklinks($rendered->html)));
            $dom = \Dom\HTMLDocument::createFromString($rendered->html, LIBXML_NOERROR);
            self::assertCount(3, $dom->querySelectorAll('a[data-footnote-backref]'));
            foreach ($dom->querySelectorAll('a[href^="#"]') as $link) {
                self::assertNotNull($dom->getElementById(substr($link->getAttribute('href'), 1)));
            }
        } else {
            self::assertSame(UpstreamFixtureTest::normalizeHtml($case['html']), UpstreamFixtureTest::normalizeHtml($rendered->html));
        }
        self::assertEquals($rendered, $converter->convert($case['source'], 'memory:regression'));
        self::assertSame($before, json_encode($parsed, JSON_THROW_ON_ERROR));
        self::assertSame($rendered->ast, $rendered->document->ast);
        self::assertSame('memory:regression', $rendered->document->origin);
        self::assertSame($case['source'], $rendered->document->source);
        self::assertSame([], $converter->convert('clean')->diagnostics);
    }

    /** @return iterable<string, array{string}> */
    public static function profiles(): iterable
    {
        yield 'upstream' => ['upstream'];
        yield 'semantic' => ['semantic'];
    }

    #[DataProvider('profiles')]
    public function testEmptyResolverAndStandaloneRendererUseTheSameAstStage(string $profile): void
    {
        $source = "(target)=\n# Heading\n\n{ref}`target`";
        $options = ['profile' => $profile];
        $converter = new Converter(['html' => $options]);
        $parsed = $converter->parse($source, 'memory:origin');
        $a = $converter->render($parsed);
        $b = (new Converter(['html' => $options], resolvers: new ResolverSet()))->render($parsed);
        $c = (new HtmlRenderer($options))->render($parsed);
        self::assertEquals($a, $b);
        self::assertEquals($a, $c);
        self::assertSame('target', $a->ast['children'][0]['identifier']);
        self::assertTrue($a->ast['children'][1]['children'][0]['children'][0]['resolved']);
        self::assertSame('mystTarget', $parsed->ast['children'][0]['type']);
        self::assertSame($a->ast, $a->jsonSerialize()['ast']);
    }

    public function testRequiredParsedValuesDoNotCallApplicationCode(): void
    {
        $calls = 0;
        $registry = new Registry();
        $registry->directiveDefinition(new Definition(['name' => 'custom', 'options' => ['caption' => ['type' => 'myst', 'required' => true]]], static function (array $data) use (&$calls): array {
            ++$calls;
            return $data['options']['caption'];
        }));
        $parser = new Parser(registry: $registry);
        foreach (['[a, b]', '{key: value}', 'null', 'true', 'false', '0'] as $value) {
            $document = $parser->parse("```{custom}\n---\ncaption: $value\n---\n```");
            self::assertContains('error', array_column($document->diagnostics, 'severity'));
        }
        self::assertSame(0, $calls);
        $parser->parse("```{custom}\n:caption: valid\n```");
        self::assertSame(1, $calls);
    }

    public function testApplicationExceptionsAreNotSwallowedByValidation(): void
    {
        $registry = new Registry();
        $registry->directiveDefinition(new Definition(['name' => 'custom'], static fn (): array => throw new \LogicException('application failure')));
        $this->expectExceptionMessage('application failure');
        (new Parser(registry: $registry))->parse("```{custom}\n```");
    }
}
