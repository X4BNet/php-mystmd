<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** MyST inline attribute grammar, shared by directive and role headers. */
final class InlineAttributes
{
    /** @return list<array<string, string>> */
    public static function tokenize(string $header): array
    {
        $pattern = <<<'REGEX'
~(\.[A-Za-z0-9_-]+(?:\s|$))|(\#[A-Za-z0-9_-]+(?:\s|$))|([a-zA-Z0-9_-]+)="((?:\\.|[^\\"])*)"(?:\s|$)|([a-zA-Z0-9_-]+)=([^\s"']+)(?:\s|$)|([A-Za-z0-9_:-]+)(?:\s|$)|([^\s]+)~
REGEX;
        preg_match_all($pattern, $header, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
        $tokens = [];
        foreach ($matches as $m) {
            if ($m[1] !== null) {
                $tokens[] = ['kind' => 'class', 'value' => trim(substr($m[1], 1))];
            } elseif ($m[2] !== null) {
                $tokens[] = ['kind' => 'id', 'value' => trim(substr($m[2], 1))];
            } elseif ($m[3] !== null) {
                $tokens[] = ['kind' => 'attr', 'key' => $m[3], 'value' => trim(str_replace('\\"', '"', $m[4]))];
            } elseif ($m[5] !== null) {
                $tokens[] = ['kind' => 'attr', 'key' => $m[5], 'value' => trim($m[6])];
            } elseif ($m[7] !== null) {
                $tokens[] = ['kind' => 'bare', 'value' => trim($m[7])];
            } else {
                $tokens[] = ['kind' => 'unknown', 'value' => trim($m[8])];
            }
        }
        return $tokens;
    }

    /** @return array{name: ?string, options: array<string, string>} */
    public static function parse(string $header): array
    {
        $tokens = self::tokenize($header);
        if ($tokens === []) {
            throw new \InvalidArgumentException('No inline tokens found');
        }
        $name = null;
        if ($tokens[0]['kind'] === 'bare') {
            $name = array_shift($tokens)['value'];
        }
        if (count(array_filter($tokens, static fn (array $t): bool => $t['kind'] === 'id')) > 1) {
            throw new \InvalidArgumentException('Cannot have more than one ID defined');
        }
        if (in_array('bare', array_column($tokens, 'kind'), true)) {
            throw new \InvalidArgumentException('No additional bare tokens allowed after the first token');
        }
        $options = [];
        foreach ($tokens as $token) {
            $kind = $token['kind'];
            $value = $token['value'];
            if ($kind === 'id' && ctype_digit($value[0])) {
                throw new \InvalidArgumentException('ID cannot start with a number: "' . $value . '"');
            }
            if ($kind === 'unknown') {
                $message = 'Unknown token "' . $value . '"';
                if (preg_match('/\.[A-Za-z0-9_.-]+/', $value)) {
                    $message .= '. Classes must be separated by spaces, try "' . trim(str_replace('.', ' .', $value)) . '"';
                }
                throw new \InvalidArgumentException($message);
            }
            $key = $kind === 'attr' ? $token['key'] : ($kind === 'id' ? 'label' : $kind);
            $options[$key] = $key === 'class' ? trim(($options['class'] ?? '') . ' ' . $value) : $value;
        }
        return ['name' => $name, 'options' => $options];
    }
}
