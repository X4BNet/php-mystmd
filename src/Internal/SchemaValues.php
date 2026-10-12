<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\Parser;

/**
 * Shared MyST value validation, including JavaScript-compatible scalar coercion.
 * @phpstan-import-type ValueSchema from \Splitice\Mystmd\Types
 */
final class SchemaValues
{
    /**
     * Resolve aliases, required options and values for built-ins and extensions.
     * @param array<string, mixed> $raw
     * @param array<string, array<string, mixed>> $schemas
     * @return array{options: array<string, mixed>, valid: bool, missingRequired: bool}
     */
    public static function options(array $raw, array $schemas, Parser $parser, string $name, int $line, bool $role, bool $parse): array
    {
        $keys = array_keys($raw);
        $options = [];
        $valid = true;
        $missingRequired = false;
        foreach ($schemas as $option => $schema) {
            $used = array_key_exists($option, $raw) ? $option : null;
            foreach ((array)($schema['alias'] ?? []) as $alias) {
                if (!array_key_exists($alias, $raw)) {
                    continue;
                }
                if ($used !== null) {
                    $parser->warn('option "' . $used . '" used instead of "' . $alias . '" (in ' . $name . ')', 'directive-options-correct');
                } else {
                    $used = $alias;
                    $raw[$option] = $raw[$alias];
                }
                unset($raw[$alias]);
            }
            if ($used === null) {
                if ($schema['required'] ?? false) {
                    $parser->warn('required option "' . $option . '" not provided (in ' . $name . ')', 'directive-options-correct', 'error');
                    $missingRequired = true;
                    $valid = false;
                }
                continue;
            }
            $optionLine = $line + ($parse && !$role ? 1 + (int)array_search($used, $keys, true) : 0);
            $value = self::convert($raw[$option], $schema, $parser, $optionLine, false, 'option "' . $option . '" (in ' . $name . ')', 'directive-options-correct', $parse);
            if ($value !== null) {
                $options[$option] = $value;
            } elseif ($schema['required'] ?? false) {
                $valid = false;
            }
            unset($raw[$option]);
        }
        foreach (array_keys($raw) as $option) {
            $parser->warn('unexpected option "' . $option . '" provided (in ' . $name . ')', 'directive-options-correct');
        }
        return ['options' => $options, 'valid' => $valid, 'missingRequired' => $missingRequired];
    }

    /**
     * Built-ins defer parsing valid strings to their body/option handlers.
     * @param array<string, mixed> $schema
     * @phpstan-param ValueSchema $schema
     */
    public static function convert(mixed $value, array $schema, Parser $parser, int $line, bool $block, string $description, string $rule, bool $parse = true): mixed
    {
        $required = $schema['required'] ?? false;
        $type = $schema['type'] ?? 'string';
        if ($value === '' && !$required) {
            return null;
        }
        if ($type === 'myst' || $type === 'parsed') {
            if (!is_string($value)) {
                $parser->warn('content is parsed from non-string value for ' . $description, $rule);
                $children = [];
            } elseif (!$parse) {
                return $value;
            } else {
                $children = $block ? $parser->parseBody($value) : $parser->parseInline($value, $line);
            }
            if ($children === []) {
                if ($required) {
                    $parser->warn('no parsed content for required ' . $description, $rule, 'error');
                }
                return null;
            }
            return $children;
        }
        if ($value === null) {
            if ($required) {
                $parser->warn('no content for required ' . $description, $rule, 'error');
            }
            return null;
        }
        if ($type === 'boolean') {
            if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
                return strtolower($value) === 'true';
            }
            if (!is_bool($value)) {
                $parser->warn('value is not a boolean for ' . $description, $rule);
            }
            return $value !== '' && $value !== 0 && $value !== 0.0 && $value !== false;
        }
        if ($type === 'number') {
            $number = is_array($value) || is_object($value) ? self::string($value) : $value;
            if (is_string($number)) {
                $number = trim($number);
            }
            if ($value === true || !(is_numeric($number) || $number === false || $number === '')) {
                $parser->warn('number not provided for ' . $description, $rule, $required ? 'error' : 'warning');
                return null;
            }
            return is_numeric($number) ? $number + 0 : 0;
        }
        if ($value === true) {
            return '';
        }
        if (!is_string($value) && !(is_numeric($value) && $value)) {
            $parser->warn('value is not a string for ' . $description, $rule);
        }
        return self::string($value);
    }

    /** Convert YAML/JSON values without PHP array-to-string warnings. */
    public static function string(mixed $value): string
    {
        if (is_array($value) && array_is_list($value)) {
            return implode(',', array_map(static fn (mixed $item): string => $item === null ? '' : self::string($item), $value));
        }
        if (is_array($value) || is_object($value)) {
            return '[object Object]';
        }
        return match ($value) {null => 'null', true => 'true', false => 'false', default => (string)$value};
    }
}
