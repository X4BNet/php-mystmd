<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

/** Collect a balanced HTML element across blank lines without changing its body. */
final class ExtraHtmlParser extends AbstractBlockContinueParser
{
    private HtmlBlock $block;
    private string $source = '';
    private bool $finished = false;

    /** Remember the outer element, allowing nested elements of the same name. */
    public function __construct(private readonly string $tag)
    {
        $this->block = new HtmlBlock(HtmlBlock::TYPE_6_BLOCK_ELEMENT);
    }

    /** Expose the standard raw HTML node. */
    public function getBlock(): HtmlBlock
    {
        return $this->block;
    }

    /** Blank lines remain part of the HTML until its matching closing tag. */
    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        return $this->finished ? null : BlockContinue::at($cursor);
    }

    /** Tokenize quoted attributes and comments before counting matching tags. */
    public function addLine(string $line): void
    {
        $this->source .= ($this->source === '' ? '' : "\n") . $line;
        preg_match_all('~<!--.*?-->|<(/?)(' . $this->tag . ')\b(?:[^>\'\"]|\'[^\']*\'|"[^"]*")*>~is', $this->source, $matches, PREG_SET_ORDER);
        $depth = 0;
        foreach ($matches as $match) {
            if (str_starts_with($match[0], '<!--')) {
                continue;
            }
            $depth += $match[1] === '/' ? -1 : (str_ends_with($match[0], '/>') ? 0 : 1);
        }
        $this->finished = $depth <= 0;
    }

    /** Preserve the exact raw HTML for the caller's rendering policy. */
    public function closeBlock(): void
    {
        $this->block->setLiteral($this->source);
    }
}
