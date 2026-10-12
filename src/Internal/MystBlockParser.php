<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

/** Collect a MyST block, leaving nested Markdown to a fresh parse context. */
final class MystBlockParser extends AbstractBlockContinueParser
{
    private bool $first = true;
    /** @var list<string> */
    private array $lines = [];

    /** Create a collector; a null fence denotes a single-line block. */
    public function __construct(private AstBlock $block, private ?string $fence = null, private int $indent = 0)
    {
    }

    /** Return the collected block. */
    public function getBlock(): AstBlock
    {
        return $this->block;
    }

    /** Continue until a matching closing delimiter or a single-line boundary. */
    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($this->fence === null) {
            return BlockContinue::none();
        }
        $line = trim($cursor->getRemainder());
        if (str_starts_with($this->fence, '\\end{')) {
            if ($line === $this->fence) {
                $this->lines[] = $line;
                return BlockContinue::finished();
            }
            return BlockContinue::at($cursor);
        }
        if (preg_match('/^' . preg_quote($this->fence, '/') . ($this->fence[0] === '$' ? '(?:\s*\(([^)]+)\))?' : preg_quote($this->fence[0], '/') . '*') . '\s*$/', $line, $match)) {
            if (isset($match[1])) {
                $this->block->ast['label'] = $match[1];
                $this->block->ast['identifier'] = mb_strtolower($match[1]);
            }
            return BlockContinue::finished();
        }
        for ($i = 0; $i < $this->indent && $cursor->advanceBySpaceOrTab(); $i++) {
        }
        return BlockContinue::at($cursor);
    }

    /** Collect source without losing code or math whitespace. */
    public function addLine(string $line): void
    {
        if ($this->first) {
            $this->first = false;
            if (isset($this->block->ast['_ams'])) {
                $this->lines[] = $this->block->ast['_ams'];
            }
            return;
        }
        $this->lines[] = $line;
    }

    /** Preserve the body for semantic directive processing. */
    public function closeBlock(): void
    {
        $this->block->body = implode("\n", $this->lines);
    }
}
