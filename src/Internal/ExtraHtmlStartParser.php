<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Extension\CommonMark\Parser\Block\HtmlBlockStartParser;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/** Preserve Extra's balanced HTML blocks and inline image/wrapper behavior. */
final class ExtraHtmlStartParser implements BlockStartParserInterface
{
    /** Recognize complete block elements; other tags remain inline Markdown. */
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return null;
        }
        $source = ltrim($cursor->getRemainder());
        if (preg_match('/^<(?:!|\?)/', $source)) {
            return (new HtmlBlockStartParser())->tryStart($cursor, $parserState);
        }
        if (!preg_match('/^<(p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|ins|del|style|section|article|header|footer|nav|aside|figure|details|summary)\b/i', $source, $match)) {
            return null;
        }
        return BlockStart::of(new ExtraHtmlParser(strtolower($match[1])))->at($cursor);
    }
}
