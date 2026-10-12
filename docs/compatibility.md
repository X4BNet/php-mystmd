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

The supported dependency floor includes Symfony YAML 7.4.20 / 8.1.8 because older
versions lose newlines from empty `|+` block scalars. Phan starts at 6.0.5 to avoid
its earlier transitive Composer patch-plugin requirement. Minimum and current
resolutions are tested independently. The local validation matrix is:

| PHP | Dependencies | ParaTest | PHPStan | Phan |
| --- | --- | --- | --- | --- |
| 8.4.26 | Current | 3,051 tests / 23,828 assertions | Pass | Pass |
| 8.4.26 | Minimum | 3,051 tests / 23,828 assertions | Pass | Pass |
| 8.5.8 | Current | 3,051 tests / 23,828 assertions | Pass | Pass |
| 8.5.8 | Minimum | 3,051 tests / 23,828 assertions | Pass | Pass |

All four runs retain nine upstream skips. The minimum development dependency
resolution emits a transitive `sabre/event` nullable-parameter deprecation; it
does not fail the checks. PHPStan's Composer script sets a 512 MB limit so a
container's default 128 MB limit does not prevent the minimum-version analysis.
Composer strict validation and installation into an independent consumer also
pass. CI repeats the matrix on pushes and pull requests; local results do not
claim a hosted Actions run has occurred.

x4b-site's separately recorded migration review covers all 86 current KB source
files, desktop/mobile and light/dark screenshots, raw HTML, semantic DOM, and
Port/Tunnel behavior. Its per-article report is in that application's
`docs/myst-migration/article-review.md`; its artifacts are separate from the
package's upstream expectations.

Project discovery, notebook execution, the publishing CLI, and non-HTML export
engines are outside this package. External references, resources, citations, and
includes are caller-supplied capabilities. Optional frontend components retain
their MyST AST semantics; application renderers supply interactive HTML behavior.

MyST's parser and HTML serializer are distinct pinned surfaces. For example, parser
front matter is initially a YAML code node; title/metadata extraction is a separate
transform. HTML placeholders and transform-dependent figure behavior follow the
pinned serializer. Consumers can register explicit transforms and renderers.

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
