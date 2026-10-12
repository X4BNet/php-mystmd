<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Splitice\Mystmd\Parser;

/** Recognize MyST syntax only where a Markdown block may start. */
final class MystBlockStartParser implements BlockStartParserInterface
{
    /** Bind parser options for this document. */
    public function __construct(private Parser $parser)
    {
    }

    /** Detect directives, targets, comments, block breaks and display math. */
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }
        $source = ltrim($cursor->getRemainder());
        $ast = null;
        $fence = null;
        if (preg_match('/^(`{3,}|~{3,}|:{3,})\s*\{\s*([^}\s]+)([^}]*)\}\s*(.*)$/', $source, $m) && ($m[1][0] !== ':' || $this->parser->enabled('colonFences'))) {
            if ($m[1][0] === '`' && str_contains(substr($source, strlen($m[1])), '`')) {
                return BlockStart::none();
            }
            $ast = ['type' => 'mystDirective', 'name' => $m[2]];
            if (trim($m[3]) !== '') {
                try {
                    $ast['options'] = $this->parser->parseInlineOptions(trim($m[3]));
                } catch (\InvalidArgumentException $exception) {
                    $ast = ['type' => 'mystDirectiveError', 'message' => $exception->getMessage(), '_errorHeader' => $m[2] . $m[3]];
                }
            }
            if ($m[4] !== '' && $ast['type'] === 'mystDirective') {
                $ast['args'] = $m[4];
            }
            $fence = $m[1];
        } elseif ($this->parser->enabled('colonFences') && preg_match('/^(:{3,})\s*(.*)$/', $source, $m)) {
            $ast = ['type' => 'code', 'lang' => explode(' ', trim($m[2]))[0]];
            $fence = $m[1];
        } elseif (preg_match('/^\(([^)]+)\)=\s*$/', $source, $m)) {
            $ast = ['type' => 'mystTarget', 'label' => $m[1]];
        } elseif (preg_match('/^%\s?(.*)$/', $source, $m)) {
            $ast = ['type' => 'comment'];
            if (trim($m[1]) !== '') {
                $ast['value'] = trim($m[1]);
            }
        } elseif ($this->parser->enabled('blocks') && preg_match('/^\+{3,}(?:\s+(.*))?$/', $source, $m)) {
            $ast = ['type' => 'blockBreak'];
            if (!empty($m[1])) {
                $ast['meta'] = $m[1];
            }
        } elseif ($this->parser->mathEnabled('amsmath') && preg_match('/^\\\\begin\{(equation\*?|align\*?|alignat\*?|gather\*?|multline\*?|flalign\*?|eqnarray\*?)\}/', $source, $m)) {
            $ast = ['type' => 'math', '_ams' => $source];
            $fence = '\\end{' . $m[1] . '}';
        } elseif ($this->parser->mathEnabled('dollarmath') && str_starts_with($source, '$$')) {
            if ($parserState->getActiveBlockParser()->getBlock() instanceof \League\CommonMark\Node\Block\Paragraph) {
                return BlockStart::none();
            }
            if (preg_match('/^\$\$(.*?)\$\$(?:\s*\(([^)]+)\))?\s*$/', $source, $m)) {
                $ast = ['type' => 'math', 'value' => trim($m[1])];
                if (isset($m[2])) {
                    $ast += ['label' => $m[2], 'identifier' => mb_strtolower($m[2])];
                }
            } elseif (trim($source) === '$$') {
                $ast = ['type' => 'math'];
                $fence = '$$';
            }
        }
        if ($ast === null) {
            return BlockStart::none();
        }
        $indent = $cursor->getIndent();
        $cursor->advanceToEnd();
        return BlockStart::of(new MystBlockParser(new AstBlock($ast), $fence, $indent))->at($cursor);
    }
}
