<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\AstUtils;
use Splitice\Mystmd\Document;
use Splitice\Mystmd\Numbering;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Transforms;

/** Document-local targets for the opt-in semantic serializer. */
final class SemanticReferences
{
    /** @var array<string, array<string, mixed>> */
    private array $targets = [];
    /** @var array<string, true> */
    private array $ids = [];

    public function resolve(Document $document): void
    {
        Transforms::propagateTargets($document->ast);
        $this->transferTargets($document->ast);
        $parser = new Parser();
        (new Numbering(['content_includes_title' => true], preserveExistingEnumerators: true))->apply($document->ast, $parser);
        array_push($document->diagnostics, ...$parser->diagnostics());
        $this->index($document->ast);
        $this->references($document->ast);
    }

    /** Transfer labels through syntax wrappers to the element that emits HTML.
     * @param array<string, mixed> $node
     */
    private function transferTargets(array &$node): void
    {
        if (in_array($node['type'], ['mystDirective', 'mystRole'], true) && isset($node['identifier'], $node['children'][0])) {
            foreach (['identifier', 'label', 'html_id'] as $key) {
                if (isset($node[$key])) {
                    $node['children'][0][$key] = $node[$key];
                    unset($node[$key]);
                }
            }
        }
        foreach ($node['children'] ?? [] as $index => $child) {
            // Captioned code directives carry their target on both parent and code.
            if ($node['type'] === 'container' && isset($node['identifier']) && ($child['identifier'] ?? null) === $node['identifier']) {
                unset($child['identifier'], $child['label'], $child['html_id']);
            }
            $this->transferTargets($child);
            $node['children'][$index] = $child;
        }
    }

    /** Assign unique DOM IDs without changing reference identifiers.
     * @param array<string, mixed> $node
     */
    private function index(array &$node): void
    {
        if (isset($node['identifier']) && !in_array($node['type'], ['crossReference', 'cite', 'footnoteReference', 'footnoteDefinition', 'link', 'definition', 'imageReference', 'linkReference'], true)) {
            $id = $node['html_id'] ?? AstUtils::normalizeLabel($node['identifier'])['html_id'] ?? '';
            if ($id === '') {
                $id = 'target-' . substr(hash('sha256', $node['identifier']), 0, 12);
            }
            $candidate = $id;
            for ($suffix = 2; isset($this->ids[$candidate]); ++$suffix) {
                $candidate = $id . '-' . $suffix;
            }
            $node['html_id'] = $candidate;
            $this->ids[$candidate] = true;
            $this->targets[Parser::label($node['identifier'])['identifier']] ??= $node;
        }
        foreach ($node['children'] ?? [] as $index => $child) {
            $this->index($child);
            $node['children'][$index] = $child;
        }
    }

    /** Resolve local shorthand before handing remaining citations to the caller.
     * @param array<string, mixed> $node
     */
    private function references(array &$node, bool $explicitCitation = false): void
    {
        $explicitCitation = $explicitCitation || ($node['type'] === 'mystRole' && str_starts_with($node['name'] ?? '', 'cite'));
        foreach ($node['children'] ?? [] as $index => $child) {
            $this->references($child, $explicitCitation);
            $node['children'][$index] = $child;
        }
        if (!in_array($node['type'], ['link', 'crossReference', 'cite'], true) || ($node['type'] === 'cite' && $explicitCitation)) {
            return;
        }
        $label = $node['type'] === 'link' ? ($node['url'] ?? '') : ($node['identifier'] ?? '');
        $fragment = str_starts_with($label, '#');
        $identifier = Parser::label($fragment ? rawurldecode(substr($label, 1)) : $label)['identifier'];
        $target = $this->targets[$identifier] ?? null;
        if ($target === null) {
            if ($node['type'] === 'link' && $fragment) {
                $node = array_replace($node, ['type' => 'crossReference', 'kind' => 'ref', 'identifier' => $identifier, 'label' => $identifier]);
                unset($node['url']);
            }
            return;
        }
        $kind = $target['type'] === 'container' ? $target['kind'] : $target['type'];
        $number = $target['enumerator'] ?? null;
        $name = $target['type'] === 'heading' ? AstUtils::toText($target) : ($target['label'] ?? $identifier);
        foreach ($target['children'] ?? [] as $child) {
            if ($child['type'] === 'caption') {
                $name = AstUtils::toText($child);
                break;
            }
        }
        $ref = $node['kind'] ?? 'ref';
        $automaticNumber = $node['type'] !== 'crossReference' || $ref === 'numref' || $ref === 'eq';
        $listing = match ($kind) {'math' => 'Equation', 'heading' => 'Section', 'code' => 'Program', default => ucfirst($kind)};
        $text = $automaticNumber && $number !== null ? ($kind === 'math' ? '(' . $number . ')' : $listing . ' ' . $number) : $name;
        if (empty($node['children'])) {
            $node['children'] = [['type' => 'text', 'value' => $text]];
        } else {
            Transforms::walk($node, static function (array &$child) use ($name, $number): void {
                if ($child['type'] === 'text') {
                    $child['value'] = str_replace(['{name}', '{number}', '%s'], [$name, $number ?? '??', $number ?? '??'], $child['value']);
                }
            });
        }
        $node = array_replace($node, ['type' => 'crossReference', 'identifier' => $identifier, 'resolved' => true, 'url' => '#' . $target['html_id']]);
    }
}
