<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

use Splitice\Mystmd\Parser;

/** Pinned MyST include selection semantics, without performing resource access. */
final class IncludeFilter
{
    /**
     * @param array<string, mixed>|null $filter
     * @return array{content: string, startingLineNumber?: int}
     */
    public static function apply(?array $filter, string $source, Parser $parser): array
    {
        if (!$filter) {
            return ['content' => $source];
        }
        $lines = explode("\n", $source);
        $total = count($lines);
        if (isset($filter['lines'])) {
            $selected = [];
            $start = null;
            foreach ($filter['lines'] as $selection) {
                $range = is_array($selection);
                $first = $range ? $selection[0] : $selection;
                $last = $range ? ($selection[1] ?? $total) : $selection;
                if ($first === 0 || $last === 0) {
                    $parser->warn('Invalid line number "0", indexing starts at 1', 'include-content-filters');
                    continue;
                }
                $from = $first > 0 ? $first - 1 : $total + $first;
                $to = $last > 0 ? $last : $total + $last + 1;
                $start ??= $from + 1;
                // Array.slice normalizes negative offsets a second time, as in upstream JS.
                $normalizedFrom = $from < 0 ? max(0, $total + $from) : min($total, $from);
                $normalizedTo = $to < 0 ? max(0, $total + $to) : min($total, $to);
                $part = array_slice($lines, $normalizedFrom, max(0, $normalizedTo - $normalizedFrom));
                if ($range && $part === []) {
                    $parser->warn('Unexpected lines, from "' . $first . '" to "' . ($selection[1] ?? '') . '"', 'include-content-filters');
                }
                array_push($selected, ...$part);
            }
            return ['content' => implode("\n", $selected)] + ($start === null ? [] : ['startingLineNumber' => $start]);
        }
        $start = 0;
        $firstText = $filter['startAt'] ?? $filter['startAfter'] ?? null;
        if ($firstText) {
            $start = self::find($lines, $firstText);
            if ($start === -1) {
                $parser->warn('Could not find starting line including "' . $firstText . '"', 'include-content-filters');
                $start = 0;
            }
        }
        if (!empty($filter['startAfter'])) {
            ++$start;
        }
        $end = $total;
        $lastText = $filter['endAt'] ?? $filter['endBefore'] ?? null;
        if ($lastText) {
            $end = self::find(array_slice($lines, $start + 1), $lastText);
            if ($end === -1) {
                $parser->warn('Could not find ending line including "' . $lastText . '"', 'include-content-filters');
                $end = $total;
            } else {
                $end += $start + (!empty($filter['endAt']) ? 1 : 0);
            }
        }
        return ['content' => implode("\n", array_slice($lines, $start, max(0, $end + 1 - $start))), 'startingLineNumber' => $start + 1];
    }

    /** @param list<string> $lines */
    private static function find(array $lines, string $needle): int
    {
        foreach ($lines as $index => $line) {
            if (str_contains($line, $needle)) {
                return $index;
            }
        }
        return -1;
    }
}
