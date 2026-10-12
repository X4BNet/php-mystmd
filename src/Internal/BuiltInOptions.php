<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\Parser;

/** Validate built-in schemas without reparsing bodies that their handlers consume. */
final class BuiltInOptions
{
    /** @var array<string, array<string, array<string, mixed>>>|null */
    private static ?array $catalog = null;

    /**
     * @param array<string, mixed> $node
     * @return array{node: array<string, mixed>, options: array<string, mixed>, valid: bool}
     */
    public static function prepare(array $node, Parser $parser): array
    {
        if (self::$catalog === null) {
            self::$catalog = [];
            $data = json_decode((string)file_get_contents(__DIR__ . '/../../resources/core-definitions.json'), true, flags: JSON_THROW_ON_ERROR);
            foreach ($data as $category => $definitions) {
                foreach ($definitions as $spec) {
                    foreach ([$spec['name'], ...(array)($spec['alias'] ?? [])] as $name) {
                        self::$catalog[$category][$name] = $spec;
                    }
                }
            }
        }
        $role = $node['type'] === 'mystRole';
        $kind = $role ? 'role' : 'directive';
        $name = $node['name'];
        $spec = self::$catalog[$kind . 's'][$name] ?? null;
        if ($spec === null) {
            return ['node' => $node, 'options' => $node['options'] ?? [], 'valid' => true];
        }
        $valid = true;
        foreach (['arg' => 'args', 'body' => 'value'] as $key => $attr) {
            $part = $key === 'arg' ? 'argument' : $key;
            if (($spec[$key]['required'] ?? false) && !isset($node[$attr])) {
                $message = 'required ' . $part . ' not provided for ' . $kind . ': ' . $name;
                $parser->warn($message, $kind . '-' . $part . '-correct', 'error');
                $node['type'] = $role ? 'mystRoleError' : 'mystDirectiveError';
                if (!$role) {
                    $node['message'] = $message;
                }
                $valid = false;
            } elseif (!isset($spec[$key]) && isset($node[$attr])) {
                $parser->warn('unexpected ' . $part . ' provided for ' . $kind . ': ' . $name, $kind . '-' . $part . '-correct');
            }
        }
        $raw = $node['options'] ?? [];
        $options = [];
        foreach ($spec['options'] ?? [] as $key => $definition) {
            $used = array_key_exists($key, $raw) ? $key : null;
            foreach ((array)($definition['alias'] ?? []) as $alias) {
                if (!array_key_exists($alias, $raw)) {
                    continue;
                }
                if ($used !== null) {
                    $parser->warn('option "' . $used . '" used instead of "' . $alias . '" (in ' . $name . ')', 'directive-options-correct');
                } else {
                    $used = $alias;
                    $raw[$key] = $raw[$alias];
                }
                unset($raw[$alias]);
            }
            if ($used === null) {
                if ($definition['required'] ?? false) {
                    $parser->warn('required option "' . $key . '" not provided (in ' . $name . ')', 'directive-options-correct', 'error');
                    $node['type'] = 'mystDirectiveError';
                    $valid = false;
                }
                continue;
            }
            $value = $raw[$key];
            unset($raw[$key]);
            $description = 'option "' . $key . '" (in ' . $name . ')';
            switch ($definition['type'] ?? 'string') {
                case 'boolean':
                    if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
                        $value = strtolower($value) === 'true';
                    } elseif (!is_bool($value)) {
                        $parser->warn('value is not a boolean for ' . $description, 'directive-options-correct');
                        $value = $value !== '' && $value !== 0 && $value !== null;
                    }
                    break;
                case 'number':
                    if ($value === true || !(is_numeric($value) || $value === '' || $value === false || $value === null)) {
                        $parser->warn('number not provided for ' . $description, 'directive-options-correct', ($definition['required'] ?? false) ? 'error' : 'warning');
                        $valid = $valid && !($definition['required'] ?? false);
                        continue 2;
                    }
                    $value = is_numeric($value) ? $value + 0 : 0;
                    break;
                case 'string':
                    if ($value === true) {
                        $value = '';
                    } elseif (!is_string($value) && (!is_numeric($value) || !$value)) {
                        $parser->warn('value is not a string for ' . $description, 'directive-options-correct');
                        $value = $value === false ? 'false' : (string)$value;
                    } else {
                        $value = (string)$value;
                    }
                    break;
            }
            $options[$key] = $value;
        }
        foreach (array_keys($raw) as $key) {
            $parser->warn('unexpected option "' . $key . '" provided (in ' . $name . ')', 'directive-options-correct');
        }
        if ($role) {
            if ($options !== []) {
                $node['options'] = $options;
            } else {
                unset($node['options']);
            }
        }
        return ['node' => $node, 'options' => $options, 'valid' => $valid];
    }
}
