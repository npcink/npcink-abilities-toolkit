# 0.5.3 Release Verification

Status: published and verified on GitHub and WordPress.org.
Date: 2026-07-11.

This note records the `0.5.3` security-hardening candidate. The release keeps
Toolkit as the WordPress ability-contract owner while making write controls,
post-meta reads, settings patches, remote media intake, and plugin bootstrap
safer by default.

## Scope

- Require explicit commit intent for write and destructive callbacks; fail
  safe when write controls are absent or conflict.
- Require explicit non-sensitive post-meta keys and prevent context readers
  from falling back to every stored value.
- Require a host-provided setting target allowlist, permanently reject
  credential-like targets, and avoid returning reversible setting fragments.
- Validate downloaded media before it enters uploads and remove the obsolete
  in-memory upload path.
- Replace unconditional implementation includes with internal autoloading and
  lazy package construction, protected by a cold-bootstrap performance budget.
- Keep approval truth, audit truth, final authorization, runtime state, model
  routing, and consumer product policy outside Toolkit.
- Correct the technical WordPress minimum to 6.9 and prove it with a disposable
  real WordPress smoke.
- Record Toolkit as the canonical owner of reusable static workflow definitions
  without adding runtime or governance state.
- Replace the catalog-size floor with representative contract assertions and
  document evidence-first convergence with future WordPress Core abilities.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | 164 ability contracts, 6 workflow recipes, 6276 assertions, boundary checks, WordPress.org guards, performance budgets, and PHP syntax lint passed. |
| `composer analyse:phpstan` | Pass | PHPStan completed successfully with the package's PHP 8.0 baseline. |
| Minimum WordPress smoke | Pass | A disposable Docker site on WordPress 6.9.4 completed the official probes and 435 authenticated contract and behavior assertions. |
| Full WordPress smoke | Pass | The current Local.app site on WordPress `7.1-alpha-62692` completed 436 authenticated contract and behavior assertions from the `0.5.3` Git tag. |
| Light-profile WordPress smoke | Pass | The same Local.app site completed 56 package-profile assertions. |
| Plugin package check | Pass | The packaged WordPress.org surface completed Plugin Check without a blocking error. |
| Adapter consumer compatibility | Pass | Adapter PR #31 is merged and preserves conditional setting-target readiness after Toolkit hardening. |
| Central cross-repo functional matrix | Pass | `composer quality:matrix:run` passed all seven configured repository gates on 2026-07-11. |
| Strict cross-repo cleanliness | Pass | `composer quality:matrix -- --fail-on-dirty` reported zero dirty files across all seven configured repositories. |
| WordPress.org publication | Pass | SVN revision `3603764` published `trunk` and release tag `tags/0.5.3` from Git tag `0.5.3`. The plugin information API reports `0.5.3` with WordPress `6.9` as the minimum. |

## Release Decision

`0.5.3` is published from Git tag `0.5.3` at commit `072405e`. The
minimum-version, current-site, package, and strict cross-repository checks pass.
The Toolkit security defaults are intentional; consumers must not weaken them
or hardcode site-specific setting names into a channel adapter.

## WordPress.org Publication Receipt

- SVN revision: `3603764`.
- SVN tag: `https://plugins.svn.wordpress.org/npcink-abilities-toolkit/tags/0.5.3/`.
- Official stable version after publication: `0.5.3`.
- Official minimum WordPress version after publication: `6.9`.
- Official ZIP integrity check: pass.
- Official ZIP SHA-256:
  `8f2256cf308b5d44d6f3fb7b47ce013f9733750d445b9689fc69eb0f22d7b802`.
- Official ZIP plugin header and runtime version constant: `0.5.3`.

## Post-Release Observation

Use existing host diagnostics, test output, and quality matrices to observe:

- rejected unallowlisted setting targets;
- legacy post-meta calls that omit an explicit key;
- PHP 8.0 and PHP 8.3 CI stability;
- cold-bootstrap and bounded runtime performance budgets.

Do not add Toolkit-owned telemetry, approval records, audit storage, queues, or
runtime state for this observation period.
