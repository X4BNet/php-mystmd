<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

/** Fuzzy links supplement CommonMark autolinks with MyST's pinned TLD list. */
final class Linkify
{
    /** @param array<string, mixed> $node */
    public static function apply(array &$node): void
    {
        if (in_array($node['type'], ['link', 'image', 'code', 'inlineCode', 'html'], true)) {
            return;
        }
        $children = [];
        foreach ($node['children'] ?? [] as $child) {
            if ($child['type'] !== 'text') {
                self::apply($child);
                $children[] = $child;
                continue;
            }
            $source = $child['value'];
            preg_match_all('~(?<![\w@/])(?:[\pL\pN](?:[\pL\pN-]*[\pL\pN])?\.)+([\pL]+)(?::\d+)?(?:/[^\s<>]*)?~u', $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            $offset = 0;
            $tlds = RuntimeResources::tlds();
            foreach ($matches as $match) {
                if (in_array(strtolower($match[1][0]), ['py', 'md', 'dot', 'next', 'so', 'es', 'java', 'zip', 'sc', 'ir'], true) || !in_array(strtolower($match[1][0]), $tlds, true)) {
                    continue;
                }
                $value = rtrim($match[0][0], '.,!?;:');
                $start = $match[0][1];
                if ($start > $offset) {
                    $children[] = array_replace($child, ['value' => substr($source, $offset, $start - $offset)]);
                }
                $children[] = ['type' => 'link', 'url' => 'http://' . $value, 'children' => [array_replace($child, ['value' => $value])]] + array_intersect_key($child, ['position' => true]);
                $offset = $start + strlen($value);
            }
            if ($offset < strlen($source)) {
                $children[] = array_replace($child, ['value' => substr($source, $offset)]);
            }
        }
        if (isset($node['children'])) {
            $node['children'] = $children;
        }
    }
}
