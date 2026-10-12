<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use Splitice\Mystmd\Parser;

/** Adapts package-owned inline parser hooks to the internal tokenizer. */
final class CustomInlineParser implements InlineParserInterface
{
    /** Bind one parser hook to the current converter. */
    public function __construct(private readonly string $trigger, private readonly \Closure $parse, private readonly Parser $parser)
    {
    }

    /** Return the caller-declared syntax introducer. */
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::string($this->trigger);
    }

    /** Consume a complete match, leaving unmatched text available to other parsers. */
    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $match = ($this->parse)($cursor->getRemainder(), $this->parser);
        if ($match === null) {
            return false;
        }
        $column = Parser::columnLength(mb_substr($cursor->getLine(), 0, $cursor->getPosition())) + 1;
        $value = mb_substr($cursor->getRemainder(), 0, $match->length);
        $ast = $match->node;
        $ast['position'] ??= ['start' => ['line' => 1, 'column' => $column], 'end' => ['line' => 1 + substr_count($value, "\n"), 'column' => $column + Parser::columnLength($value)]];
        $cursor->advanceBy($match->length);
        $inlineContext->getContainer()->appendChild(new AstInline($ast));
        return true;
    }
}
