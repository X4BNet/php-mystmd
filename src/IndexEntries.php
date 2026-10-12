<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Index directive/role parsing with the upstream error semantics. */
final class IndexEntries
{
    /** @return array<string, list<string>> */
    public static function emptyLists(): array
    {
        return array_fill_keys(['single', 'pair', 'triple', 'see', 'seealso'], []);
    }

    /** @param array<string, list<string>> $lists */
    public static function parseLine(string $line, array &$lists, Parser $parser): void
    {
        if (trim($line) === '') {
            return;
        }
        $parts = self::split($line, ':');
        if (count($parts) > 2) {
            $parser->warn('Too many colons encountered in index line "' . $line . '"', '', 'error');
        } elseif (count($parts) === 2) {
            if (array_key_exists($parts[0], $lists)) {
                $lists[$parts[0]][] = $parts[1];
            } else {
                $parser->warn('Unknown index entry type "' . $parts[0] . '"', '', 'error');
            }
        } else {
            array_push($lists['single'], ...self::split($parts[0], ','));
        }
    }

    /** @return array{emphasis: bool, splitEntry: list<string>} */
    public static function splitEntryValue(string $entry): array
    {
        $emphasis = str_starts_with($entry, '!');
        if ($emphasis || str_starts_with($entry, '\\!')) {
            $entry = substr($entry, 1);
        }
        return ['emphasis' => $emphasis, 'splitEntry' => array_values(array_filter(self::split($entry, ';'), static fn (string $part): bool => $part !== ''))];
    }

    /**
     * @param array<string, list<string>> $lists
     * @return list<array<string, mixed>>
     */
    public static function create(array $lists, Parser $parser): array
    {
        $entries = [];
        foreach ($lists as $kind => $values) {
            foreach ($values as $value) {
                ['emphasis' => $emphasis, 'splitEntry' => $parts] = self::splitEntryValue($value);
                $valid = $kind === 'single' ? in_array(count($parts), [1, 2], true) : count($parts) === ($kind === 'triple' ? 3 : 2);
                if (!$valid) {
                    $parser->warn('Unable to parse index "' . $kind . '" entry "' . $value . '"', '', 'error');
                    continue;
                }
                $pairs = match ($kind) {
                    'pair' => [[0, 1], [1, 0]],
                    'triple' => [[0, 1], [1, 0], [1, 2], [2, 1], [0, 2], [2, 0]],
                    default => [[0, 1]],
                };
                foreach ($pairs as [$first, $second]) {
                    $entry = ['entry' => $parts[$first], 'emphasis' => $emphasis];
                    if (!empty($parts[$second])) {
                        $entry['subEntry'] = ['value' => $parts[$second], 'kind' => in_array($kind, ['see', 'seealso'], true) ? $kind : 'entry'];
                    }
                    $entries[] = $entry;
                }
            }
        }
        if ($entries === []) {
            $parser->warn('No entries parsed from index directive', '', 'error');
        }
        return $entries;
    }

    /** Split unescaped delimiters and unescape only their first occurrence, as upstream.
     * @return list<string>
     */
    private static function split(string $value, string $delimiter): array
    {
        $parts = preg_split('/(?<!\\\\)' . preg_quote($delimiter, '/') . '/', $value) ?: [];
        return array_map(static function (string $part) use ($delimiter): string {
            $part = trim($part);
            $index = strpos($part, '\\' . $delimiter);
            return $index === false ? $part : substr_replace($part, $delimiter, $index, 2);
        }, $parts);
    }
}
