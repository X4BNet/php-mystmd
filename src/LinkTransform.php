<?php

declare(strict_types=1);

namespace Splitice\Mystmd;

/** Optional DOI, GitHub and wiki link normalization; never fetches remote data. */
final class LinkTransform
{
    private string $wikiUrl;
    private ?string $language;

    /** @param array<string, mixed> $options */
    public function __construct(public readonly string $protocol, private readonly array $options = [])
    {
        if (!in_array($protocol, ['doi', 'github', 'wiki'], true)) {
            throw new \InvalidArgumentException('Unknown link protocol: ' . $protocol);
        }
        $this->wikiUrl = self::removeWiki($options['url'] ?? ('https://' . ($options['lang'] ?? 'en') . '.wikipedia.org/'), '/');
        preg_match('~^(?:https?://)?(?:([a-z]+)\.)?wikipedia\.org/wiki/(.+)$~', $this->wikiUrl . 'wiki/x', $match);
        $this->language = $options['lang'] ?? ($match[1] ?? null);
    }

    /** Recognize a DOI while making inference from publisher URLs opt-in.
     * @param array<string, mixed> $options
     */
    public static function isRecognizedDoi(?string $uri, array $options = []): bool
    {
        if (!$uri) {
            return false;
        }
        $host = parse_url($uri, PHP_URL_HOST);
        if (empty($options['inferDoisFromUrls']) && $host && !in_array(strtolower($host), ['doi.org', 'www.doi.org', 'dx.doi.org'], true)) {
            return false;
        }
        return self::doi($uri) !== null;
    }

    /** Recognize a supported link without changing it. */
    public function test(?string $uri): bool
    {
        if (!$uri) {
            return false;
        }
        return match ($this->protocol) {
            'doi' => str_starts_with($uri, 'doi:') || self::isRecognizedDoi($uri, $this->options),
            'github' => str_starts_with(self::withoutHttp($uri), 'github.com/'),
            'wiki' => str_starts_with($uri, 'wiki:') || ((preg_match('~^(?:https?://)?(?:[a-z]+\.)?wikipedia\.org/wiki/.+$~', $uri) || str_starts_with(self::withoutHttp($uri), self::withoutHttp($this->wikiUrl))) && !preg_match('~/w/index\.php(?:\?[^/]*)?$~', $uri)),
            default => false,
        };
    }

    /** Extract a wiki page name and base URL.
     * @return array<string, string>|null
     */
    public function pageName(string $uri): ?array
    {
        $base = ['wiki' => $this->wikiUrl];
        if ($this->language !== null && $this->language !== '') {
            $base['lang'] = $this->language;
        }
        if (str_starts_with($uri, 'wiki:')) {
            return ['page' => trim(substr($uri, 5))] + $base;
        }
        if (str_starts_with(self::withoutHttp($uri), self::withoutHttp($this->wikiUrl))) {
            return ['page' => self::removeWiki(substr(self::withoutHttp($uri), strlen(self::withoutHttp($this->wikiUrl))))] + $base;
        }
        if (!preg_match('~^(?:https?://)?(?:([a-z]+)\.)?wikipedia\.org/wiki/(.+)$~', $uri, $match)) {
            return null;
        }
        return ['page' => $match[2], 'wiki' => 'https://' . ($match[1] ?: 'en') . '.wikipedia.org/'] + ($match[1] !== '' ? ['lang' => $match[1]] : []);
    }

    /** Normalize link metadata and fill empty display text.
     * @param array<string, mixed> $link
     */
    public function transform(array &$link, Parser $parser): bool
    {
        $uri = $link['urlSource'] ?? $link['url'];
        $title = '';
        if ($this->protocol === 'doi') {
            $doi = self::doi($uri);
            if ($doi === null) {
                $parser->warn('DOI is not valid: ' . $uri, 'doi-link-valid', 'error');
                return false;
            }
            $link['url'] = 'https://doi.org/' . $doi;
            $data = ['doi' => $doi];
        } elseif ($this->protocol === 'wiki') {
            $result = $this->pageName($uri);
            if ($result === null || preg_match('/\s/u', $result['page'])) {
                $parser->warn('Wikipedia pagenames should not contain spaces in link: ' . $uri, 'wikipedia-link-valid');
                if ($result === null) {
                    return false;
                }
            }
            if (str_contains($result['page'], '/')) {
                $parser->warn('Wikipedia pagenames should not contain "/" in link: ' . $uri, 'wikipedia-link-valid', 'error');
                return false;
            }
            $page = preg_replace('/\s+/u', '_', $result['page']) ?? $result['page'];
            $page = preg_replace('/_{2,}/', '_', $page, 1) ?? $page;
            $page = preg_replace('/^_|_$/', '', $page) ?? $page;
            $link['url'] = $result['wiki'] . 'wiki/' . $page;
            $data = array_replace($result, ['page' => $page]);
            $title = str_replace('_', ' ', $page);
        } else {
            $url = parse_url('https://' . self::withoutHttp($uri));
            if (!$url || ($url['host'] ?? '') !== 'github.com') {
                return false;
            }
            if (preg_match('~^/([^/]+)/([^/]+)/(?:issues|pull)/([0-9]+)~', $url['path'] ?? '', $match)) {
                $data = ['kind' => 'issue', 'org' => $match[1], 'repo' => $match[2], 'issue_number' => $match[3]];
                $title = $match[1] . '/' . $match[2] . '#' . $match[3];
            } elseif (preg_match('~^/([^/]+)/([^/]+)/blob/([^/]+)/(.+)~', $url['path'] ?? '', $match)) {
                $title = $match[4];
                $data = ['kind' => 'file', 'org' => $match[1], 'repo' => $match[2], 'reference' => $match[3], 'file' => $match[4], 'raw' => 'https://raw.githubusercontent.com/' . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $match[4]];
                if (preg_match('/L([0-9]+)(?:-L([0-9]+))?/', $url['fragment'] ?? '', $lines)) {
                    $data['from'] = (int)$lines[1];
                    if (isset($lines[2])) {
                        $data['to'] = (int)$lines[2];
                    }
                }
            } else {
                return false;
            }
        }
        $link['data'] = array_replace($link['data'] ?? [], $data);
        $link['internal'] = false;
        if ($title !== '' && (empty($link['children']) || $link['children'] === [['type' => 'text', 'value' => $link['urlSource'] ?? $link['url']]])) {
            $link['children'] = [['type' => 'text', 'value' => $title]];
        }
        return true;
    }

    /** Add line-break opportunities to long URL display text.
     * @param array<string, mixed> $link
     */
    public static function formatText(array &$link): void
    {
        if (count($link['children'] ?? []) !== 1 || ($link['children'][0]['type'] ?? '') !== 'text') {
            return;
        }
        $url = $link['children'][0]['value'];
        if (mb_strlen($url) < 20 || preg_match('/\s/u', $url) || str_contains($url, "\u{200b}")) {
            return;
        }
        $parts = array_map(static function (string $part): string {
            $part = preg_replace('~([/\~.,\-_?#%])~u', "\u{200b}" . '$1', $part) ?? $part;
            return preg_replace('/([=&])/u', "\u{200b}" . '$1' . "\u{200b}", $part) ?? $part;
        }, explode('//', $url));
        $link['children'][0]['value'] = preg_replace('/\x{200b}(.{1,2})$/u', '$1', implode("//\u{200b}", $parts));
    }

    /** Expose the serializable configuration used by upstream link transforms.
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        $state = ['protocol' => $this->protocol];
        if ($this->protocol === 'doi' && $this->options !== []) {
            $state['doiOpts'] = $this->options;
        }
        if ($this->protocol === 'wiki') {
            $state += ['formatsText' => true, 'wikiUrl' => $this->wikiUrl];
            if ($this->language !== null && $this->language !== '') {
                $state['lang'] = $this->language;
            }
        }
        return $state;
    }

    /** Remove the first HTTP scheme, matching upstream URL recognition. */
    private static function withoutHttp(string $uri): string
    {
        return preg_replace('~https?://~', '', $uri, 1) ?? $uri;
    }

    /** Normalize a wiki base path or page path. */
    private static function removeWiki(string $uri, string $replacement = ''): string
    {
        $uri = preg_replace('~/?(wiki/?)?$~', $replacement, $uri, 1) ?? $uri;
        return preg_replace('~^/?wiki/~', '', $uri) ?? $uri;
    }

    /** Normalize DOI syntax using the pinned doi-utils resolver rules. */
    private static function doi(string $uri): ?string
    {
        $pattern = '~^10\.\d{4,9}/[-._;()/:A-Z0-9]+$~iD';
        $raw = str_starts_with($uri, 'doi:') ? substr($uri, 4) : $uri;
        if (preg_match($pattern, $raw)) {
            return $raw;
        }
        $url = parse_url(str_starts_with($uri, 'http') ? $uri : 'http://' . $uri);
        if (!$url || empty($url['host'])) {
            return null;
        }
        $host = strtolower($url['host']);
        $path = $url['path'] ?? '';
        if (preg_match('/(?:dx\.)?(?:www\.)?doi\.org/', $host)) {
            return ltrim($path, '/') ?: null;
        }
        $parts = array_values(array_filter(explode('/', $path), static fn (string $p): bool => $p !== ''));
        foreach ($parts as $index => $part) {
            $candidate = $part . '/' . ($parts[$index + 1] ?? '');
            if (preg_match($pattern, $candidate)) {
                return str_ends_with($host, 'biorxiv.org') ? preg_replace('/v\d+(\.[a-zA-Z-]+)?$/', '', $candidate) : $candidate;
            }
        }
        if (str_ends_with($host, 'elifesciences.org') && preg_match('~^/(?:articles|reviewed-preprints)/(\d+)$~D', $path, $match)) {
            return '10.7554/eLife.' . $match[1];
        }
        if (str_ends_with($host, 'zenodo.org') && preg_match('~^/(?:record|badge/latestdoi)/(.+)~', $path, $match) && preg_match($pattern, '10.5281/zenodo.' . $match[1])) {
            return '10.5281/zenodo.' . $match[1];
        }
        parse_str($url['query'] ?? '', $query);
        return is_string($query['id'] ?? null) && preg_match($pattern, $query['id']) ? $query['id'] : null;
    }
}
