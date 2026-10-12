<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Internal\HtmlToAst;
use Splitice\Mystmd\ReferenceState;
use Symfony\Component\Yaml\Yaml;

/** Unmodified transform fixtures from the pinned upstream HTML packages. */
final class UpstreamTransformTest extends TestCase
{
    /** @return iterable<string, array{string, array<string, mixed>}> */
    public static function cases(): iterable
    {
        foreach (['myst-to-html/tests/transforms/addenumerators.yml', 'myst-to-html/tests/transforms/converthtml.yml', 'myst-transforms/tests/html.yml', 'myst-transforms/tests/abbreviations.yml', 'myst-transforms/tests/enumerators.yml'] as $file) {
            foreach (Yaml::parseFile(__DIR__ . '/Fixtures/upstream/packages/' . $file)['cases'] as $index => $case) {
                yield $file . ':' . $index . ':' . $case['title'] => [$file, $case];
            }
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('cases')]
    public function testUpstreamTransform(string $file, array $case): void
    {
        $tree = $case['before'];
        if (str_contains($file, '/enumerators')) {
            (new \Splitice\Mystmd\Numbering($case['opts'] ?? []))->apply($tree);
        } elseif (str_contains($file, 'addenumerators')) {
            (new ReferenceState())->enumerateTargets($tree, $case['opts'] ?? []);
        } elseif (str_contains($file, 'abbreviations')) {
            \Splitice\Mystmd\Abbreviations::apply($tree, $case['opts'] ?? []);
        } else {
            HtmlToAst::apply($tree, $case['opts'] ?? [], str_starts_with($file, 'myst-transforms/'));
        }
        self::assertEquals($case['after'], $tree);
    }
}
