<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\Node\Block as Block;
use League\CommonMark\Extension\CommonMark\Node\Inline as Inline;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\StringContainerInterface;
use League\CommonMark\Parser\MarkdownParser;
use Splitice\Mystmd\Internal\AstBlock;
use Splitice\Mystmd\Internal\AstInline;
use Splitice\Mystmd\Internal\MystBlockStartParser;
use Splitice\Mystmd\Internal\MystInlineParser;
use Symfony\Component\Yaml\Yaml;

/** MyST parser backed by CommonMark's block and delimiter machinery. */
final class Parser
{
    /** @var array<string, mixed> */
    public readonly array $options;
    /** @var list<Diagnostic> */
    private array $diagnostics = [];
    private int $depth = 0;
    private int $sourceOffset = 0;
    private int $bodyOffset = 0;
    /** @var list<string> */
    private array $sourceLines = [];

    /** @param array<string, mixed> $options */
    public function __construct(array $options = [], public readonly Registry $registry = new Registry())
    {
        $this->options = array_replace_recursive($registry->defaults, $options);
    }

    /** Return whether a named MyST extension is active. */
    public function enabled(string $name): bool
    {
        return (bool)($this->options['extensions'][$name] ?? ($name !== 'strikethrough'));
    }

    /** Select AMS or dollar math according to MyST's nested extension options. */
    public function mathEnabled(string $syntax): bool
    {
        $math = $this->options['extensions']['math'] ?? true;
        return is_array($math) ? (bool)($math[$syntax] ?? false) : (bool)$math;
    }

    /** Parse one independent document, resetting all per-document state. */
    public function parse(string $source): Document
    {
        $this->diagnostics = [];
        $this->depth = 0;
        $ast = $this->parseTree($source);
        $document = new Document($ast, $this->diagnostics, $source);
        foreach ($this->registry->transforms as $transform) {
            $transform($document);
        }
        return $document;
    }

    /** @return array<string, mixed> */
    public function parseTree(string $source, int $lineOffset = 0, bool $nestBlocks = true): array
    {
        if (++$this->depth > (int)($this->options['maxNesting'] ?? $this->options['markdownit']['maxNesting'] ?? 20)) {
            --$this->depth;
            $this->warn('Maximum parsing depth exceeded', 'parse-nesting');
            return ['type' => 'root', 'children' => [['type' => 'text', 'value' => $source]]];
        }
        $previousLines = $this->sourceLines;
        $previousOffset = $this->sourceOffset;
        $this->sourceOffset = $lineOffset;
        $source = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", "�"], $source);
        $this->sourceLines = explode("\n", $source);
        try {
            $environment = $this->environment();
            $parseSource = $source;
            if ($this->enabled('frontmatter') && preg_match('/\A---[ \t]*\n(.*?)\n---[ \t]*(?=\n|\z)/s', $source, $frontmatter)) {
                $parseSource = '```yaml' . "\n" . $frontmatter[1] . "\n```" . substr($source, strlen($frontmatter[0]));
            }
            $tree = (new MarkdownParser($environment))->parse($parseSource);
            $ast = ['type' => 'root', 'children' => $this->children($tree)];
            if ($nestBlocks && ($this->options['mdast']['nestBlocks'] ?? true)) {
                $out = [];
                $block = null;
                foreach ($ast['children'] as $child) {
                    if ($child['type'] === 'blockBreak') {
                        if ($block !== null) {
                            $out[] = $block;
                        }
                        $block = array_replace($child, ['type' => 'block', 'children' => []]);
                    } elseif ($block !== null) {
                        $block['children'][] = $child;
                    } else {
                        $out[] = $child;
                    }
                }
                if ($block !== null) {
                    $out[] = $block;
                }
                $ast['children'] = $out;
            }
            if ($this->options['markdownit']['linkify'] ?? false) {
                Internal\Linkify::apply($ast);
            }
            return $ast;
        } finally {
            $this->sourceLines = $previousLines;
            $this->sourceOffset = $previousOffset;
            --$this->depth;
        }
    }

    /** Parse phrasing content without interpreting its leading characters as blocks.
     * @return list<array<string, mixed>>
     */
    public function parseInline(string $source, int $line = 1): array
    {
        $tree = $this->parseTree('myst-inline-prefix ' . $source, $line - 1);
        $children = $tree['children'][0]['children'] ?? [];
        if (($children[0]['type'] ?? null) === 'text') {
            $children[0]['value'] = substr($children[0]['value'], strlen('myst-inline-prefix '));
            if ($children[0]['value'] === '') {
                array_shift($children);
            }
        }
        foreach ($children as &$child) {
            Transforms::walk($child, static function (array &$node) use ($line): void {
                foreach (['start', 'end'] as $edge) {
                    if (($node['position'][$edge]['line'] ?? null) === $line && ($node['position'][$edge]['column'] ?? 1) > strlen('myst-inline-prefix ')) {
                        $node['position'][$edge]['column'] -= strlen('myst-inline-prefix ');
                    }
                }
            });
        }
        unset($child);
        return $children;
    }

    /** Parse the current directive body, preserving its absolute line numbers.
     * @return list<array<string, mixed>>
     */
    public function parseBody(string $source, int $relativeOffset = 0): array
    {
        return $this->parseTree($source, $this->bodyOffset + $relativeOffset, false)['children'];
    }

    /** Record a recoverable parsing diagnostic.
     * @param array<string, mixed>|null $position
     */
    public function warn(string $message, string $ruleId, string $severity = 'warning', ?array $position = null): void
    {
        $this->diagnostics[] = new Diagnostic($message, $ruleId, $severity, $position);
    }

    /** @return list<Diagnostic> */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    /** Build an isolated CommonMark environment for each nested parse. */
    private function environment(): Environment
    {
        $environment = new Environment(['max_nesting_level' => (int)($this->options['maxNesting'] ?? $this->options['markdownit']['maxNesting'] ?? 20)]);
        $environment->addExtension(new Internal\CoreExtension($this->options['markdownit']['html'] ?? true, $this->options['mdast']['markdownExtra'] ?? false));
        $extensions = [
            'tables' => \League\CommonMark\Extension\Table\TableExtension::class,
            'footnotes' => \League\CommonMark\Extension\Footnote\FootnoteExtension::class,
            'deflist' => \League\CommonMark\Extension\DescriptionList\DescriptionListExtension::class,
            'tasklist' => \League\CommonMark\Extension\TaskList\TaskListExtension::class,
            'strikethrough' => \League\CommonMark\Extension\Strikethrough\StrikethroughExtension::class,
        ];
        foreach ($extensions as $name => $class) {
            if ($this->enabled($name)) {
                $environment->addExtension(new $class());
            }
        }
        if ($this->enabled('smartquotes')) {
            $environment->addExtension(new Internal\SmartQuotesExtension($this->options['markdownit']['quotes'] ?? '“”‘’'));
        }
        if ($this->options['markdownit']['linkify'] ?? false) {
            $environment->addExtension(new \League\CommonMark\Extension\Autolink\AutolinkExtension());
        }
        foreach ($this->registry->blockParsers as $parse) {
            $environment->addBlockStartParser(new Internal\CustomBlockParser($parse, $this), 400);
        }
        foreach ($this->registry->inlineParsers as $definition) {
            $environment->addInlineParser(new Internal\CustomInlineParser($definition['trigger'], $definition['parse'], $this), 400);
        }
        $environment->addBlockStartParser(new MystBlockStartParser($this), 300);
        $environment->addInlineParser(new MystInlineParser($this), 300);
        return $environment;
    }

    /** @return list<array<string, mixed>> */
    private function children(Node $parent): array
    {
        $children = [];
        foreach ($parent->children() as $child) {
            $converted = $this->node($child);
            if ($converted === null) {
                continue;
            }
            $nodes = $converted['type'] === '_lift' ? $converted['children'] : [$converted];
            foreach ($nodes as $node) {
                $last = array_key_last($children);
                if ($node['type'] === 'text' && $last !== null && isset($children[$last]) && $children[$last]['type'] === 'text') {
                    $children[$last]['value'] .= $node['value'];
                } else {
                    $children[] = $node;
                }
            }
        }
        return $children;
    }

    /** @return array<string, mixed>|null */
    private function node(Node $node): ?array
    {
        $parentNode = $node->parent();
        $children = $this->children($node);
        $class = (new \ReflectionClass($node))->getShortName();
        $types = ['Document' => 'root', 'Paragraph' => 'paragraph', 'BlockQuote' => 'blockquote', 'ThematicBreak' => 'thematicBreak', 'Emphasis' => 'emphasis', 'Strong' => 'strong', 'Text' => 'text', 'Code' => 'inlineCode', 'HtmlInline' => 'html', 'HtmlBlock' => 'html', 'Strikethrough' => 'delete', 'Table' => 'table', 'TableSection' => '_lift', 'TableRow' => 'tableRow', 'TableCell' => 'tableCell', 'DescriptionList' => 'definitionList', 'DescriptionTerm' => 'definitionTerm', 'Description' => 'definitionDescription', 'FootnoteContainer' => '_lift'];
        if ($node instanceof AstInline) {
            $ast = $node->ast;
            $positioned = $this->position($ast, $node);
            $line = $positioned['position']['start']['line'] ?? 1;
            Transforms::walk($ast, static function (array &$child) use ($line): void {
                if (isset($child['position'])) {
                    $child['position']['start']['line'] += $line - 1;
                    $child['position']['end']['line'] += $line - 1;
                }
            });
            if ($ast['type'] === 'mystRole') {
                $block = $node->parent();
                while ($block !== null && !$block instanceof AbstractBlock) {
                    $block = $block->parent();
                }
                if ($block instanceof AbstractBlock) {
                    $ast['position']['end']['line'] = ($block->getEndLine() ?? $line) + $this->sourceOffset;
                }
            }
            return isset($ast['position']) ? $ast : $positioned;
        }
        if ($node instanceof AstBlock) {
            $ast = $this->position($node->ast, $node);
            if ($node->relativeChildren && $node->bodyParser === null) {
                $offset = $ast['position']['start']['line'] - 1;
                foreach ($ast['children'] ?? [] as $index => $child) {
                    Transforms::walk($child, static function (array &$descendant) use ($offset): void {
                        if (isset($descendant['position'])) {
                            $descendant['position']['start']['line'] += $offset;
                            $descendant['position']['end']['line'] += $offset;
                        }
                    });
                    $ast['children'][$index] = $child;
                }
            }
            if ($node->bodyParser !== null) {
                $previousBodyOffset = $this->bodyOffset;
                $this->bodyOffset = $ast['position']['start']['line'];
                try {
                    $ast = ($node->bodyParser)($node->body, $this) + array_intersect_key($ast, ['position' => true]);
                } finally {
                    $this->bodyOffset = $previousBodyOffset;
                }
            } elseif ($ast['type'] === 'mystDirectiveError' && isset($ast['_errorHeader'])) {
                $this->warn('Error parsing "' . $ast['_errorHeader'] . '" directive: ' . $ast['message'], '', 'error', $ast['position']);
                unset($ast['_errorHeader']);
                $ast['children'] = [['type' => 'text', 'value' => $node->body, 'position' => $ast['position']]];
            } elseif ($ast['type'] === 'mystDirective') {
                $start = $node->getStartLine() ?? 1;
                $end = $node->getEndLine() ?? $start;
                $before = $start > 1 && trim($this->sourceLines[$start - 2] ?? '') !== '';
                $after = trim($this->sourceLines[$end] ?? '') !== '';
                if ($before || $after) {
                    $ast['tight'] = $before && $after ? true : ($before ? 'before' : 'after');
                }
                $ast = $this->directive($ast, $node->body);
            } elseif (!isset($ast['value']) && in_array($ast['type'], ['code', 'math'], true)) {
                $ast['value'] = $ast['type'] === 'math' ? trim($node->body) : $node->body;
            }
            if (isset($ast['_ams'])) {
                unset($ast['_ams']);
                // The pinned amsmath tokenizer excludes the closing line from its map.
                --$ast['position']['end']['line'];
                $start = $node->getStartLine() ?? 1;
                $end = $node->getEndLine() ?? $start;
                $before = $start > 1 && trim($this->sourceLines[$start - 2] ?? '') !== '';
                $after = trim($this->sourceLines[$end] ?? '') !== '';
                if ($before || $after) {
                    $ast['tight'] = $before && $after ? true : ($before ? 'before' : 'after');
                }
            }
            return $ast;
        }
        if ($node instanceof Block\Heading) {
            return $this->position(['type' => 'heading', 'depth' => $node->getLevel(), 'children' => $children], $node);
        }
        if ($node instanceof \League\CommonMark\Extension\Table\Table) {
            $ast = $this->position(['type' => 'table', 'children' => $children], $node);
            $firstLine = $ast['position']['start']['line'];
            foreach ($ast['children'] as $index => &$row) {
                $line = $firstLine + ($index === 0 ? 0 : $index + 1);
                Transforms::walk($row, static function (array &$child) use ($line): void {
                    $child['position']['start'] = ['line' => $line, 'column' => $child['position']['start']['column'] ?? 1];
                    $child['position']['end'] = ['line' => $line, 'column' => $child['position']['end']['column'] ?? 1];
                });
            }
            unset($row);
            return $ast;
        }
        if ($node instanceof Block\FencedCode || $node instanceof Block\IndentedCode) {
            $language = $node instanceof Block\FencedCode ? ($node->getInfoWords()[0] ?? '') : '';
            return $this->position(['type' => 'code', 'lang' => $language, 'value' => preg_replace('/\n\z/', '', $node->getLiteral())], $node);
        }
        if ($node instanceof Block\ListBlock) {
            $data = $node->getListData();
            $ast = ['type' => 'list', 'ordered' => $data->type === Block\ListBlock::TYPE_ORDERED, 'spread' => false];
            if ($ast['ordered']) {
                $ast['start'] = $data->start;
            }
            return $this->position($ast + ['children' => $children], $node);
        }
        if ($node instanceof Block\ListItem) {
            $ast = ['type' => 'listItem', 'spread' => true];
            $listParent = $parentNode;
            $tight = $listParent instanceof Block\ListBlock && $listParent->isTight();
            if ($this->options['mdast']['markdownExtra'] ?? false) {
                $start = $node->getStartLine() ?? 1;
                $end = $node->getEndLine() ?? $start;
                $itemSource = implode("\n", array_slice($this->sourceLines, $start - 1, $end - $start + 1));
                $hasBlockGap = preg_match('/\n[ \t]*\n/', $itemSource) === 1;
                $after = trim($this->sourceLines[$end] ?? '') === '' && $node->next() !== null;
                $before = $node->previous() !== null && trim($this->sourceLines[$start - 2] ?? '') === '';
                $hasCode = array_filter($children, static fn (array $child): bool => $child['type'] === 'code') !== [];
                $tight = !$hasBlockGap && !$after && !$before && !$hasCode;
            }
            foreach ($children as &$child) {
                if ($child['type'] === 'paragraph' && isset($child['children'][0]['_checked'])) {
                    $ast['checked'] = $child['children'][0]['_checked'];
                    array_shift($child['children']);
                    if (($child['children'][0]['type'] ?? null) === 'text') {
                        $child['children'][0]['value'] = preg_replace('/^ /', '', $child['children'][0]['value']);
                    }
                }
                if ($child['type'] === 'paragraph' && $tight) {
                    unset($child['position']);
                }
            }
            unset($child);
            if (!($this->options['mdast']['listItemParagraphs'] ?? true) && $tight) {
                $children = array_merge(...array_map(static fn (array $child): array => $child['type'] === 'paragraph' ? $child['children'] : [$child], $children));
            }
            return $this->position($ast + ['children' => $children], $node);
        }
        if ($node instanceof Inline\Link || $node instanceof Inline\Image) {
            $ast = ['type' => $node instanceof Inline\Image ? 'image' : 'link', 'url' => $node->getUrl()];
            if ($node->getTitle() !== null && $node->getTitle() !== '') {
                $ast['title'] = $node->getTitle();
            }
            if ($node instanceof Inline\Image) {
                $alt = self::text($children);
                if ($alt !== '') {
                    $ast['alt'] = $alt;
                }
            } else {
                $ast['children'] = $children;
            }
            return $this->position($ast, $node);
        }
        if ($node instanceof \League\CommonMark\Node\Inline\Newline) {
            return $this->position($node->getType() === \League\CommonMark\Node\Inline\Newline::HARDBREAK ? ['type' => 'break'] : ['type' => 'text', 'value' => "\n"], $node);
        }
        if ($node instanceof \League\CommonMark\Extension\TaskList\TaskListItemMarker) {
            return ['type' => '_task', '_checked' => $node->isChecked()];
        }
        if ($class === 'FootnoteBackref') {
            return null;
        }
        if ($node instanceof \League\CommonMark\Extension\Footnote\Node\Footnote || $node instanceof \League\CommonMark\Extension\Footnote\Node\FootnoteRef) {
            $label = $class === 'FootnoteRef' ? substr($node->getReference()->getDestination(), 4) : $node->getReference()->getLabel();
            $ast = ['type' => $class === 'Footnote' ? 'footnoteDefinition' : 'footnoteReference'] + self::label($label);
            if ($class === 'Footnote') {
                $ast['children'] = $children;
                $previous = $node->parent()?->previous();
                if ($previous instanceof AbstractBlock) {
                    return $this->position($ast, $previous);
                }
            }
            return $this->position($ast, $node);
        }
        $ast = ['type' => $types[$class] ?? '_lift'];
        if ($node instanceof \League\CommonMark\Extension\Table\TableCell) {
            if ($node->getType() === 'header') {
                $ast['header'] = true;
            }
            if ($node->getAlign() !== null) {
                $ast['align'] = $node->getAlign();
            }
        }
        if ($node instanceof StringContainerInterface) {
            $ast['value'] = $node->getLiteral();
            if ($ast['type'] === 'html') {
                $ast['value'] = preg_replace('/\n\z/', '', $ast['value']);
                // The pinned MyST parser uses CommonMark 0.30's comment grammar.
                if ($node instanceof Inline\HtmlInline && str_starts_with($ast['value'], '<!--') && !preg_match('/^<!--(?!>|->)(?:(?!--).)*?(?<!-)-->$/s', $ast['value'])) {
                    $ast['type'] = 'text';
                }
                if (!($this->options['markdownit']['html'] ?? true)) {
                    $ast['type'] = 'text';
                }
            }
        } elseif ($ast['type'] !== 'thematicBreak') {
            $ast['children'] = $children;
        }
        if ($node instanceof \League\CommonMark\Extension\DescriptionList\Node\Description) {
            $ast = $this->position($ast, $node);
            $ast['position']['start'] = $children[0]['position']['start'] ?? $ast['position']['start'];
            if ($node->isTight()) {
                $ast['children'] = [];
                foreach ($children as $child) {
                    array_push($ast['children'], ...($child['type'] === 'paragraph' ? $child['children'] : [$child]));
                }
            }
            return $ast;
        }
        if ($ast['type'] === 'paragraph' && count($children) === 1 && $children[0]['type'] === 'image' && ($this->options['mdast']['hoistSingleImagesOutofParagraphs'] ?? true)) {
            return $children[0];
        }
        return $this->position($ast, $node);
    }

    /**
     *
     * @param array<string, mixed> $ast
     * @return array<string, mixed>
     */
    private function position(array $ast, Node $node): array
    {
        if ($node instanceof AbstractBlock && $node->getStartLine() !== null) {
            $ast['position'] = ['start' => ['line' => $node->getStartLine() + $this->sourceOffset, 'column' => 1], 'end' => ['line' => ($node->getEndLine() ?? $node->getStartLine()) + $this->sourceOffset, 'column' => 1]];
        } elseif (!$node instanceof AbstractBlock) {
            $parent = $node->parent();
            while ($parent !== null && !$parent instanceof AbstractBlock) {
                $parent = $parent->parent();
            }
            $line = ($parent instanceof AbstractBlock ? ($parent->getStartLine() ?? 1) : 1) + $this->sourceOffset;
            $ast['position'] = ['start' => ['line' => $line, 'column' => 1], 'end' => ['line' => $line, 'column' => 1]];
        }
        return $ast;
    }

    /** @param list<array<string, mixed>> $nodes */
    public static function text(array $nodes): string
    {
        return implode('', array_map(static fn (array $node): string => (string)($node['value'] ?? $node['alt'] ?? self::text($node['children'] ?? [])), $nodes));
    }

    /** Match upstream JavaScript's UTF-16 source column units. */
    public static function columnLength(string $value): int
    {
        return intdiv(strlen(mb_convert_encoding($value, 'UTF-16LE', 'UTF-8')), 2);
    }

    /** @return array{identifier: string, label: string} */
    public static function label(string $label): array
    {
        return ['identifier' => mb_strtolower(preg_replace('/\s+/u', ' ', trim($label)) ?? $label), 'label' => $label];
    }

    /** @return array<string, mixed> */
    public function parseInlineOptions(string $source): array
    {
        $options = InlineAttributes::parse('options ' . $source)['options'];
        foreach ($options as $key => $value) {
            if ($key !== 'class' && $key !== 'label') {
                $options[$key] = is_numeric($value) ? (strpbrk($value, '.eE') !== false ? (float)$value : (int)$value) : ($value === 'true' ? true : ($value === 'false' ? false : $value));
            }
        }
        return $options;
    }

    /**
     *
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    public function role(array $node): array
    {
        if (isset($this->registry->roles[$node['name']])) {
            return ($this->registry->roles[$node['name']])($node, $this);
        }
        return Internal\BuiltInRoles::apply($node, $this);
    }

    /**
     *
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    public function directive(array $node, string $body): array
    {
        $lines = explode("\n", $body);
        $originalCount = count($lines);
        $options = $node['options'] ?? [];
        if ($lines[0] === '---') {
            array_shift($lines);
            $yaml = [];
            while ($lines !== [] && $lines[0] !== '---') {
                $yaml[] = array_shift($lines);
            }
            array_shift($lines);
            try {
                $decoded = Yaml::parse(implode("\n", $yaml));
                if (is_array($decoded)) {
                    $options = $decoded;
                }
            } catch (\Symfony\Component\Yaml\Exception\ParseException $exception) {
                $this->warn($exception->getMessage(), 'directive-options');
            }
        } else {
            while ($lines !== [] && preg_match('/^:([\w-]+):\s*(.*)$/', $lines[0], $match)) {
                if ($match[1] === 'class' && isset($options['class'])) {
                    $options['class'] .= ' ' . $match[2];
                } elseif (array_key_exists($match[1], $options)) {
                    $this->warn('duplicate option "' . $match[1] . '" declared (in ' . $node['name'] . ')', 'directive-options-correct');
                } else {
                    $options[$match[1]] = $match[2] === '' ? true : $match[2];
                }
                array_shift($lines);
            }
        }
        if ($options !== []) {
            $node['options'] = $options;
        }
        while ($lines !== [] && trim($lines[0]) === '') {
            array_shift($lines);
        }
        $body = rtrim(implode("\n", $lines), "\n");
        if ($body !== '') {
            $node['value'] = $body;
        }
        $previous = $this->bodyOffset;
        $this->bodyOffset = ($node['position']['start']['line'] ?? 1) + $originalCount - count($lines);
        try {
            if (isset($this->registry->directives[$node['name']])) {
                return ($this->registry->directives[$node['name']])($node, $this);
            }
            return Internal\BuiltInDirectives::apply($node, $this);
        } finally {
            $this->bodyOffset = $previous;
        }
    }
}
