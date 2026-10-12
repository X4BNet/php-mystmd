<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** A recoverable source diagnostic; malformed input does not abort a document. */
final readonly class Diagnostic implements \JsonSerializable
{
    /** @param array<string, mixed>|null $position */
    public function __construct(public string $message, public string $ruleId, public string $severity = 'warning', public ?array $position = null)
    {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
