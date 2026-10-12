# Third-party notices

MyST compatibility is pinned to jupyter-book/mystmd revision
`807672d850695c2863143571f296327b15f203bd`: `myst-parser` 1.7.4,
`myst-to-html` 1.7.4, and `myst-spec` 0.0.6. Parser, role, directive,
transformation, and HTML behavior ports derive from that source. Unmodified
fixtures and their SHA-256 hashes are in `tests/Fixtures/upstream/manifest.json`.
The upstream MIT license, copyright 2024 Project Jupyter, is reproduced in
`tests/Fixtures/upstream/LICENSE`.

The CommonMark machinery is supplied by `league/commonmark` under its BSD-3-Clause
license. `Internal/CoreExtension.php` follows its parser registration order;
the upstream copyright holders include Colin O'Dell and John MacFarlane.
The installed dependency includes the full notice in `vendor/league/commonmark/LICENSE`.

HTML comment escaping follows `hast-util-to-html` (MIT), and include/content
normalization follows the corresponding pinned MyST dependencies. Developer-only
reference exports use the locked npm packages in `tools/reference/`; their lockfile
records versions and integrity hashes. These tools are not runtime dependencies.

Symfony YAML is used under the MIT license. Composer dependencies retain their
own licenses and notices in their distributions. No fixture expectations are
generated from the PHP implementation.

The pure DOI normalization rules are derived from `doi-utils` as locked in
`tools/reference/package-lock.json` (MIT; see `resources/licenses/doi-utils.txt`).
JSON5 widget models are decoded by `colinodell/json5` (MIT, distributed through
Composer with its license). No JavaScript runtime is needed by consumers.
