<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\Parser;
use Symfony\Component\Yaml\Yaml;

/** Shared code and include option semantics from myst-directives. */
final class DirectiveUtils
{
    /** Match JSON.stringify for JSON5 non-finite numbers, retaining object/array identity. */
    public static function jsonCompatible(mixed $value): mixed
    {
        if (is_float($value) && !is_finite($value)) {
            return null;
        }
        if (is_array($value)) {
            return array_map(self::jsonCompatible(...), $value);
        }
        if ($value instanceof \stdClass) {
            foreach (get_object_vars($value) as $key => $child) {
                $value->{$key} = self::jsonCompatible($child);
            }
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function codeOptions(array $options, Parser $parser, ?string $defaultFilename = null): array
    {
        if (isset($options['lineno-start'], $options['number-lines'])) {
            $parser->warn('Cannot use both "lineno-start" and "number-lines"', 'directive-options-correct');
        }
        $attrs = [];
        $emphasis = [];
        foreach (explode(',', (string)($options['emphasize-lines'] ?? '')) as $part) {
            $part = trim($part);
            if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $part, $m)) {
                if ((int)$m[1] > (int)$m[2]) {
                    $parser->warn('Invalid emphasize-lines range "' . $part . '" (start > end)', 'directive-options-correct');
                } elseif ((int)$m[2] - (int)$m[1] > 100000) {
                    $parser->warn('Emphasize-lines range exceeds the configured resource limit', 'directive-options-correct');
                } else {
                    array_push($emphasis, ...range((int)$m[1], (int)$m[2]));
                }
            } elseif (ctype_digit($part)) {
                $emphasis[] = (int)$part;
            } elseif ($part !== '') {
                $parser->warn('Invalid emphasize-lines value "' . $part . '"', 'directive-options-correct');
            }
        }
        if ($emphasis !== []) {
            $emphasis = array_values(array_unique($emphasis));
            sort($emphasis);
            $attrs['emphasizeLines'] = $emphasis;
        }
        if (($options['linenos'] ?? false) || ($options['lineno-start'] ?? false) || ($options['lineno-match'] ?? false) || ($options['number-lines'] ?? false)) {
            $attrs['showLineNumbers'] = true;
        }
        $start = ($options['number-lines'] ?? 0) > 1 ? $options['number-lines'] : ($options['lineno-start'] ?? null);
        if ($options['lineno-match'] ?? false) {
            $attrs['startingLineNumber'] = 'match';
        } elseif ($start !== null && (int)$start > 1) {
            $attrs['startingLineNumber'] = (int)$start;
        }
        $filename = $options['filename'] ?? $defaultFilename;
        if (is_string($filename) && $filename !== '' && strtolower($filename) !== 'false') {
            $attrs['filename'] = $filename;
        }
        return $attrs;
    }

    /** @return list<string>|null */
    public static function parseTags(mixed $input, Parser $parser): ?array
    {
        if ($input === '' || $input === null) {
            return null;
        }
        if (is_string($input) && str_starts_with($input, '[') && str_ends_with($input, ']')) {
            try {
                return self::parseTags(Yaml::parse($input), $parser);
            } catch (\Symfony\Component\Yaml\Exception\ParseException) {
                $parser->warn('Could not load tags for code-cell directive', 'directive-options-correct', 'error');
                return null;
            }
        }
        if (is_string($input)) {
            $input = preg_split('/[,\s]/', $input) ?: [];
        }
        if (!is_array($input)) {
            return null;
        }
        if (array_filter($input, static fn (mixed $tag): bool => !is_string($tag)) !== []) {
            $parser->warn('tags in code-cell directive must be a list of strings', 'directive-options-correct');
            return null;
        }
        $tags = array_values(array_filter(array_map('trim', $input), static fn (string $tag): bool => $tag !== ''));
        return $tags !== [] ? $tags : null;
    }

    /** @return list<int|list<int>>|null */
    public static function parseLinesString(string $source, Parser $parser): ?array
    {
        if ($source === '') {
            return null;
        }
        $lines = [];
        foreach (explode(',', $source) as $part) {
            if (!preg_match('/^(\d+)(-)?(\d+)?$/', trim($part), $match)) {
                $parser->warn('Unknown lines match "' . $part . '"', 'directive-options-correct');
            } else {
                $lines[] = isset($match[3]) ? [(int)$match[1], (int)$match[3]] : (isset($match[2]) ? [(int)$match[1]] : (int)$match[1]);
            }
        }
        return $lines;
    }
}
