<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\Parser;
use Symfony\Component\Yaml\Yaml;

/** MyST directive semantics, separate from the fence tokenizer. */
final class BuiltInDirectives
{
    /**
     *
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    public static function apply(array $node, Parser $parser): array
    {
        $prepared = BuiltInOptions::prepare($node, $parser);
        $node = $prepared['node'];
        if (!$prepared['valid']) {
            return $node;
        }
        $name = $node['name'];
        $arg = $node['args'] ?? '';
        $body = $node['value'] ?? '';
        $options = $prepared['options'];
        $common = self::common($options, $parser);
        $parse = static fn (): array => $parser->parseBody($body);
        $admonitions = ['admonition', 'attention', 'caution', 'danger', 'error', 'important', 'hint', 'note', 'seealso', 'tip', 'warning'];
        if (in_array($name, $admonitions, true)) {
            $child = ['type' => 'admonition'] + $common;
            if ($name !== 'admonition') {
                $child['kind'] = $name;
            }
            $child['children'] = $arg !== '' ? [['type' => $body !== '' ? 'admonitionTitle' : 'paragraph', 'children' => $parser->parseInline($arg)]] : [];
            array_push($child['children'], ...$parse());
            if (in_array($options['icon'] ?? null, [false, 'false'], true)) {
                $child['icon'] = false;
            }
            if (isset($options['open'])) {
                $child['class'] = trim(($child['class'] ?? '') . ' dropdown');
                if (self::boolean($options['open'])) {
                    $child['open'] = true;
                }
            }
        } elseif (in_array($name, ['blockquote', 'epigraph', 'pull-quote'], true)) {
            $child = ['type' => 'container', 'kind' => 'quote', 'children' => [['type' => 'blockquote', 'children' => $parse()]]] + $common;
            $child['class'] = trim(($options['class'] ?? '') . ($name === 'blockquote' ? '' : ' ' . $name));
        } elseif (in_array($name, ['aside', 'margin', 'sidebar', 'div', 'glossary'], true)) {
            $child = ['type' => in_array($name, ['aside', 'margin', 'sidebar'], true) ? 'aside' : $name] + $common;
            if ($name === 'sidebar') {
                $child['kind'] = 'sidebar';
            }
            if ($name !== 'div' || $body !== '') {
                $child['children'] = $parse();
            }
        } elseif ($name === 'dropdown') {
            $child = ['type' => 'details'] + $common;
            if (self::boolean($options['open'] ?? false)) {
                $child['open'] = true;
            }
            $child['children'] = $arg !== '' ? [['type' => 'summary', 'children' => $parser->parseInline($arg)]] : [];
            array_push($child['children'], ...$parse());
        } elseif (in_array($name, ['code', 'code-block', 'sourcecode', 'code-cell'], true)) {
            $child = ['type' => 'code'] + $common;
            if ($arg !== '') {
                $child['lang'] = $arg;
            }
            $child += DirectiveUtils::codeOptions($options, $parser);
            $child['value'] = $body;
            if ($name === 'code-cell') {
                $child['executable'] = true;
                $child = ['type' => 'block', 'kind' => 'notebook-code', 'children' => [$child, ['type' => 'outputs', 'id' => bin2hex(random_bytes(12)), 'children' => []]], 'data' => []] + $common;
                $tags = DirectiveUtils::parseTags($options['tags'] ?? $options['tag'] ?? null, $parser);
                if ($tags !== null) {
                    $child['data']['tags'] = $tags;
                }
                if (isset($options['caption'])) {
                    $child['data']['caption'] = [['type' => 'paragraph', 'children' => $parser->parseInline($options['caption'])]];
                }
            } elseif (isset($options['caption'])) {
                $child = ['type' => 'container', 'kind' => 'code', 'children' => [$child, ['type' => 'caption', 'children' => [['type' => 'paragraph', 'children' => $parser->parseInline($options['caption'])]]]]] + $common;
            }
        } elseif ($name === 'math') {
            $child = ['type' => 'math'] + $common + ['value' => $body];
            if (isset($options['typst'])) {
                $child['typst'] = $options['typst'];
            }
            if (isset($node['tight'])) {
                $child['tight'] = $node['tight'];
            }
        } elseif (in_array($name, ['image', 'figure', 'iframe'], true)) {
            if ($arg === '' && $name !== 'figure') {
                return self::missing($node, 'argument', $parser);
            }
            $image = ['type' => $name === 'iframe' ? 'iframe' : 'image', $name === 'iframe' ? 'src' : 'url' => $arg];
            if ($name === 'image') {
                $image['align'] = $options['align'] ?? 'center';
            }
            foreach (['height', 'width', 'alt', 'align', 'title'] as $key) {
                if (isset($options[$key])) {
                    $image[$key] = (string)$options[$key];
                }
            }
            if ($name === 'figure' || ($name === 'iframe' && $body !== '')) {
                $child = ['type' => 'container', 'kind' => $options['kind'] ?? 'figure'] + $common;
                $image += array_intersect_key($options, array_flip(['remove-input', 'remove-output']));
                $child['children'] = $arg !== '' ? [$image] : [];
                if (!empty($options['placeholder'])) {
                    $child['children'][] = ['type' => 'image', 'placeholder' => true, 'url' => $options['placeholder']] + array_intersect_key($image, array_flip(['alt', 'width', 'height', 'align']));
                }
                if ($options['no-subfigures'] ?? false) {
                    $child['noSubcontainers'] = true;
                }
                if ($name === 'iframe') {
                    $child['children'][] = ['type' => 'caption', 'children' => $parse()];
                } else {
                    array_push($child['children'], ...$parse());
                }
            } else {
                $child = $image + $common;
            }
        } elseif ($name === 'include' || $name === 'literalinclude') {
            if ($arg === '') {
                return self::missing($node, 'argument', $parser);
            }
            $extension = pathinfo($arg, PATHINFO_EXTENSION);
            $lang = $options['lang'] ?? $options['language'] ?? $options['code'] ?? (['md' => 'markdown', 'py' => 'python', 'ts' => 'typescript', 'js' => 'javascript', 'mjs' => 'javascript', 'tex' => 'latex', 'yml' => 'yaml'][$extension] ?? $extension);
            $literal = $name === 'literalinclude' || self::boolean($options['literal'] ?? false) || isset($options['lang']) || isset($options['language']) || isset($options['code']);
            $child = ['type' => 'include', 'file' => $arg, 'lang' => $lang, 'literal' => $literal] + $common;
            if ($literal) {
                $child += DirectiveUtils::codeOptions($options, $parser, basename(str_replace('\\', '/', $arg)));
                if (isset($options['caption'])) {
                    $child['caption'] = $parser->parseInline((string)$options['caption'], ($node['position']['start']['line'] ?? 0) + 1 + (int)array_search('caption', array_keys($node['options'] ?? []), true));
                }
            }
            foreach ([['start-at', 'start-line', 'start-after', 'lines'], ['end-at', 'end-line', 'end-before', 'lines']] as $exclusive) {
                $present = array_values(array_intersect($exclusive, array_keys($options)));
                if (count($present) > 1) {
                    $parser->warn('Conflicting options for directive: ["' . implode('", "', $present) . '"]', 'directive-options-correct');
                }
            }
            $filter = [];
            foreach (['start-at' => 'startAt', 'start-after' => 'startAfter', 'end-at' => 'endAt', 'end-before' => 'endBefore'] as $option => $key) {
                if (isset($options[$option])) {
                    $filter[$key] = (string)$options[$option];
                }
            }
            if (!empty($options['lines'])) {
                $filter['lines'] = DirectiveUtils::parseLinesString($options['lines'], $parser);
            } elseif (isset($options['start-line']) || isset($options['end-line'])) {
                $range = [(int)($options['start-line'] ?? 0)];
                if (isset($options['end-line'])) {
                    $range[] = (int)$options['end-line'];
                }
                $filter['lines'] = [array_map(static fn (int $line): int => $line >= 0 ? $line + 1 : $line, $range)];
            }
            if ($filter !== []) {
                $child['filter'] = $filter;
            }
        } elseif ($name === 'bibliography') {
            $child = ['type' => 'bibliography'];
            if (isset($options['filter'])) {
                $child['filter'] = $options['filter'];
            }
        } elseif (in_array($name, ['mermaid', 'myst'], true)) {
            $child = ['type' => $name, 'value' => $body];
            if (isset($options['numbering'])) {
                try {
                    $child['numbering'] = is_string($options['numbering']) ? Yaml::parse($options['numbering']) : $options['numbering'];
                } catch (\Symfony\Component\Yaml\Exception\ParseException) {
                    // The pinned directive ignores invalid optional numbering YAML.
                }
            }
        } elseif ($name === 'mdast') {
            $child = ['type' => 'mdast', 'id' => $arg];
        } elseif ($name === 'embed') {
            $child = ['type' => 'embed'] + $common;
            foreach (['remove-input', 'remove-output'] as $key) {
                if (isset($options[$key])) {
                    $child[$key] = self::boolean($options[$key]);
                }
            }
            $child['source'] = str_starts_with($arg, '#') ? ['label' => substr($arg, 1)] : ['url' => $arg];
        } elseif (in_array($name, ['table', 'list-table', 'csv-table'], true)) {
            $parsedBody = [];
            $table = ['type' => 'table', 'children' => []];
            if (isset($options['align'])) {
                $table['align'] = $options['align'];
            }
            if ($name === 'list-table') {
                $parsedBody = $parse();
                $list = $parsedBody[0]['children'] ?? [];
                if (count($parsedBody) !== 1 || ($parsedBody[0]['type'] ?? '') !== 'list') {
                    $parser->warn('list-table directive must have one list as body', 'directive-body-correct', 'error');
                    $list = [];
                } else {
                    foreach ($list as $listItem) {
                        if ($listItem['type'] !== 'listItem' || count($listItem['children'] ?? []) !== 1 || ($listItem['children'][0]['type'] ?? '') !== 'list') {
                            $parser->warn('list-table directive must have a list of lists', 'directive-body-correct', 'error');
                            $list = [];
                            break;
                        }
                    }
                }
                foreach ($list as $rowIndex => $row) {
                    $cells = [];
                    foreach ($row['children'][0]['children'] ?? [] as $cell) {
                        $cells[] = ['type' => 'tableCell'] + ($rowIndex < (int)($options['header-rows'] ?? 0) ? ['header' => true] : []) + ['children' => $cell['children']];
                    }
                    $table['children'][] = ['type' => 'tableRow', 'children' => $cells];
                }
            } elseif ($name === 'csv-table') {
                if (isset($options['header'])) {
                    $table['children'] = CsvTable::rows($options['header'], $options, $parser, $node['position']['start']['line'] ?? 1, true);
                }
                array_push($table['children'], ...CsvTable::rows($body, $options, $parser, $node['position']['start']['line'] ?? 1));
            } else {
                $parsedBody = $parse();
            }
            $child = ['type' => 'container', 'kind' => 'table'] + $common + ['children' => []];
            if ($arg !== '') {
                $child['children'][] = ['type' => 'caption', 'children' => [['type' => 'paragraph', 'children' => $parser->parseInline($arg, $node['position']['start']['line'] ?? 1)]]];
            }
            array_push($child['children'], ...($name === 'table' ? $parsedBody : [$table]));
        } elseif (in_array($name, ['raw', 'raw:latex', 'raw:tex', 'raw:typst', 'raw:typ'], true)) {
            if ($name === 'raw') {
                $child = ['type' => 'raw', 'lang' => $arg, 'value' => $body];
                if (in_array($arg, ['tex', 'latex', 'typst', 'typ'], true)) {
                    $child[in_array($arg, ['tex', 'latex'], true) ? 'tex' : 'typst'] = "\n" . $body . "\n";
                }
            } else {
                $lang = in_array($name, ['raw:latex', 'raw:tex'], true) ? 'tex' : 'typst';
                $child = ['type' => 'raw', 'lang' => $lang, $lang => $body !== '' ? "\n" . $body . "\n" : ''];
            }
        } elseif ($name === 'index') {
            $lists = \Splitice\Mystmd\IndexEntries::emptyLists();
            \Splitice\Mystmd\IndexEntries::parseLine($arg, $lists, $parser);
            foreach (array_keys($lists) as $key) {
                if (!empty($options[$key])) {
                    $parser->warn('Index entry definitions should not use :option: syntax', '', 'error');
                    $lists[$key][] = $options[$key];
                }
            }
            foreach (explode("\n", $body) as $line) {
                \Splitice\Mystmd\IndexEntries::parseLine($line, $lists, $parser);
            }
            $child = ['type' => 'mystTarget', 'indexEntries' => \Splitice\Mystmd\IndexEntries::create($lists, $parser)] + array_intersect_key($options, ['label' => true]);
        } elseif (in_array($name, ['toc', 'tableofcontents', 'table-of-contents', 'toctree', 'contents', 'show-index', 'genindex'], true)) {
            $child = ['type' => in_array($name, ['show-index', 'genindex'], true) ? 'genindex' : 'toc', 'children' => []];
            if ($child['type'] === 'toc') {
                $kind = $options['context'] ?? ($name === 'contents' ? 'section' : 'project');
                if (!in_array($kind, ['project', 'children', 'page', 'section'], true)) {
                    $parser->warn('Unknown context for ' . $name . ' directive: ' . $kind, '', 'error');
                    $kind = 'project';
                }
                $child['kind'] = $kind;
                if (isset($options['depth'])) {
                    if ($options['depth'] < 1) {
                        $parser->warn("Table of Contents 'depth' must be a number greater than 0", '', 'error');
                    } else {
                        $child['depth'] = $options['depth'];
                    }
                }
                $child += $common;
            }
            if ($arg !== '') {
                $parsed = $parser->parseInline($arg, $node['position']['start']['line'] ?? 1);
                $child['children'] = [['type' => 'heading', 'depth' => 2, 'enumerated' => false, 'children' => $parsed]];
            }
        } elseif ($name === 'anywidget') {
            $model = new \stdClass();
            if (isset($node['value'])) {
                try {
                    $model = DirectiveUtils::jsonCompatible(\json5_decode($body));
                    if (!is_object($model) && !is_array($model)) {
                        $parser->warn('Invalid JSON5/JSON supplied.', '');
                    }
                } catch (\JsonException) {
                    $parser->warn('Invalid JSON5/JSON supplied.', '');
                    $model = ['error' => 'Invalid JSON5/JSON supplied.'];
                }
            }
            $child = ['type' => 'anywidget', 'esm' => $arg, 'model' => $model, 'id' => bin2hex(random_bytes(11))] + array_intersect_key($options, ['css' => true, 'class' => true]);
        } else {
            $parser->warn('unknown directive: ' . $name, 'directive-known', 'error');
            if (isset($node['options'])) {
                $lines = [];
                foreach ($node['options'] as $key => $value) {
                    $lines[] = ':' . $key . ': ' . SchemaValues::string($value);
                }
                $node['value'] = implode("\n", $lines) . ($body !== '' ? "\n\n" . $body : '');
                unset($node['options']);
            }
            return $node;
        }
        $node['children'] = [$child];
        return $node;
    }

    /**
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function common(array $options, Parser $parser): array
    {
        $attrs = [];
        $name = $options['label'] ?? $options['name'] ?? null;
        if (is_string($name) && $name !== '') {
            $attrs += Parser::label($name);
        }
        if (isset($options['label'], $options['name'])) {
            $parser->warn('Both label and name provided; label takes precedence', 'directive-options');
        }
        if (isset($options['class'])) {
            $attrs['class'] = $options['class'];
        }
        if (isset($options['enumerated'])) {
            $attrs['enumerated'] = self::boolean($options['enumerated']);
        }
        return $attrs;
    }

    /** Parse MyST boolean options without PHP's nonempty-string coercion. */
    private static function boolean(mixed $value): bool
    {
        return !in_array($value, [false, 'false', '', null, 0, '0'], true);
    }

    /**
     *
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private static function missing(array $node, string $part, Parser $parser): array
    {
        $message = 'required ' . $part . ' not provided for directive: ' . $node['name'];
        $parser->warn($message, 'directive-' . $part . '-correct', 'error');
        $node['type'] = 'mystDirectiveError';
        $node['message'] = $message;
        return $node;
    }
}
