<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Extension\CommonMark\Parser\Block\ListBlockStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\ListItemParser;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/** Markdown Extra requires a block boundary before a top-level list. */
final class ExtraListStartParser implements BlockStartParserInterface
{
    /** Delegate marker recognition while retaining legacy continuation rules. */
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($parserState->getParagraphContent() !== null) {
            if (!self::insideList($parserState->getActiveBlockParser()->getBlock())) {
                return BlockStart::none();
            }
        }
        $result = (new ListBlockStartParser())->tryStart($cursor, $parserState);
        if ($result === null) {
            return null;
        }
        $parsers = [];
        foreach ($result->getBlockParsers() as $parser) {
            $parsers[] = $parser instanceof ListItemParser ? new ExtraListItemParser($parser) : $parser;
        }
        return BlockStart::of(...$parsers)->at($cursor);
    }

    /** Inspect the core node hierarchy rather than a particular continuation parser. */
    private static function insideList(\League\CommonMark\Node\Node $node): bool
    {
        return $node->parent() instanceof \League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
    }
}
