<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

/** Resolved content and its canonical identity, used to detect include cycles. */
final readonly class IncludedSource
{
    /** Keep retrieval and path authorization entirely within the caller's resolver. */
    public function __construct(public string $source, public string $identity)
    {
    }
}
