<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Escaped HTML templates with a single zero-valued content insertion point. */
final class HtmlTemplate
{
    private const array EMPTY_ELEMENTS = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    /** @param array<string, mixed> $attributes */
    public static function formatTag(string $tag, array $attributes, bool $inline = false): string
    {
        $content = $attributes['children'] ?? null;
        unset($attributes['children']);
        $attrs = [];
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $value = is_array($value) ? implode(' ', $value) : ($value === true ? '' : (string)$value);
            $attrs[] = $key . '="' . HtmlRenderer::escape($value) . '"';
        }
        return '<' . HtmlRenderer::escape($tag) . ($attrs ? ' ' . implode(' ', $attrs) : '') . '>'
            . ($content ? ($inline ? '' : "\n") . HtmlRenderer::escape((string)$content) : '');
    }

    /**
     * @param list<mixed> $template
     * @param array{inline?: bool} $options
     * @return array{string, ?string}
     */
    public static function toHtml(array $template, array $options = []): array
    {
        $inline = $options['inline'] ?? false;
        [$before, $after] = self::expand($template, $inline);
        $join = $inline ? '' : "\n";
        return [$before . $join, $after ? $after . $join : null];
    }

    /**
     * @param list<mixed> $template
     * @return array{string, ?string}
     */
    private static function expand(array $template, bool $inline): array
    {
        if (self::holes($template) > 1) {
            throw new \InvalidArgumentException('There cannot be more than one hole in the template.');
        }
        $tag = $template[0];
        $hasAttributes = isset($template[1]) && is_array($template[1]) && (!array_is_list($template[1]) || $template[1] === []);
        $before = [self::formatTag($tag, $hasAttributes ? $template[1] : [], $inline)];
        $after = [];
        $found = false;
        foreach (array_slice($template, $hasAttributes ? 2 : 1) as $value) {
            if ($value === 0) {
                $found = true;
                continue;
            }
            [$b, $a] = self::expand($value, $inline);
            $before[] = $b;
            if ($a) {
                $found = true;
                $after[] = $a;
            }
        }
        $closing = in_array($tag, self::EMPTY_ELEMENTS, true) ? '' : '</' . $tag . '>';
        if (!$found) {
            if ($closing !== '') {
                $before[] = $closing;
            }
            return [implode($inline ? '' : "\n", $before), null];
        }
        if ($closing !== '') {
            $after[] = $closing;
        }
        return [implode($inline ? '' : "\n", $before), implode($inline ? '' : "\n", $after)];
    }

    /** Count content holes, excluding values inside attribute maps.
     * @param list<mixed> $template
     */
    private static function holes(array $template): int
    {
        $count = 0;
        foreach ($template as $value) {
            $count += $value === 0 ? 1 : (is_array($value) && array_is_list($value) ? self::holes($value) : 0);
        }
        return $count;
    }
}
