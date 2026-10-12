# splitice/mystmd

A PHP 8.4+ MyST Markdown parser and HTML renderer. The package owns its serializable
AST, diagnostic, resolver, and extension APIs; CommonMark is an internal parser dependency.
Compatibility targets `myst-parser` 1.7.4, `myst-to-html` 1.7.4, and `myst-spec` 0.0.6
at upstream revision `807672d850695c2863143571f296327b15f203bd`.

This is an unpublished release candidate. The committed suite passes on PHP 8.4
and 8.5 with minimum and current dependencies. See [compatibility coverage](docs/compatibility.md)
for the exact tested surfaces and scope exclusions before choosing it for a project.

## Installation

Requires PHP 8.4 or later and the `dom`, `intl`, and `mbstring` extensions.
During development, use a Composer path repository:

```json
{
  "repositories": [{
    "type": "path",
    "url": "../php-mystmd",
    "options": {"symlink": true, "versions": {"splitice/mystmd": "dev-main"}}
  }],
  "require": {"splitice/mystmd": "dev-main"}
}
```

After publication, remove the path repository and require the released version.
The path arrangement requires the sibling checkout and is intentionally transitional.

## API

```php
use Splitice\Mystmd\Converter;

$converter = new Converter();
$result = $converter->convert("# Hello\n\n{kbd}`Ctrl` and **MyST**", 'guide.md');
echo $result->html;
$ast = $result->ast;
$diagnostics = $result->diagnostics;

$document = $converter->parse('Some Markdown', 'guide.md');
$result = $converter->render($document);
echo json_encode($document, JSON_THROW_ON_ERROR); // MyST AST
echo json_encode($result, JSON_THROW_ON_ERROR);   // HTML, AST, diagnostics
```

Documents retain source text and optional source origin. Parsing and rendering may
be repeated on the same converter. Rendering copies its input. Diagnostics contain
`message`, `ruleId`, `severity`, and an optional source `position`. Positions use
one-based lines and columns, with upstream JavaScript UTF-16 column units.

Options follow the MyST `extensions`, `markdownit`, and `mdast` groups. For example:

```php
$converter = new Converter([
    'extensions' => ['smartquotes' => false, 'strikethrough' => true],
    'markdownit' => ['linkify' => true],
    'html' => ['headingOffset' => 1, 'focusableCode' => true],
]);
```

The default extensions include tables, footnotes, citations, definition lists,
task lists, colon fences, front matter, block metadata, math, and smart quotes.
Strikethrough is opt-in, matching the pinned upstream parser. Math can also be
configured as `['amsmath' => true, 'dollarmath' => false]`.

Raw HTML parsing is independent of raw HTML rendering. Set `markdownit.html` to
`false` to treat HTML syntax as Markdown text. The HTML renderer omits raw nodes by
default; trusted content can set `html.allowDangerousHtml` to `true`. Active URL
schemes are disabled unless `html.allowDangerousUrls` is explicitly enabled.
These controls are not a replacement for an application HTML sanitizer, especially
when registering trusted component callbacks or custom renderers.

Source front matter initially appears as a YAML code node, as in `myst-parser`.
`Frontmatter::extract()` provides the optional metadata/title transformation.
`Abbreviations::apply()` provides abbreviation expansion. HTML rendering resolves
document-local labels and references. Includes, external references, citations,
and resource rewriting use explicitly supplied resolver interfaces; parsing does
not read files or fetch URLs. See [extensions and resolvers](docs/extensions.md).

## Development

```sh
composer install --no-interaction --no-progress
composer test:paratest
composer phpstan
composer phan
php bin/verify-consumer.php
```

Normal tests use committed fixtures and require neither Node nor upstream access.
CI runs minimum and current dependency resolutions on PHP 8.4 and 8.5.
[Fixture update instructions](docs/fixtures.md) explain the separate reference tools.
The package is [MIT licensed](LICENSE); see [third-party notices](THIRD_PARTY_NOTICES.md).
