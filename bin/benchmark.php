<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Composer\InstalledVersions;
use Splitice\Mystmd\Converter;
use Splitice\Mystmd\Resolver\IncludeResolverInterface;
use Splitice\Mystmd\Resolver\IncludedSource;
use Splitice\Mystmd\Resolver\ResolutionContext;
use Splitice\Mystmd\Resolver\ResolverSet;

$iterations = filter_var($argv[1] ?? '5', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
if ($iterations === false) {
    fwrite(STDERR, "Usage: php bin/benchmark.php [iterations: 1..100]\n");
    exit(1);
}
$includes = new class implements IncludeResolverInterface {
    public function resolveInclude(string $path, ResolutionContext $context): ?IncludedSource
    {
        $depth = (int)$path;
        return new IncludedSource("(included-$depth)=\n\nIncluded **content** at depth $depth.\n\n" . ($depth < 12 ? '```{include} ' . ($depth + 1) . "\n```" : ''), 'memory:' . $depth);
    }
};
$report = ['php' => PHP_VERSION, 'os' => PHP_OS_FAMILY, 'iterations' => $iterations, 'max_nesting' => 64, 'dependencies' => [], 'cases' => []];
foreach (['league/commonmark', 'symfony/yaml'] as $package) {
    $report['dependencies'][$package] = InstalledVersions::getPrettyVersion($package);
}
$cases = [];
$unit = '{kbd}`Ctrl` {math}`x^2` {ref}`target` **bold** ';
foreach ([8192, 16384, 32768, 65536] as $size) {
    $cases['roles-' . $size] = "(target)=\n\n# Target\n\n" . str_repeat($unit, (int)ceil($size / strlen($unit)));
}
$nested = "Deepest content.\n";
for ($depth = 0; $depth < 20; ++$depth) {
    $fence = str_repeat(':', 3 + $depth);
    $nested = $fence . "{note}\n" . $nested . $fence . "\n";
}
$cases['nested-directives-20'] = $nested;
$cases['nested-includes-12'] = "```{include} 1\n```\n\n[](#included-12)";
foreach (['upstream', 'semantic'] as $profile) {
    $converter = new Converter(['html' => ['profile' => $profile], 'maxNesting' => 64], resolvers: new ResolverSet(includes: $includes));
    foreach ($cases as $name => $source) {
        $converter->convert($source, 'memory:root'); // Warm parser caches before measuring.
        $times = [];
        $peak = 0;
        $used = 0;
        for ($iteration = 0; $iteration < $iterations; ++$iteration) {
            gc_collect_cycles();
            memory_reset_peak_usage();
            $start = hrtime(true);
            $result = $converter->convert($source, 'memory:root');
            $times[] = (hrtime(true) - $start) / 1e6;
            $peak = max($peak, memory_get_peak_usage(true));
            $used = max($used, memory_get_peak_usage(false));
            $outputHash = hash('sha256', $result->html);
            $diagnostics = count($result->diagnostics);
            unset($result);
        }
        sort($times);
        $middle = intdiv($iterations, 2);
        $median = $iterations % 2 ? $times[$middle] : ($times[$middle - 1] + $times[$middle]) / 2;
        $report['cases'][] = ['profile' => $profile, 'case' => $name, 'source_bytes' => strlen($source), 'median_ms' => round($median, 3), 'peak_bytes' => $peak, 'peak_used_bytes' => $used, 'html_sha256' => $outputHash, 'diagnostics' => $diagnostics];
    }
}
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
