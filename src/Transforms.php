<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Reusable transformations on serializable MyST trees. */
final class Transforms
{
    /** Visit children before their parent, allowing in-place replacement.
     * @param array<string, mixed> $node @param callable(array<string, mixed>&): void $visitor
     */
    public static function walk(array &$node, callable $visitor): void
    {
        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                self::walk($child, $visitor);
            }
            unset($child);
        }
        $visitor($node);
    }

    /** Convert figure content into ordered subfigures, captions and legends.
     * @param array<string, mixed> $tree
     */
    public static function containerChildren(array &$tree, ?Parser $parser = null): void
    {
        $types = ['embed', 'block', 'container', 'image', 'mermaid', 'iframe', 'table', 'code', 'outputs', 'output', 'card', 'anywidget', 'grid'];
        self::walk($tree, static function (array &$node) use ($types, $parser): void {
            if (($node['type'] ?? '') !== 'container' || ($node['kind'] ?? '') === 'quote') {
                return;
            }
            $children = [];
            foreach ($node['children'] ?? [] as $child) {
                if ($child['type'] === 'paragraph' && array_filter($child['children'], static fn (array $n): bool => !in_array($n['type'], $types, true) && !($n['type'] === 'text' && $n['value'] === "\n")) === []) {
                    array_push($children, ...array_values(array_filter($child['children'], static fn (array $n): bool => $n['type'] !== 'text')));
                } else {
                    $children[] = $child;
                }
            }
            $content = $other = [];
            $special = [];
            foreach ($children as $child) {
                $type = $child['type'];
                if ($type === 'caption' || $type === 'legend' || ($type === 'image' && !empty($child['placeholder']))) {
                    $key = $type === 'image' ? 'placeholder' : $type;
                    if (isset($special[$key])) {
                        $parser?->warn('container has multiple ' . $key . 's', 'container-children-valid', 'error');
                    } else {
                        $special[$key] = $child;
                    }
                } elseif (in_array($type, $types, true)) {
                    $content[] = $child;
                } else {
                    $other[] = $child;
                }
            }
            $caption = $special['caption'] ?? null;
            $legend = $special['legend'] ?? null;
            $placeholder = $special['placeholder'] ?? null;
            if ($caption === null && $other !== []) {
                $caption = ['type' => 'caption', 'children' => [array_shift($other)]];
            }
            if ($legend === null && $other !== []) {
                $legend = ['type' => 'legend', 'children' => $other];
            } elseif ($other !== []) {
                $parser?->warn('container includes unexpected children of ' . (count($other) === 1 ? 'type ' : 'types ') . implode(', ', array_column($other, 'type')), 'container-children-valid', 'error');
            }
            if ($content === []) {
                $suffix = implode(', ', array_filter([$caption !== null ? 'caption' : null, $legend !== null ? 'legend' : null, $placeholder !== null ? 'placeholder image' : null]));
                $parser?->warn('container of kind ' . ($node['kind'] ?? '') . ' contains no valid content' . ($suffix !== '' ? ' besides ' . $suffix : ''), 'container-children-valid', 'error');
            }
            if (count($content) > 1 && !($node['noSubcontainers'] ?? false)) {
                foreach ($content as &$child) {
                    $target = array_intersect_key($child, array_flip(['label', 'identifier', 'html_id']));
                    if (isset($child['label'])) {
                        $target = array_replace($target, Parser::label($child['label']));
                    }
                    unset($child['label'], $child['identifier'], $child['html_id']);
                    $sub = $child['type'] === 'container' ? $child['children'] : [$child];
                    if ($child['type'] === 'image' && !empty($child['alt'])) {
                        unset($sub[0]['alt']);
                        $sub[] = ['type' => 'caption', 'children' => [['type' => 'paragraph', 'children' => [['type' => 'text', 'value' => $child['alt']]]]]];
                    }
                    $child = ['type' => 'container', 'kind' => $child['kind'] ?? $node['kind'] ?? 'figure', 'subcontainer' => true, 'children' => $sub] + $target;
                }
                unset($child);
            }
            if ($placeholder !== null) {
                $content[] = $placeholder;
            }
            if ($caption !== null) {
                if (($node['kind'] ?? '') === 'table') {
                    array_unshift($content, $caption);
                } else {
                    $content[] = $caption;
                }
            }
            if ($legend !== null) {
                $content[] = $legend;
            }
            $node['children'] = $content;
        });
    }

    /** Move standalone target labels onto their following sibling.
     * @param array<string, mixed> $tree
     */
    public static function propagateTargets(array &$tree): void
    {
        $hasContent = false;
        self::walk($tree, static function (array &$node) use (&$hasContent): void {
            if (!in_array($node['type'], ['root', 'mystDirective', 'mystRole', 'mystTarget'], true)) {
                $hasContent = true;
            }
        });
        if (!$hasContent) {
            return;
        }
        self::walk($tree, static function (array &$node): void {
            if (!isset($node['children'])) {
                return;
            }
            $children = [];
            $target = null;
            foreach ($node['children'] as $child) {
                if ($child['type'] === 'mystTarget') {
                    $target = $child['label'] ?? null;
                    continue;
                }
                if ($target !== null) {
                    $child = array_replace($child, Parser::label($target));
                    $target = null;
                }
                $children[] = $child;
            }
            $node['children'] = $children;
        });
    }
}
