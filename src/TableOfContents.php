<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Builds local tables of contents, with optional caller-provided project pages. */
final class TableOfContents
{
    /** Expand TOC nodes without performing project discovery or resource access.
     * @param array<string, mixed> $tree
     * @param list<array<string, mixed>>|null $pages
     */
    public static function apply(array &$tree, Parser $parser, ?array $pages = null, ?string $projectSlug = null, ?string $currentSlug = null): void
    {
        $nodes = [];
        self::collect($tree, $nodes);
        foreach (['project', 'children', 'page', 'section'] as $kind) {
            $indices = array_keys(array_filter($nodes, static fn (array $node): bool => ($node['type'] ?? '') === 'toc' && ($node['kind'] ?? '') === $kind));
            if ($indices === []) {
                continue;
            }
            if (in_array($kind, ['project', 'children'], true) && ($pages === null || ($kind === 'children' && $currentSlug === null))) {
                $parser->warn('Pages not available to build ' . ($kind === 'children' ? 'children ' : '') . 'Table of Contents', '', 'error');
                continue;
            }
            $items = $pages ?? [];
            $currentLevel = 0;
            if ($kind === 'project' && ($items[0]['level'] ?? 1) !== 1) {
                $parser->warn('First page of Table of Contents must be level 1', '');
            }
            if ($kind === 'children') {
                $currentIndex = null;
                foreach ($items as $index => $page) {
                    if (($page['slug'] ?? null) === $currentSlug) {
                        $currentIndex = $index;
                        break;
                    }
                }
                if ($currentIndex === null) {
                    $parser->warn('Current page not found in project pages for children Table of Contents', '');
                    continue;
                }
                $currentLevel = $items[$currentIndex]['level'];
                $children = [];
                foreach (array_slice($items, $currentIndex + 1) as $page) {
                    if ($page['level'] <= $currentLevel) {
                        break;
                    }
                    $children[] = $page;
                }
                $items = $children;
            }
            $pageHeadings = array_values(array_filter($nodes, static fn (array $node): bool => ($node['type'] ?? '') === 'heading'));
            if ($kind === 'page') {
                if ($pageHeadings === []) {
                    $parser->warn('No page headings found for Table of Contents', '');
                    continue;
                }
                if (min(array_column($pageHeadings, 'depth')) !== $pageHeadings[0]['depth']) {
                    $parser->warn('Page heading levels do not start with highest level', '');
                }
            }
            foreach ($indices as $index) {
                $toc = &$nodes[$index];
                $selected = $items;
                $headings = in_array($kind, ['page', 'section'], true);
                if ($headings) {
                    $selected = $kind === 'page' ? $pageHeadings : array_values(array_filter(array_slice($nodes, $index + 1), static fn (array $node): bool => ($node['type'] ?? '') === 'heading'));
                    if ($selected === []) {
                        $parser->warn('No section headings found for Table of Contents', '');
                        continue;
                    }
                    if (!empty($toc['depth'])) {
                        $firstDepth = $selected[0]['depth'];
                        $selected = array_values(array_filter($selected, static fn (array $heading): bool => $heading['depth'] - $firstDepth < $toc['depth']));
                    }
                    if ($kind === 'section') {
                        foreach ($selected as $headingIndex => $heading) {
                            if ($heading['depth'] < $selected[0]['depth']) {
                                $selected = array_slice($selected, 0, $headingIndex);
                                break;
                            }
                        }
                    }
                } elseif (!empty($toc['depth'])) {
                    $selected = array_values(array_filter($selected, static fn (array $page): bool => $page['level'] - $currentLevel <= $toc['depth']));
                }
                $toc['type'] = 'block';
                unset($toc['kind']);
                $toc['data'] = ['part' => 'toc:' . $kind];
                $toc['children'][] = self::makeList($selected, $headings, $projectSlug);
                unset($toc);
            }
        }
    }

    /** Collect nodes in source order, excluding headings immediately inside a TOC.
     * @param array<string, mixed> $node
     * @param list<array<string, mixed>> $nodes
     */
    private static function collect(array &$node, array &$nodes, bool $inToc = false): void
    {
        $type = $node['type'] ?? '';
        if ($type === 'toc' || ($type === 'heading' && !$inToc)) {
            $nodes[] = &$node;
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                self::collect($child, $nodes, $type === 'toc');
            }
            unset($child);
        }
    }

    /** Convert a flat, depth-ordered sequence into nested lists.
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private static function makeList(array $items, bool $headings, ?string $projectSlug): array
    {
        $list = ['type' => 'list', 'children' => []];
        if ($items === []) {
            return $list;
        }
        $depthKey = $headings ? 'depth' : 'level';
        $depth = $items[0][$depthKey];
        foreach ($items as $index => $item) {
            if ($item[$depthKey] < $depth) {
                break;
            }
            if ($item[$depthKey] !== $depth) {
                continue;
            }
            $text = ['type' => 'text', 'value' => (!empty($item['enumerator']) ? $item['enumerator'] . ' ' : '') . ($headings ? AstUtils::toText($item['children'] ?? []) : $item['title'])];
            $child = $text;
            if ($headings && !empty($item['identifier'])) {
                $child = ['type' => 'link', 'url' => '#' . $item['identifier'], 'internal' => true, 'children' => [$text], 'suppressImplicitWarning' => true];
            } elseif (!$headings && isset($item['url'])) {
                $child = ['type' => 'link', 'url' => $item['url'], 'internal' => false, 'children' => [$text]];
            } elseif (!$headings && isset($item['slug'])) {
                $child = ['type' => 'link', 'url' => ($projectSlug ? '/' . $projectSlug : '') . '/' . AstUtils::slugToUrl($item['slug']), 'internal' => true, 'children' => [$text]];
            }
            $listItem = ['type' => 'listItem', 'children' => [$child]];
            if (isset($items[$index + 1]) && $items[$index + 1][$depthKey] > $depth) {
                $listItem['children'][] = self::makeList(array_slice($items, $index + 1), $headings, $projectSlug);
            }
            $list['children'][] = $listItem;
        }
        return $list;
    }
}
