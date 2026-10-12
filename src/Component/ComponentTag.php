<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Component;

/** Structural scanner for the legacy component syntax, with quoted attributes. */
final class ComponentTag
{
    /**
     * @return array{name: string, attributes: array<string, string>, length: int, error: bool}|null
     */
    public static function parse(string $source): ?array
    {
        if (!str_starts_with($source, '<$')) {
            return null;
        }
        $quote = null;
        $end = null;
        for ($i = 2, $size = strlen($source); $i < $size; ++$i) {
            if ($quote !== null) {
                if ($source[$i] === $quote) {
                    $quote = null;
                }
            } elseif ($source[$i] === '"' || $source[$i] === "'") {
                $quote = $source[$i];
            } elseif ($source[$i] === '>') {
                $end = $i;
                break;
            }
        }
        $length = $end !== null ? $end + 1 : (strcspn($source, "\n") ?: strlen($source));
        $tag = substr($source, 0, $length);
        preg_match('/^<\$([^\s\/>]+)/', $tag, $nameMatch);
        $name = $nameMatch[1] ?? '';
        $result = ['name' => $name, 'attributes' => [], 'length' => mb_strlen($tag), 'error' => true];
        if ($name === '' || !str_ends_with($tag, '/>') || $quote !== null) {
            return $result;
        }
        $attrs = substr($tag, strlen($name) + 2, -2);
        $offset = 0;
        while ($offset < strlen($attrs)) {
            if (trim(substr($attrs, $offset)) === '') {
                break;
            }
            if (!preg_match('/\G\s+([A-Za-z_:][A-Za-z0-9_.:-]*)\s*=\s*([\'"])(.*?)\2/s', $attrs, $match, 0, $offset)) {
                return $result;
            }
            if (array_key_exists($match[1], $result['attributes'])) {
                return $result;
            }
            $result['attributes'][$match[1]] = html_entity_decode($match[3], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $offset += strlen($match[0]);
        }
        $result['error'] = false;
        return $result;
    }
}
