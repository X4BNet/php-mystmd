<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Definition;
use Splitice\Mystmd\Extension\FirstPartyExtension;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Registry;
use Splitice\Mystmd\Transforms;

/** Handwritten upstream parser tests, including their full source positions. */
final class UpstreamReferenceTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>}> */
    public static function cases(): iterable
    {
        $fixture = json_decode((string)file_get_contents(__DIR__ . '/Fixtures/reference-calls.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($fixture['cases'] as $index => $case) {
            yield $index . ': ' . $case['file'] . ': ' . $case['title'] => [$case];
        }
    }

    /**
     * @param array<string, mixed> $case
     */
    #[DataProvider('cases')]
    public function testHandwrittenParserCase(array $case): void
    {
        $registry = new Registry();
        foreach (['directives', 'roles'] as $kind) {
            foreach ($case['options'][$kind] ?? [] as $spec) {
                if ($spec['name'] === 'subscript') {
                    continue; // Same built-in definition as the upstream test registers again.
                }
                $run = str_contains($case['file'], '/myst-ext-')
                    ? ($kind === 'roles' ? FirstPartyExtension::role(...) : FirstPartyExtension::directive(...))
                    : self::customCallback(...);
                $definition = new Definition($spec, $run);
                $kind === 'roles' ? $registry->roleDefinition($definition) : $registry->directiveDefinition($definition);
            }
        }
        $actual = (new Parser($case['options'], $registry))->parse($case['source']);
        $expected = $case['ast'];
        if (str_contains($case['title'], 'exercises are enumerated with labels by default')) {
            // The original test asserts string type, because upstream generates a random ID.
            self::assertIsString($actual->ast['children'][0]['children'][0]['identifier'] ?? null);
            self::assertSame($actual->ast['children'][0]['children'][0]['identifier'], $actual->ast['children'][0]['children'][0]['label']);
            foreach (['identifier', 'label'] as $key) {
                $expected['children'][0]['children'][0][$key] = $actual->ast['children'][0]['children'][0][$key];
            }
        }
        self::assertSame(self::sortKeys($expected), self::sortKeys($actual->ast));
        if (isset($case['diagnostics'])) {
            self::assertSame($case['diagnostics'], array_map(static fn ($d): array => ['message' => $d->message, 'ruleId' => $d->ruleId], $actual->diagnostics));
        }
    }

    /** Port the extension callbacks defined in ext.directive/role and recursiveParsing.spec.ts.
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    private static function customCallback(array $data, Parser $parser): array
    {
        if ($data['name'] === 'parse') {
            $lines = explode("\n", $data['body']);
            $first = array_shift($lines);
            return [['type' => 'parse', 'data' => ['firstLine' => $first], 'children' => $parser->parseBody(implode("\n", $lines), 1)]];
        }
        if ($data['name'] === 'widget') {
            return [['type' => 'widget', 'children' => $data['body'], 'class' => $data['options']['class'], 'open' => $data['options']['open'], 'max' => $data['options']['max']] + Parser::label($data['options']['label'])];
        }
        if (array_key_exists('flag', $data['options'])) {
            return [['type' => 'test', 'value' => $data['options']['flag'] ? 'flag' : 'no flag']];
        }
        if (isset($data['options']['something'])) {
            return [['type' => $data['body'], 'children' => $data['options']['something']]];
        }
        if (isset($data['options']['a'])) {
            return [['type' => 'test', 'value' => $data['options']['a']]];
        }
        if (isset($data['arg']) && is_array($data['arg'])) {
            return [['type' => $data['body'], 'children' => $data['arg']]];
        }
        return [['type' => $data['arg'] ?? 'test'] + (is_array($data['body']) ? ['children' => $data['body']] : ['value' => 'test: ' . $data['body']])];
    }

    /** Sort object keys without deleting any node or position information. */
    private static function sortKeys(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $value = array_map(self::sortKeys(...), $value);
        if (!array_is_list($value)) {
            ksort($value);
        }
        return $value;
    }
}
