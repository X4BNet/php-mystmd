<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/**
 * Shared PHPStan contracts; runtime AST values remain ordinary serializable arrays.
 *
 * @phpstan-type Point array{line: int, column: int}
 * @phpstan-type Position array{start: Point, end: Point}
 * @phpstan-type ValueSchema array{type?: string, required?: bool, alias?: list<string>, ...<string, mixed>}
 * @phpstan-type DefinitionSchema array{name: string, alias?: list<string>, arg?: ValueSchema, body?: ValueSchema, options?: array<string, ValueSchema>, ...<string, mixed>}
 * @phpstan-type HtmlOptions array{profile?: string, allowDangerousHtml?: bool, allowDangerousUrls?: bool, clobberPrefix?: string, headingOffset?: int, focusableCode?: bool, closeSelfClosing?: bool, legacyCodeClasses?: bool, tableCellAlignment?: bool|'style', ...<string, mixed>}
 * @phpstan-type Options array{html?: HtmlOptions, extensions?: array<string, mixed>, markdownit?: array<string, mixed>, mdast?: array<string, mixed>, maxNesting?: int, ...<string, mixed>}
 * @phpstan-type ReferenceResult array{url: string, title?: string, children?: list<array<string, mixed>>}
 */
final class Types
{
    private function __construct()
    {
    }
}
