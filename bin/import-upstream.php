<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$source = $argv[1] ?? '';
if (!is_dir($source . '/packages/myst-parser')) {
    fwrite(STDERR, "Usage: php bin/import-upstream.php /path/to/mystmd-at-807672d\n");
    exit(1);
}
$destination = dirname(__DIR__) . '/tests/Fixtures/upstream';
$packages = ['markdown-it-myst', 'myst-spec', 'myst-spec-ext', 'myst-parser', 'myst-directives', 'myst-roles', 'myst-to-html', 'myst-transforms', 'myst-frontmatter', 'myst-common', 'myst-ext-button', 'myst-ext-card', 'myst-ext-exercise', 'myst-ext-grid', 'myst-ext-icon', 'myst-ext-proof', 'myst-ext-reactive', 'myst-ext-tabs'];
$manifest = ['repository' => 'https://github.com/jupyter-book/mystmd', 'revision' => '807672d850695c2863143571f296327b15f203bd', 'versions' => ['myst-parser' => '1.7.4', 'myst-to-html' => '1.7.4', 'myst-spec' => '0.0.6'], 'files' => []];
foreach ($packages as $package) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . '/packages/' . $package, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $relative = substr($file->getPathname(), strlen($source) + 1);
        if (!preg_match('/(?:\.spec\.ts|\.(?:yml|yaml|json)|\/tests\/.*\.md|\/tests\/position\.ts)$/', $relative)) {
            continue;
        }
        $target = $destination . '/' . $relative;
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }
        copy($file->getPathname(), $target);
        $entry = ['sha256' => hash_file('sha256', $target)];
        if (preg_match('/\.ya?ml$/', $relative)) {
            $data = Yaml::parseFile($target);
            $entry['cases'] = array_map(static fn (array $case): string => (string)($case['title'] ?? $case['name'] ?? $case['id'] ?? 'unnamed'), $data['cases'] ?? []);
        }
        $manifest['files'][$relative] = $entry;
    }
}
ksort($manifest['files']);
copy($source . '/LICENSE', $destination . '/LICENSE');
$manifest['license'] = ['name' => 'MIT', 'file' => 'LICENSE', 'sha256' => hash_file('sha256', $destination . '/LICENSE')];
file_put_contents($destination . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
printf("Imported %d files from %s.\n", count($manifest['files']), $manifest['revision']);
