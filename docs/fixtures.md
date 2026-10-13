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
6. Run `node bin/export-definitions.mjs`, `node bin/export-edge-cases.mjs`, and
   `node bin/export-regressions.mjs`. The last two are supplementary compatibility
   probes evaluated by upstream, including malformed option values and footnotes.
7. Run `composer resources:embed` to update the PHP-packaged runtime catalogs from
   the pinned JSON resources.
8. Run `php bin/source-provenance.php /absolute/path/to/mystmd` and
   `php bin/build-coverage.php`. The latter must report no pending tests.
9. Review fixture hashes, reference diffs, source version pins, licenses, and the
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
The edge-case fixtures also run through `Converter::convert()` from MyST source;
regressions check the returned AST, diagnostics, and parsed-document immutability.

The cyclic-footnote regression retains the original upstream HTML expectation.
Upstream omits a backlink discovered after rendering an earlier footnote; PHP
intentionally completes that backlink. The test compares the rest of the upstream
output and independently checks all backlinks against existing DOM targets.

`FixtureIntegrityTest` verifies each implemented mapping against its actual PHPUnit
data provider key and values, as well as the source fixture identity/content.
Mappings shared by the original backend runners are intentional and allowed; the
referenced executable dataset must still exist and match. Filtered or removed
datasets cannot remain marked implemented merely because a test method exists.

Upstream skips are recorded separately. A failing PHP case must be fixed; it must
never be reclassified as an upstream skip or silently removed from a data provider.
