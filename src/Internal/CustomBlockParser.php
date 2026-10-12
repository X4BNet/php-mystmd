<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Splitice\Mystmd\Parser;

/** Adapts application block grammars without exposing CommonMark in the public API. */
final class CustomBlockParser implements BlockStartParserInterface
{
    /** Bind one block grammar to the current parser. */
    public function __construct(private readonly \Closure $parse, private readonly Parser $parser)
    {
    }

    /** Recognize an unindented block opener and collect its optional fenced body. */
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }
        $match = ($this->parse)(ltrim($cursor->getRemainder()), $this->parser);
        if ($match === null) {
            return BlockStart::none();
        }
        $block = new AstBlock($match->node, $match->bodyParser, true);
        $indent = $cursor->getIndent();
        $cursor->advanceToEnd();
        return BlockStart::of(new MystBlockParser($block, $match->fence, $indent))->at($cursor);
    }
}
