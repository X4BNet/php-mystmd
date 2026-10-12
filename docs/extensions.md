# Extensions and resolvers

Every converter owns an isolated registry. Implement `ExtensionInterface::register()`
to register directives, roles, parser hooks, AST transforms, or node renderers.
Callbacks use PHP arrays for MyST nodes and package-owned parser/renderer objects.
No application code needs to depend on CommonMark classes.

```php
use Splitice\Mystmd\Converter;
use Splitice\Mystmd\Definition;
use Splitice\Mystmd\Extension\ExtensionInterface;
use Splitice\Mystmd\Registry;

$extension = new class implements ExtensionInterface {
    /** Register a directive whose body is nested MyST. */
    public function register(Registry $registry): void
    {
        $registry->directiveDefinition(new Definition([
            'name' => 'panel',
            'body' => ['type' => 'myst', 'required' => true],
            'options' => ['class' => ['type' => 'string']],
        ], static fn (array $data): array => [[
            'type' => 'panel',
            'class' => $data['options']['class'] ?? '',
            'children' => $data['body'],
        ]]));
        $registry->renderer('panel', static fn (array $node, $renderer): string =>
            $renderer->tag('section', ['class' => $node['class']], $renderer->children($node['children'])));
    }
};
$converter = new Converter(extensions: [$extension]);
echo $converter->convert("```{panel}\n**Nested Markdown**\n```");
```

`roleDefinition()` uses the same definition schema. Types are `string`, `number`,
`boolean`, and `myst` (also `parsed`). Definitions declare `arg`, `body`, and named
`options`; option definitions support `required` and `alias`. For application
semantics needing direct access to a raw node, use `directive()` or `role()`.

`inlineParser($trigger, $callback)` returns an `InlineMatch` or null. Its length is
the number of Unicode characters consumed, not the byte length. `blockParser()`
returns a `BlockMatch` or null; a block match may provide a closing fence and a body
callback. `Parser::parseInline()` and `Parser::parseBody()` support nested content.
Source coordinates are assigned to matched nodes. `transform()` receives the
parsed `Document`, and `renderer()` receives a node and the current `HtmlRenderer`.
Use `HtmlRenderer::escape()` for literal content and `tag()` for attributes.

For a single-line `BlockMatch`, positions on children parsed with `parseInline()`
are relative to the matched line; the parser rebases their line numbers to the
document. A fenced match's body callback receives a parser carrying the body
offset, so `parseBody()` already returns document-relative positions.

## Optional first-party syntax

```php
use Splitice\Mystmd\Extension\FirstPartyExtension;

$converter = new Converter(extensions: [
    new FirstPartyExtension(['button', 'card', 'exercise', 'grid', 'icon', 'proof', 'reactive', 'tabs']),
]);
```

These packages provide upstream language/AST semantics. An interactive frontend
must register appropriate HTML renderers and client code for interactive nodes.
The default HTML serializer follows `myst-to-html`, including its placeholder output.

## Trusted components

```php
use Splitice\Mystmd\Component\Component;
use Splitice\Mystmd\Component\OutputKind;
use Splitice\Mystmd\Extension\ComponentExtension;
use Splitice\Mystmd\HtmlRenderer;

$converter = new Converter(extensions: [new ComponentExtension([
    new Component('Badge', static fn (array $attributes): string =>
        '<strong>' . HtmlRenderer::escape($attributes['text'] ?? '') . '</strong>', OutputKind::Inline),
])]);
echo $converter->convert('Status: <$Badge text="Available" />');
```

Components default to block output; block components split surrounding paragraphs.
The callback runs during rendering, never during parsing. Only explicitly named
callbacks are available. Returned HTML is trusted application output.
The equivalent directive is `{Badge}` with `:text: Available` as its option.
Quoted legacy attributes support single/double quotes and XML entities. Tags in
code remain examples. Malformed and unknown components produce escaped markers and
diagnostics. Callback exceptions produce `TEMPLATE INVALID` and a render diagnostic.

## Resolver capabilities

Pass a `ResolverSet` as the converter's `resolvers` argument. Its optional members are:

| Capability | Interface | Result |
| --- | --- | --- |
| Includes | `IncludeResolverInterface` | `IncludedSource` with source and canonical identity |
| External references | `ReferenceResolverInterface` | Reference properties, typically `url` and `title` |
| Citations | `CitationResolverInterface` | Replacement MyST AST node |
| Resources | `ResourceResolverInterface` | Rewritten URI |

All receive a `ResolutionContext` containing the document, current origin, and
include ancestry. Return null for unavailable content. Exceptions and unavailable
results produce diagnostics. Include identity checks and the configurable depth
limit bound recursion. Nested resources use the included document's origin.
The package never supplies implicit filesystem or network access. The caller decides
which paths, URL schemes, hosts, and resources its resolvers permit.

Includes support line ranges, start/end markers, literal code, and original line
number matching. References to included document-local labels resolve after inclusion.
Parsed input documents are preserved when resolving and rendering.

## Transform recipes

Use the named transform APIs for application code. `LanguageTransforms::apply()`
and the existing positional upstream-compatible entrypoints remain available.
Registry transforms run during parsing, including parsing included documents;
semantic HTML preparation runs after include expansion. Custom renderer callbacks
take precedence over either profile's built-in node serializer.

### Extract front matter and keep the title

```php
use Splitice\Mystmd\Frontmatter;
use Splitice\Mystmd\Parser;

$parser = new Parser();
$document = $parser->parse("---\ntitle: Guide\n---\n\n# Guide\n\nBody");
$warnings = new Parser();
$metadata = Frontmatter::extract($document->ast, $warnings, [
    'propagateTargets' => true,
    'keepTitleNode' => true,
]);
array_push($document->diagnostics, ...$warnings->diagnostics());
$title = $metadata['frontmatter']['title'];
```

Extraction is explicit in both HTML profiles. Use a separate diagnostic collector
when applying transforms to an existing document to avoid duplicating parse warnings.

### Set figure numbering before semantic rendering

```php
use Splitice\Mystmd\Numbering;
use Splitice\Mystmd\Transforms;

$converter = new Converter(['html' => ['profile' => 'semantic']]);
$document = $converter->parse($source);
Transforms::propagateTargets($document->ast);
$warnings = new Parser();
$numbering = new Numbering([
    'content_includes_title' => true,
    'numbering' => ['figure' => ['enabled' => true, 'start' => 5]],
]);
$numbering->apply($document->ast, $warnings);
array_push($document->diagnostics, ...$warnings->diagnostics());
$result = $converter->render($document);
```

Semantic preparation preserves existing `enumerator` and `enumerated` values. The
configuration above is the explicit per-kind Numbering schema; it does not imply
project/frontmatter configuration normalization or number unseen include content.

### Adjust heading depth

```php
use Splitice\Mystmd\LanguageTransforms;

$warnings = new Parser();
LanguageTransforms::headingDepth($document->ast, $warnings, ['firstDepth' => 2]);
array_push($document->diagnostics, ...$warnings->diagnostics());
```

Shared PHPStan shapes in `Splitice\Mystmd\Types` describe positions, HTML/parser
options, definition schemas, and reference resolver results. Import them with
`@phpstan-import-type` when annotating application extensions; the runtime AST
remains serializable PHP arrays. Definition callbacks receive validated/coerced
values. Malformed content produces diagnostics; programming exceptions raised by
registered definition callbacks propagate to the application.
