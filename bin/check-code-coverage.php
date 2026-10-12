<?php

declare(strict_types=1);

/** Compare measured line and branch ratios; fixture mappings are a separate check. */
$root = dirname(__DIR__);
$reportPath = $argv[1] ?? $root . '/build/coverage/clover.xml';
$baselinePath = $argv[2] ?? $root . '/tests/coverage-baseline.json';
$update = ($argv[3] ?? '') === '--update-baseline';
try {
    $xml = new DOMDocument();
    if (!is_file($reportPath) || !$xml->load($reportPath, LIBXML_NONET)) {
        throw new RuntimeException('Missing or invalid Clover report: ' . $reportPath);
    }
    $metrics = (new DOMXPath($xml))->query('/coverage/project/metrics')?->item(0);
    if (!$metrics instanceof DOMElement) {
        throw new RuntimeException('Missing project coverage metrics');
    }
    $measurement = ['php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, 'metrics' => []];
    foreach (['lines' => ['coveredstatements', 'statements'], 'branches' => ['coveredconditionals', 'conditionals']] as $kind => [$covered, $total]) {
        $values = [$metrics->getAttribute($covered), $metrics->getAttribute($total)];
        if (!ctype_digit($values[0]) || !ctype_digit($values[1]) || (int)$values[1] < 1 || (int)$values[0] > (int)$values[1]) {
            throw new RuntimeException('Invalid ' . $kind . ' metrics; collect coverage with Xdebug and --path-coverage');
        }
        $measurement['metrics'][$kind] = ['covered' => (int)$values[0], 'total' => (int)$values[1]];
    }
    if ($update) {
        file_put_contents($baselinePath, json_encode($measurement, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
        echo "Baseline updated; review the measured changes before committing.\n";
    }
    $baseline = json_decode((string)file_get_contents($baselinePath), true, flags: JSON_THROW_ON_ERROR);
    if (($baseline['php'] ?? '') !== $measurement['php']) {
        throw new RuntimeException('Measure the baseline and report with the same PHP minor version');
    }
    $failed = false;
    foreach ($measurement['metrics'] as $kind => $counts) {
        $minimum = $baseline['metrics'][$kind] ?? [];
        if (!isset($minimum['covered'], $minimum['total']) || !is_int($minimum['covered']) || !is_int($minimum['total']) || $minimum['total'] < 1 || $minimum['covered'] < 0 || $minimum['covered'] > $minimum['total']) {
            throw new RuntimeException('Invalid baseline for ' . $kind);
        }
        $regressed = $counts['covered'] * $minimum['total'] < $minimum['covered'] * $counts['total'];
        printf("%s: %d/%d (%.4f%%), baseline %.4f%% %s\n", ucfirst($kind), $counts['covered'], $counts['total'], 100 * $counts['covered'] / $counts['total'], 100 * $minimum['covered'] / $minimum['total'], $regressed ? 'FAIL' : 'PASS');
        $failed = $failed || $regressed;
    }
    exit($failed ? 1 : 0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
