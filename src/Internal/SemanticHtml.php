<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\AstUtils;
use Splitice\Mystmd\HtmlRenderer;

/** Native HTML and readable fallbacks for nodes the pinned serializer omits. */
final class SemanticHtml
{
    /**
     * @param array<string, mixed> $node
     * @param array<string, mixed> $attrs
     */
    public static function node(array $node, array $attrs, HtmlRenderer $renderer): ?string
    {
        $type = $node['type'];
        $children = $node['children'] ?? [];
        if (in_array($type, ['details', 'summary', 'aside', 'div', 'span'], true)) {
            if ($type === 'details' && !empty($node['open'])) {
                $attrs['open'] = true;
            }
            return $renderer->tag($type, $attrs, $renderer->children($children));
        }
        if ($type === 'underline' || $type === 'smallcaps') {
            if ($type === 'smallcaps') {
                $attrs['class'] = trim(($attrs['class'] ?? '') . ' smallcaps');
            }
            return $renderer->tag($type === 'underline' ? 'u' : 'span', $attrs, $renderer->children($children));
        }
        if ($type === 'citeGroup') {
            return $renderer->tag('span', $attrs + ['class' => 'citation-group'], $renderer->children($children, '; '));
        }
        if ($type === 'crossReference' && !($node['resolved'] ?? false)) {
            $renderer->warn('Unresolved reference: ' . ($node['identifier'] ?? ''), 'reference-unresolved', 'warning', $node['position'] ?? null);
            $content = $children !== [] ? $renderer->children($children) : HtmlRenderer::escape($node['label'] ?? $node['identifier'] ?? 'reference');
            return $renderer->tag('span', ['class' => 'reference unresolved'], $content);
        }
        if ($type === 'cite') {
            $renderer->warn('Unresolved citation: ' . ($node['identifier'] ?? ''), 'citation-unresolved', 'warning', $node['position'] ?? null);
            $text = trim(($node['prefix'] ?? '') . ' @' . ($node['label'] ?? $node['identifier'] ?? '') . (isset($node['suffix']) ? ' ' . $node['suffix'] : ''));
            return $renderer->tag('span', ['class' => 'citation unresolved'], HtmlRenderer::escape($text));
        }
        if (in_array($type, ['iframe', 'bibliography', 'output', 'outputs', 'include', 'embed', 'mdast', 'margin', 'mermaid', 'myst', 'anywidget', 'inlineExpression', 'icon', 'toc', 'genindex'], true) || str_starts_with($type, 'r:')) {
            return self::fallback($node, $attrs, $renderer);
        }
        return null;
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, mixed> $attrs
     */
    public static function fallback(array $node, array $attrs, HtmlRenderer $renderer): string
    {
        $type = $node['type'];
        $renderer->warn('No semantic HTML renderer for node: ' . $type, 'html-unsupported', 'warning', $node['position'] ?? null);
        $text = $node['value'] ?? $node['file'] ?? $node['src'] ?? $node['esm'] ?? $node['source']['url'] ?? $node['source']['label'] ?? $node['name'] ?? '';
        $title = AstUtils::toText($node['title'] ?? null);
        $body = $renderer->children($node['children'] ?? []);
        $inline = in_array($type, ['inlineExpression', 'icon', 'chemicalFormula', 'raw'], true) || str_starts_with($type, 'r:');
        $attrs['class'] = trim(($attrs['class'] ?? '') . ' myst-unsupported');
        $literal = HtmlRenderer::escape(SchemaValues::string($text));
        $content = ($title !== '' ? HtmlRenderer::escape($title) . ' ' : '') . $literal . $body;
        if ($content === '') {
            $content = HtmlRenderer::escape('[' . $type . ']');
        }
        if (in_array($type, ['mermaid', 'myst'], true)) {
            return $renderer->tag('pre', $attrs, $renderer->tag('code', [], $literal));
        }
        return $renderer->tag($inline ? 'span' : 'div', $attrs, $content);
    }

    /** Respect each cell's header flag, including multiple header rows or none.
     * @param array<string, mixed> $node
     */
    public static function table(array $node, HtmlRenderer $renderer): string
    {
        $header = $body = '';
        $inHeader = true;
        foreach ($node['children'] ?? [] as $row) {
            $allHeader = ($row['children'] ?? []) !== [] && array_filter($row['children'], static fn (array $cell): bool => empty($cell['header'])) === [];
            $inHeader = $inHeader && $allHeader;
            if ($inHeader) {
                $header .= $renderer->node($row);
            } else {
                $body .= $renderer->node($row);
            }
        }
        $attrs = array_intersect_key($node, array_flip(['class', 'align']));
        if (isset($node['html_id']) || isset($node['identifier'])) {
            $attrs['id'] = $node['html_id'] ?? $node['identifier'];
        }
        return $renderer->tag('table', $attrs, ($header !== '' ? $renderer->tag('thead', [], $header) : '') . ($body !== '' ? $renderer->tag('tbody', [], $body) : ''));
    }
}
