<?php

declare(strict_types=1);

$source = realpath($argv[1] ?? '') ?: '';
if (!is_file($source . '/packages/myst-parser/src/myst.ts')) {
    throw new InvalidArgumentException('Provide the pinned upstream checkout path.');
}
$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS)) as $file) {
    $relative = substr($file->getPathname(), strlen($source) + 1);
    if (preg_match('~(?:^|/)(?:node_modules|\.git)/~', $relative)) {
        continue;
    }
    if ($file->isFile() && (str_ends_with($relative, '.ts') || basename($relative) === 'package.json' || basename($relative) === 'LICENSE')) {
        $files[$relative] = hash_file('sha256', $file->getPathname());
    }
}
ksort($files);
$destination = dirname(__DIR__) . '/resources';
file_put_contents($destination . '/upstream-source-checksums.json', json_encode(['revision' => '807672d850695c2863143571f296327b15f203bd', 'files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
$math = file_get_contents($source . '/packages/myst-transforms/src/mathSimplifications.ts');
$maps = [];
foreach (['TEXT_REPLACEMENTS', 'SYM_REPLACEMENTS'] as $name) {
    preg_match('/const ' . $name . '.*?= \{(.*?)\};/s', $math, $section);
    preg_match_all("/'([^']+)': '([^']+)'/", $section[1], $entries, PREG_SET_ORDER);
    foreach ($entries as $entry) {
        $maps[$name][str_replace('\\\\', '\\', $entry[1])] = $entry[2];
    }
}
file_put_contents($destination . '/math-symbols.json', json_encode($maps, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
