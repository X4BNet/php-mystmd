<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Document;
use Splitice\Mystmd\HtmlRenderer;
use Splitice\Mystmd\LanguageTransforms;
use Splitice\Mystmd\Parser;

/** Handwritten ports of upstream schema and random-key property assertions. */
final class UpstreamManualTest extends TestCase
{
    /** myst-to-html/tests/schema.spec.ts: Converts a keyboard node to kbd. */
    public function testKeyboardSchema(): void
    {
        $tree = ['type' => 'root', 'children' => [['type' => 'paragraph', 'children' => [['type' => 'keyboard', 'children' => [['type' => 'text', 'value' => 'Ctrl']]]]]]];
        $html = (new HtmlRenderer())->render(new Document($tree))->html;
        $dom = \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $body = $dom->getElementsByTagName('body')->item(0);
        self::assertNotNull($body);
        $paragraph = $body->firstElementChild;
        self::assertNotNull($paragraph);
        self::assertSame('p', $paragraph->localName);
        self::assertSame('kbd', $paragraph->firstElementChild?->localName);
        self::assertSame('Ctrl', $body->textContent);
        self::assertSame(1, $body->childElementCount);
        self::assertSame(1, $paragraph->childElementCount);
    }

    /** myst-transforms/src/keys.spec.ts: Assigns a key. */
    public function testKeysTransform(): void
    {
        $args = [['children' => [['type' => 'node']]]];
        $result = LanguageTransforms::apply('keysTransform', $args, new Parser());
        self::assertNotEmpty($args[0]['children'][0]['key']);
        self::assertMatchesRegularExpression('/^[a-zA-Z]/', $args[0]['children'][0]['key']);
        self::assertSame($args[0], $result);
    }

    /** myst-transforms/src/keys.spec.ts: Test basic pipeline. */
    public function testKeysPlugin(): void
    {
        $args = [['type' => 'root', 'children' => [['type' => 'node']]]];
        $parser = new Parser();
        LanguageTransforms::apply('keysPlugin', $args, $parser);
        self::assertSame([], $parser->diagnostics());
        self::assertNotEmpty($args[0]['children'][0]['key']);
        self::assertMatchesRegularExpression('/^[a-zA-Z]/', $args[0]['children'][0]['key']);
    }
}
