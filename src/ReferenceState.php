<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Per-render document targets and enumerators. */
final class ReferenceState
{
    /** @var array<string, array<string, mixed>> */
    public array $targets = [];
    /** @var array<string, int> */
    private array $counts = [];
    /** @var list<int|null> */
    private array $headings = [0, 0, 0, 0, 0, 0];
    /** @var array<string, mixed> */
    private array $options = [];

    /** Number targets in document order and resolve local references.
     * @param array<string, mixed> $tree
     */
    public function resolve(array &$tree): void
    {
        $this->enumerateTargets($tree);
        $this->resolveReferences($tree);
    }

    /**
     * @param array<string, mixed> $tree
     * @param array<string, mixed> $options
     */
    public function enumerateTargets(array &$tree, array $options = []): void
    {
        $this->counts = $this->targets = [];
        $this->options = $options;
        $depths = [];
        Transforms::walk($tree, static function (array &$node) use (&$depths): void {
            if ($node['type'] === 'heading' && ($node['enumerated'] ?? true)) {
                $depths[] = $node['depth'];
            }
        });
        $this->headings = array_map(static fn (int $depth): ?int => in_array($depth, $depths, true) ? 0 : null, range(1, 6));
        $this->enumerate($tree);
    }

    /** @param array<string, mixed> $tree */
    public function resolveReferences(array &$tree): void
    {
        Transforms::walk($tree, function (array &$node): void {
            if ($node['type'] === 'link') {
                $id = Parser::label($node['url'])['identifier'];
                if (isset($this->targets[$id])) {
                    $node['type'] = 'crossReference';
                    $node['kind'] = $this->targets[$id]['type'] === 'math' ? 'eq' : 'ref';
                    $node += Parser::label($node['url']);
                    unset($node['url']);
                }
            }
            if ($node['type'] !== 'crossReference' || !isset($this->targets[$node['identifier'] ?? ''])) {
                return;
            }
            $target = $this->targets[$node['identifier']];
            $kind = $target['type'] === 'container' ? $target['kind'] : $target['type'];
            $ref = $node['kind'] ?? 'ref';
            $children = null;
            if ($ref === 'eq' && $kind === 'math' && isset($target['enumerator'])) {
                $children = [['type' => 'text', 'value' => '(' . $target['enumerator'] . ')']];
            } elseif ($ref === 'ref' && $kind === 'heading') {
                $children = $target['children'];
            } elseif ($ref === 'ref' && in_array($kind, ['figure', 'table'], true)) {
                foreach ($target['children'] as $child) {
                    if ($child['type'] === 'caption') {
                        $children = $child['children'][0]['children'] ?? $child['children'];
                        break;
                    }
                }
                $children ??= [];
            } elseif ($ref === 'numref' && in_array($kind, ['figure', 'table'], true) && isset($target['enumerator'])) {
                $children = [['type' => 'text', 'value' => ucfirst($kind) . ' ' . $target['enumerator']]];
            }
            if ($children === null) {
                return;
            }
            if (empty($node['children'])) {
                $node['children'] = $children;
            }
            if ($ref === 'numref') {
                Transforms::walk($node, static function (array &$child) use ($target): void {
                    if ($child['type'] === 'text') {
                        $child['value'] = str_replace(['%s', '{number}'], (string)$target['enumerator'], $child['value']);
                    }
                });
            }
            $node['resolved'] = true;
        });
    }

    /** Enumerate parents before children, preserving source order.
     * @param array<string, mixed> $node
     */
    private function enumerate(array &$node): void
    {
        $kind = $node['type'] === 'container' ? ($node['kind'] ?? '') : $node['type'];
        $disabled = match ($node['type']) {
            'container' => $this->options['disableContainerEnumeration'] ?? false,
            'math' => $this->options['disableEquationEnumeration'] ?? false,
            'heading' => $this->options['disableHeadingEnumeration'] ?? false,
            default => true,
        };
        if (!$disabled && in_array($kind, ['heading', 'math', 'figure', 'table', 'code'], true)) {
            if (($node['enumerated'] ?? true) !== false) {
                if ($kind === 'heading') {
                    $this->headings = self::incrementHeadingCounts($node['depth'], $this->headings);
                    $node['enumerator'] = self::formatHeadingEnumerator($this->headings);
                } else {
                    $this->counts[$kind] = ($this->counts[$kind] ?? 0) + 1;
                    $node['enumerator'] = (string)$this->counts[$kind];
                }
            }
            if (isset($node['identifier'])) {
                $this->targets[$node['identifier']] = $node;
            }
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->enumerate($child);
            }
            unset($child);
        }
    }

    /**
     *
     * @param list<int|null> $counts
     * @return list<int|null>
     */
    public static function incrementHeadingCounts(int $depth, array $counts): array
    {
        foreach ($counts as $index => &$count) {
            if ($count !== null && $index >= $depth - 1) {
                $count = $index === $depth - 1 ? $count + 1 : 0;
            }
        }
        unset($count);
        return $counts;
    }

    /** @param list<int|null> $counts */
    public static function formatHeadingEnumerator(array $counts): string
    {
        $counts = array_values(array_filter($counts, static fn (?int $value): bool => $value !== null));
        while ($counts !== [] && end($counts) === 0) {
            array_pop($counts);
        }
        return implode('.', $counts);
    }
}
