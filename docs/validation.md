# Validation and performance

The PHP 8.4/8.5 minimum/current dependency CI matrix runs the complete suite,
PHPStan, Phan, strict Composer validation, fixture integrity, and installation into
an independent Composer consumer. Nine documented upstream skips remain; PHP
failures must never be skipped. Supplemental MyST cases exercise parsing through
HTML, result AST/diagnostics, repeated conversions, and input immutability. Semantic
tests also check DOM structure, local targets, Unicode, escaping, nested includes,
numbering, extension combinations, and whitespace-sensitive content.

## Measured code coverage

The initial baseline was measured on PHP 8.5.8, Xdebug 3.5.3, and PHPUnit 12.5.38
with current dependencies. All 66 PHP source files appear in the report.

| Metric | Covered / total | Baseline |
| --- | --- | --- |
| Lines | 2,948 / 3,155 | 93.4390% |
| Branches | 3,354 / 4,236 | 79.1785% |

With Xdebug installed and enabled on PHP 8.5:

```sh
composer test:coverage
composer coverage:check
```

The dedicated CI job collects line and branch data (`--path-coverage` enables
Xdebug's branch instrumentation), publishes HTML and Clover reports as an Actions
artifact, and checks both ratios against `tests/coverage-baseline.json`. The baseline
stores measured covered/total counts; the gate compares exact ratios without rounded
percentage tolerance. Missing branch data or a different PHP minor fails the gate.
This is code coverage, distinct from the upstream test registration manifest.
Coverage runs install Xdebug's source filter before Composer/PHPUnit loads and
disable CLI OPcache so a previously cached opcode file cannot bypass the filter.
Application and benchmark settings are unaffected.
Branch instrumentation is substantially slower than the normal suite; the coverage
script disables Composer's process timeout while retaining PHPUnit's failure status.

To adopt a reviewed improvement in the measured baseline:

```sh
php bin/check-code-coverage.php build/coverage/clover.xml tests/coverage-baseline.json --update-baseline
```

Review that change as part of the PR; do not lower the baseline to hide a regression.
Instrumentation/compiler changes can affect branch totals, so investigate differences
when updating PHP, Xdebug, or PHPUnit. Reports include uncovered lines and branches
to guide further tests; neither ratio establishes specification completeness.

## Reproducible performance reports

```sh
mkdir -p build
php -d xdebug.mode=off bin/benchmark.php 5 > build/benchmark.json
```

The deterministic workloads cover 8/16/32/64 KiB of role-heavy input, 20 nested
directives, and 12 nested in-memory includes in both profiles. The benchmark raises
`maxNesting` to 64 so the directive workload fits within the parser limit. Each case
warms up once, then records median wall-clock milliseconds, peak allocated process
memory, used memory (`peak_used_bytes`), source size, diagnostics, and an output hash.
Reports include PHP and dependency versions. The optional argument sets measured
iterations (1–100).

Peak memory is reset between iterations, but includes the process's retained parser
caches and allocator pages; it is not incremental per-document memory. Use the same
runtime, iteration count, and machine load when comparing reports. CI uploads the
report without a timing threshold. Growth across input sizes helps identify parser
hotspots without turning noisy shared-runner timings into a release gate.

The committed [PHP 8.5 current-dependency sample](benchmarks/php85-current.json)
records a five-iteration run with Xdebug disabled on a shared Linux host. It is a
reporting example, not a performance threshold. The largest role-heavy case used
about 56 MiB of PHP memory; increasing input size shows substantial superlinear
runtime growth worth profiling separately before optimizing the parser.
