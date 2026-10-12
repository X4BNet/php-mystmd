<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Extension;

use Splitice\Mystmd\Definition;
use Splitice\Mystmd\Internal\BuiltInDirectives;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Registry;

/** Optional language extensions shipped by the pinned MyST project. */
final class FirstPartyExtension implements ExtensionInterface
{
    /** @param list<string> $packages */
    public function __construct(private readonly array $packages = ['button', 'card', 'exercise', 'grid', 'icon', 'proof', 'reactive', 'tabs'])
    {
    }

    /** Install only the requested packages into this converter's registry. */
    public function register(Registry $registry): void
    {
        $catalog = json_decode((string)file_get_contents(__DIR__ . '/../../resources/first-party-definitions.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($this->packages as $package) {
            if (!isset($catalog[$package])) {
                throw new \InvalidArgumentException('Unknown first-party extension: ' . $package);
            }
            foreach ($catalog[$package]['directives'] as $spec) {
                $registry->directiveDefinition(new Definition($spec, self::directive(...)));
            }
            foreach ($catalog[$package]['roles'] as $spec) {
                $registry->roleDefinition(new Definition($spec, self::role(...)));
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    public static function directive(array $data, Parser $parser): array
    {
        $name = $data['name'];
        $body = $data['body'] ?? [];
        $options = $data['options'];
        $common = BuiltInDirectives::common($options, $parser);
        if (in_array($name, ['tab-set', 'tabSet'], true)) {
            return [['type' => 'tabSet', 'children' => $body] + $common];
        }
        if (in_array($name, ['tab-item', 'tabItem', 'tab'], true)) {
            return [['type' => 'tabItem', 'title' => $data['arg'] ?? 'Tab Title', 'children' => $body] + array_intersect_key($options, array_flip(['sync', 'selected'])) + $common];
        }
        if ($name === 'grid') {
            $columns = array_values(array_map(static fn (string $value): int => max(1, min(12, (int)floor((float)$value))), array_filter(preg_split('/\s/', $data['arg'] ?? '1 2 2 3') ?: [], static fn (string $value): bool => $value === '' || is_numeric($value))));
            return [['type' => 'grid', 'columns' => count($columns) > 0 && count($columns) <= 4 ? $columns : [1, 2, 2, 3], 'children' => $body] + $common];
        }
        if ($name === 'grid-item') {
            return [['type' => 'grid-item', 'children' => $body] + array_intersect_key($options, ['columns' => true]) + $common];
        }
        if (in_array($name, ['card', 'grid-item-card'], true)) {
            return [self::card($data)];
        }
        if ($name === 'r:var') {
            $node = ['type' => 'r:var'] + array_intersect_key($options, array_flip(['name', 'value', 'format']));
            if (isset($options['rValue'])) {
                $node['valueFunction'] = $options['rValue'];
            }
            return [$node];
        }
        $kind = explode('-', $name)[0];
        if (in_array($kind, ['exercise', 'solution'], true)) {
            if (str_ends_with($name, '-end')) {
                return [['type' => $kind, 'gate' => 'end']];
            }
            $node = ['type' => $kind, 'children' => []] + array_intersect_key($options, ['hidden' => true]) + $common;
            if (!empty($data['arg'])) {
                $title = $kind === 'solution' ? [['type' => 'text', 'value' => 'Solution to '], ['type' => 'crossReference'] + Parser::label($data['arg'])] : $data['arg'];
                $node['children'][] = ['type' => 'admonitionTitle', 'children' => $title];
            }
            array_push($node['children'], ...$body);
            if ($kind === 'exercise') {
                $node['enumerated'] = isset($options['nonumber']) ? !$options['nonumber'] : ($options['enumerated'] ?? true);
                if ($node['enumerated'] && !isset($node['label'])) {
                    $node += Parser::label('exercise-' . bin2hex(random_bytes(6)));
                }
            }
            if (str_ends_with($name, '-start')) {
                $node['gate'] = 'start';
            }
            return [$node];
        }
        $node = ['type' => 'proof', 'enumerated' => isset($options['nonumber']) ? !$options['nonumber'] : ($options['enumerated'] ?? true), 'children' => []];
        if ($name !== 'proof') {
            $node['kind'] = preg_replace('/^(prf|proof):/', '', $name);
        }
        if (!empty($data['arg'])) {
            $node['children'][] = ['type' => 'admonitionTitle', 'children' => $data['arg']];
        }
        array_push($node['children'], ...$body);
        return [array_replace($node, $common)];
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    public static function role(array $data, Parser $parser): array
    {
        $name = $data['name'];
        $body = $data['body'];
        if ($name === 'button') {
            if (!preg_match('/^([^<>]*)(<[^<>]+>)?$/', $body, $match)) {
                $parser->warn('Invalid {button} role with body: "' . $body . '"', 'role-body-correct');
                return [['type' => 'span', 'class' => 'button', 'children' => [['type' => 'text', 'value' => $body]]]];
            }
            $url = isset($match[2]) ? trim(substr($match[2], 1, -1)) : '';
            $text = trim($match[1]) ?: $url;
            return [($url !== '' ? ['type' => 'link', 'url' => $url] : ['type' => 'span']) + ['class' => 'button', 'children' => $text !== '' ? [['type' => 'text', 'value' => $text]] : []]];
        }
        if (str_starts_with($name, 'r:')) {
            $node = ['type' => $name];
            foreach (explode('", ', $body) as $part) {
                $parts = explode('="', preg_replace('/",?\s?$/', '', $part) ?? $part, 2);
                $key = $parts[0];
                $node[str_starts_with($key, 'r') ? strtolower(substr($key, 1)) . 'Function' : $key] = $parts[1] ?? null;
            }
            return [$node];
        }
        $legacy = ['octicon' => 'oct', 'fas' => 'fas', 'far' => 'far', 'fab' => 'fab', 'material-twotone' => 'mtt', 'material-sharp' => 'msp', 'material-regular' => 'mrg', 'material-round' => 'mrd', 'material-outline' => 'mol'];
        return [['type' => 'icon', 'kind' => $legacy[$name] ?? substr($name, 5), 'name' => $body]];
    }

    /**
     * @param array<string, mixed> $node
     * @return array{before: array<string, mixed>|null, after: array<string, mixed>|null, post: bool}
     */
    public static function splitParagraphNode(array $node): array
    {
        $before = $after = [];
        $post = false;
        foreach ($node['children'] ?? [] as $child) {
            if ($post) {
                $after[] = $child;
            } elseif ($child['type'] !== 'text' || !preg_match('/((?<before>[\s\S]*?)\s+)?\^\^\^(\s+(?<after>[\s\S]*))?/', $child['value'] ?? '', $match)) {
                $before[] = $child;
            } else {
                $post = true;
                if (!empty($match['before'])) {
                    $before[] = ['type' => 'text', 'value' => $match['before']];
                }
                if (!empty($match['after'])) {
                    $after[] = ['type' => 'text', 'value' => $match['after']];
                }
            }
        }
        return ['before' => $before === [] ? null : array_replace($node, ['children' => $before]), 'after' => $after === [] ? null : array_replace($node, ['children' => $after]), 'post' => $post];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function card(array $data): array
    {
        $options = $data['options'];
        $header = $footer = $body = [];
        if (isset($options['header']) || isset($options['footer'])) {
            $header = isset($options['header']) ? [['type' => 'paragraph', 'children' => $options['header']]] : [];
            $footer = isset($options['footer']) ? [['type' => 'paragraph', 'children' => $options['footer']]] : [];
            $body = $data['body'];
        } else {
            $before = $after = [];
            $post = false;
            foreach ($data['body'] as $child) {
                if ($post) {
                    $after[] = $child;
                } elseif ($child['type'] !== 'paragraph') {
                    $before[] = $child;
                } else {
                    $split = self::splitParagraphNode($child);
                    $post = $split['post'];
                    if ($split['before'] !== null) {
                        $before[] = $split['before'];
                    }
                    if ($split['after'] !== null) {
                        $after[] = $split['after'];
                    }
                }
            }
            $header = $after !== [] ? $before : [];
            $post = false;
            foreach ($after !== [] ? $after : $before as $child) {
                if ($post) {
                    $footer[] = $child;
                } elseif ($child['type'] === 'blockBreak') {
                    $post = true;
                } else {
                    $body[] = $child;
                }
            }
        }
        $children = $header !== [] ? [['type' => 'header', 'children' => $header]] : [];
        if (!empty($data['arg'])) {
            $children[] = ['type' => 'cardTitle', 'children' => $data['arg']];
        }
        array_push($children, ...$body);
        if ($footer !== []) {
            $children[] = ['type' => 'footer', 'children' => $footer];
        }
        return ['type' => 'card', 'children' => $children] + array_intersect_key($options, ['url' => true]);
    }
}
