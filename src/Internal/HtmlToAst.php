<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

/** HTML5 fragment conversion for the two upstream HTML transform profiles. */
final class HtmlToAst
{
    /**
     * @param array<string, mixed> $tree
     * @param array<string, mixed> $options
     */
    public static function apply(array &$tree, array $options = [], bool $extended = false): void
    {
        if (!isset($tree['children'])) {
            return;
        }
        $children = [];
        foreach ($tree['children'] as $child) {
            if ($child['type'] !== 'html') {
                self::apply($child, $options, $extended);
                $children[] = $child;
                continue;
            }
            $document = \Dom\HTMLDocument::createFromString('<!doctype html><html><head><meta charset="UTF-8"></head><body>' . $child['value'] . '</body></html>', LIBXML_NOERROR);
            $fragment = [];
            foreach ($document->body->childNodes as $element) {
                array_push($fragment, ...self::convert($element, $options, $extended));
            }
            if (!$extended && count($fragment) === 1 && $fragment[0]['type'] === 'image') {
                $fragment = [['type' => 'paragraph', 'children' => $fragment]];
            }
            array_push($children, ...$fragment);
        }
        $tree['children'] = $children;
    }

    /**
     * @param array<string, mixed> $options
     * @return list<array<string, mixed>>
     */
    private static function convert(\Dom\Node $element, array $options, bool $extended): array
    {
        if ($element instanceof \Dom\Text) {
            if (trim($element->data) === '') {
                return [];
            }
            return [['type' => 'text', 'value' => preg_replace('/\s+/u', ' ', $element->data)]];
        }
        if (!$element instanceof \Dom\Element || in_array($element->localName, ['script', 'style', 'head'], true)) {
            return [];
        }
        $name = $element->localName;
        $special = $extended ? ['video' => 'image', 'iframe' => 'iframe', 'figure' => 'container', 'figcaption' => 'caption', 'u' => 'underline', 'details' => 'details', 'summary' => 'summary'] : [];
        $types = ['p' => 'paragraph', 'em' => 'emphasis', 'i' => 'emphasis', 'strong' => 'strong', 'b' => 'strong', 'del' => 'delete', 's' => 'delete', 'kbd' => 'keyboard', 'sup' => 'superscript', 'sub' => 'subscript', 'br' => 'break', 'hr' => 'thematicBreak', 'table' => 'table', 'tr' => 'tableRow', 'th' => 'tableCell', 'td' => 'tableCell', 'img' => 'image', 'a' => 'link', 'code' => 'inlineCode', 'pre' => 'code', 'blockquote' => 'blockquote', 'ul' => 'list', 'ol' => 'list', 'li' => 'listItem', 'dl' => 'definitionList', 'dt' => 'definitionTerm', 'dd' => 'definitionDescription'];
        $types = $special + $types;
        $children = [];
        foreach ($element->childNodes as $child) {
            array_push($children, ...self::convert($child, $options, $extended));
        }
        if (!isset($types[$name]) && !preg_match('/^h[1-6]$/', $name) && (!$extended || !in_array($name, ['div', 'span'], true))) {
            return $children;
        }
        $node = ['type' => $types[$name] ?? (str_starts_with($name, 'h') ? 'heading' : $name)];
        if ($node['type'] === 'break' && !($options['keepBreaks'] ?? true)) {
            return [];
        }
        if ($extended) {
            foreach ($element->attributes as $attribute) {
                if (in_array($attribute->name, ['class', 'align', 'width', 'height', 'colspan', 'rowspan'], true)) {
                    $node[$attribute->name] = in_array($attribute->name, ['colspan', 'rowspan'], true) ? (int)$attribute->value : $attribute->value;
                } elseif ($attribute->name === 'style') {
                    foreach (explode(';', $attribute->value) as $rule) {
                        $pair = explode(':', $rule, 2);
                        if (count($pair) === 2) {
                            $key = preg_replace_callback('/-([a-z])/', static fn (array $m): string => strtoupper($m[1]), trim($pair[0]));
                            $node['style'][$key] = trim($pair[1]);
                        }
                    }
                } elseif ($attribute->name === 'id') {
                    $node += \Splitice\Mystmd\Parser::label($attribute->value);
                }
            }
        }
        if ($name === 'th') {
            $node['header'] = true;
        } elseif ($name === 'iframe') {
            $node['src'] = $element->getAttribute('src') ?? '';
            $node['width'] = '100%';
            if ($element->hasAttribute('title')) {
                $node['title'] = $element->getAttribute('title');
            }
        } elseif ($name === 'img' || $name === 'video') {
            $node['url'] = $element->getAttribute('src') ?? '';
            foreach (['title', 'alt'] as $attribute) {
                if ($element->hasAttribute($attribute) || (!$extended && $attribute === 'alt')) {
                    $node[$attribute] = $element->getAttribute($attribute) ?? '';
                }
            }
        } elseif ($name === 'a') {
            $node['url'] = $element->getAttribute('href') ?? '';
            if ($element->hasAttribute('title')) {
                $node['title'] = $element->getAttribute('title');
            }
        } elseif ($node['type'] === 'heading') {
            $node['depth'] = (int)substr($name, 1);
        } elseif ($node['type'] === 'list') {
            $node['ordered'] = $name === 'ol';
            $node['spread'] = false;
            if ($name === 'ol') {
                $node['start'] = (int)($element->getAttribute('start') ?: 1);
            }
        }
        if (in_array($node['type'], ['code', 'inlineCode'], true)) {
            $node['value'] = $element->textContent;
        } elseif (!in_array($node['type'], ['break', 'thematicBreak', 'image', 'iframe'], true)) {
            $node['children'] = $children;
        }
        return [$node];
    }
}
