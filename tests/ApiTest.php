<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Component\Component;
use Splitice\Mystmd\Component\OutputKind;
use Splitice\Mystmd\Converter;
use Splitice\Mystmd\Definition;
use Splitice\Mystmd\Document;
use Splitice\Mystmd\Extension\ComponentExtension;
use Splitice\Mystmd\Extension\ExtensionInterface;
use Splitice\Mystmd\HtmlRenderer;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Registry;
use Splitice\Mystmd\Syntax\BlockMatch;
use Splitice\Mystmd\Syntax\InlineMatch;

/** Exercise package-owned extension APIs, positions and conversion isolation. */
final class ApiTest extends TestCase
{
    /** Parser hooks can nest MyST without depending on CommonMark classes. */
    public function testCustomSyntaxAndTransforms(): void
    {
        $extension = new class implements ExtensionInterface {
            /** Register independent block and inline grammars and a transform. */
            public function register(Registry $registry): void
            {
                $registry->inlineParser('[[', static function (string $source, Parser $parser): ?InlineMatch {
                    if (!preg_match('/^\[\[(.+?)\]\]/u', $source, $match)) {
                        return null;
                    }
                    return new InlineMatch(['type' => 'mark', 'children' => $parser->parseInline($match[1])], mb_strlen($match[0]));
                });
                $registry->blockParser(static fn (string $source, Parser $parser): ?BlockMatch => $source === '!!!'
                    ? new BlockMatch(['type' => 'panel'], '!!!', static fn (string $body, Parser $nested): array => ['type' => 'panel', 'children' => $nested->parseBody($body)]) : null);
                $registry->renderer('mark', static fn (array $node, HtmlRenderer $renderer): string => $renderer->tag('mark', [], $renderer->children($node['children'])));
                $registry->renderer('panel', static fn (array $node, HtmlRenderer $renderer): string => $renderer->tag('section', [], $renderer->children($node['children'])));
                $registry->transform(static function (Document $document): void {
                    $document->ast['profile'] = 'custom';
                });
            }
        };
        $converter = new Converter(extensions: [$extension]);
        $source = "😀 [[é **bold**]]\n\n!!!\nNested [[word]]\n!!!";
        $document = $converter->parse($source, 'memory:article');
        self::assertSame('custom', $document->ast['profile']);
        $inline = $document->ast['children'][0]['children'][1];
        self::assertSame(['line' => 1, 'column' => 4], $inline['position']['start']);
        self::assertSame('mark', $inline['type']);
        self::assertSame(['line' => 4, 'column' => 1], $document->ast['children'][1]['children'][0]['position']['start']);
        $before = json_encode($document, JSON_THROW_ON_ERROR);
        $first = $converter->render($document);
        self::assertSame('<p>😀 <mark>é <strong>bold</strong></mark></p>' . "\n<section><p>Nested <mark>word</mark></p></section>", $first->html);
        self::assertSame($first->html, $converter->render($document)->html);
        self::assertSame($before, json_encode($document, JSON_THROW_ON_ERROR));
        self::assertSame('memory:article', $first->document->origin);
        self::assertArrayNotHasKey('profile', (new Converter())->parse('plain')->ast);
        self::assertStringNotContainsString('<mark>', (new Converter())->convert('[[word]]')->html);
    }

    /** Required typed values prevent execution; aliases and diagnostics stay isolated. */
    public function testDefinitionValidation(): void
    {
        $observer = new class {
            /** @var list<array<string, mixed>> */
            public array $calls = [];
        };
        $registry = new Registry();
        $registry->directiveDefinition(new Definition([
            'name' => 'count', 'body' => ['type' => 'myst', 'required' => true],
            'options' => ['total' => ['type' => 'number', 'required' => true, 'alias' => ['n']], 'enabled' => ['type' => 'boolean']],
        ], static function (array $data) use ($observer): array {
            $observer->calls[] = $data;
            return $data['body'];
        }));
        $parser = new Parser(registry: $registry);
        $bad = $parser->parse("```{count}\n:total: nope\n\nbody\n```");
        self::assertCount(0, $observer->calls);
        self::assertSame('number not provided for option "total" (in count)', $bad->diagnostics[0]->message);
        self::assertSame('error', $bad->diagnostics[0]->severity);
        $valid = $parser->parse("```{count}\n:n: 2\n:enabled: FALSE\n\n**body**\n```");
        self::assertSame([], $valid->diagnostics);
        self::assertSame(['total' => 2, 'enabled' => false], $observer->calls[0]['options']);
        self::assertSame('strong', $observer->calls[0]['body'][0]['children'][0]['type']);
        $duplicate = $parser->parse("```{count}\n:total: 3\n:n: 5\n:unknown: x\n\nbody\n```");
        self::assertCount(2, $duplicate->diagnostics);
        self::assertSame('option "total" used instead of "n" (in count)', $duplicate->diagnostics[0]->message);
        self::assertSame(3, $observer->calls[1]['options']['total']);
    }

    /** Callback failures preserve document state and never leak exception HTML. */
    public function testComponentCallbackFailureIsIsolated(): void
    {
        $converter = new Converter(extensions: [new ComponentExtension([new Component('Fail', static fn (array $attrs): string => throw new \RuntimeException('<script>private</script>'), OutputKind::Inline)])]);
        $document = $converter->parse('A <$Fail /> B');
        $result = $converter->render($document);
        self::assertSame('<p>A <span>TEMPLATE INVALID</span> B</p>', $result->html);
        self::assertSame('component-render', $result->diagnostics[0]->ruleId);
        self::assertSame([], $document->diagnostics);
        self::assertSame([], $converter->convert('clean')->diagnostics);
    }

    /** HTML and comment escaping cannot introduce active elements in the safe profile. */
    public function testEscapingAndRepeatedFootnotes(): void
    {
        $converter = new Converter();
        $result = $converter->convert("% --><script>alert(1)</script><!--\n\n<script>alert(2)</script>\n\n[unsafe](javascript:alert(3))\n\nx[^n] y[^n]\n\n[^n]: End");
        $dom = \Dom\HTMLDocument::createFromString($result->html, LIBXML_NOERROR);
        self::assertCount(0, $dom->querySelectorAll('script'));
        self::assertCount(0, $dom->querySelectorAll('a[href^="javascript:"]'));
        $ids = array_map(static fn ($node): string => $node->getAttribute('id'), iterator_to_array($dom->querySelectorAll('[id]')));
        self::assertSame($ids, array_values(array_unique($ids)));
        self::assertStringNotContainsString('footnote', $converter->convert('next')->html);
    }

    /** Recursive grammars stop at their configured bound and reset for the next document. */
    public function testNestingLimitDoesNotLeak(): void
    {
        $converter = new Converter(['maxNesting' => 2]);
        $result = $converter->convert(":::::{note}\n::::{note}\n:::{note}\nDeep\n:::\n::::\n:::::");
        self::assertContains('parse-nesting', array_map(static fn ($d): string => $d->ruleId, $result->diagnostics));
        self::assertSame([], $converter->convert('next')->diagnostics);
    }

    /** JSON5 models remain JSON serializable, including non-finite numeric values. */
    public function testWidgetModelsAreSerializable(): void
    {
        $result = (new Converter())->convert("```{anywidget} widget.js\n{infinity: Infinity, nested: [NaN, -Infinity], empty: {}}\n```");
        $serialized = json_encode($result, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('"infinity":null', $serialized);
        self::assertStringContainsString('"nested":[null,null]', $serialized);
        self::assertStringContainsString('"empty":{}', $serialized);
        self::assertSame([], $result->diagnostics);
    }
}
