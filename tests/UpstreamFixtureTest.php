<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Transforms;
use Splitice\Mystmd\HtmlRenderer;
use Splitice\Mystmd\Document;
use Symfony\Component\Yaml\Yaml;

/** Direct port of myst-parser's myst.spec.ts and directives/roles YAML runners. */
final class UpstreamFixtureTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function htmlCases(): iterable
    {
        foreach (glob(__DIR__ . '/Fixtures/upstream/packages/myst-spec/docs/examples/*.yml') ?: [] as $file) {
            foreach (Yaml::parseFile($file)['cases'] ?? [] as $index => $case) {
                if (isset($case['html'])) {
                    yield basename($file) . ':' . $index . ':' . $case['title'] => [$case, basename($file)];
                }
            }
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('htmlCases')]
    public function testUpstreamHtml(array $case, string $file): void
    {
        if ($case['skip'] ?? false) {
            self::markTestSkipped('Skipped in the pinned upstream fixture: ' . $case['title']);
        }
        $actual = (new HtmlRenderer(['allowDangerousHtml' => true, 'allowDangerousUrls' => true, 'clobberPrefix' => 'm-', 'closeSelfClosing' => $file === 'cmark_spec_0.30.yml']))->render(new Document($case['mdast']))->html;
        // The pinned runner removes an empty alt attribute for CommonMark 580.
        $expected = $file === 'cmark_spec_0.30.yml' ? str_replace(' alt=""', '', $case['html']) : $case['html'];
        self::assertSame(self::normalizeHtml($expected), self::normalizeHtml($actual));
    }

    /** Match the upstream HTML runner's insignificant serialization differences. */
    public static function normalizeHtml(string $html): string
    {
        $html = preg_replace('/\n\s*/', '', trim($html)) ?? $html;
        $html = preg_replace_callback('/&(?:#x[\da-f]+|#\d+|[a-z]+);/i', static function (array $match): string {
            $decoded = html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return match ($decoded) { '<' => '&#x3C;', '&' => '&#x26;', default => $decoded };
        }, $html) ?? $html;
        $html = str_replace('<h2 id="footnote-label" class="sr-only">', '<h2 class="sr-only" id="footnote-label">', $html);
        return $html;
    }

    /** @return iterable<string, array{array<string, mixed>, bool, string}> */
    public static function cases(): iterable
    {
        $base = __DIR__ . '/Fixtures/upstream/packages/';
        foreach (['myst-spec/docs/examples/*.yml', 'myst-parser/tests/directives/*.yml', 'myst-parser/tests/roles/*.yml'] as $pattern) {
            foreach (glob($base . $pattern) ?: [] as $file) {
                $spec = str_contains($file, '/myst-spec/');
                foreach (Yaml::parseFile($file)['cases'] ?? [] as $index => $case) {
                    if (!isset($case['myst']) && !isset($case['markdown'])) {
                        continue;
                    }
                    yield basename($file) . ':' . $index . ':' . $case['title'] => [$case, $spec, basename($file)];
                }
            }
        }
    }

    /** Assert the original expected AST, using only the upstream runner's adapters.
     * @param array<string, mixed> $case
     */
    #[DataProvider('cases')]
    public function testUpstreamParse(array $case, bool $spec, string $file): void
    {
        if ($case['skip'] ?? false) {
            self::markTestSkipped('Skipped in the pinned upstream fixture: ' . $case['title']);
        }
        if ($file === 'cmark_spec_0.30.yml' && preg_match('/example (44|25|333|353|506)$/', $case['title'])) {
            self::markTestSkipped('Pinned myst.spec.ts SKIP_TESTS: ' . $case['title']);
        }
        $options = $spec ? ['mdast' => ['hoistSingleImagesOutofParagraphs' => false, 'listItemParagraphs' => false, 'nestBlocks' => false], 'extensions' => ['frontmatter' => false, 'citations' => false, 'smartquotes' => false]] : [];
        $document = (new Parser($options))->parse($case['myst'] ?? $case['markdown']);
        if ($spec && str_contains($case['myst'] ?? '', '{figure}')) {
            Transforms::containerChildren($document->ast);
        }
        self::assertSame(self::canonical($case['mdast']), self::canonical($document->ast, $spec));
        if (!$spec && str_contains($file, '.yml') && !str_contains($file, 'role')) {
            // The roles runner does not assert diagnostics; the directives runner does.
            if (array_key_exists('warnings', $case) || isset($case['markdown']) && str_starts_with($case['markdown'], '```')) {
                self::assertCount($case['warnings'] ?? 0, $document->diagnostics);
            }
        }
    }

    /** Remove positions and reproduce the explicitly documented spec adapters.
     * @param array<string|int, mixed> $node
     * @return array<string|int, mixed>
     */
    public static function canonical(array $node, bool $spec = false): array
    {
        unset($node['position']);
        if ($spec && ($node['type'] ?? '') === 'comment') {
            $node['type'] = 'mystComment';
        }
        if ($spec && ($node['type'] ?? '') === 'mystDirective') {
            unset($node['tight']);
            if (isset($node['value'])) {
                $node['value'] = trim($node['value']);
            }
            foreach ($node['options'] ?? [] as $key => $value) {
                if (is_string($value) && is_numeric($value)) {
                    $node['options'][$key] = $value + 0;
                } elseif (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
                    $node['options'][$key] = strtolower($value) === 'true';
                }
            }
        }
        foreach ($node as &$value) {
            if (is_array($value)) {
                $value = self::canonical($value, $spec);
            }
        }
        unset($value);
        if (!array_is_list($node)) {
            ksort($node);
        }
        return $node;
    }
}
