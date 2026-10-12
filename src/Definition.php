<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Declarative argument, body and option validation for roles and directives. */
final class Definition
{
    /**
     * @param array<string, mixed> $spec
     * @param \Closure $run
     * @phpstan-param \Closure(array<string, mixed>, Parser): list<array<string, mixed>> $run
     */
    public function __construct(public readonly array $spec, private readonly \Closure $run)
    {
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    public function apply(array $node, Parser $parser): array
    {
        $role = $node['type'] === 'mystRole';
        $kind = $role ? 'role' : 'directive';
        $line = $node['position']['start']['line'] ?? 1;
        $data = ['name' => $node['name'], 'node' => $node, 'options' => []];
        $valid = true;
        foreach (['arg', 'options', 'body'] as $key) {
            if ($key === 'arg' && $role) {
                continue;
            }
            if ($key !== 'options') {
                $attribute = $key === 'arg' ? 'args' : 'value';
                $part = $key === 'arg' ? 'argument' : 'body';
                $value = $node[$attribute] ?? null;
                if (!isset($this->spec[$key])) {
                    if ($value !== null) {
                        $parser->warn('unexpected ' . $part . ' provided for ' . $kind . ': ' . $node['name'], $kind . '-' . $part . '-correct');
                    }
                    continue;
                }
                $schema = $this->spec[$key];
                if ($value === null && ($schema['required'] ?? false)) {
                    $message = 'required ' . $part . ' not provided for ' . $kind . ': ' . $node['name'];
                    $parser->warn($message, $kind . '-' . $part . '-correct', 'error');
                    $node['type'] = $role ? 'mystRoleError' : 'mystDirectiveError';
                    if (!$role) {
                        $node['message'] = $message;
                    }
                    $valid = false;
                } elseif ($value !== null) {
                    $converted = $this->convert($value, $schema, $parser, $line, !$role && $key === 'body', $part . ' of ' . $kind . ': ' . $node['name'], $kind . '-' . $part . '-correct');
                    if ($converted !== null) {
                        $data[$key] = $converted;
                    } elseif ($schema['required'] ?? false) {
                        $valid = false;
                    }
                }
                continue;
            }
            $raw = $node['options'] ?? [];
            foreach ($this->spec['options'] ?? [] as $option => $schema) {
                $used = array_key_exists($option, $raw) ? $option : null;
                foreach ((array)($schema['alias'] ?? []) as $alias) {
                    if (!array_key_exists($alias, $raw)) {
                        continue;
                    }
                    if ($used !== null) {
                        $parser->warn('option "' . $used . '" used instead of "' . $alias . '" (in ' . $node['name'] . ')', 'directive-options-correct');
                    } else {
                        $used = $alias;
                        $raw[$option] = $raw[$alias];
                    }
                    unset($raw[$alias]);
                }
                if ($used === null) {
                    if ($schema['required'] ?? false) {
                        $parser->warn('required option "' . $option . '" not provided (in ' . $node['name'] . ')', 'directive-options-correct', 'error');
                        $node['type'] = 'mystDirectiveError';
                        $valid = false;
                    }
                    continue;
                }
                $optionLine = $line + ($role ? 0 : 1 + (int)array_search($used, array_keys($node['options']), true));
                $converted = $this->convert($raw[$option], $schema, $parser, $optionLine, false, 'option "' . $option . '" (in ' . $node['name'] . ')', 'directive-options-correct');
                if ($converted !== null) {
                    $data['options'][$option] = $converted;
                } elseif ($schema['required'] ?? false) {
                    $valid = false;
                }
                unset($raw[$option]);
            }
            foreach (array_keys($raw) as $option) {
                $parser->warn('unexpected option "' . $option . '" provided (in ' . $node['name'] . ')', 'directive-options-correct');
            }
            if ($role) {
                if ($data['options'] !== []) {
                    $node['options'] = $data['options'];
                } else {
                    unset($node['options']);
                }
            }
        }
        if (!$valid) {
            unset($node['children']);
            return $node;
        }
        $node['children'] = ($this->run)($data, $parser);
        return $node;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function convert(mixed $value, array $schema, Parser $parser, int $line, bool $block, string $description, string $rule): mixed
    {
        $required = $schema['required'] ?? false;
        $type = $schema['type'] ?? 'string';
        if ($type === 'myst' || $type === 'parsed') {
            if (!is_string($value)) {
                $parser->warn('content is parsed from non-string value for ' . $description, $rule);
            }
            $children = $block ? $parser->parseBody((string)$value) : $parser->parseInline((string)$value, $line);
            if ($children === []) {
                if ($required) {
                    $parser->warn('no parsed content for required ' . $description, $rule, 'error');
                }
                return null;
            }
            return $children;
        }
        if ($type === 'boolean') {
            if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
                return strtolower($value) === 'true';
            }
            if (!is_bool($value)) {
                $parser->warn('value is not a boolean for ' . $description, $rule);
            }
            // JavaScript treats the nonempty string "0" as true.
            return $value !== '' && $value !== 0 && $value !== 0.0 && $value !== null && $value !== false;
        }
        if ($type === 'number') {
            if ($value === true || !(is_numeric($value) || $value === false || $value === '' || $value === null)) {
                $parser->warn('number not provided for ' . $description, $rule, $required ? 'error' : 'warning');
                return null;
            }
            return is_numeric($value) ? $value + 0 : 0;
        }
        if ($value === true) {
            return '';
        }
        if (!is_string($value) && !(is_numeric($value) && $value)) {
            $parser->warn('value is not a string for ' . $description, $rule);
        }
        return $value === false ? 'false' : (string)$value;
    }
}
