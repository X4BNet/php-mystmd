<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

use Splitice\Mystmd\Internal\HtmlToAst;

/** Optional document-local language transforms, independent of project discovery. */
final class LanguageTransforms
{
    /** Invoke a named upstream-compatible transform with serializable arguments.
     * @param list<mixed> $args
     */
    public static function apply(string $name, array &$args, Parser $parser): mixed
    {
        switch ($name) {
            case 'keysTransform': case 'keysPlugin':
                Transforms::walk($args[0], static function (array &$node): void {
                    if (empty($node['key'])) {
                        $node['key'] = 'm' . bin2hex(random_bytes(8));
                    }
                });
                return $name === 'keysTransform' ? $args[0] : null;
            case 'buildTocTransform': TableOfContents::apply($args[0], $parser, $args[2] ?? null, $args[3] ?? null, $args[4] ?? null); break;
            case 'mathNestingTransform': MathTransforms::nesting($args[0]); break;
            case 'inlineMathSimplificationTransform': MathTransforms::simplify($args[0], $args[1] ?? []); break;
            case 'codeBlockToDirectiveTransform':
                Transforms::walk($args[0], static function (array &$node) use ($args): void {
                    if (($node['type'] ?? '') !== 'code' || empty($node['lang'])) {
                        return;
                    }
                    foreach ($args[2]['translate'] ?? [] as $translation) {
                        if ((is_string($translation) ? $translation : $translation['lang']) === $node['lang']) {
                            $node['type'] = is_string($translation) ? $translation : ($translation['directive'] ?? $translation['lang']);
                            unset($node['lang']);
                            break;
                        }
                    }
                });
                break;
            case 'listItemParagraphsTransform': self::listItemParagraphs($args[0]); break;
            case 'unnestTransform': self::unnest($args[0], $args[1], $args[2]); break;
            case 'headingDepthTransform': self::headingDepth($args[0], $parser, $args[2] ?? []); break;
            case 'removeUnicodeTransform':
                Transforms::walk($args[0], static function (array &$node): void {
                    if (($node['type'] ?? '') === 'text') {
                        $node['value'] = str_replace(["\x07", "\x08", "\x03"], '', $node['value']);
                    }
                });
                break;
            case 'footnotesPlugin': case 'footnotesTransform': self::footnotes($args[0], $parser); break;
            case 'joinGatesPlugin': self::joinGates($args[0], $parser); break;
            case 'blockNestingTransform': self::blockNesting($args[0]); break;
            case 'blockMetadataTransform': self::blockMetadata($args[0], $parser); break;
            case 'blockToFigureTransform': self::blockToFigure($args[0]); break;
            case 'admonitionHeadersTransform': self::admonitionHeaders($args[0], $args[1] ?? []); break;
            case 'admonitionQmdTransform': self::admonitionQmd($args[0]); break;
            case 'admonitionBlockquoteTransform': self::admonitionBlockquote($args[0]); break;
            case 'containerChildrenTransform': Transforms::containerChildren($args[0], $parser); break;
            case 'htmlTransform': HtmlToAst::apply($args[0], $args[1] ?? [], true); return $args[0];
            case 'blockquoteTransform': self::blockquote($args[0]); break;
            case 'codeTransform': self::code($args[0], $parser, $args[2] ?? []); break;
            case 'inlineCodeFlattenTransform': self::inlineCodeFlatten($args[0], $parser); break;
            case 'reconstructHtmlTransform': self::reconstructHtml($args[0]); return $args[0];
            case 'indexIdentifierTransform': self::indexIdentifier($args[0]); break;
            case 'getNormalizedFirstLetter': return self::normalizedFirstLetter($args[0]);
            default: throw new \InvalidArgumentException('Unknown language transform: ' . $name);
        }
        return null;
    }

    /** Split a parent around children of the requested type, retaining attributes.
     * @param array<string, mixed> $tree
     */
    public static function unnest(array &$tree, string $parentType, string $childType): void
    {
        Transforms::walk($tree, static function (array &$node) use ($parentType, $childType): void {
            if (!isset($node['children'])) {
                return;
            }
            $children = [];
            foreach ($node['children'] as $child) {
                if (($child['type'] ?? '') !== $parentType) {
                    $children[] = $child;
                    continue;
                }
                $current = array_replace($child, ['children' => []]);
                foreach ($child['children'] ?? [] as $nested) {
                    if (($nested['type'] ?? '') === $childType) {
                        if ($current['children'] !== []) {
                            $children[] = $current;
                            $current['children'] = [];
                        }
                        $children[] = $nested;
                    } else {
                        $current['children'][] = $nested;
                    }
                }
                if ($current['children'] !== []) {
                    $children[] = $current;
                }
            }
            $node['children'] = $children;
        });
    }

    /**
     * @param array<string, mixed> $tree
     * @param array<string, mixed> $options
     */
    public static function headingDepth(array &$tree, Parser $parser, array $options = []): void
    {
        if (!isset($options['firstDepth'])) {
            return;
        }
        $first = max(1, (int)$options['firstDepth']);
        $depths = [];
        Transforms::walk($tree, static function (array &$node) use (&$depths): void {
            if (($node['type'] ?? '') === 'heading' && !empty($node['depth'])) {
                $depths[] = $node['depth'];
            }
        });
        $depths = array_values(array_unique($depths));
        sort($depths);
        if ($depths === []) {
            return;
        }
        for ($depth = min($depths) + 1; $depth < max($depths); ++$depth) {
            if (!in_array($depth, $depths, true)) {
                $parser->warn('missing heading depth ' . $depth, '');
            }
        }
        if (count($depths) + $first > 7) {
            $parser->warn('max number of heading depth levels exceeded; must be ≤ ' . (7 - $first), '');
        }
        Transforms::walk($tree, static function (array &$node) use ($first, $depths): void {
            if (($node['type'] ?? '') === 'heading' && ($index = array_search($node['depth'], $depths, true)) !== false) {
                $node['depth'] = min(6, $index + $first);
            }
        });
    }

    /** @param array<string, mixed> $tree */
    public static function footnotes(array &$tree, Parser $parser): void
    {
        $definitions = $reserved = [];
        Transforms::walk($tree, static function (array &$node) use (&$definitions, &$reserved): void {
            if (($node['type'] ?? '') === 'footnoteDefinition') {
                $definitions[$node['identifier']] = &$node;
            } elseif (($node['type'] ?? '') === 'footnoteReference' && is_numeric($node['identifier'] ?? '') && (float)$node['identifier'] > 0) {
                $reserved[] = (int)$node['identifier'];
            }
        });
        $count = 0;
        Transforms::walk($tree, static function (array &$node) use (&$definitions, &$count, $reserved, $parser): void {
            if (($node['type'] ?? '') !== 'footnoteReference') {
                return;
            }
            $id = $node['identifier'] ?? null;
            if (!$id || !isset($definitions[$id])) {
                $parser->warn(!$id ? 'FootnoteReference does not have an identifier' : 'No footnoteDefinition found for ' . $id, 'footnote-references-definition');
                return;
            }
            if (isset($definitions[$id]['enumerator'])) {
                $node['enumerator'] = $definitions[$id]['enumerator'];
            } elseif (is_numeric($id) && (float)$id > 0) {
                $node['enumerator'] = $definitions[$id]['enumerator'] = (string)($id + 0);
            } else {
                do {
                    ++$count;
                } while (in_array($count, $reserved, true));
                $node['enumerator'] = $definitions[$id]['enumerator'] = (string)$count;
            }
        });
    }

    /** @param array<string, mixed> $tree */
    public static function joinGates(array &$tree, Parser $parser): void
    {
        if (!isset($tree['children'])) {
            return;
        }
        $children = [];
        foreach ($tree['children'] as $child) {
            $last = array_key_last($children);
            if ($last === null || ($children[$last]['gate'] ?? '') !== 'start' || ($child['gate'] ?? '') === 'start') {
                $children[] = $child;
            } elseif (($child['gate'] ?? '') === 'end') {
                if ($child['type'] !== $children[$last]['type']) {
                    $parser->warn('Gate close ("' . $child['type'] . '") does not match opening gate (end).', 'gated-nodes-join');
                }
                unset($children[$last]['gate']);
                if (($children[$last - 1]['gate'] ?? '') === 'start') {
                    $closed = array_pop($children);
                    $children[$last - 1]['children'][] = $closed;
                }
            } else {
                $children[$last]['children'][] = $child;
            }
        }
        $last = array_key_last($children);
        if ($last !== null && ($children[$last]['gate'] ?? '') === 'start') {
            $parser->warn('Gated node is not closed, expected a {' . $children[$last]['type'] . '-end} directive.', 'gated-nodes-join', 'error');
        }
        foreach ($children as &$child) {
            self::joinGates($child, $parser);
        }
        unset($child);
        $tree['children'] = $children;
    }

    /** @param array<string, mixed> $tree */
    public static function blockNesting(array &$tree): void
    {
        $children = [];
        $block = [];
        foreach ($tree['children'] ?? [] as $child) {
            if (($child['type'] ?? '') === 'block') {
                if ($block !== []) {
                    $children[] = ['type' => 'block', 'children' => $block];
                    $block = [];
                }
                $children[] = $child;
            } else {
                $block[] = $child;
            }
        }
        if ($block !== []) {
            $children[] = ['type' => 'block', 'children' => $block];
        }
        $tree['children'] = $children;
    }

    /** @param array<string, mixed> $tree */
    public static function blockMetadata(array &$tree, Parser $parser): void
    {
        Transforms::walk($tree, static function (array &$node) use ($parser): void {
            if (($node['type'] ?? '') !== 'block') {
                return;
            }
            if (!empty($node['meta'])) {
                try {
                    $node['data'] = array_replace($node['data'] ?? [], json_decode($node['meta'], true, flags: JSON_THROW_ON_ERROR));
                    unset($node['meta']);
                } catch (\JsonException|\TypeError) {
                    $parser->warn('Problem parsing JSON for block', 'block-metadata-loads', 'error');
                }
            }
            if (!empty($node['data']['kind'])) {
                $node['kind'] = $node['data']['kind'];
                unset($node['data']['kind']);
            }
            if (is_string($node['data']['class'] ?? null)) {
                $node['class'] = trim(($node['class'] ?? '') . ' ' . $node['data']['class']);
                unset($node['data']['class']);
            }
            if (isset($node['data']) && $node['data'] === []) {
                unset($node['data']);
            }
            if (isset($node['data']) && is_string($node['data']['label'] ?? null)) {
                $node = array_replace($node, Parser::label($node['data']['label']));
                $node['html_id'] = str_replace('_', '-', $node['identifier']);
                unset($node['data']['label']);
            }
            if (isset($node['identifier'], $node['label'])) {
                $hasCode = $hasOutputs = false;
                Transforms::walk($node, static function (array &$child) use ($node, &$hasCode, &$hasOutputs): void {
                    if (($child['type'] ?? '') === 'code' && !$hasCode) {
                        $child['identifier'] ??= $node['identifier'] . '-code';
                        $hasCode = true;
                    } elseif (($child['type'] ?? '') === 'outputs' && !$hasOutputs) {
                        if (!isset($child['identifier'])) {
                            $child['identifier'] = $node['identifier'] . '-outputs';
                            foreach ($child['children'] as $index => &$output) {
                                $output['identifier'] ??= $child['identifier'] . '-' . $index;
                            }
                            unset($output);
                        }
                        $hasOutputs = true;
                    }
                });
            }
        });
    }

    /** @param array<string, mixed> $tree */
    public static function blockToFigure(array &$tree): void
    {
        Transforms::walk($tree, static function (array &$node): void {
            if (($node['type'] ?? '') !== 'block') {
                return;
            }
            $caption = $node['data']['caption'] ?? $node['data']['fig-cap'] ?? $node['data']['tbl-cap'] ?? null;
            if (!$caption) {
                return;
            }
            $children = is_string($caption) ? [['type' => 'paragraph', 'children' => [['type' => 'text', 'value' => $caption]]]] : $caption;
            array_push($children, ...$node['children']);
            $container = ['type' => 'container', 'kind' => $node['data']['kind'] ?? (isset($node['data']['tbl-cap']) ? 'table' : 'figure'), 'children' => $children] + array_intersect_key($node, ['identifier' => true, 'label' => true]);
            if (($node['kind'] ?? '') === 'notebook-code') {
                $container['noSubcontainers'] = true;
            }
            $node['children'] = [$container];
            unset($node['data']['caption'], $node['data']['fig-cap'], $node['data']['tbl-cap'], $node['data']['kind'], $node['label'], $node['identifier']);
        });
    }

    /**
     * @param array<string, mixed> $tree
     * @param array<string, mixed> $options
     */
    public static function admonitionHeaders(array &$tree, array $options = []): void
    {
        Transforms::walk($tree, static function (array &$node) use ($options): void {
            if (($node['type'] ?? '') !== 'admonition' || empty($node['kind']) || $node['kind'] === 'admonition' || ($node['children'][0]['type'] ?? '') === 'admonitionTitle') {
                return;
            }
            $children = [['type' => 'text', 'value' => $node['kind'] === 'seealso' ? 'See Also' : ucfirst($node['kind'])]];
            $first = $node['children'][0] ?? [];
            if ($options['replaceAdmonitionTitles'] ?? true) {
                if (($first['type'] ?? '') === 'heading') {
                    $children = $first['children'];
                    array_shift($node['children']);
                } elseif (($first['type'] ?? '') === 'paragraph' && count($first['children']) === 1 && $first['children'][0]['type'] === 'strong') {
                    $children = $first['children'][0]['children'];
                    array_shift($node['children']);
                }
            }
            $node['children'] = [['type' => 'admonitionTitle', 'children' => $children], ...($node['children'] ?? [])];
        });
    }

    /** @param array<string, mixed> $tree */
    public static function admonitionQmd(array &$tree): void
    {
        Transforms::walk($tree, static function (array &$node): void {
            if (($node['type'] ?? '') !== 'div') {
                return;
            }
            $classes = preg_split('/\s+/', $node['class'] ?? '') ?: [];
            foreach ($classes as $index => $class) {
                if (preg_match('/^callout-(note|warning|important|tip|caution)$/', $class, $m)) {
                    $node['type'] = 'admonition';
                    $node['kind'] = $m[1];
                    unset($classes[$index]);
                    $node['class'] = implode(' ', array_filter($classes));
                    if ($node['class'] === '') {
                        unset($node['class']);
                    }
                    break;
                }
            }
        });
    }

    /** @param array<string, mixed> $tree */
    public static function admonitionBlockquote(array &$tree): void
    {
        Transforms::walk($tree, static function (array &$node): void {
            if (($node['type'] ?? '') !== 'blockquote' || ($node['children'][0]['type'] ?? '') !== 'paragraph') {
                return;
            }
            $first = $node['children'][0]['children'][0] ?? [];
            $kind = null;
            if (($first['type'] ?? '') === 'strong') {
                $kind = strtolower(trim($first['children'][0]['value'] ?? ''));
                if (in_array($kind, ['note', 'tip', 'important', 'warning', 'caution'], true)) {
                    array_shift($node['children'][0]['children']);
                }
            } elseif (($first['type'] ?? '') === 'text' && preg_match('/^\[!([A-Za-z]+)\]\s*/', $first['value'], $m)) {
                $kind = strtolower($m[1]);
                if (in_array($kind, ['note', 'tip', 'important', 'warning', 'caution'], true)) {
                    $node['children'][0]['children'][0]['value'] = substr($first['value'], strlen($m[0]));
                }
            }
            if (!in_array($kind, ['note', 'tip', 'important', 'warning', 'caution'], true)) {
                return;
            }
            $node['type'] = 'admonition';
            $node['kind'] = $kind;
            $node['class'] = trim(($node['class'] ?? '') . ' simple');
            array_unshift($node['children'], ['type' => 'admonitionTitle', 'children' => [['type' => 'text', 'value' => ucfirst($kind)]]]);
        });
    }
    /** @param array<string, mixed> $tree */
    public static function blockquote(array &$tree): void
    {
        if (!isset($tree['children'])) {
            return;
        }
        $hasCaption = in_array('caption', array_column($tree['children'], 'type'), true);
        foreach ($tree['children'] as &$child) {
            self::blockquote($child);
            if (($child['type'] ?? '') !== 'blockquote' || $hasCaption) {
                continue;
            }
            $last = array_key_last($child['children']);
            $paragraph = $last !== null ? $child['children'][$last] : [];
            $text = $paragraph['children'][0] ?? [];
            $caption = null;
            if (($paragraph['type'] ?? '') === 'paragraph' && ($text['type'] ?? '') === 'text' && preg_match('/^(?:---?|—) +(.*)/u', $text['value'], $m)) {
                if ($m[1] !== '') {
                    $paragraph['children'][0]['value'] = $m[1];
                    $caption = ['type' => 'caption', 'children' => [$paragraph]];
                } elseif (count($paragraph['children']) > 1) {
                    array_shift($paragraph['children']);
                    $caption = ['type' => 'caption', 'children' => [$paragraph]];
                }
            }
            if ($caption !== null) {
                array_pop($child['children']);
                if (($tree['type'] ?? '') === 'container' && ($tree['kind'] ?? '') === 'quote') {
                    $tree['children'][] = $caption;
                } else {
                    $child = ['type' => 'container', 'kind' => 'quote', 'children' => [$child, $caption]];
                }
            }
        }
        unset($child);
        $out = [];
        foreach ($tree['children'] as $child) {
            if (($child['type'] ?? '') === 'container' && ($child['kind'] ?? '') === 'quote' && empty($child['class']) && !in_array('caption', array_column($child['children'], 'type'), true)) {
                array_push($out, ...$child['children']);
            } else {
                $out[] = $child;
            }
        }
        $tree['children'] = $out;
    }

    /**
     * @param array<string, mixed> $tree
     * @param array<string, mixed> $options
     */
    public static function code(array &$tree, Parser $parser, array $options = []): void
    {
        Transforms::walk($tree, static function (array &$node) use ($options, $parser): void {
            if (($node['type'] ?? '') !== 'code' || ($node['lang'] ?? null) === '') {
                return;
            }
            if (empty($node['lang'])) {
                if (empty($options['lang'])) {
                    $parser->warn('Language is not defined for code block', 'code-lang-defined');
                    return;
                }
                $node['lang'] = $options['lang'];
            }
            if (str_contains(strtolower($node['lang']), 'python') && ($options['transformPython'] ?? true)) {
                $node['lang'] = 'python';
            }
            if (strtolower($node['lang']) === 'r' && ($options['transformR'] ?? true)) {
                $node['lang'] = 'r';
            }
        });
    }

    /** @param array<string, mixed> $tree */
    public static function inlineCodeFlatten(array &$tree, Parser $parser): void
    {
        Transforms::walk($tree, static function (array &$node) use ($parser): void {
            if (($node['type'] ?? '') !== 'inlineCode' || !isset($node['children'])) {
                return;
            }
            if (!empty($node['value'])) {
                $parser->warn('Both children and value defined for inline code.', 'inline-code-malformed');
                return;
            }
            if (array_filter($node['children'], static fn (array $child): bool => $child['type'] !== 'text') === []) {
                $node['value'] = Parser::text($node['children']);
                unset($node['children']);
            }
        });
    }

    /** @param array<string, mixed> $tree */
    public static function indexIdentifier(array &$tree): void
    {
        Transforms::walk($tree, static function (array &$node): void {
            if (isset($node['indexEntries'])) {
                $node['label'] ??= 'index-' . bin2hex(random_bytes(6));
                if (!isset($node['identifier'])) {
                    $node += Parser::label($node['label']);
                    $node['html_id'] = str_replace('_', '-', $node['identifier']);
                }
            }
        });
    }

    /** Normalize only canonical decompositions, matching JavaScript's NFD. */
    public static function normalizedFirstLetter(string $text): string
    {
        $first = mb_substr(trim($text), 0, 1);
        $normal = \Normalizer::normalize($first, \Normalizer::FORM_D);
        $first = mb_strtoupper(preg_replace('/\p{M}/u', '', $normal === false ? $first : $normal) ?? $first);
        return preg_match('/^[1-9A-Z]$/', $first) ? $first : 'Other';
    }

    /** Rejoin split HTML nodes and render Markdown found between matching tags.
     * @param array<string, mixed> $tree
     */
    public static function reconstructHtml(array &$tree): void
    {
        if (!isset($tree['children'])) {
            return;
        }
        $stack = [];
        $nodes = $tree['children'];
        foreach ($nodes as $index => &$child) {
            $last = array_key_last($stack);
            if (($child['type'] ?? '') === 'html') {
                $value = trim($child['value']);
                $selfClosing = str_ends_with($value, '/>') || preg_match('/<(?:area|base|br|col|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)(?:\s[^>]*)?\/?\s*>/i', $value);
                if ($selfClosing) {
                    if ($last !== null && isset($stack[$last])) {
                        $nodes[$stack[$last]]['children'][] = &$child;
                    }
                } elseif (str_starts_with($value, '</')) {
                    $open = array_pop($stack);
                    if ($open === null) {
                        continue;
                    }
                    $rendered = [];
                    $renderer = new HtmlRenderer(['allowDangerousHtml' => true]);
                    foreach ($nodes[$open]['children'] as &$inside) {
                        if (($inside['type'] ?? '') === 'html') {
                            $html = preg_replace('/\s+\/>/', '>', $inside['value']) ?? $inside['value'];
                        } else {
                            $html = str_replace(['&quot;', '&apos;'], ['"', "'"], $renderer->node($inside));
                        }
                        $rendered[] = $html;
                        $inside['type'] = '__delete__';
                    }
                    unset($inside);
                    $nodes[$open]['value'] = trim($nodes[$open]['value']) . implode("\n", $rendered) . $value;
                    unset($nodes[$open]['children']);
                    $child['type'] = '__delete__';
                    if ($stack !== []) {
                        $nodes[$stack[array_key_last($stack)]]['children'][] = &$nodes[$open];
                    }
                } elseif (!str_ends_with($value, '-->')) {
                    $child['children'] = [];
                    $stack[] = $index;
                }
            } else {
                self::reconstructHtml($child);
                if ($last !== null && isset($stack[$last])) {
                    $nodes[$stack[$last]]['children'][] = &$child;
                }
            }
        }
        unset($child);
        foreach ($stack as $index) {
            unset($nodes[$index]['children']);
        }
        $out = [];
        foreach ($nodes as $child) {
            if (($child['type'] ?? '') === '__delete__') {
                continue;
            }
            $last = array_key_last($out);
            if (($child['type'] ?? '') === 'html' && $last !== null && ($out[$last]['type'] ?? '') === 'html') {
                $out[$last]['value'] .= $child['value'];
            } else {
                $out[] = $child;
            }
        }
        $tree['children'] = $out;
    }
    /** Wrap adjacent phrasing nodes while preserving intervening block content.
     * @param array<string, mixed> $tree
     */
    public static function listItemParagraphs(array &$tree): void
    {
        $types = ['text', 'emphasis', 'strong', 'delete', 'link', 'image', 'break', 'subscript', 'superscript', 'smallcaps', 'inlineCode', 'inlineMath', 'mystRole', 'footnoteReference', 'crossReference', 'cite', 'citeGroup', 'html'];
        Transforms::walk($tree, static function (array &$node) use ($types): void {
            if (($node['type'] ?? '') !== 'listItem' || empty($node['children'])) {
                return;
            }
            $children = $phrasing = [];
            foreach ($node['children'] as $child) {
                if (in_array($child['type'], $types, true)) {
                    $phrasing[] = $child;
                } else {
                    if ($phrasing !== []) {
                        $children[] = ['type' => 'paragraph', 'children' => $phrasing];
                        $phrasing = [];
                    }
                    $children[] = $child;
                }
            }
            if ($phrasing !== []) {
                $children[] = ['type' => 'paragraph', 'children' => $phrasing];
            }
            $node['children'] = $children;
        });
    }
}
