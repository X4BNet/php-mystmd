<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$root = dirname(__DIR__);
$fixtures = $root . '/tests/Fixtures';
$inventory = json_decode((string)file_get_contents($fixtures . '/upstream-tests.json'), true, flags: JSON_THROW_ON_ERROR);
$mapped = [];
foreach (['reference-calls' => 'UpstreamReferenceTest::testHandwrittenParserCase', 'reference-behavior' => 'UpstreamBehaviorTest::testHandwrittenBehavior'] as $file => $method) {
    $exported = json_decode((string)file_get_contents($fixtures . '/' . $file . '.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($exported['cases'] as $index => $case) {
        $mapped[$case['file'] . "\n" . $case['title']][] = $method . ' / ' . $file . '.json / ' . $index;
    }
}
$yamlCases = [];
foreach (['myst-spec/docs/examples/*.yml', 'myst-parser/tests/directives/*.yml', 'myst-parser/tests/roles/*.yml', 'myst-to-html/tests/transforms/*.yml', 'myst-transforms/tests/{html,abbreviations,enumerators}.yml'] as $pattern) {
    foreach (glob($fixtures . '/upstream/packages/' . $pattern, GLOB_BRACE) ?: [] as $path) {
        foreach (Yaml::parseFile($path)['cases'] ?? [] as $index => $case) {
            $key = hash('sha256', json_encode($case, JSON_THROW_ON_ERROR));
            $yamlCases[$key][] = substr($path, strlen($fixtures . '/upstream/')) . ':' . $index;
        }
    }
}
$exclusions = [
    'packages/myst-transforms/src/links/myst.spec.ts' => 'External MyST project-inventory adapter. Cross-project inventories and discovery are supplied by the caller through ReferenceResolver; no project catalog is fetched or interpreted implicitly.',
    'packages/myst-transforms/src/links/sphinx.spec.ts' => 'External Sphinx inventory adapter. Inventory loading and lookup belong to the caller-supplied ReferenceResolver, whose success/failure and isolation behavior is tested in ResolverTest.',
    'packages/myst-common/src/extractParts.spec.ts' => 'Project/export part selection. The package parses and preserves block metadata but does not assemble publication parts.',
    'packages/myst-frontmatter/src/licenses/licenses.spec.ts' => 'Publication license normalization and remote SPDX catalog checks; raw document YAML is preserved without publication metadata validation.',
    'packages/myst-frontmatter/src/socials/validators.spec.ts' => 'Publication contributor social-profile validation; outside document parsing and HTML serialization.',
    'packages/myst-frontmatter/src/utils/fillPageFrontmatter.spec.ts' => 'Project-to-page publication metadata inheritance. Project discovery and publishing are excluded.',
    'packages/myst-frontmatter/src/utils/parseName.spec.ts' => 'Publication author-name normalization; outside document parsing and HTML serialization.',
    'packages/myst-frontmatter/src/utils/referenceStash.spec.ts' => 'Project metadata object-reference stashing. Document references instead use the package resolver interfaces.',
    'packages/myst-frontmatter/src/utils/unnestKernelSpec.spec.ts' => 'Notebook kernel metadata normalization. Notebook execution is excluded.',
    'packages/myst-frontmatter/tests/examples.spec.ts' => 'Publication/project frontmatter schema normalization. YAML parsing and document title extraction are covered independently; publishing metadata validation is excluded.',
];
$entries = [];
foreach ($inventory['tests'] as $test) {
    $file = $test['file'];
    $title = $test['title'];
    $entry = ['file' => $file, 'title' => $title, 'status' => 'pending'];
    $key = $file . "\n" . $title;
    if ($file === 'packages/myst-to-html/tests/schema.spec.ts') {
        $entry['status'] = 'implemented';
        $entry['php'] = ['UpstreamManualTest::testKeyboardSchema'];
        $entry['note'] = 'Checks the same root/paragraph/kbd/text structure through the public HTML renderer and DOM. HAST is a private JavaScript intermediate representation.';
    } elseif ($file === 'packages/myst-transforms/src/keys.spec.ts') {
        $entry['status'] = 'implemented';
        $entry['php'] = [str_ends_with($title, 'Assigns a key') ? 'UpstreamManualTest::testKeysTransform' : 'UpstreamManualTest::testKeysPlugin'];
    } elseif (isset($mapped[$key])) {
        $entry['status'] = 'implemented';
        $entry['php'] = $mapped[$key];
    } elseif (str_ends_with($file, '/commonmark.spec.ts')) {
        $entry['status'] = $test['upstreamSkip'] ? 'upstream-skip' : 'implemented';
        $entry['php'] = ['UpstreamCommonMarkTest::testEndToEnd / example ' . $test['parameters'][1]['example']];
        $entry['note'] = 'Both upstream backend runners map to the public PHP parse-and-render pipeline; whitespace is retained.';
    } elseif (preg_match('~/(?:myst|directives|roles)\.spec\.ts$~', $file) && isset($test['parameters'][1]['mdast'])) {
        $case = $test['parameters'][1];
        $lookup = hash('sha256', json_encode($case, JSON_THROW_ON_ERROR));
        if (isset($yamlCases[$lookup])) {
            $method = str_contains($title, 'mdast --> html') ? 'testUpstreamHtml' : 'testUpstreamParse';
            $entry['status'] = $test['upstreamSkip'] ? 'upstream-skip' : 'implemented';
            $entry['php'] = array_map(static fn (string $case): string => 'UpstreamFixtureTest::' . $method . ' / ' . $case, $yamlCases[$lookup]);
        }
    } elseif (isset($test['parameters'][1]['before'])) {
        $lookup = hash('sha256', json_encode($test['parameters'][1], JSON_THROW_ON_ERROR));
        if (isset($yamlCases[$lookup])) {
            $entry['status'] = 'implemented';
            $entry['php'] = array_map(static fn (string $case): string => 'UpstreamTransformTest::testUpstreamTransform / ' . $case, $yamlCases[$lookup]);
        }
    }
    if ($entry['status'] === 'pending' && isset($exclusions[$file])) {
        $entry['status'] = 'scope-exclusion';
        $entry['reason'] = $exclusions[$file];
    }
    if ($entry['status'] === 'pending' && $file === 'packages/myst-transforms/src/math.spec.ts') {
        $entry['status'] = 'scope-exclusion';
        $entry['reason'] = 'KaTeX engine typesetting, macro expansion and TeX validation. The pinned myst-to-html serializer emits math source, which the PHP HTML renderer matches; callers may install a math node renderer. Math parsing, nesting and simplification are ported.';
    }
    if ($entry['status'] === 'pending' && $file === 'packages/myst-transforms/src/links/plugin.spec.ts' && str_starts_with($title, 'Link Plugin Transformer /')) {
        $entry['status'] = 'scope-exclusion';
        $entry['reason'] = 'External MyST inventory adapter orchestration, delegated to the caller-supplied ReferenceResolver. The six independent link-text formatting tests are ported.';
    }
    if ($test['upstreamSkip']) {
        $entry['status'] = 'upstream-skip';
        $entry['note'] = 'Explicit skip in the pinned upstream test registration.';
    }
    $entries[] = $entry;
}
$counts = array_count_values(array_column($entries, 'status'));
$manifest = ['revision' => $inventory['revision'], 'counts' => $counts, 'tests' => $entries, 'additionalUpstreamSkips' => [
    ['file' => 'packages/myst-parser/tests/myst.spec.ts', 'examples' => [25, 333, 353, 506], 'reason' => 'Explicit upstream SKIP_TESTS filter; example 44 has no parser case in the imported MyST examples.', 'php' => 'UpstreamFixtureTest::testUpstreamParse'],
]];
file_put_contents($root . '/tests/upstream-coverage.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
echo json_encode($counts, JSON_THROW_ON_ERROR) . "\n";
