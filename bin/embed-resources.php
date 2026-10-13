<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$resources = [
    'CORE_DEFINITIONS' => 'core-definitions.json',
    'FIRST_PARTY_DEFINITIONS' => 'first-party-definitions.json',
    'MATH_SYMBOLS' => 'math-symbols.json',
    'TLDS' => 'tlds.json',
];

$constants = [];
foreach ($resources as $constant => $filename) {
    $contents = file_get_contents($root . '/resources/' . $filename);
    if ($contents === false) {
        throw new RuntimeException('Unable to read resource: ' . $filename);
    }
    $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    $constants[] = sprintf(
        "    private const %s = %s;\n",
        $constant,
        var_export($decoded, true)
    );
}

$source = <<<'PHP'
<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

/** Runtime data generated from the package's pinned JSON resources. */
final class RuntimeResources
{
__CONSTANTS__
    /** @return array<string, list<array<string, mixed>>> */
    public static function coreDefinitions(): array
    {
        return self::CORE_DEFINITIONS;
    }

    /** @return array<string, array{directives: list<array<string, mixed>>, roles: list<array<string, mixed>>}> */
    public static function firstPartyDefinitions(): array
    {
        return self::FIRST_PARTY_DEFINITIONS;
    }

    /** @return array<string, array<string, string>> */
    public static function mathSymbols(): array
    {
        return self::MATH_SYMBOLS;
    }

    /** @return list<string> */
    public static function tlds(): array
    {
        return [...self::TLDS];
    }
}
PHP;

$source = str_replace('__CONSTANTS__', implode("\n", $constants), $source);
$destination = $root . '/src/Internal/RuntimeResources.php';
if (file_put_contents($destination, $source) === false) {
    throw new RuntimeException('Unable to write generated runtime resources: ' . $destination);
}
