<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Internal\RuntimeResources;

/** Verify generated runtime data stays synchronized with its pinned source files. */
final class RuntimeResourcesTest extends TestCase
{
    /** Every runtime catalog must match the committed upstream-derived JSON. */
    public function testEmbeddedResourcesMatchPinnedJson(): void
    {
        $root = dirname(__DIR__);
        $resources = [
            'core-definitions.json' => RuntimeResources::coreDefinitions(),
            'first-party-definitions.json' => RuntimeResources::firstPartyDefinitions(),
            'math-symbols.json' => RuntimeResources::mathSymbols(),
            'tlds.json' => RuntimeResources::tlds(),
        ];

        foreach ($resources as $filename => $embedded) {
            $contents = file_get_contents($root . '/resources/' . $filename);
            self::assertNotFalse($contents, $filename);
            self::assertSame(
                json_decode($contents, true, flags: JSON_THROW_ON_ERROR),
                $embedded,
                $filename
            );
        }
    }

    /** Runtime PHP must not read package resources from the filesystem. */
    public function testSourceDoesNotLoadResourceFilesAtRuntime(): void
    {
        $sourceRoot = dirname(__DIR__) . '/src';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            self::assertNotFalse($source, $file->getPathname());
            self::assertStringNotContainsString('/resources/', $source, $file->getPathname());
        }
    }
}
