<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\Parser;

/** CSV directive parsing with quoted multiline fields and strict row widths. */
final class CsvTable
{
    /** @param array<string, mixed> $options
     * @return list<list<string>>
     */
    public static function records(string $source, array $options): array
    {
        $delimiter = match ($options['delim'] ?? ',') { 'tab' => "\t", 'space' => ' ', default => $options['delim'] ?? ',' };
        $quote = $options['quote'] ?? '"';
        $escape = $options['escape'] ?? '"';
        if ($delimiter === '' || strlen($quote) !== 1 || strlen($escape) !== 1) {
            throw new \InvalidArgumentException('Invalid CSV dialect');
        }
        $records = $row = [];
        $field = '';
        $quoted = $closed = $started = false;
        $length = strlen($source);
        for ($index = 0; $index < $length; ++$index) {
            $char = $source[$index];
            if ($quoted) {
                if ($char === $escape && isset($source[$index + 1]) && in_array($source[$index + 1], [$quote, $escape], true)) {
                    $field .= $source[++$index];
                } elseif ($char === $quote) {
                    $quoted = false;
                    $closed = true;
                } else {
                    $field .= $char;
                }
                continue;
            }
            if (substr($source, $index, strlen($delimiter)) === $delimiter) {
                $row[] = $field;
                $field = '';
                $closed = $started = false;
                $index += strlen($delimiter) - 1;
            } elseif ($char === "\n" || $char === "\r") {
                $row[] = $field;
                $records[] = $row;
                $row = [];
                $field = '';
                $closed = $started = false;
                if ($char === "\r" && ($source[$index + 1] ?? '') === "\n") {
                    ++$index;
                }
            } elseif (!$started && empty($options['keepspace']) && ($char === ' ' || $char === "\t")) {
                continue;
            } elseif ($char === $quote && !$started && !$closed) {
                $quoted = $started = true;
            } elseif ($closed || $char === $quote) {
                throw new \InvalidArgumentException('Invalid quoted CSV field');
            } else {
                $field .= $char;
                $started = true;
            }
        }
        if ($quoted) {
            throw new \InvalidArgumentException('Unclosed quoted CSV field');
        }
        if ($row !== [] || $field !== '' || $started || ($source !== '' && str_ends_with($source, $delimiter))) {
            $row[] = $field;
            $records[] = $row;
        }
        $width = count($records[0] ?? []);
        foreach ($records as $record) {
            if (count($record) !== $width) {
                throw new \InvalidArgumentException('Inconsistent CSV row widths');
            }
        }
        return $records;
    }

    /** Parse cell contents in the directive's source context.
     * @param array<string, mixed> $options
     * @return list<array<string, mixed>>
     */
    public static function rows(string $source, array $options, Parser $parser, int $directiveLine, bool $header = false): array
    {
        try {
            $records = self::records($source, $options);
        } catch (\InvalidArgumentException) {
            $parser->warn('csv-table directive ' . ($header ? 'header' : 'body') . ' must be valid CSV-formatted MyST', $header ? 'directive-options-correct' : 'directive-body-correct', 'error');
            return [];
        }
        $rows = [];
        foreach ($records as $index => $record) {
            $cells = [];
            foreach ($record as $value) {
                $parsed = $parser->parseTree($value, $directiveLine + $index)['children'];
                $children = count($parsed) === 1 && $parsed[0]['type'] === 'paragraph' ? $parsed[0]['children'] : [];
                $cells[] = ['type' => 'tableCell', 'children' => $children] + ($header || $index < ($options['header-rows'] ?? 0) ? ['header' => true] : []);
            }
            $rows[] = ['type' => 'tableRow', 'children' => $cells];
        }
        return $rows;
    }
}
