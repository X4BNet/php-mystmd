<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use Dom\HTMLDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Document;
use Splitice\Mystmd\HtmlRenderer;
use Splitice\Mystmd\Parser;

/** Additional independent upstream probes for whitespace, escaping, and Unicode. */
final class CompatibilityEdgeTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>}> */
    public static function cases(): iterable
    {
        $data = json_decode((string)file_get_contents(__DIR__ . '/Fixtures/reference-edge-cases.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($data['cases'] as $case) {
            yield $case['title'] => [$case];
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('cases')]
    public function testParserAndDiagnostics(array $case): void
    {
        $document = (new Parser($case['options']))->parse($case['source']);
        $actual = json_decode(json_encode($document->ast, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        if (str_starts_with($case['title'], 'Widget ')) {
            // nanoid() is deliberately random upstream; check identity properties instead.
            \Splitice\Mystmd\Transforms::walk($actual, static function (array &$node): void {
                if ($node['type'] === 'anywidget') {
                    self::assertNotEmpty($node['id']);
                    self::assertIsString($node['id']);
                    $node['id'] = '<generated-widget-id>';
                }
            });
            \Splitice\Mystmd\Transforms::walk($case['ast'], static function (array &$node): void {
                if ($node['type'] === 'anywidget') {
                    $node['id'] = '<generated-widget-id>';
                }
            });
        }
        self::assertEquals($case['ast'], $actual);
        self::assertEquals($case['diagnostics'], array_map(static fn ($d): array => ['message' => $d->message, 'ruleId' => $d->ruleId !== '' ? $d->ruleId : null, 'fatal' => $d->severity === 'error'], $document->diagnostics));
    }

    /** Whitespace-sensitive elements are checked before the upstream HTML normalization.
     * @param array<string, mixed> $case
     */
    #[DataProvider('cases')]
    public function testHtmlAndWhitespace(array $case): void
    {
        $html = (new HtmlRenderer())->render(new Document($case['ast']))->html;
        self::assertSame(self::whitespaceContent($case['html']), self::whitespaceContent($html));
        self::assertSame(UpstreamFixtureTest::normalizeHtml($case['html']), UpstreamFixtureTest::normalizeHtml($html));
    }

    /** @return list<string> */
    private static function whitespaceContent(string $html): array
    {
        $document = HTMLDocument::createFromString('<!doctype html><html><body>' . $html . '</body></html>', LIBXML_NOERROR, 'UTF-8');
        return array_map(static fn ($node): string => $node->textContent, iterator_to_array($document->querySelectorAll('pre, code, textarea')));
    }
}
