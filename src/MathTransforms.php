<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Document math transforms from myst-transforms, independent of a TeX engine. */
final class MathTransforms
{
    /** Preserve paragraph spacing when lifting display mathematics.
     * @param array<string, mixed> $tree
     */
    public static function nesting(array &$tree): void
    {
        Transforms::walk($tree, static function (array &$node): void {
            if (($node['type'] ?? '') !== 'paragraph' || count($node['children'] ?? []) < 2) {
                return;
            }
            foreach ($node['children'] as $index => &$child) {
                if ($child['type'] !== 'math') {
                    continue;
                }
                $child['tight'] = $index === 0 ? 'after' : ($index === count($node['children']) - 1 ? 'before' : true);
                if (($node['children'][$index - 1]['type'] ?? '') === 'text') {
                    $node['children'][$index - 1]['value'] = preg_replace('/\n$/', '', $node['children'][$index - 1]['value'] ?? '');
                }
                if (($node['children'][$index + 1]['type'] ?? '') === 'text') {
                    $node['children'][$index + 1]['value'] = preg_replace('/^\n/', '', $node['children'][$index + 1]['value'] ?? '');
                }
            }
            unset($child);
        });
        LanguageTransforms::unnest($tree, 'paragraph', 'math');
    }

    /** Replace simple inline TeX with its equivalent text or script nodes.
     * @param array<string, mixed> $tree
     * @param array<string, bool> $options
     */
    public static function simplify(array &$tree, array $options = []): void
    {
        $symbols = Internal\RuntimeResources::mathSymbols();
        $replacement = static function (string $value) use ($options, $symbols): ?string {
            if (($options['replaceText'] ?? true) && preg_match('/^[a-zA-Z]+$/D', $value)) {
                return $value;
            }
            if (($options['replaceNumber'] ?? true) && preg_match('/^[0-9+-]+$/D', $value)) {
                return $value;
            }
            return (($options['replaceText'] ?? true) ? $symbols['TEXT_REPLACEMENTS'][$value] ?? null : null)
                ?? (($options['replaceSymbol'] ?? true) ? $symbols['SYM_REPLACEMENTS'][$value] ?? null : null);
        };
        Transforms::walk($tree, static function (array &$node) use ($options, $replacement): void {
            if (($node['type'] ?? '') !== 'inlineMath') {
                return;
            }
            $value = $node['value'];
            if (preg_match('/^\\\\[a-zA-Z]+$/D', $value) && ($text = $replacement($value)) !== null) {
                $node['type'] = 'text';
                $node['value'] = $text;
                return;
            }
            if (($options['replaceScripts'] ?? true) && preg_match('/^(\^|_)(?:\{(\\\\?[a-zA-Z0-9+-]+)\}|(\\\\?[a-zA-Z0-9+-]+))$/D', $value, $match)) {
                $text = $replacement($match[2] ?: $match[3]);
                if ($text !== null && $text === '∘' && $match[1] === '^') {
                    $node['type'] = 'text';
                    $node['value'] = '°';
                    return;
                }
                if ($text !== null && ($options['replaceSymbol'] ?? true)) {
                    $node['type'] = $match[1] === '^' ? 'superscript' : 'subscript';
                    $node['children'] = [['type' => 'text', 'value' => $text]];
                    unset($node['value']);
                    return;
                }
            }
            if (!($options['replaceNumber'] ?? true)) {
                return;
            }
            if (preg_match('/^([+-]?[\d.]+)(\^|_)(?:\{([+-]?[\d.]+)\}|([+-]?[\d.]+))$/D', $value, $match)) {
                $node['type'] = 'span';
                $node['children'] = [
                    ['type' => 'text', 'value' => $match[1]],
                    ['type' => $match[2] === '^' ? 'superscript' : 'subscript', 'children' => [['type' => 'text', 'value' => $match[3] ?: $match[4]]]],
                ];
                unset($node['value']);
            } elseif (preg_match('/^[+-]?[0-9.]+$/D', $value)) {
                $node['type'] = 'text';
            }
        });
    }
}
