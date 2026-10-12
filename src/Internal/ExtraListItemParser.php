<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Parser\Block\FencedCodeParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\ListItemParser;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

/** Extra accepts one-to-four-space continuation indentation and global fences. */
final class ExtraListItemParser extends AbstractBlockContinueParser
{
    /** Retain CommonMark's node and closing bookkeeping. */
    public function __construct(private readonly ListItemParser $delegate)
    {
    }

    /** Return the package-private list item node. */
    public function getBlock(): ListItem
    {
        return $this->delegate->getBlock();
    }

    /** A list item contains nested blocks. */
    public function isContainer(): bool
    {
        return true;
    }

    /** Sibling items belong to the enclosing list. */
    public function canContain(AbstractBlock $childBlock): bool
    {
        return $this->delegate->canContain($childBlock);
    }

    /** Preserve indented legacy content even when its marker has wider padding. */
    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($cursor->isBlank()) {
            return $this->delegate->tryContinue($cursor, $activeBlockParser);
        }
        if ($activeBlockParser instanceof FencedCodeParser || ($cursor->getIndent() > 0 && !preg_match('/^\s*(?:[-+*]|\d+[.)])\s/', $cursor->getRemainder()))) {
            $cursor->advanceBy(min(4, $cursor->getIndent()), true);
            return BlockContinue::at($cursor);
        }
        return $this->delegate->tryContinue($cursor, $activeBlockParser);
    }

    /** Complete the original node's source range. */
    public function closeBlock(): void
    {
        $this->delegate->closeBlock();
    }
}
