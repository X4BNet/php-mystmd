<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Apply page abbreviation definitions without rewriting protected content. */
final class Abbreviations
{
    /**
     * @param array<string, mixed> $tree
     * @param array{abbreviations?: array<string, ?string>, firstTimeLong?: bool} $options
     */
    public static function apply(array &$tree, array $options = []): void
    {
        $definitions = $options['abbreviations'] ?? [];
        if ($definitions === []) {
            return;
        }
        Transforms::walk($tree, static function (array &$node) use ($definitions): void {
            if (($node['type'] ?? '') === 'abbreviation' && empty($node['title'])) {
                $text = self::text($node);
                if (!empty($definitions[$text])) {
                    $node['title'] = $definitions[$text];
                }
            }
        });
        $keys = array_filter(array_keys($definitions), static fn (string $key): bool => mb_strlen($key) > 1);
        usort($keys, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a) ?: strcmp($a, $b));
        if ($keys !== []) {
            $pattern = '~(' . implode('|', array_map(static fn (string $key): string => preg_quote($key, '~'), $keys)) . ')~u';
            self::replace($tree, $pattern, $definitions);
        }
        if ($options['firstTimeLong'] ?? false) {
            $explained = [];
            Transforms::walk($tree, static function (array &$node) use (&$explained): void {
                if (($node['type'] ?? '') === 'abbreviation' && !isset($explained[$node['title'] ?? ''])) {
                    $title = $node['title'] ?? 'undefined';
                    $explained[$node['title'] ?? ''] = true;
                    if (isset($node['children'][0]['value'])) {
                        $node['children'][0]['value'] = $title . ' (' . $node['children'][0]['value'] . ')';
                    }
                }
            });
        }
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, ?string> $definitions
     */
    private static function replace(array &$node, string $pattern, array $definitions): void
    {
        if (in_array($node['type'] ?? '', ['link', 'crossReference', 'cite', 'code', 'abbreviation'], true)) {
            return;
        }
        if (!isset($node['children'])) {
            return;
        }
        $children = [];
        foreach ($node['children'] as $child) {
            if (($child['type'] ?? '') !== 'text') {
                self::replace($child, $pattern, $definitions);
                $children[] = $child;
                continue;
            }
            $parts = preg_split($pattern, $child['value'], flags: PREG_SPLIT_DELIM_CAPTURE) ?: [];
            if (count($parts) === 1) {
                $children[] = $child;
                continue;
            }
            foreach ($parts as $index => $part) {
                if ($part === '') {
                    continue;
                }
                $text = ['type' => 'text', 'value' => $part];
                $children[] = $index % 2 && !empty($definitions[$part]) ? ['type' => 'abbreviation', 'title' => $definitions[$part], 'children' => [$text]] : $text;
            }
        }
        $node['children'] = $children;
    }

    /** @param array<string, mixed> $node */
    private static function text(array $node): string
    {
        return isset($node['value']) ? (string)$node['value'] : implode('', array_map(self::text(...), $node['children'] ?? []));
    }
}
