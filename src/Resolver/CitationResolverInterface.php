<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

/** Caller-owned bibliography and citation formatting. */
interface CitationResolverInterface
{
    /**
     * @param array<string, mixed> $citation
     * @return array<string, mixed>|null A MyST node; raw HTML still follows renderer controls.
     */
    public function resolveCitation(array $citation, ResolutionContext $context): ?array;
}
