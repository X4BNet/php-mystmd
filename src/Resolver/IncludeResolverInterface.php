<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

/** Resolves includes without giving the parser implicit filesystem/network access. */
interface IncludeResolverInterface
{
    /** Return authorized content, or null when the requested source is unavailable. */
    public function resolveInclude(string $target, ResolutionContext $context): ?IncludedSource;
}
