# Compatibility status

This is an unpublished release candidate with the validation results below.
The pinned targets are `myst-parser` 1.7.4, `myst-to-html` 1.7.4, and `myst-spec` 0.0.6
at `807672d850695c2863143571f296327b15f203bd`.

The committed suites cover the specification YAML AST and HTML fixtures, parser
roles/directives, handwritten parser tests and positions, optional first-party
language extensions, HTML utilities, CommonMark end-to-end conversion, selected
language transforms, include filters, front matter extraction, components,
resolver failures, and supplemental malformed/Unicode/escaping cases.

The manifest in `tests/upstream-coverage.json` accounts for all 3,999 original
registrations: 3,448 have PHP equivalents, 545 have explicit scope exclusions,
and six were skipped upstream. Both original CommonMark backend runners map to
the same public PHP pipeline. Four additional upstream MyST example filters and
five CommonMark skips are preserved in the executable PHP suite; no PHP failures
are marked as skipped. Fixture checksums and manifest completeness are CI gates.
These counts describe test registrations, not code coverage or total MyST feature
coverage. Provider dataset identities and fixture contents are verified too.

The supported dependency floor includes Symfony YAML 7.4.20 / 8.1.8 because older
versions lose newlines from empty `|+` block scalars. Phan starts at 6.0.5 to avoid
its earlier transitive Composer patch-plugin requirement. Minimum and current
resolutions are tested independently. The local validation matrix is:

| PHP | Dependencies | ParaTest | PHPStan | Phan |
| --- | --- | --- | --- | --- |
| 8.4.26 | Current | 3,173 tests / 24,639 assertions | Pass | Pass |
| 8.4.26 | Minimum | 3,173 tests / 24,639 assertions | Pass | Pass |
| 8.5.8 | Current | 3,173 tests / 24,639 assertions | Pass | Pass |
| 8.5.8 | Minimum | 3,173 tests / 24,639 assertions | Pass | Pass |

All four runs retain nine upstream skips. The minimum development dependency
resolution emits a transitive `sabre/event` nullable-parameter deprecation; it
does not fail the checks. PHPStan's Composer script sets a 512 MB limit so a
container's default 128 MB limit does not prevent the minimum-version analysis.
Composer strict validation and installation into an independent consumer also
pass. CI repeats the matrix on pushes and pull requests; local results do not
claim a hosted Actions run has occurred.

Project discovery, notebook execution, the publishing CLI, and non-HTML export
engines are outside this package. External references, resources, citations, and
includes are caller-supplied capabilities. Optional frontend components retain
their MyST AST semantics; application renderers supply interactive HTML behavior.

MyST's parser and HTML serializer are distinct pinned surfaces. Parser front matter
is initially a YAML code node; title/metadata extraction remains a separate transform.
`html.profile: upstream` preserves the pinned serializer's placeholders and limitations.
`html.profile: semantic` deliberately improves the HTML behavior listed below.
Both profiles recover from malformed optional captions/numbering and render the
complete reachable footnote graph. Cyclic backlinks intentionally fix an upstream
serializer bug; the independent upstream fixture and the additional DOM assertions
document that difference.

## Feature matrix

| Surface | Parsing / AST | Default HTML (`upstream`) | Semantic HTML | Optional transforms / capabilities |
| --- | --- | --- | --- | --- |
| CommonMark and core MyST inline/block syntax | Supported by pinned fixtures | Pinned serializer | Same core output, labeled block anchors | Parser options control syntax |
| Roles and directives | Built-ins plus registered definitions | Pinned handlers | Same syntax; shared safe option validation | Registry definitions and custom renderers |
| Dropdowns | Details, summary, open state | Empty placeholder | Native details/summary, retained body and open | No frontend needed |
| Figures / code captions | Containers and captions | Transform-dependent layout | Normalized captions and numbering | Explicit caller numbering preserved in semantic mode |
| Tables | Header flags retained | First row treated as header | Per-cell flags, multiple/no header rows | Alignment options still apply |
| Admonitions | Title and body retained | Pinned title behavior | One title | Custom renderer can replace output |
| Local cross-references | Roles, fragments, citation shorthand | Partial local resolution | Role references, automatic fragment text, local `@label` before citation fallback | Targets indexed after includes |
| Footnotes | Definitions and references | Reachable chains/cycles, complete backlinks | Same | No resolver needed |
| Front matter | YAML code node | Literal YAML until extracted | Same opt-in extraction | `Frontmatter::extract()` |
| Includes | Include node and filter options | Placeholder without resolver | Readable fallback and diagnostic without resolver | Explicit include resolver; nested origins and cycle/depth checks |
| External references / citations / resources | Reference nodes and URIs | Caller-provided resolution | Local targets take precedence; unresolved references remain readable with diagnostics | Explicit resolver interfaces; no automatic I/O |
| Math | Inline/display source | Escaped source, no typesetting | Same; local equation references | `MathTransforms`, `Numbering`; no KaTeX engine |
| Tabs, cards, reactive nodes, widgets | Optional first-party extensions | Pinned/default placeholders | Readable content/source with diagnostic when unsupported | Application renderer/frontend required for interaction |
| Raw HTML | Parser option | Omitted unless explicitly allowed | Same | Trusted applications control HTML/URL policy |
| Abbreviations, heading depth, link rewrites, ToC | Optional transforms | Caller chooses transforms | Caller chooses transforms | Named recipes in `extensions.md` |
| Notebook execution, publishing, non-HTML exports | Out of scope | Out of scope | Out of scope | No automatic filesystem/network access or frontend execution |

Semantic rendering orders include expansion, structural normalization, target
indexing/numbering, local resolution, external resolution, and serialization.
Custom renderer overrides retain precedence. The returned AST is the working tree
passed to serialization, while the caller's parsed document stays unchanged in
both profiles. Render-time diagnostics accumulate on the result, preserving source
and origin. See [validation](validation.md) for measured line/branch coverage and
performance reporting.

Explicit exclusions cover project/publication metadata normalization, remote
MyST and Sphinx inventory adapters delegated to caller resolvers, and KaTeX
engine typesetting. They are listed per test with reasons. The pinned HTML
serializer emits math source, not KaTeX markup. DOI, wiki and GitHub URL transforms
are available without network access through `LinkTransform`. `Numbering`,
`TableOfContents`, `MathTransforms`, `Frontmatter` and `LanguageTransforms` expose
optional document transforms without project discovery.

`MarkdownExtraExtension` supplies the x4b compatibility profile: headings without
a separating space, heading attributes, legacy code classes, table alignment and
list output, and literal punctuation/currency/email text. It is opt-in. Raw HTML
requires a separate caller setting. Components are independently opt-in and never
execute in fenced, indented or inline code.
