<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Extension;

use Splitice\Mystmd\Component\Component;
use Splitice\Mystmd\Component\ComponentTag;
use Splitice\Mystmd\Component\OutputKind;
use Splitice\Mystmd\Document;
use Splitice\Mystmd\HtmlRenderer;
use Splitice\Mystmd\LanguageTransforms;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Registry;
use Splitice\Mystmd\Syntax\InlineMatch;

/** Allowlisted components with equivalent legacy tags and MyST directives. */
final class ComponentExtension implements ExtensionInterface
{
    /** @var array<string, Component> */
    private array $components = [];

    /** @param list<Component> $components */
    public function __construct(array $components)
    {
        foreach ($components as $component) {
            if (isset($this->components[$component->name])) {
                throw new \InvalidArgumentException('Duplicate component: ' . $component->name);
            }
            $this->components[$component->name] = $component;
        }
    }

    /** Register parsing and rendering without executing callbacks during parsing. */
    public function register(Registry $registry): void
    {
        $registry->inlineParser('<$', function (string $source, Parser $parser): ?InlineMatch {
            $tag = ComponentTag::parse($source);
            if ($tag === null) {
                return null;
            }
            $node = $this->node($tag['name'], $tag['attributes'], $tag['error'], $parser);
            return new InlineMatch($node, $tag['length']);
        });
        foreach ($this->components as $name => $component) {
            $registry->directive($name, function (array $node, Parser $parser) use ($name): array {
                $attrs = [];
                foreach ($node['options'] ?? [] as $key => $value) {
                    if (!is_scalar($value)) {
                        $node['children'] = [$this->node($name, [], true, $parser)];
                        return $node;
                    }
                    $attrs[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
                }
                $node['children'] = [$this->node($name, $attrs, isset($node['args']) || isset($node['value']), $parser)];
                return $node;
            });
        }
        $render = function (array $node, HtmlRenderer $renderer): string {
            $tag = $node['type'] === 'componentInline' ? 'span' : 'div';
            if (isset($node['error'])) {
                return $renderer->tag($tag, [], HtmlRenderer::escape($node['error']));
            }
            try {
                return ($this->components[$node['name']]->render)($node['attributes']);
            } catch (\Throwable) {
                $renderer->warn('Component rendering failed: ' . $node['name'], 'component-render', 'error', $node['position'] ?? null);
                return $renderer->tag($tag, [], 'TEMPLATE INVALID');
            }
        };
        $registry->renderer('componentBlock', $render);
        $registry->renderer('componentInline', $render);
        $registry->transform(static function (Document $document): void {
            LanguageTransforms::unnest($document->ast, 'paragraph', 'componentBlock');
        });
    }

    /**
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    private function node(string $name, array $attributes, bool $invalid, Parser $parser): array
    {
        $component = $this->components[$name] ?? null;
        $node = ['type' => $component?->output === OutputKind::Inline ? 'componentInline' : 'componentBlock', 'name' => $name, 'attributes' => $attributes];
        if ($invalid || $component === null) {
            $node['error'] = $invalid ? 'TEMPLATE ATTRS INVALID' : 'TEMPLATE NOT FOUND: ' . $name;
            $parser->warn($node['error'], 'component-invalid', 'error');
        }
        return $node;
    }
}
