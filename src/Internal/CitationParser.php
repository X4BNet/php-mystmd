<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Parser\InlineParserContext;
use Splitice\Mystmd\Parser;

/** Pandoc citations, ported from the pinned markdown-it-myst citation grammar. */
final class CitationParser
{
    /** Parse a citation while respecting ordinary links, escapes and code spans. */
    public static function parse(InlineParserContext $context): bool
    {
        $cursor = $context->getCursor();
        $source = $cursor->getRemainder();
        $start = $cursor->getPosition();
        $left = mb_substr($cursor->getLine(), 0, $start);
        $start = Parser::columnLength($left);
        if (preg_match('/\[[^\]]*$/', $left) && preg_match('/^[^\]]*\]\s*[\[(]/', $source)) {
            return false;
        }
        if (preg_match('~(\S+)$~u', $left, $word) && !(str_starts_with($word[1], '<') && str_contains($word[1], '>')) && (str_contains($word[1], '://') || str_starts_with($word[1], 'www.') || preg_match('~^([a-z0-9-]+\.)+[a-z]{2,}(/|$)~i', $word[1]))) {
            return false;
        }
        if ($source[0] === '@') {
            if ($left !== '' && preg_match('/[a-zA-Z0-9]$/', $left)) {
                return false;
            }
            if (!preg_match('~^@((?:[A-Za-z0-9_|{][A-Za-z0-9_:.#$%&+?\x7e/\x2d]*[A-Za-z0-9_|}])|[A-Za-z0-9_])(\s*)(\[([^\]]{1,50})\])?~u', $source, $m)) {
                return false;
            }
            $length = mb_strlen($m[0]) - (isset($m[3]) ? 0 : mb_strlen($m[2]));
            $node = ['type' => 'cite', 'kind' => 'narrative'] + Parser::label(trim($m[1], '{}'));
            if (isset($m[4])) {
                $node['suffix'] = $m[4];
            }
            $node['position'] = self::position($start, $start + Parser::columnLength(mb_substr($source, 0, $length)));
        } else {
            $end = self::closingBracket($source);
            if ($end === null || in_array(mb_substr($source, $end + 1, 1), ['(', '['], true)) {
                return false;
            }
            $parts = explode(';', mb_substr($source, 1, $end - 1));
            $children = [];
            $column = $start + 1;
            foreach ($parts as $part) {
                if (!preg_match('~^([^^-]|[^^].+?)?(-)?@([A-Za-z0-9_][A-Za-z0-9_:.#$%&+?\x7e/\x2d]*)(.+)?$~us', $part, $m)) {
                    return false;
                }
                $cite = ['type' => 'cite', 'kind' => 'parenthetical'] + Parser::label(trim($m[3], '{}'));
                if ($m[2] !== '') {
                    $cite['partial'] = 'year';
                }
                if (trim($m[1]) !== '') {
                    $cite['prefix'] = trim($m[1]);
                }
                $suffix = preg_replace('/^,\s*/', '', trim($m[4] ?? ''));
                if ($suffix !== '') {
                    $cite['suffix'] = $suffix;
                }
                $cite['position'] = self::position($column, $column + Parser::columnLength($part));
                $column += Parser::columnLength($part) + 1;
                $children[] = $cite;
            }
            $node = ['type' => 'citeGroup', 'kind' => 'parenthetical', 'position' => self::position(0, 0), 'children' => $children];
            $length = $end + 1;
        }
        $context->getContainer()->appendChild(new AstInline($node));
        $cursor->advanceBy($length);
        return true;
    }

    /** Find the matching closing bracket, respecting escapes and nested brackets. */
    private static function closingBracket(string $source): ?int
    {
        $depth = 0;
        $escaped = false;
        foreach (mb_str_split($source) as $index => $char) {
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($char === '\\') {
                $escaped = true;
            } elseif ($char === '[') {
                ++$depth;
            } elseif ($char === ']' && --$depth === 0) {
                return $index;
            }
        }
        return null;
    }

    /** @return array<string, array<string, int>> */
    private static function position(int $start, int $end): array
    {
        return ['start' => ['line' => 1, 'column' => $start + 1], 'end' => ['line' => 1, 'column' => $end + 1]];
    }
}
