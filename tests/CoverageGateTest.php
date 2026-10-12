<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\TestCase;

/** The release gate must reject regressions and reports without branch data. */
final class CoverageGateTest extends TestCase
{
    public function testMeasuredBaselineAndFailureModes(): void
    {
        $directory = sys_get_temp_dir() . '/mystmd-coverage-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $report = $directory . '/clover.xml';
        $baseline = $directory . '/baseline.json';
        $metrics = static function (int $lines, int $branches, int $totalBranches = 10) use ($report): void {
            file_put_contents($report, '<coverage><project><metrics statements="10" coveredstatements="' . $lines . '" conditionals="' . $totalBranches . '" coveredconditionals="' . $branches . '"/></project></coverage>');
        };
        $run = static function (bool $update = false) use ($report, $baseline): int {
            $command = [PHP_BINARY, dirname(__DIR__) . '/bin/check-code-coverage.php', $report, $baseline];
            if ($update) {
                $command[] = '--update-baseline';
            }
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            return proc_close($process);
        };
        try {
            $metrics(8, 6);
            self::assertSame(0, $run(true));
            self::assertSame(0, $run());
            $metrics(9, 7);
            self::assertSame(0, $run());
            $metrics(7, 7);
            self::assertSame(1, $run());
            $metrics(9, 5);
            self::assertSame(1, $run());
            $metrics(9, 0, 0);
            self::assertSame(1, $run());
        } finally {
            unlink($report);
            unlink($baseline);
            rmdir($directory);
        }
    }
}
