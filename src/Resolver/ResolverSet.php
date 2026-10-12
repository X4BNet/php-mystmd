<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

/** Explicit capabilities granted by the embedding application. */
final readonly class ResolverSet
{
    /** Supply only the capabilities appropriate to the current document profile. */
    public function __construct(
        public ?IncludeResolverInterface $includes = null,
        public ?ReferenceResolverInterface $references = null,
        public ?CitationResolverInterface $citations = null,
        public ?ResourceResolverInterface $resources = null,
        public int $maxIncludeDepth = 16,
    ) {
        if ($maxIncludeDepth < 1) {
            throw new \InvalidArgumentException('Maximum include depth must be positive');
        }
    }
}
