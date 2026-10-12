<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/**
 * Declarative argument, body and option validation for roles and directives.
 * @phpstan-import-type DefinitionSchema from Types
 */
final class Definition
{
    /**
     * @param array<string, mixed> $spec
     * @phpstan-param DefinitionSchema $spec
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
                    $converted = Internal\SchemaValues::convert($value, $schema, $parser, $line, !$role && $key === 'body', $part . ' of ' . $kind . ': ' . $node['name'], $kind . '-' . $part . '-correct');
                    if ($converted !== null) {
                        $data[$key] = $converted;
                    } elseif ($schema['required'] ?? false) {
                        $valid = false;
                    }
                }
                continue;
            }
            $validated = Internal\SchemaValues::options($node['options'] ?? [], $this->spec['options'] ?? [], $parser, $node['name'], $line, $role, true);
            $data['options'] = $validated['options'];
            $valid = $valid && $validated['valid'];
            if ($validated['missingRequired']) {
                $node['type'] = 'mystDirectiveError';
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

}
