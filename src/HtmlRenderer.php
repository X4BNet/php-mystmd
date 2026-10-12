<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/**
 * Render the MyST AST without executing document content.
 * @phpstan-import-type HtmlOptions from Types
 */
final class HtmlRenderer
{
    /** @var array<string, array<string, mixed>> */
    private array $definitions = [];
    /** @var array<string, array<string, mixed>> */
    private array $footnotes = [];
    /** @var array<string, int> */
    private array $footnoteOrder = [];
    /** @var array<string, int> */
    private array $footnoteUses = [];
    /** @var list<string|int> */
    private array $footnoteQueue = [];
    private readonly bool $semantic;
    /** @var list<Diagnostic> */
    private array $diagnostics = [];

    /**
     * @param array<string, mixed> $options
     * @phpstan-param HtmlOptions $options
     */
    public function __construct(public readonly array $options = [], public readonly Registry $registry = new Registry())
    {
        $profile = array_key_exists('profile', $options) ? $options['profile'] : 'upstream';
        if (!in_array($profile, ['upstream', 'semantic'], true)) {
            throw new \InvalidArgumentException('Unknown HTML profile: ' . Internal\SchemaValues::string($profile));
        }
        $this->semantic = $profile === 'semantic';
    }

    /** Render a copy, so rendering does not alter a caller's parse tree. */
    public function render(Document $document, ?Resolver\ResolverPipeline $resolvers = null): RenderResult
    {
        $this->definitions = $this->footnotes = $this->footnoteOrder = $this->footnoteUses = [];
        $this->footnoteQueue = [];
        $document = $resolvers?->expandIncludes($document) ?? $document->copy();
        Internal\RenderPreparation::prepare($document, $this->semantic);
        $resolvers?->resolveExternal($document);
        $this->diagnostics = $document->diagnostics;
        Transforms::walk($document->ast, function (array &$node): void {
            if ($node['type'] === 'definition') {
                $this->definitions[$node['identifier']] = $node;
            } elseif ($node['type'] === 'footnoteDefinition') {
                $this->footnotes[$node['identifier']] = $node;
            }
        });
        $html = $this->node($document->ast);
        $html .= $this->renderFootnotes();
        $document->diagnostics = $this->diagnostics;
        return new RenderResult(trim($html), $document);
    }

    /** Report an extension rendering failure without changing the input document.
     * @param array<string, mixed>|null $position
     */
    public function warn(string $message, string $ruleId, string $severity = 'warning', ?array $position = null): void
    {
        $this->diagnostics[] = new Diagnostic($message, $ruleId, $severity, $position);
    }

    /** @param list<array<string, mixed>> $nodes */
    public function children(array $nodes, string $separator = ''): string
    {
        return implode($separator, array_map($this->node(...), $nodes));
    }

    /** Render one node; extension callbacks can recursively use this method.
     * @param array<string, mixed> $node
     */
    public function node(array $node): string
    {
        $type = $node['type'];
        if (isset($this->registry->renderers[$type])) {
            return ($this->registry->renderers[$type])($node, $this);
        }
        $children = $node['children'] ?? [];
        $value = (string)($node['value'] ?? '');
        $attrs = [];
        if (isset($node['identifier'])) {
            $attrs['id'] = $this->semantic ? ($node['html_id'] ?? $node['identifier']) : $node['identifier'];
        }
        if (isset($node['class'])) {
            $attrs['class'] = $node['class'];
        }
        if ($this->semantic) {
            $semantic = Internal\SemanticHtml::node($node, $attrs, $this);
            if ($semantic !== null) {
                return $semantic;
            }
        }
        switch ($type) {
            case 'root': return $this->children($children, "\n");
            case 'text': return self::escape($value);
            case 'inlineCode': return $this->tag('code', [], self::escape($value));
            case 'paragraph': return $this->tag('p', $this->semantic ? $attrs : [], $this->children($children));
            case 'heading': return $this->tag('h' . max(1, min(6, (int)$node['depth'] + (int)($this->options['headingOffset'] ?? 0))), $attrs, $this->children($children));
            case 'emphasis': return $this->tag('em', [], $this->children($children));
            case 'strong': return $this->tag('strong', [], $this->children($children));
            case 'delete': return $this->tag('del', [], $this->children($children));
            case 'subscript': return $this->tag('sub', [], $this->children($children));
            case 'superscript': return $this->tag('sup', [], $this->children($children));
            case 'keyboard': return $this->tag('kbd', [], $this->children($children));
            case 'abbreviation': return $this->tag('abbr', isset($node['title']) ? ['title' => $node['title']] : [], $this->children($children));
            case 'break': return $this->tag('br', []) . "\n";
            case 'thematicBreak': return $this->tag('hr', $this->semantic ? $attrs : []);
            case 'blockquote':
                $body = $this->children($children, "\n");
                return $this->tag('blockquote', $this->semantic ? $attrs : [], "\n" . $body . ($body !== '' ? "\n" : ''));
            case 'code':
                unset($attrs['class']);
                $classes = [];
                if (!empty($node['lang'])) {
                    $classes[] = ($this->options['legacyCodeClasses'] ?? false) ? $node['lang'] : 'language-' . $node['lang'];
                }
                if (!empty($node['class'])) {
                    $classes[] = $node['class'];
                }
                if ($classes !== []) {
                    $attrs['class'] = implode(' ', $classes);
                }
                $pre = ($this->options['focusableCode'] ?? false) ? ['tabindex' => '0', 'aria-label' => 'Code block'] : [];
                return $this->tag('pre', $pre, $this->tag('code', $attrs, self::escape($value !== '' ? $value . "\n" : '')));
            case 'html': return ($this->options['allowDangerousHtml'] ?? false) ? $value : '';
            case 'comment': case 'mystComment': return '<!--' . preg_replace_callback('/^>|^->|<!--|-->|--!>|<!-$/', static fn (array $m): string => str_replace(['<', '>'], ['&#x3C;', '&#x3E;'], $m[0]), $value) . '-->';
            case 'yaml': case 'definition': case 'footnoteDefinition': return '';
            case 'linkReference': case 'imageReference':
                $definition = $this->definitions[$node['identifier']] ?? null;
                if ($definition === null) {
                    return $type === 'imageReference' ? self::escape($node['alt'] ?? '') : $this->children($children);
                }
                return $this->node(array_replace($node, ['type' => $type === 'imageReference' ? 'image' : 'link', 'url' => $definition['url']] + array_intersect_key($definition, ['title' => true])));
            case 'link':
                $link = ['href' => $this->url((string)($node['url'] ?? ''))];
                if (isset($node['title'])) {
                    $link['title'] = $node['title'];
                }
                return $this->tag('a', $link, $this->children($children));
            case 'image':
                $image = ['src' => $this->url((string)$node['url'])];
                if ($this->semantic && isset($attrs['id'])) {
                    $image['id'] = $attrs['id'];
                }
                foreach (['alt', 'title'] as $key) {
                    if (isset($node[$key])) {
                        $image[$key] = $node[$key];
                    }
                }
                $class = trim((isset($node['align']) ? 'align-' . $node['align'] : '') . ' ' . ($node['class'] ?? ''));
                if ($class !== '') {
                    $image['class'] = $class;
                }
                foreach (['height', 'width'] as $key) {
                    if (isset($node[$key])) {
                        $image[$key] = $node[$key];
                    }
                }
                return $this->tag('img', $image);
            case 'list':
                $listAttrs = ($node['ordered'] ?? false) && ($node['start'] ?? 1) !== 1 ? ['start' => $node['start']] : [];
                if ($this->semantic) {
                    $listAttrs += $attrs;
                }
                foreach ($children as $child) {
                    if (isset($child['checked'])) {
                        $listAttrs['class'] = 'contains-task-list';
                    }
                }
                return $this->tag(($node['ordered'] ?? false) ? 'ol' : 'ul', $listAttrs, "\n" . $this->children($children, "\n") . "\n");
            case 'listItem':
                $itemAttrs = $this->semantic ? $attrs : [];
                $prefix = '';
                if (isset($node['checked'])) {
                    $itemAttrs['class'] = 'task-list-item';
                    $prefix = $this->tag('input', ['type' => 'checkbox', 'checked' => (bool)$node['checked'], 'disabled' => true]) . ' ';
                }
                $body = $prefix;
                $previousBlock = false;
                foreach ($children as $index => $child) {
                    $block = in_array($child['type'], ['paragraph', 'list', 'blockquote', 'code', 'html', 'thematicBreak', 'heading', 'table', 'componentBlock'], true);
                    if ($block || $previousBlock) {
                        $body .= "\n";
                    }
                    $body .= $this->node($child);
                    $previousBlock = $block;
                }
                if ($previousBlock) {
                    $body .= "\n";
                }
                return $this->tag('li', $itemAttrs, $body);
            case 'definitionList': return $this->tag('dl', $this->semantic ? $attrs : [], $this->children($children, "\n"));
            case 'definitionTerm': return $this->tag('dt', $this->semantic ? $attrs : [], $this->children($children));
            case 'definitionDescription': return $this->tag('dd', $this->semantic ? $attrs : [], $this->children($children));
            case 'table': return $this->table($node);
            case 'tableRow': return $this->tag('tr', [], $this->children($children));
            case 'tableCell':
                $cellAttrs = isset($node['align']) ? ['align' => $node['align']] : [];
                if (isset($node['align']) && ($this->options['tableCellAlignment'] ?? false) === 'style') {
                    $cellAttrs = ['style' => 'text-align: ' . $node['align'] . ';'];
                }
                return $this->tag(!empty($node['header']) ? 'th' : 'td', $cellAttrs, $this->children($children));
            case 'math':
                $math = $this->tag('div', ['id' => $attrs['id'] ?? null, 'class' => 'math-display'], self::escape($value));
                return str_contains($value, "\n") ? $this->tag('pre', [], $math) : $math;
            case 'inlineMath': return $this->tag('span', ['class' => 'math-inline'], self::escape(str_replace(["\r\n", "\r", "\n"], ' ', $value)));
            case 'admonition':
                $kind = $node['kind'] ?? '';
                $class = trim(($node['class'] ?? '') . ' admonition ' . ($kind !== 'admonition' ? $kind : ''));
                if ($kind !== '' && $kind !== 'admonition' && (!$this->semantic || ($children[0]['type'] ?? '') !== 'admonitionTitle')) {
                    array_unshift($children, ['type' => 'admonitionTitle', 'children' => [['type' => 'text', 'value' => $kind === 'seealso' ? 'See Also' : ucfirst($kind)]]]);
                }
                return $this->tag('aside', ['class' => $class] + ($this->semantic ? $attrs : []), $this->children($children));
            case 'admonitionTitle': return $this->tag('p', ['class' => 'admonition-title'], $this->children($children));
            case 'container':
                $attrs['class'] = trim((($node['enumerated'] ?? true) !== false ? 'numbered ' : '') . ($node['class'] ?? ''));
                if ($attrs['class'] === '') {
                    unset($attrs['class']);
                }
                if (isset($node['identifier'], $node['enumerator']) && ($node['enumerated'] ?? true)) {
                    foreach ($children as &$child) {
                        if ($child['type'] === 'caption' && isset($child['children'][0]['children'])) {
                            array_unshift($child['children'][0]['children'], ['type' => 'captionNumber', 'kind' => $node['kind'], 'value' => $node['enumerator']]);
                        }
                    }
                    unset($child);
                }
                return $this->tag('figure', $attrs, $this->children($children));
            case 'caption': return $this->tag('figcaption', [], $this->children($children));
            case 'captionNumber': return $this->tag('span', ['class' => 'caption-number'], self::escape(ucfirst($node['kind']) . ' ' . $value));
            case 'legend': return $this->tag('div', ['class' => 'legend'], $this->children($children));
            case 'block': return $this->tag('div', ['id' => $node['html_id'] ?? $node['identifier'] ?? null, 'class' => 'block', 'data-block' => $node['meta'] ?? null], $this->children($children));
            case 'crossReference':
                if ($node['resolved'] ?? false) {
                    return $this->tag('a', ['href' => isset($node['url']) ? $this->url($node['url']) : '#' . $node['identifier'], 'title' => $node['title'] ?? null], $this->children($children));
                }
                return $this->tag('span', ['class' => 'reference role unhandled'], $this->tag('code', ['class' => 'kind'], self::escape('{' . ($node['kind'] ?? 'ref') . '}')) . $this->tag('code', [], self::escape($node['identifier'] ?? '')));
            case 'mystRole':
                if (isset($node['children'])) {
                    return $this->children($children);
                }
                return $this->tag('span', ['class' => 'role unhandled'], $this->tag('code', ['class' => 'kind'], self::escape('{' . $node['name'] . '}')) . ($value !== '' ? $this->tag('code', [], self::escape($value)) : ''));
            case 'mystDirective':
                if (isset($node['children'])) {
                    return $this->children($children);
                }
                $header = $this->tag('code', ['class' => 'kind'], self::escape('{' . $node['name'] . '}'));
                if (isset($node['args'])) {
                    $header .= $this->tag('code', ['class' => 'args'], self::escape($node['args']));
                }
                $content = '';
                if (isset($node['options'])) {
                    $lines = [];
                    foreach ($node['options'] as $key => $option) {
                        $lines[] = ':' . $key . ': ' . (is_bool($option) ? ($option ? 'true' : 'false') : $option);
                    }
                    $content = $this->tag('pre', [], $this->tag('code', ['class' => 'options'], self::escape(implode("\n", $lines))));
                }
                return $this->tag('div', ['class' => 'directive unhandled'], $this->tag('p', [], $header) . $content . $this->tag('pre', [], $this->tag('code', [], self::escape($value))));
            case 'footnoteReference':
                $id = $node['identifier'];
                if (!isset($this->footnoteOrder[$id])) {
                    $this->footnoteOrder[$id] = count($this->footnoteOrder) + 1;
                    $this->footnoteQueue[] = $id;
                }
                $this->footnoteUses[$id] = ($this->footnoteUses[$id] ?? 0) + 1;
                $suffix = $this->footnoteUses[$id] > 1 ? '-' . $this->footnoteUses[$id] : '';
                $prefix = $this->options['clobberPrefix'] ?? 'user-content-';
                return $this->tag('sup', [], $this->tag('a', ['href' => '#' . $prefix . 'fn-' . $id, 'id' => $prefix . 'fnref-' . $id . $suffix, 'data-footnote-ref' => true, 'aria-describedby' => 'footnote-label'], (string)$this->footnoteOrder[$id]));
            case 'iframe': case 'bibliography': case 'output': return $this->tag('div', ['class' => $type], '');
            case 'include': return $this->tag('div', ['file' => $node['file']], '');
            case 'embed': return $this->tag('div', [], '');
            case 'mdast': return $this->tag('div', ['id' => $node['id'] ?? null], '');
            case 'margin': return $this->tag('aside', ['class' => 'margin'], '');
            case 'mermaid': case 'myst': return $this->tag('div', ['class' => 'margin'], '');
            case 'details': case 'summary': return $this->tag($type, [], '');
            default: return $this->semantic ? Internal\SemanticHtml::fallback($node, $attrs, $this) : (isset($node['value']) ? self::escape($value) : $this->tag('div', [], $this->children($children)));
        }
    }

    /** @param array<string, mixed> $node */
    private function table(array $node): string
    {
        if ($this->semantic) {
            return Internal\SemanticHtml::table($node, $this);
        }
        $rows = $node['children'] ?? [];
        if ($rows === []) {
            return '<table></table>';
        }
        foreach ($rows as $index => &$row) {
            foreach ($row['children'] as &$cell) {
                $cell['header'] = $index === 0;
                if (!($this->options['tableCellAlignment'] ?? false)) {
                    unset($cell['align']);
                }
            }
            unset($cell);
        }
        unset($row);
        $first = array_shift($rows);
        $html = $this->tag('thead', [], $this->node($first));
        if ($rows !== []) {
            $html .= $this->tag('tbody', [], $this->children($rows));
        }
        return $this->tag('table', isset($node['align']) ? ['align' => $node['align']] : [], $html);
    }

    /** Append referenced definitions in order of first use. */
    private function renderFootnotes(): string
    {
        if ($this->footnoteOrder === []) {
            return '';
        }
        $items = '';
        $prefix = $this->options['clobberPrefix'] ?? 'user-content-';
        $contents = [];
        // Rendering a definition may discover more notes. Each is rendered once,
        // so cycles terminate and callbacks are not executed by a second pass.
        for ($next = 0; $next < count($this->footnoteQueue); ++$next) {
            $id = $this->footnoteQueue[$next];
            $contents[$id] = $this->children($this->footnotes[$id]['children'] ?? []);
        }
        foreach ($this->footnoteOrder as $id => $index) {
            $backs = [];
            for ($use = 1; $use <= $this->footnoteUses[$id]; ++$use) {
                $suffix = $use > 1 ? '-' . $use : '';
                $backs[] = $this->tag('a', ['href' => '#' . $prefix . 'fnref-' . $id . $suffix, 'data-footnote-backref' => true, 'class' => 'data-footnote-backref', 'aria-label' => 'Back to content'], '↩' . ($use > 1 ? $this->tag('sup', [], (string)$use) : ''));
            }
            $back = implode(' ', $backs);
            $content = $contents[$id];
            $content = str_ends_with($content, '</p>') ? substr($content, 0, -4) . ' ' . $back . '</p>' : $content . $back;
            $items .= $this->tag('li', ['id' => $prefix . 'fn-' . $id], $content);
        }
        return $this->tag('section', ['data-footnotes' => true, 'class' => 'footnotes'], $this->tag('h2', ['class' => 'sr-only', 'id' => 'footnote-label'], 'Footnotes') . $this->tag('ol', [], $items));
    }

    /** Escape literal content and attribute values. */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /** Render an escaped HTML element.
     * @param array<string, mixed> $attributes
     */
    public function tag(string $name, array $attributes, ?string $content = null): string
    {
        $html = '<' . $name;
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $html .= ' ' . $key;
            if ($value !== true) {
                $html .= '="' . self::escape((string)$value) . '"';
            }
        }
        return $content === null ? $html . (($this->options['closeSelfClosing'] ?? false) ? ' />' : '>') : $html . '>' . $content . '</' . $name . '>';
    }

    /** Reject active URL schemes unless explicitly requested by a trusted caller. */
    private function url(string $url): string
    {
        $normalized = preg_replace('/[\x00-\x20]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? $url;
        return !($this->options['allowDangerousUrls'] ?? false) && preg_match('/^(?:javascript|vbscript|file|data):/i', $normalized) ? '' : \League\CommonMark\Util\UrlEncoder::unescapeAndEncode($url);
    }
}
