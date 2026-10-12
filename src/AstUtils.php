<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** MyST tree utilities available to extension and resolver implementations. */
final class AstUtils
{
    /** @param array<mixed>|string|null $content */
    public static function toText(array|string|null $content): string
    {
        if ($content === null || is_string($content)) {
            return $content ?? '';
        }
        if (array_is_list($content)) {
            return implode('', array_map(self::toText(...), $content));
        }
        return isset($content['value']) ? (string)$content['value'] : self::toText($content['children'] ?? null);
    }

    /** @return array{identifier: string, label: string, html_id: string}|null */
    public static function normalizeLabel(?string $label): ?array
    {
        if (!$label) {
            return null;
        }
        $identifier = mb_strtolower(trim(preg_replace('/[\t\n\r ]+/', ' ', preg_replace('/[\x{0027}‘’"“”]+/u', '', $label) ?? $label) ?? $label));
        $id = preg_replace('/[^a-z0-9-]/', '-', $identifier) ?? $identifier;
        $id = preg_replace('/^([0-9-])/', 'id-$1', $id) ?? $id;
        $id = trim(preg_replace('/-{2,}/', '-', $id) ?? $id, '-');
        return ['identifier' => $identifier, 'label' => $label, 'html_id' => $id];
    }

    /** @param array<string, mixed> $tree */
    public static function liftChildren(array &$tree, string $type): void
    {
        if (!isset($tree['children'])) {
            return;
        }
        $children = [];
        foreach ($tree['children'] as $child) {
            self::liftChildren($child, $type);
            array_push($children, ...(($child['type'] ?? '') === $type && isset($child['children']) ? $child['children'] : [$child]));
        }
        $tree['children'] = $children;
    }

    /**
     * @param array<string, mixed> $tree
     * @return array<string, mixed>
     */
    public static function mergeTextNodes(array &$tree): array
    {
        if (!isset($tree['children'])) {
            return $tree;
        }
        $children = [];
        foreach ($tree['children'] as $child) {
            $index = array_key_last($children);
            if (($child['type'] ?? '') === 'text' && $index !== null && ($children[$index]['type'] ?? '') === 'text') {
                $children[$index]['value'] = ($children[$index]['value'] ?? '') . ($child['value'] ?? '');
                if (isset($child['position']['end'])) {
                    $children[$index]['position']['end'] = $child['position']['end'];
                }
            } else {
                $children[] = self::mergeTextNodes($child);
            }
        }
        $tree['children'] = $children;
        return $tree;
    }

    /** Convert an upstream page slug to a relative URL. */
    public static function slugToUrl(?string $slug): ?string
    {
        return $slug === null ? null : str_replace('.', '/', preg_replace('/\.index$/', '', $slug) ?? $slug);
    }

    /** @param int|array<mixed>|null $count */
    public static function plural(string $format, int|array|null $count = null): string
    {
        $number = is_int($count) ? $count : count($count ?? []);
        $format = preg_replace('/%s/', (string)$number, $format, 1) ?? $format;
        return preg_replace_callback('/\((?:([a-z0-9A-Z-]*)\|)?([a-z0-9A-Z-]*)\)/', static fn (array $m): string => $number === 1 ? $m[1] : $m[2], $format) ?? $format;
    }
}
