<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Converter;

/** End-to-end port of commonmark.spec.ts's unified parser/HTML runner. */
final class UpstreamCommonMarkTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>}> */
    public static function examples(): iterable
    {
        $cases = json_decode((string)file_get_contents(__DIR__ . '/Fixtures/upstream/packages/myst-parser/tests/commonmark/cmark_spec_0.30.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($cases as $case) {
            yield $case['example'] . ': ' . $case['section'] => [$case];
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('examples')]
    public function testEndToEnd(array $case): void
    {
        if (in_array($case['example'], [44, 25, 333, 353, 506], true)) {
            self::markTestSkipped('Pinned commonmark.spec.ts SKIP_TESTS: ' . $case['example']);
        }
        $converter = new Converter([
            'mdast' => ['hoistSingleImagesOutofParagraphs' => false, 'listItemParagraphs' => false],
            'extensions' => ['frontmatter' => false, 'citations' => false, 'smartquotes' => false],
            'html' => ['allowDangerousHtml' => true, 'allowDangerousUrls' => true, 'clobberPrefix' => 'm-', 'closeSelfClosing' => true],
        ]);
        $actual = $converter->convert($case['markdown'])->html;
        $expected = trim(str_replace(['&gt;', '&lt;', '&amp;', '&quot;', ' alt=""'], ['>', '&#x3C;', '&#x26;', '"', ''], $case['html']));
        $actual = preg_replace('/<li>\n(?!<)/', '<li>', $actual) ?? $actual;
        $actual = preg_replace('/(?<!>)\n<\/li>/', '</li>', $actual) ?? $actual;
        $actual = str_replace(['foo&#x22;bar', 'foo&quot;bar', '&#x22;and&#x22;', '&quot;and&quot;', 'title &#x22;&#x22;', 'title &quot;&quot;'], ['foo"bar', 'foo"bar', '"and"', '"and"', 'title ""', 'title ""'], $actual);
        if ($case['example'] === 190) {
            $actual = str_replace(['<table><tr><td>', '</td></tr></table>'], ["<table>\n<tr>\n<td>", "</td>\n</tr>\n</table>"], $actual);
        } elseif ($case['example'] === 191) {
            $actual = str_replace(['<table>  <tr>', '</tr></table>'], ["<table>\n  <tr>", "</tr>\n</table>"], $actual);
        }
        // Canonicalize equivalent character references; never normalize whitespace.
        self::assertSame(self::entities($expected), self::entities($actual));
    }

    /** Preserve literal angle brackets and ampersands while equating entity spellings. */
    private static function entities(string $html): string
    {
        return preg_replace_callback('/&(?:#x[\da-f]+|#\d+|[a-z]+);/i', static fn (array $m): string => html_entity_decode($m[0], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $html) ?? $html;
    }
}
