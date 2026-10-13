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
            $data = RuntimeResources::coreDefinitions();
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
        $validated = SchemaValues::options($node['options'] ?? [], $spec['options'] ?? [], $parser, $name, $node['position']['start']['line'] ?? 1, $role, false);
        $options = $validated['options'];
        $valid = $valid && $validated['valid'];
        if ($validated['missingRequired']) {
            $node['type'] = 'mystDirectiveError';
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
