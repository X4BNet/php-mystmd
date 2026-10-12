<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Resolver;

/**
 * Resolve references that cannot be satisfied by targets in the current document.
 * @phpstan-import-type ReferenceResult from \Splitice\Mystmd\Types
 */
interface ReferenceResolverInterface
{
    /**
     * @return array{url: string, title?: string, children?: list<array<string, mixed>>}|null
     * @phpstan-return ReferenceResult|null
     */
    public function resolveReference(string $identifier, string $kind, ResolutionContext $context): ?array;
}
