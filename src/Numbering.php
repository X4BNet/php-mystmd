<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Configurable document numbering from myst-transforms. */
final class Numbering
{
    /** @var array<string, mixed> */
    public array $numbering;
    /** @var array<string, mixed> */
    public array $counts;
    /** @var array<string, array<string, mixed>> */
    public array $targets = [];
    /** @var list<string> */
    private array $identifiers;
    private int $offset;

    /** @param array<string, mixed> $frontmatter
     * @param array<string, mixed>|null $previousCounts
     * @param list<string> $identifiers
     * @param bool $preserveExistingEnumerators Keep explicit values, including zero, during semantic preparation.
     */
    public function __construct(array $frontmatter = [], ?array $previousCounts = null, array $identifiers = [], private readonly bool $preserveExistingEnumerators = false)
    {
        $defaults = [];
        foreach (['equation' => '(%s)', 'subequation' => '(%s)', 'figure' => 'Figure %s', 'subfigure' => 'Figure %s', 'table' => 'Table %s', 'code' => 'Program %s'] as $kind => $template) {
            $defaults[$kind] = ['enabled' => true, 'template' => $template];
        }
        foreach (range(1, 6) as $depth) {
            $defaults['heading_' . $depth] = ['template' => 'Section %s'];
        }
        $this->numbering = self::fill($frontmatter['numbering'] ?? [], $defaults);
        $this->offset = $this->numbering['title']['offset'] ?? 0;
        $this->counts = self::initializeCounts($this->numbering, $previousCounts, $this->offset);
        $this->identifiers = $identifiers;
        if (($this->numbering['title']['enabled'] ?? $this->numbering['all']['enabled'] ?? false)
            && empty($frontmatter['content_includes_title']) && ($this->numbering['heading_' . ($this->offset + 1)]['enabled'] ?? true)) {
            $this->counts['heading'] = ReferenceState::incrementHeadingCounts($this->offset + 1, $this->counts['heading']);
        }
    }

    /** Fill missing per-kind configuration, honoring explicit global switches.
     * @param array<string, mixed> $base
     * @param array<string, mixed> $filler
     * @return array<string, mixed>
     */
    public static function fill(array $base = [], array $filler = []): array
    {
        $output = array_replace($filler, $base);
        foreach ($filler as $key => $value) {
            if (in_array($key, ['enumerator', 'all', 'headings', 'title'], true)) {
                continue;
            }
            $output[$key] = $base[$key] ?? [];
            foreach (['enabled', 'start', 'enumerator', 'template', 'continue'] as $property) {
                $fallback = in_array($property, ['enabled', 'continue'], true) ? ($base['all'][$property] ?? $value[$property] ?? null) : ($value[$property] ?? null);
                if (!isset($output[$key][$property]) && $fallback !== null) {
                    $output[$key][$property] = $fallback;
                }
            }
        }
        return $output;
    }

    /** Initialize continuation, explicit starts, and disabled heading levels.
     * @param array<string, mixed> $numbering
     * @param array<string, mixed>|null $previous
     * @return array<string, mixed>
     */
    public static function initializeCounts(array $numbering, ?array $previous = null, int $offset = 0): array
    {
        $counts = ['heading' => []];
        foreach (range(1, 6) as $depth) {
            $item = $numbering['heading_' . $depth] ?? [];
            $cont = $item['continue'] ?? $numbering['all']['continue'] ?? false;
            $enabled = $item['enabled'] ?? $numbering['all']['enabled'] ?? true;
            $hasPrevious = array_key_exists($depth - 1, $previous['heading'] ?? []);
            $prev = $previous['heading'][$depth - 1] ?? null;
            if ($cont && $enabled && $hasPrevious) {
                $counts['heading'][] = $prev;
            } elseif (!empty($numbering['title']['enabled']) && $depth - 1 <= $offset && $prev !== null) {
                $counts['heading'][] = $prev;
            } else {
                $counts['heading'][] = empty($numbering['title']['enabled']) && $depth <= $offset ? null : 0;
            }
        }
        foreach ($previous ?? [] as $key => $value) {
            if ($key !== 'heading' && (!empty($numbering[$key]['continue']) || !empty($numbering['all']['continue']))) {
                $counts[$key] = $value;
            }
        }
        foreach ($numbering as $key => $value) {
            if (preg_match('/^heading_([1-6])$/D', $key, $match)) {
                if (($value['enabled'] ?? null) === false) {
                    $counts['heading'][(int)$match[1] - 1] = null;
                } elseif (!empty($value['start'])) {
                    $counts['heading'][(int)$match[1] - 1] = $value['start'] - 1;
                }
            } elseif (!empty($value['start'])) {
                $counts[$key] = ['main' => $value['start'] - 1, 'sub' => 0];
            }
        }
        return $counts;
    }

    /** Enumerate parents before children and assign implicit subfigure labels.
     * @param array<string, mixed> $tree
     */
    public function apply(array &$tree, ?Parser $parser = null, bool $hidden = false): void
    {
        $this->visit($tree, $parser, $hidden);
        $this->subfigures($tree, $parser, $hidden);
    }

    /** @param array<string, mixed> $node */
    private function visit(array &$node, ?Parser $parser, bool $hidden): void
    {
        $type = $node['type'] ?? '';
        if (!in_array($type, ['crossReference', 'cite', 'footnoteDefinition', 'footnoteReference', 'captionNumber', 'link'], true)
            && (!empty($node['identifier']) || !empty($node['enumerated']) || in_array($type, ['container', 'mathGroup', 'math', 'heading', 'proof'], true))) {
            $this->addTarget($node, $parser, $hidden);
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->visit($child, $parser, $hidden);
            }
            unset($child);
        }
    }

    /** @param array<string, mixed> $node */
    private function addTarget(array &$node, ?Parser $parser, bool $hidden): void
    {
        $kind = match ($node['type']) {
            'container' => $node['kind'] ?? 'figure',
            'math' => ($node['kind'] ?? '') === 'subequation' ? 'subequation' : 'equation',
            'mathGroup' => 'equation',
            default => $node['type'] . (isset($node['kind']) ? ':' . $node['kind'] : ''),
        };
        $headingDepth = ($node['depth'] ?? 1) - (!empty($this->numbering['title']['enabled']) ? 0 : 1) + $this->offset;
        $configKey = $kind === 'heading' ? 'heading_' . $headingDepth : (!empty($node['subcontainer']) ? 'subfigure' : $kind);
        $enabled = $node['enumerated'] ?? $this->numbering[$configKey]['enabled'] ?? $this->numbering['all']['enabled'] ?? false;
        $hasEnumerator = $this->preserveExistingEnumerators ? array_key_exists('enumerator', $node) : !empty($node['enumerator']);
        if (!$hidden && $enabled && !$hasEnumerator) {
            if ($kind === 'heading') {
                $this->counts['heading'] = ReferenceState::incrementHeadingCounts($headingDepth, $this->counts['heading']);
                $node['enumerator'] = $this->format(ReferenceState::formatHeadingEnumerator($this->counts['heading']), $configKey);
            } else {
                $countKind = $kind === 'subequation' ? 'equation' : $kind;
                $this->counts[$countKind] ??= ['main' => 0, 'sub' => 0];
                $count = &$this->counts[$countKind];
                if (!empty($node['subcontainer']) || $kind === 'subequation') {
                    $letter = chr((++$count['sub'] - 1) % 26 + ord('a'));
                    if (!empty($node['subcontainer'])) {
                        $node['parentEnumerator'] = $this->format((string)$count['main'], $countKind);
                        $node['enumerator'] = $letter;
                    } else {
                        $node['enumerator'] = $this->format($count['main'] . $letter, $countKind);
                    }
                } else {
                    ++$count['main'];
                    $count['sub'] = 0;
                    $node['enumerator'] = $this->format((string)$count['main'], $kind);
                }
                unset($count);
            }
        }
        if (empty($node['html_id']) && !empty($node['identifier'])) {
            $node['html_id'] = AstUtils::normalizeLabel($node['identifier'])['html_id'];
        }
        $identifier = $node['identifier'] ?? '';
        if ($identifier === '') {
            return;
        }
        if (isset($this->targets[$identifier]) || in_array($identifier, $this->identifiers, true)) {
            if (empty($node['implicit'])) {
                $parser?->warn('Duplicate identifier in file "' . $identifier . '"', 'identifier-is-unique');
            }
            return;
        }
        $this->targets[$identifier] = ['node' => &$node, 'kind' => $kind];
    }

    /** @param array<string, mixed> $node */
    private function subfigures(array &$node, ?Parser $parser, bool $hidden, ?string $parentLabel = null): void
    {
        if (($node['type'] ?? '') === 'container') {
            if (empty($node['subcontainer'])) {
                $parentLabel = $node['label'] ?? $node['identifier'] ?? null;
            } elseif (empty($node['identifier']) && $parentLabel && !empty($node['enumerator'])) {
                $label = AstUtils::normalizeLabel($parentLabel . '-' . $node['enumerator']);
                $node['label'] = $label['label'];
                $node['identifier'] = $label['identifier'];
                $node['implicit'] = true;
                $this->addTarget($node, $parser, $hidden);
            }
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->subfigures($child, $parser, $hidden, $parentLabel);
            }
            unset($child);
        }
    }

    /** Substitute the per-kind or document-wide enumerator template. */
    private function format(string $value, string $kind): string
    {
        $template = $this->numbering[$kind]['enumerator'] ?? $this->numbering['enumerator']['enumerator'] ?? null;
        return $template ? str_replace('%s', $value, $template) : $value;
    }
}
