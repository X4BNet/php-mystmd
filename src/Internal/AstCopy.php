<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

/** Copy the JSON-compatible values owned by a document, without serializing them. */
final class AstCopy
{
    /**
     * @phpstan-template T
     * @phpstan-param T $value
     * @phpstan-return T
     */
    public static function copy(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $child) {
                $result[$key] = self::copy($child);
            }
            return $result;
        }
        if ($value instanceof \stdClass) {
            $result = new \stdClass();
            foreach (get_object_vars($value) as $key => $child) {
                $result->$key = self::copy($child);
            }
            return $result;
        }
        return $value;
    }
}
