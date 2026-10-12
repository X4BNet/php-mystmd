<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Node\Inline\AbstractInline;

/** Internal carrier for a parsed MyST inline node. */
final class AstInline extends AbstractInline
{
    /** @param array<string, mixed> $ast */
    public function __construct(public array $ast)
    {
        parent::__construct();
    }
}
