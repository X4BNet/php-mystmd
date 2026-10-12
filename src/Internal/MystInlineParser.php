<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use Splitice\Mystmd\Parser;

/** Parse roles and math before the ordinary code-span parser. */
final class MystInlineParser implements InlineParserInterface
{
    /** Bind to the current document's semantic parser. */
    public function __construct(private Parser $parser)
    {
    }

    /** Match only syntax introducers; quoted code is consumed by CommonMark. */
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::oneOf('{', '$', '@', '[');
    }

    /** Parse a complete inline construct or leave the cursor untouched. */
    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $source = $cursor->getRemainder();
        if ($this->parser->enabled('citations') && in_array($source[0] ?? '', ['@', '['], true)) {
            return CitationParser::parse($inlineContext);
        }
        $ast = null;
        if (preg_match('/^\{\s*([^}]+?)\s*\}(`+)(?!`)(.+?)(?<!`)\2(?!`)/s', $source, $m)) {
            try {
                $header = \Splitice\Mystmd\InlineAttributes::parse($m[1]);
                if ($header['name'] === null) {
                    throw new \InvalidArgumentException('A role name is required');
                }
                $ast = ['type' => 'mystRole', 'name' => $header['name'], 'value' => $m[3]];
                if ($header['options'] !== []) {
                    $ast['options'] = $this->parser->parseInlineOptions(trim(substr(trim($m[1]), strlen($header['name']))));
                }
                $ast = $this->parser->role($ast);
            } catch (\InvalidArgumentException) {
                $ast = ['type' => 'mystRoleError', 'value' => $m[3]];
            }
        } elseif ($this->parser->mathEnabled('dollarmath') && preg_match('/^\$\$(.+?)\$\$/s', $source, $m)) {
            $ast = ['type' => 'math', 'value' => trim($m[1])];
        } elseif ($this->parser->mathEnabled('dollarmath') && preg_match('/^\$(?![\s$])((?:\\\\.|[^$\\\\])+?)(?<!\s)\$(?!\d)/s', $source, $m)) {
            $ast = ['type' => 'inlineMath', 'value' => $m[1]];
        }
        if ($ast === null) {
            return false;
        }
        if ($ast['type'] === 'mystRole') {
            $column = Parser::columnLength(mb_substr($cursor->getLine(), 0, $cursor->getPosition()));
            $ast['position'] = ['start' => ['line' => 1, 'column' => $column + 1], 'end' => ['line' => 1 + substr_count($m[0], "\n"), 'column' => $column + Parser::columnLength($m[0]) + 1]];
        }
        $cursor->advanceBy(mb_strlen($m[0]));
        $inlineContext->getContainer()->appendChild(new AstInline($ast));
        return true;
    }
}
