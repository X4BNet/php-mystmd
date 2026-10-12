<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

/** Rewrites or authorizes resources without implicit downloads. */
interface ResourceResolverInterface
{
    /** Return a resource URL, or null when unavailable. */
    public function resolveResource(string $uri, ResolutionContext $context): ?string;
}
