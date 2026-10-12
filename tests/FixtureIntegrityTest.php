<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\TestCase;

/** Ensure fixtures and test accounting cannot silently drift or lose coverage. */
final class FixtureIntegrityTest extends TestCase
{
    /** Verify each committed fixture against the pinned-source checksum manifest. */
    public function testPinnedFixtureChecksums(): void
    {
        $root = __DIR__ . '/Fixtures/upstream/';
        $manifest = json_decode((string)file_get_contents($root . 'manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('807672d850695c2863143571f296327b15f203bd', $manifest['revision']);
        foreach ($manifest['files'] as $file => $entry) {
            self::assertFileExists($root . $file);
            self::assertSame($entry['sha256'], hash_file('sha256', $root . $file), $file);
        }
        self::assertSame($manifest['license']['sha256'], hash_file('sha256', $root . $manifest['license']['file']));
    }

    /** Every original registration has a PHP mapping, explicit exclusion or original skip. */
    public function testEveryUpstreamTestIsAccountedFor(): void
    {
        $inventory = json_decode((string)file_get_contents(__DIR__ . '/Fixtures/upstream-tests.json'), true, flags: JSON_THROW_ON_ERROR);
        $coverage = json_decode((string)file_get_contents(__DIR__ . '/upstream-coverage.json'), true, flags: JSON_THROW_ON_ERROR);
        $mappings = new Support\CoverageMappings();
        self::assertSame($inventory['revision'], $coverage['revision']);
        self::assertCount(count($inventory['tests']), $coverage['tests']);
        foreach ($inventory['tests'] as $index => $original) {
            $entry = $coverage['tests'][$index];
            $identity = $original['file'] . ': ' . $original['title'];
            self::assertSame($original['file'], $entry['file'], $identity);
            self::assertSame($original['title'], $entry['title'], $identity);
            self::assertContains($entry['status'], ['implemented', 'scope-exclusion', 'upstream-skip'], $identity);
            if ($entry['status'] === 'upstream-skip') {
                self::assertTrue($original['upstreamSkip'], $identity);
            } elseif ($entry['status'] === 'scope-exclusion') {
                self::assertNotEmpty($entry['reason'], $identity);
            } else {
                self::assertNotEmpty($entry['php'], $identity);
                foreach ($entry['php'] as $mapping) {
                    $mappings->verify($original, $mapping);
                    self::addToAssertionCount(1);
                }
            }
        }
        self::assertEquals($coverage['counts'], array_count_values(array_column($coverage['tests'], 'status')));
    }

    public function testStaleDatasetIsRejectedEvenWhenTheMethodExists(): void
    {
        $fixture = json_decode((string)file_get_contents(__DIR__ . '/Fixtures/reference-calls.json'), true, flags: JSON_THROW_ON_ERROR);
        $original = array_intersect_key($fixture['cases'][0], ['file' => true, 'title' => true]);
        $this->expectExceptionMessage('Stale reference identity');
        (new Support\CoverageMappings())->verify($original, 'UpstreamReferenceTest::testHandwrittenParserCase / reference-calls.json / 999999');
    }

    public function testProviderFilteringCannotSilentlyLoseCoverage(): void
    {
        $file = 'packages/myst-spec/docs/examples/blocks.yml';
        $case = \Symfony\Component\Yaml\Yaml::parseFile(__DIR__ . '/Fixtures/upstream/' . $file)['cases'][4];
        self::assertArrayNotHasKey('myst', $case);
        self::assertArrayNotHasKey('markdown', $case);
        $this->expectExceptionMessage('Missing or changed executable dataset');
        (new Support\CoverageMappings())->verify(['parameters' => [null, $case]], 'UpstreamFixtureTest::testUpstreamParse / ' . $file . ':4');
    }
}
