<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Converter;
use Splitice\Mystmd\Extension\MarkdownExtraExtension;

/** Legacy behavior is enabled explicitly and cannot leak into standard MyST. */
final class MarkdownExtraTest extends TestCase
{
    /** Existing heading syntax keeps attributes and demotes no further than h6. */
    public function testHeadingsAndAttributes(): void
    {
        $converter = new Converter(['html' => ['headingOffset' => 1]], [new MarkdownExtraExtension()]);
        self::assertSame('<h2 id="ID" class="wide">Title</h2>', $converter->convert('#Title {#ID .wide}')->html);
        self::assertSame('<h6 id="last">Last</h6>', $converter->convert('###### Last {#last}')->html);
        self::assertSame('<p>#Title</p>', (new Converter())->convert('#Title')->html);
        $heading = $converter->parse("Before\n\n#Title {kbd}`Ctrl`")->ast['children'][1];
        self::assertSame(3, $heading['position']['start']['line']);
        self::assertSame(3, $heading['children'][1]['position']['start']['line']);
    }

    /** Code is untouched and existing code classes and text punctuation survive. */
    public function testCodeListsAndText(): void
    {
        $converter = new Converter(['html' => ['focusableCode' => true]], [new MarkdownExtraExtension()]);
        $html = $converter->convert("\"Text\" @name and \$value\n\n- First\n- Second\n\n```bash\n#Comment\n```")->html;
        self::assertStringContainsString('<p>"Text" @name and $value</p>', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        self::assertStringContainsString('<li>First</li>', $html);
        self::assertStringContainsString('<pre tabindex="0" aria-label="Code block"><code class="bash">#Comment', $html);
        self::assertStringNotContainsString('<h', $html);
    }

    /** Raw HTML remains a separate caller choice. */
    public function testRawHtmlRequiresExplicitPermission(): void
    {
        self::assertSame('', (new Converter(extensions: [new MarkdownExtraExtension()]))->convert('<div>trusted</div>')->html);
        self::assertSame('<div>trusted</div>', (new Converter(['html' => ['allowDangerousHtml' => true]], [new MarkdownExtraExtension()]))->convert('<div>trusted</div>')->html);
    }

    /** Real KB tables contain blank lines and nested HTML with quoted delimiters. */
    public function testBalancedHtmlAndInlineWrappers(): void
    {
        $converter = new Converter(['html' => ['allowDangerousHtml' => true]], [new MarkdownExtraExtension()]);
        $table = "<table title=\"a > b\">\n<tr><td>One</td></tr>\n\n    <tr><td>Two</td></tr>\n</table>";
        self::assertSame($table . "\n<p>After</p>", $converter->convert($table . "\n\nAfter")->html);
        $nested = "<div>\n<div>Nested</div>\n\n<!-- </div> -->\nMore\n</div>";
        self::assertSame($nested, $converter->convert($nested)->html);
        self::assertSame('<p><img src="image.png" alt="Example" /></p>', $converter->convert('<img src="image.png" alt="Example" />')->html);
        self::assertStringContainsString('<img src="image.png" alt="Example">', $converter->convert("<center>\n![Example](image.png)\n</center>")->html);
        self::assertStringNotContainsString('![Example]', $converter->convert("<center>\n![Example](image.png)\n</center>")->html);
        self::assertSame('<p><img src="image.png" alt="Example"></p>', $converter->convert('![Example](image.png)')->html);
        self::assertSame('<p><img src="image.png" alt=""></p>', $converter->convert('![](image.png)')->html);
    }

    /** Legacy lists use a blank boundary and accept lightly indented continuation blocks. */
    public function testLegacyListBoundariesAndFencedExamples(): void
    {
        $converter = new Converter(extensions: [new MarkdownExtraExtension()]);
        self::assertSame("<p>Introduction\n- plain line\n- second line</p>", $converter->convert("Introduction\n- plain line\n- second line")->html);
        $source = "1. First\n\n  **Explanation**\n ```\ncommand\n ```\n  More details\n\n2. Second\n\n# End";
        $html = $converter->convert($source)->html;
        self::assertSame(1, substr_count($html, '<ol>'));
        self::assertSame(2, substr_count($html, '<li>'));
        self::assertStringContainsString("<pre><code>command\n</code></pre>", $html);
        self::assertStringContainsString('<p>More details</p>', $html);
        self::assertStringEndsWith('<h1>End</h1>', $html);
        self::assertSame(1, $converter->parse($source)->ast['children'][0]['position']['start']['line']);
        self::assertStringContainsString('<ul>', (new Converter())->convert("Introduction\n- plain line")->html);
        $mixed = $converter->convert(" - First\n - Second\n\n Continuation")->html;
        self::assertStringContainsString('<li>First</li>', $mixed);
        self::assertStringContainsString('<p>Second</p>', $mixed);
        self::assertStringContainsString('<p>Continuation</p>', $mixed);
        $nested = $converter->convert("- Parent\n  - Child\n  - Another\n- Sibling")->html;
        self::assertSame(2, substr_count($nested, '<ul>'));
        self::assertSame(4, substr_count($nested, '<li>'));
    }
}
