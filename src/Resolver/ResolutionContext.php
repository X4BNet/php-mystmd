<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

use Splitice\Mystmd\Document;

/** Origin and include ancestry provided to caller-owned resolvers. */
final readonly class ResolutionContext
{
    /** @param list<string> $ancestry */
    public function __construct(public Document $document, public ?string $origin = null, public array $ancestry = [])
    {
    }
}
