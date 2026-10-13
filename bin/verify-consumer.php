<?php

declare(strict_types=1);

/** Install the package through Composer's path repository into an independent project. */
$root = dirname(__DIR__);
$directory = sys_get_temp_dir() . '/mystmd-consumer-' . bin2hex(random_bytes(6));
mkdir($directory, 0700, true);
$package = [
    'name' => 'splitice/mystmd-consumer-check',
    'require' => ['splitice/mystmd' => 'dev-main'],
    'repositories' => [['type' => 'path', 'url' => $root, 'options' => ['symlink' => false, 'reference' => 'none', 'versions' => ['splitice/mystmd' => 'dev-main']]]],
    'config' => ['allow-plugins' => false],
];
file_put_contents($directory . '/composer.json', json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
$composer = getenv('MYST_COMPOSER_BINARY') ?: 'composer';
$command = str_ends_with($composer, '.phar') ? [PHP_BINARY, $composer] : [$composer];
array_push($command, 'update', '--no-dev', '--prefer-stable', '--no-interaction', '--no-progress', '--no-ansi');
$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $directory);
if (!is_resource($process) || proc_close($process) !== 0) {
    fwrite(STDERR, "Consumer dependency installation failed in $directory\n");
    exit(1);
}
$installedResources = $directory . '/vendor/splitice/mystmd/resources';
if (!rename($installedResources, $directory . '/removed-package-resources')) {
    fwrite(STDERR, "Unable to isolate the consumer from package resource files in $directory\n");
    exit(1);
}
$probe = <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$converter = new Splitice\Mystmd\Converter();
$result = $converter->convert("# Installed package\n\n{kbd}`Ctrl` and **text**");
if ($result->html !== "<h1>Installed package</h1>\n<p><kbd>Ctrl</kbd> and <strong>text</strong></p>" || $result->diagnostics !== [] || $result->ast['type'] !== 'root') {
    throw new RuntimeException('Installed package failed its API probe');
}
$optional = new Splitice\Mystmd\Converter(extensions: [new Splitice\Mystmd\Extension\FirstPartyExtension(['tabs'])]);
if ($optional->parse("```{tab-item} Example\nbody\n```")->ast['children'][0]['children'][0]['type'] !== 'tabItem') {
    throw new RuntimeException('Installed package is missing optional extension resources');
}
$semantic = new Splitice\Mystmd\Converter(['html' => ['profile' => 'semantic']]);
$document = $semantic->parse("(target)=\n\n```{dropdown} Title\n:open:\n\nBody\n```\n\n[](#target)", 'guide.md');
$original = json_encode($document, JSON_THROW_ON_ERROR);
$result = $semantic->render($document);
if (!str_contains($result->html, '<summary>Title</summary>') || !str_contains($result->html, 'href="#target"') || $result->ast !== $result->document->ast || $result->document->origin !== 'guide.md' || json_encode($document, JSON_THROW_ON_ERROR) !== $original || $result->diagnostics !== []) {
    throw new RuntimeException('Installed package failed its semantic rendering contract');
}
echo "Independent consumer API and resource checks passed.\n";
PHP;
file_put_contents($directory . '/probe.php', $probe);
$process = proc_open([PHP_BINARY, $directory . '/probe.php'], [STDIN, STDOUT, STDERR], $pipes, $directory);
$status = is_resource($process) ? proc_close($process) : 1;
printf("Consumer project: %s\n", $directory);
exit($status);
