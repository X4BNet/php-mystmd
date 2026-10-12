<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Component\Component;
use Splitice\Mystmd\Component\OutputKind;
use Splitice\Mystmd\Converter;
use Splitice\Mystmd\Extension\ComponentExtension;
use Splitice\Mystmd\HtmlRenderer;

/** Structural component parsing and explicit rendering capability boundaries. */
final class ComponentTest extends TestCase
{
    /** Parsing never executes trusted callbacks; quoted delimiters remain attributes. */
    public function testQuotedAttributesAndBlockPlacement(): void
    {
        $calls = [];
        $extension = new ComponentExtension([new Component('Port', static function (array $attributes) use (&$calls): string {
            $calls[] = $attributes;
            return '<form><input value="' . HtmlRenderer::escape($attributes['label']) . '"></form>';
        })]);
        $converter = new Converter(extensions: [$extension]);
        $document = $converter->parse("Before <\$Port label=\"a > b /&gt; c\" protocol='tcp' /> after");
        self::assertSame([], $calls);
        $result = $converter->render($document);
        self::assertSame([['label' => 'a > b /> c', 'protocol' => 'tcp']], $calls);
        self::assertSame('<p>Before </p>' . "\n" . '<form><input value="a &gt; b /&gt; c"></form>' . "\n" . '<p> after</p>', $result->html);
        self::assertSame([], $result->diagnostics);
    }

    /** Inline callbacks and equivalent directive syntax share named registration. */
    public function testInlineAndDirectiveForms(): void
    {
        $converter = new Converter(extensions: [new ComponentExtension([
            new Component('Badge', static fn (array $attrs): string => '<b>' . HtmlRenderer::escape($attrs['text']) . '</b>', OutputKind::Inline),
            new Component('Port', static fn (array $attrs): string => '<form data-port="' . HtmlRenderer::escape($attrs['From_0']) . '"></form>'),
        ])]);
        self::assertSame('<p>A <b>safe</b> badge</p>', $converter->convert('A <$Badge text="safe" /> badge')->html);
        self::assertSame('<form data-port="80"></form>', $converter->convert("```{Port}\n:From_0: 80\n```")->html);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidCases(): iterable
    {
        yield 'unknown' => ['<$Unknown />', 'TEMPLATE NOT FOUND: Unknown'];
        yield 'quoted marker' => ['<$Evil<script />', 'TEMPLATE NOT FOUND: Evil&lt;script'];
        yield 'unquoted attribute' => ['<$Port x=1 />', 'TEMPLATE ATTRS INVALID'];
        yield 'duplicate attribute' => ['<$Port x="1" x="2" />', 'TEMPLATE ATTRS INVALID'];
        yield 'missing end' => ['<$Port x="1"', 'TEMPLATE ATTRS INVALID'];
        yield 'not self closing' => ['<$Port>', 'TEMPLATE ATTRS INVALID'];
    }

    /** Invalid or unknown components never instantiate arbitrary application classes. */
    #[DataProvider('invalidCases')]
    public function testInvalidComponents(string $source, string $marker): void
    {
        $converter = new Converter(extensions: [new ComponentExtension([new Component('Port', static fn (array $attrs): string => 'UNEXPECTED EXECUTION')])]);
        $result = $converter->convert($source);
        self::assertSame('<div>' . $marker . '</div>', $result->html);
        self::assertCount(1, $result->diagnostics);
    }

    /** Code examples and converters without the extension never execute components. */
    public function testCodeExamplesAndExtensionIsolation(): void
    {
        $count = 0;
        $converter = new Converter(extensions: [new ComponentExtension([new Component('Port', static function (array $attrs) use (&$count): string {
            ++$count;
            return '<form></form>';
        })])]);
        $result = $converter->convert("`<\$Port />`\n\n```\n<\$Port />\n```\n\n    <\$Port />");
        self::assertSame(0, $count);
        self::assertSame(3, substr_count($result->html, '&lt;$Port /&gt;'));
        self::assertStringContainsString('&lt;$Port /&gt;', (new Converter())->convert('<$Port />')->html);
        self::assertSame('<form></form>', $converter->convert('<$Port />')->html);
        self::assertSame(1, $count);
    }
}
