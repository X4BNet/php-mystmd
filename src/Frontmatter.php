<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

use Symfony\Component\Yaml\Yaml;

/** Optional extraction of document metadata and the leading title. */
final class Frontmatter
{
    /**
     * @param array<string, mixed> $tree
     * @param array<string, mixed> $options
     * @return array{tree: array<string, mixed>, frontmatter: array<string, mixed>, identifiers: list<string>}
     */
    public static function extract(array &$tree, Parser $parser, array $options = ['propagateTargets' => true]): array
    {
        if ($options['propagateTargets'] ?? false) {
            Transforms::propagateTargets($tree);
        }
        $parent =& $tree;
        if (($tree['children'][0]['type'] ?? '') === 'block') {
            $parent =& $tree['children'][0];
        }
        $first = $parent['children'][0] ?? [];
        $firstYaml = ($first['type'] ?? '') === 'code' && ($first['lang'] ?? '') === 'yaml';
        $metadata = [];
        $identifiers = [];
        if ($firstYaml) {
            try {
                $metadata = Yaml::parse($first['value']) ?? [];
                if (!is_array($metadata)) {
                    throw new \UnexpectedValueException('Frontmatter must be a mapping');
                }
                $parent['children'][0]['type'] = '__delete__';
            } catch (\Symfony\Component\Yaml\Exception\ParseException|\UnexpectedValueException) {
                $parser->warn('Invalid YAML frontmatter', 'frontmatter-is-yaml', 'error');
                $metadata = [];
            }
        }
        $metadata = array_replace($metadata, $options['preFrontmatter'] ?? []);
        if (isset($metadata['content_includes_title'])) {
            $parser->warn("'frontmatter' cannot explicitly set: content_includes_title", 'valid-page-frontmatter');
            unset($metadata['content_includes_title']);
        }
        $titleNull = array_key_exists('title', $metadata) && $metadata['title'] === null;
        if ($titleNull) {
            unset($metadata['title']);
        }
        $heading = self::firstHeading($tree);
        if (empty($metadata['title']) && $heading !== null) {
            $metadata['title'] = self::text($heading);
            $metadata['content_includes_title'] = true;
        }
        $next =& $parent['children'][0];
        if ($firstYaml || ($first['type'] ?? '') === 'comment') {
            $next = $parent['children'][0] ?? null;
            foreach (array_keys($parent['children']) as $index) {
                if ($index === 0 || $parent['children'][$index]['type'] === 'comment') {
                    continue;
                }
                $next =& $parent['children'][$index];
                if ($next['type'] === 'block') {
                    foreach (array_keys($next['children']) as $childIndex) {
                        if ($next['children'][$childIndex]['type'] !== 'comment') {
                            $next =& $next['children'][$childIndex];
                            break;
                        }
                    }
                }
                break;
            }
        }
        if (($next['type'] ?? '') === 'heading' && ($next['depth'] ?? null) === 1 && !$titleNull
            && !empty($metadata['title']) && $metadata['title'] === self::text($next) && !($options['keepTitleNode'] ?? false)) {
            $next['type'] = '__delete__';
            $metadata['content_includes_title'] = false;
            if (!empty($next['label'])) {
                $identifiers[] = Parser::label($next['label'])['identifier'];
            }
        }
        unset($next, $parent);
        $beforeRemoval = $tree;
        self::removeDeleted($tree);
        if (empty($tree['children'])) {
            $tree = $beforeRemoval;
            self::removeDeleted($tree, false);
        }
        return ['tree' => $tree, 'frontmatter' => $metadata, 'identifiers' => $identifiers];
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>|null
     */
    private static function firstHeading(array $node): ?array
    {
        if (($node['type'] ?? '') === 'heading') {
            return $node;
        }
        foreach ($node['children'] ?? [] as $child) {
            $heading = self::firstHeading($child);
            if ($heading !== null) {
                return $heading;
            }
        }
        return null;
    }

    /** @param array<string, mixed> $node */
    private static function text(array $node): string
    {
        return isset($node['value']) ? (string)$node['value'] : implode('', array_map(self::text(...), $node['children'] ?? []));
    }

    /** @param array<string, mixed> $tree */
    private static function removeDeleted(array &$tree, bool $cascade = true): void
    {
        if (!isset($tree['children'])) {
            return;
        }
        $children = [];
        foreach ($tree['children'] as $child) {
            if ($child === null || $child['type'] === '__delete__') {
                continue;
            }
            $wasNonempty = !empty($child['children']);
            self::removeDeleted($child, $cascade);
            if (!$cascade || !$wasNonempty || !empty($child['children'])) {
                $children[] = $child;
            }
        }
        $tree['children'] = $children;
    }
}
