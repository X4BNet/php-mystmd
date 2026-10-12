<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use Dom\HTMLDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Converter;
use Splitice\Mystmd\Document;
use Splitice\Mystmd\Extension\ExtensionInterface;
use Splitice\Mystmd\Extension\FirstPartyExtension;
use Splitice\Mystmd\HtmlRenderer;
use Splitice\Mystmd\Numbering;
use Splitice\Mystmd\Registry;
use Splitice\Mystmd\Resolver\CitationResolverInterface;
use Splitice\Mystmd\Resolver\IncludedSource;
use Splitice\Mystmd\Resolver\IncludeResolverInterface;
use Splitice\Mystmd\Resolver\ReferenceResolverInterface;
use Splitice\Mystmd\Resolver\ResolutionContext;
use Splitice\Mystmd\Resolver\ResolverSet;
use Splitice\Mystmd\Resolver\ResourceResolverInterface;
use Splitice\Mystmd\Transforms;

/** Semantic HTML is tested through the public pipeline and the resulting DOM. */
final class SemanticHtmlTest extends TestCase
{
    private function converter(): Converter
    {
        return new Converter(['html' => ['profile' => 'semantic']]);
    }

    private static function dom(string $html): HTMLDocument
    {
        return HTMLDocument::createFromString('<!doctype html><html><body>' . $html . '</body></html>', LIBXML_NOERROR, 'UTF-8');
    }

    public function testProfilesAndNativeDropdowns(): void
    {
        $source = "```{dropdown} A **title**\n:open:\n\nBody `code`\n```";
        self::assertSame('<details></details>', (new Converter())->convert($source)->html);
        $result = $this->converter()->convert($source);
        $dom = self::dom($result->html);
        self::assertCount(1, $dom->querySelectorAll('details[open] > summary > strong'));
        self::assertSame('A titleBody code', $dom->body->textContent);
        self::assertSame([], $result->diagnostics);
        $this->expectException(\InvalidArgumentException::class);
        new Converter(['html' => ['profile' => 'unknown']]);
    }

    public function testFigureCaptionsAdmonitionsAndTableHeaders(): void
    {
        $source = "```{note} Custom title\nBody\n```\n\n```{figure} a.png\n:label: fig\n\nA **caption**\n```\n\n{ref}`fig` [](#fig)\n\n```{list-table}\n:header-rows: 2\n\n* - First\n  - Header\n* - Second\n  - Header\n* - Body\n  - Cell\n```\n\n```{list-table}\n* - No\n  - Headers\n```";
        $result = $this->converter()->convert($source);
        $dom = self::dom($result->html);
        self::assertCount(1, $dom->querySelectorAll('.admonition-title'));
        self::assertSame('Custom title', $dom->querySelector('.admonition-title')?->textContent);
        self::assertCount(1, $dom->querySelectorAll('figure#fig > figcaption strong'));
        self::assertSame('A caption', $dom->querySelector('a[href="#fig"]')?->textContent);
        self::assertCount(2, $dom->querySelectorAll('thead tr'));
        self::assertCount(4, $dom->querySelectorAll('th'));
        self::assertCount(4, $dom->querySelectorAll('tbody td'));
        self::assertSame([], $result->diagnostics);
    }

    /** @return iterable<string, array{string}> */
    public static function labeledBlocks(): iterable
    {
        yield 'paragraph' => ['Paragraph **text**'];
        yield 'list' => ["- One\n- Two"];
        yield 'quote' => ['> Quoted text'];
        yield 'code' => ["```php\n  preserved\n```"];
        yield 'image' => ['![Alt](image.png)'];
        yield 'directive' => ["```{note}\nA note\n```"];
        yield 'math' => ['$$x$$'];
    }

    #[DataProvider('labeledBlocks')]
    public function testLabeledBlocksAndReferenceSyntax(string $block): void
    {
        $source = "(Some_Label)=\n$block\n\n{ref}`Some_Label` [](#Some_Label) @Some_Label";
        $result = $this->converter()->convert($source);
        $dom = self::dom($result->html);
        self::assertCount(1, $dom->querySelectorAll('#some-label'));
        self::assertCount(3, $dom->querySelectorAll('a[href="#some-label"]'));
        foreach ($dom->querySelectorAll('a') as $a) {
            self::assertNotSame('', $a->textContent);
        }
        self::assertSame([], $result->diagnostics);
    }

    public function testUnicodeAndDuplicateLabelsHaveUniqueAnchors(): void
    {
        $result = $this->converter()->convert("(α)=\nFirst\n\n(α)=\nSecond\n\n{ref}`α` [](#%CE%B1)");
        $dom = self::dom($result->html);
        $ids = array_map(static fn ($e): string => $e->getAttribute('id'), iterator_to_array($dom->querySelectorAll('[id]')));
        self::assertCount(2, $ids);
        self::assertSame($ids, array_values(array_unique($ids)));
        foreach ($dom->querySelectorAll('a') as $a) {
            self::assertNotNull($dom->getElementById(substr($a->getAttribute('href'), 1)));
        }
        self::assertContains('identifier-is-unique', array_column($result->diagnostics, 'ruleId'));
    }

    public function testExplicitNumberingAndInputIsolation(): void
    {
        $converter = $this->converter();
        $document = $converter->parse("```{figure} test.png\n:label: fig\n\nCaption\n```\n\n{numref}`fig` [See {name}: {number}](#fig)");
        Transforms::containerChildren($document->ast);
        $numbering = new Numbering(['numbering' => ['figure' => ['enabled' => true, 'start' => 5, 'enumerator' => 'A%s']]]);
        $numbering->apply($document->ast);
        $before = json_encode($document, JSON_THROW_ON_ERROR);
        $result = $converter->render($document);
        self::assertStringContainsString('Figure A5', $result->html);
        self::assertStringContainsString('See Caption: A5', $result->html);
        self::assertSame('A5', $result->ast['children'][0]['children'][0]['enumerator']);
        self::assertSame($before, json_encode($document, JSON_THROW_ON_ERROR));
        self::assertEquals($result, $converter->render($document));
        $document->ast['children'][0]['children'][0]['enumerator'] = '0';
        $zero = $converter->render($document);
        self::assertSame('0', $zero->ast['children'][0]['children'][0]['enumerator']);
        self::assertStringContainsString('Figure 0', $zero->html);
    }

    public function testIncludePreparationPrecedesLocalAndExternalResolution(): void
    {
        $resolver = new class implements IncludeResolverInterface, CitationResolverInterface, ReferenceResolverInterface, ResourceResolverInterface {
            /** @var list<string> */
            public array $citations = [];
            /** @var list<array{string, ?string}> */
            public array $resources = [];
            public function resolveInclude(string $target, ResolutionContext $context): ?IncludedSource
            {
                return match ($target) {
                    'chapter' => new IncludedSource("```{include} child\n```", '/book/chapter'),
                    'child' => new IncludedSource("(included)=\n# Included heading\n\n![A](a.png)\n\n@paper", '/book/child'),
                    default => null,
                };
            }
            /**
             * @param array<string, mixed> $citation
             * @return array<string, mixed>
             */
            public function resolveCitation(array $citation, ResolutionContext $context): array
            {
                $this->citations[] = $citation['identifier'];
                return ['type' => 'text', 'value' => '(Citation ' . $citation['identifier'] . ')'];
            }
            /** @return array{url: string, title: string} */
            public function resolveReference(string $identifier, string $kind, ResolutionContext $context): array
            {
                return ['url' => 'https://example.test/external', 'title' => 'External'];
            }
            public function resolveResource(string $uri, ResolutionContext $context): string
            {
                $this->resources[] = [$uri, $context->origin];
                return '/assets/' . $uri;
            }
        };
        $converter = new Converter(['html' => ['profile' => 'semantic']], resolvers: new ResolverSet($resolver, $resolver, $resolver, $resolver));
        $source = "```{include} chapter\n```\n\n@included {cite}`included` [](#included) {ref}`external`";
        $parsed = $converter->parse($source, '/root');
        $before = json_encode($parsed, JSON_THROW_ON_ERROR);
        $result = $converter->render($parsed);
        self::assertSame(['paper', 'included'], $resolver->citations);
        self::assertSame([['a.png', '/book/child']], $resolver->resources);
        self::assertCount(2, self::dom($result->html)->querySelectorAll('a[href="#included"]'));
        self::assertStringContainsString('>External</a>', $result->html);
        self::assertStringNotContainsString('_includeContext', json_encode($result, JSON_THROW_ON_ERROR));
        self::assertSame([], $result->diagnostics);
        self::assertSame($before, json_encode($parsed, JSON_THROW_ON_ERROR));
        self::assertSame('<p>clean</p>', $converter->convert('clean')->html);
    }

    public function testFallbacksAreReadableAndEscaped(): void
    {
        $source = "@missing {ref}`unknown`\n\n```{mermaid}\n<script>alert(1)</script>\n  a --> b\n```\n\n```{anywidget} widget.js\n```\n\n<script>alert(2)</script>\n\n[bad](javascript:alert(3))";
        $result = $this->converter()->convert($source);
        $dom = self::dom($result->html);
        self::assertStringContainsString('@missing unknown', $dom->body->textContent);
        self::assertStringContainsString('widget.js', $dom->body->textContent);
        self::assertSame("<script>alert(1)</script>\n  a --> b", $dom->querySelector('pre code')?->textContent);
        self::assertCount(0, $dom->querySelectorAll('script, a[href^="javascript:"]'));
        self::assertContains('html-unsupported', array_column($result->diagnostics, 'ruleId'));
        self::assertContains('reference-unresolved', array_column($result->diagnostics, 'ruleId'));
        self::assertContains('citation-unresolved', array_column($result->diagnostics, 'ruleId'));
    }

    public function testCustomRenderersAndFirstPartyExtensionsKeepPrecedence(): void
    {
        $extension = new class implements ExtensionInterface {
            public function register(Registry $registry): void
            {
                $registry->renderer('tabItem', static fn (array $node, HtmlRenderer $renderer): string => $renderer->tag('section', [], HtmlRenderer::escape($node['title']) . $renderer->children($node['children'])));
                $registry->renderer('tabSet', static fn (array $node, HtmlRenderer $renderer): string => $renderer->children($node['children']));
                $registry->renderer('details', static fn (): string => '<p>Custom dropdown</p>');
            }
        };
        $converter = new Converter(['html' => ['profile' => 'semantic']], [new FirstPartyExtension(['tabs']), $extension]);
        $result = $converter->convert("::::{tab-set}\n:::{tab-item} Tab\nBody\n:::\n::::\n\n```{dropdown} Title\nIgnored by override\n```");
        self::assertStringContainsString('<section>Tab<p>Body</p></section>', $result->html);
        self::assertStringContainsString('<p>Custom dropdown</p>', $result->html);
        self::assertSame([], $result->diagnostics);
    }

    public function testJsonModelObjectsAreCopiedBeforeCallingRenderers(): void
    {
        $registry = new Registry();
        $registry->renderer('widget', static function (array $node): string {
            $node['model']->value = 'rendered';
            return 'widget';
        });
        $model = (object)['value' => 'parsed'];
        $document = new Document(['type' => 'root', 'children' => [['type' => 'widget', 'model' => $model]]]);
        $result = (new HtmlRenderer(registry: $registry))->render($document);
        self::assertSame('parsed', $model->value);
        self::assertSame('rendered', $result->ast['children'][0]['model']->value);
    }
}
