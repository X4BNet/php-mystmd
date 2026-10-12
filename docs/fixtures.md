# Updating pinned fixtures

Normal PHP tests read committed fixtures and never fetch upstream or run Node.
Keep original fixture bytes, configuration, warnings, AST, and HTML expectations.
Do not update expectations using results from the PHP implementation.

1. Check out `jupyter-book/mystmd` at revision
   `807672d850695c2863143571f296327b15f203bd` in a separate directory.
2. Install developer-only dependencies with
   `npm ci --prefix tools/reference --ignore-scripts --no-fund --no-audit`.
3. Set `MYST_REFERENCE_NODE_MODULES` to the absolute path of
   `tools/reference/node_modules`.
4. Run `php bin/import-upstream.php /absolute/path/to/mystmd`.
5. Run each exporter with that upstream directory:
   `bin/export-reference.mjs`, `bin/export-behavior.mjs`, and
   `bin/inventory-upstream.mjs` using Node. The first two execute the original
   upstream assertions while recording their inputs and expected results.
6. Run `node bin/export-definitions.mjs` and `node bin/export-edge-cases.mjs`.
   The latter is a set of supplementary compatibility probes evaluated by upstream.
7. Run `php bin/source-provenance.php /absolute/path/to/mystmd` and
   `php bin/build-coverage.php`. The latter must report no pending tests.
8. Review fixture hashes, reference diffs, source version pins, licenses, and the
   test coverage manifest. Run the complete PHP test and analysis suites.

`tests/Fixtures/upstream/manifest.json` records copied fixture hashes.
`resources/upstream-source-checksums.json` records the source inputs used for the
ports. The npm lockfile records dependency versions and registry integrity hashes.
Changing the compatibility revision requires reviewing all of these together.

The reference harness normalizes undefined JavaScript object properties using JSON
serialization. Random identifiers are constrained by the original test assertions
and normalized only in tests whose source checks type/presence rather than value.
Upstream HTML fixture normalization is preserved. The end-to-end CommonMark runner
retains whitespace checks, and supplementary cases check exact text inside `pre`,
`code`, and `textarea` before applying upstream serialization normalization.

Upstream skips are recorded separately. A failing PHP case must be fixed; it must
never be reclassified as an upstream skip or silently removed from a data provider.
