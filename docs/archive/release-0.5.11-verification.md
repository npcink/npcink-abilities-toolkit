# 0.5.11 Release Verification

Status: verified on master `8192e74` (release tag `0.5.11`); all gates below
passed before the WordPress.org commit r3738863 on 2026-10-11.

## Scope

0.5.11 is the security and performance hardening release from the 2026-10
audit. Ten pull requests since 0.5.10 fold in: #222 (per-post edit gates in
the publishing workflow reports), #223 (catalog fingerprint skipped on
anonymous frontend contexts), #224 (contract projection and ETag cached in a
bounded transient, hardened per advisory review with the id-set digest key,
array-shape validation, and the extracted `contract_cache_key()`), #225
(read-cache version bump deduplicated across all invalidation hooks), #226
(autoloader dot rejection, realpath fail-closed, merge-terms per-object
authorization with the blocked-source non-deletion rule, and the uninstall
preserve-default change for the shared legacy `_npcink_ai_` history meta),
#227 (pages-tree single full-object query), #229 (built-in package
registration skipped on anonymous frontend requests with the
`npcink_abilities_toolkit_register_frontend_contexts` host opt-out), #230
(duplicate-check per-post gate and per-segment media path validation on both
sides), #231 (host approval contract scoping section plus the
`host_approval_unconditional` health-notice probe), and #232 (eleven
site-level read callbacks re-check their capability through a shared helper,
plus the dispatch-layer capability re-check in `observed_execute_callback`
with the `abilities.callback.denied` event). #228 staged the changelog and
#233 prepared the release. No ability contract, schema, annotation,
response shape, or workflow definition changed anywhere in the batch; the
one user-visible behavior change is the uninstall preserve default, which is
documented in both changelog surfaces with its opt-out filter.

Two advisory review findings worth recording: the OpenCodeReview run on #229
caught a critical REST-timing bug before merge (`REST_REQUEST` is defined
only after `init`, so the first context-gate iteration would have skipped
package registration on every REST request; fixed with `wp_is_rest_endpoint()`
plus a cron-clear regression), and the run on #232 drove the move from
per-callback guards to the dispatch-layer check.

## Baseline Actions In This Release

- `tests/fixtures/phpstan-level4-total-baseline.txt`: unchanged at **675**;
  the release lane measured **671**, four findings below baseline — the
  audit fixes paid down existing level-4 debt.
- Structure ratchet baseline: unchanged (every touched `includes/` file was
  held at or below its recorded ceiling inside its own pull request).

## Boundary

- `composer check:boundary` passed (project boundary guard).
- `composer check:wporg` passed (WordPress.org static review rules guard,
  locale po/mo pairing intact with the two new notice strings present in the
  template and all seven bundled locales).
- `AGENTS.md` vs `docs/workflow-definition-contract.md` boundary-language
  drift check before tagging: none (neither file changed in the release
  window; spot check consistent).

## Verification Matrix

| Gate | Result | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | Full source, contract, package, lifecycle, cron, uninstall, performance, and syntax lane; 16,921 behavioral assertions. |
| `composer analyse:phpstan` | Pass | PHPStan 2.2.16 reported no errors. |
| `composer check:phpstan-total` | Pass | Level-4 total 671 ≤ baseline 675 (enforced inside the release lane). |
| `composer release:verify` (LocalWP + M4 remote evidence) | Pass | Source gate, PHPStan, level-4 total ratchet, LocalWP real-site smoke (58 lifecycle assertions on the `magick-ai` Local site through its site MySQL socket), and packaged Plugin Check with zero errors. The local Docker daemon was unavailable, so per the release runbook the duplicate local Docker leg was replaced by revision-bound M4 evidence generated on the remote M4 host (Docker 29.7.2; WordPress 6.9.4/PHP 8.0 and WordPress 7.0/PHP 8.5 profiles, 479 smoke assertions), bound to `8192e7461d29d2946c19e8ff9c0bf46d0995150a` in `build/m4-wordpress-smoke-evidence.json`. |
| Cross-repo quality matrix | Pass | `npcink-abilities-toolkit`, `npcink-governance-core`, and `npcink-ai-client-adapter` gates (`composer test:all`) all passed against this master, confirming the boot context gate and the approval probe introduce no consumer regressions. |
| `git diff --check` | Pass | No whitespace errors. |

## Publication

- WordPress.org SVN commit **r3738863** on 2026-10-11 (trunk update plus
  `tags/0.5.11`, source commit `8192e74`), committed non-interactively with
  the maintainer's keychain-cached credentials at the maintainer's direction.
- Git tag `0.5.11` pushed at `8192e74`.
- Live-state verification: trunk readme `Stable tag: 0.5.11`;
  `tags/0.5.11/` present on the SVN remote with header `Version: 0.5.11`.

## Findings During Verification

- **First release published through the fully agent-assisted keychain path.**
  All preconditions from the runbook were verified before the commit (cached
  keychain identity for `muze233` at `plugins.svn.wordpress.org`, zero `*`
  markers from `svn status -u`), no credentials were typed anywhere, and the
  maintainer directed the commit explicitly.
- The M4 evidence substitution was used for the second consecutive release
  (local Docker unavailable both times); the runbook's remote path produced
  the required minimum and current WordPress profiles without deferring any
  other gate.
