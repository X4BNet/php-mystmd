<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Node\Block\AbstractBlock;

/** Internal carrier for syntax that CommonMark does not understand. */
final class AstBlock extends AbstractBlock
{
    public string $body = '';

    /** @param array<string, mixed> $ast */
    public function __construct(public array $ast, public ?\Closure $bodyParser = null, public bool $relativeChildren = false)
    {
        parent::__construct();
    }
}
