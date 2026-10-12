<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\Parser;

/** MyST roles adapted from myst-roles (MIT; see THIRD_PARTY_NOTICES). */
final class BuiltInRoles
{
    /**
     *
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    public static function apply(array $node, Parser $parser): array
    {
        $prepared = BuiltInOptions::prepare($node, $parser);
        $node = $prepared['node'];
        if (!$prepared['valid']) {
            return $node;
        }
        $name = $node['name'];
        $body = $node['value'];
        $options = $prepared['options'];
        $types = ['sub' => 'subscript', 'subscript' => 'subscript', 'sup' => 'superscript', 'superscript' => 'superscript', 'sc' => 'smallcaps', 'smallcaps' => 'smallcaps', 'u' => 'underline', 'underline' => 'underline', 'del' => 'delete', 'delete' => 'delete', 'strike' => 'delete', 'kbd' => 'keyboard', 'keyboard' => 'keyboard', 'span' => 'span'];
        if (isset($types[$name])) {
            $child = ['type' => $types[$name], 'children' => $parser->parseInline($body)];
            if (isset($options['class'])) {
                $child['class'] = $options['class'];
            }
        } elseif (in_array($name, ['math', 'chem', 'chemicalFormula', 'eval', 'raw:latex', 'raw:tex', 'raw:typst', 'raw:typ'], true)) {
            $child = ['type' => match ($name) {'math' => 'inlineMath', 'chem', 'chemicalFormula' => 'chemicalFormula', 'eval' => 'inlineExpression', default => 'raw'}, 'value' => $body];
            if (isset($options['typst'])) {
                $child['typst'] = $options['typst'];
            }
            if (str_starts_with($name, 'raw:')) {
                $lang = in_array($name, ['raw:latex', 'raw:tex'], true) ? 'tex' : 'typst';
                $child = ['type' => 'raw', 'lang' => $lang, $lang => $body];
            }
        } elseif (in_array($name, ['abbreviation', 'abbr'], true)) {
            $child = ['type' => 'abbreviation'];
            if (preg_match('/^(.*)\(([^()]*)\)$/s', $body, $m)) {
                $body = trim($m[1]);
                $child['title'] = trim($m[2]);
            }
            $child['children'] = [['type' => 'text', 'value' => $body]];
        } elseif (in_array($name, ['ref', 'numref', 'eq', 'doc', 'term', 'download'], true)) {
            $label = $body;
            $text = null;
            if (preg_match('/^(.*?)\s*<([^<>]+)>$/s', $body, $m)) {
                $text = trim($m[1]);
                $label = $m[2];
            }
            $child = ['type' => 'crossReference', 'kind' => $name] + Parser::label($label);
            if ($text !== null) {
                $child['children'] = [['type' => 'text', 'value' => $text]];
            }
        } elseif ($name === 'cite' || str_starts_with($name, 'cite:')) {
            $kind = str_starts_with($name, 'cite:p') || str_contains($name, 'par') || str_starts_with($name, 'cite:alp') ? 'parenthetical' : 'narrative';
            $labels = preg_split('/[,;]/', $body) ?: [];
            $citations = [];
            foreach ($labels as $label) {
                $label = trim($label);
                $attrs = [];
                if (str_starts_with($name, 'cite:year')) {
                    $attrs['partial'] = 'year';
                } elseif (str_starts_with($name, 'cite:author') || str_starts_with($name, 'cite:cauthor')) {
                    $attrs['partial'] = 'author';
                }
                if (preg_match('/^\{([^}]+)\}\s*(.*)$/s', $label, $match)) {
                    $attrs['prefix'] = $match[1];
                    $label = $match[2];
                }
                if (preg_match('/^(.*?)\s*\{([^}]+)\}$/s', $label, $match)) {
                    $attrs['suffix'] = $match[2];
                    $label = $match[1];
                }
                $citations[] = ['type' => 'cite', 'kind' => $kind] + Parser::label(trim($label)) + $attrs;
            }
            if (str_starts_with($name, 'cite:alp')) {
                $node['children'] = $citations;
                return $node;
            }
            $child = count($citations) === 1 && $name === 'cite' ? $citations[0] : ['type' => 'citeGroup', 'kind' => $kind, 'children' => $citations];
        } elseif ($name === 'si') {
            $child = self::si($body);
        } elseif ($name === 'index') {
            $lists = \Splitice\Mystmd\IndexEntries::emptyLists();
            preg_match('/^(.+?)<([^<>]+)>$/', $body, $match);
            \Splitice\Mystmd\IndexEntries::parseLine($match[2] ?? $body, $lists, $parser);
            $node['children'] = [['type' => 'mystTarget', 'indexEntries' => \Splitice\Mystmd\IndexEntries::create($lists, $parser)], ['type' => 'span', 'children' => [['type' => 'text', 'value' => $match[1] ?? $body]]]];
            return $node;
        } else {
            $parser->warn('unknown role: ' . $name, 'role-known', 'error');
            unset($node['options']);
            return $node;
        }
        if (isset($options['label'])) {
            $child += Parser::label($options['label']);
        }
        $node['children'] = [$child];
        return $node;
    }

    /** @return array<string, mixed> */
    private static function si(string $value): array
    {
        if (!preg_match('/([0-9.,eE-]+)\s?<([\\\\a-zA-Z\s]+)>/', $value, $m)) {
            return ['type' => 'si', 'error' => true, 'value' => $value];
        }
        preg_match_all('/\\\\([a-zA-Z]+)/', $m[2], $matches);
        $units = ['ampere' => 'A', 'candela' => 'cd', 'kelvin' => 'K', 'kilogram' => 'kg', 'metre' => 'm', 'meter' => 'm', 'mole' => 'mol', 'second' => 's', 'becquerel' => 'Bq', 'degreeCelsius' => '°C', 'coulomb' => 'C', 'farad' => 'F', 'gray' => 'Gy', 'hertz' => 'Hz', 'henry' => 'H', 'joule' => 'J', 'lumen' => 'lm', 'katal' => 'kat', 'lux' => 'lx', 'newton' => 'N', 'ohm' => 'Ω', 'pascal' => 'Pa', 'radian' => 'rad', 'siemens' => 'S', 'sievert' => 'Sv', 'steradian' => 'sr', 'tesla' => 'T', 'volt' => 'V', 'watt' => 'W', 'weber' => 'Wb', 'astronomicalunit' => 'au', 'bel' => 'B', 'dalton' => 'Da', 'day' => 'd', 'decibel' => 'dB', 'degree' => '°', 'electronvolt' => 'eV', 'hectare' => 'ha', 'hour' => 'h', 'litre' => 'L', 'liter' => 'L', 'arcminute' => '′', 'minute' => 'min', 'arcsecond' => '″', 'neper' => 'Np', 'tonne' => 't', 'yocto' => 'y', 'zepto' => 'z', 'atto' => 'a', 'femto' => 'f', 'pico' => 'p', 'nano' => 'n', 'micro' => 'µ', 'milli' => 'm', 'centi' => 'c', 'deci' => 'd', 'deca' => 'da', 'hecto' => 'h', 'kilo' => 'k', 'mega' => 'M', 'giga' => 'G', 'tera' => 'T', 'peta' => 'P', 'exa' => 'E', 'zetta' => 'Z', 'yotta' => 'Y', 'angstrom' => 'Å'];
        $translated = implode('', array_map(static fn (string $unit): string => $units[$unit] ?? $unit, $matches[1]));
        return ['type' => 'si', 'number' => $m[1], 'unit' => $translated, 'units' => $matches[1], 'alt' => implode(' ', $matches[1]), 'value' => $m[1] . ' ' . $translated];
    }
}
