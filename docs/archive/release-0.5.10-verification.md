# 0.5.10 Release Verification

Status: verified on master `dd4b42f` (release tag `0.5.10`); all gates below
passed before the WordPress.org commit r3737495 on 2026-10-10.

## Scope

0.5.10 is the documentation follow-up release. Four pull requests since
0.5.9 fold in: #216 (0.5.9 publication record r3733635 and milestone
closeout), #217 (article-publish-preflight closed-loop host proof), #218
(preflight proof failure attribution corrected to grant mode), and #219
(release preparation). The only non-documentation change is one
static-contract needle in `tests/run.php` pinning the fourth closed
host-proof target. No ability contract, schema, annotation, callback,
workflow definition, admin surface, or shipped runtime code changed; the
packaged plugin differs from 0.5.9 only in the header version constant
and readme metadata.

## Baseline Actions In This Release

- `tests/fixtures/phpstan-level4-total-baseline.txt`: unchanged at
  **675**; the release lane confirms the total did not grow.
- Structure ratchet baseline: unchanged (no `includes/` file touched).

## Boundary

- `composer check:boundary` passed (project boundary guard).
- `composer check:wporg` passed (WordPress.org static review rules guard,
  locale po/mo pairing intact).
- `AGENTS.md` vs `docs/workflow-definition-contract.md` boundary-language
  drift check before tagging: none.

## Verification Matrix

| Gate | Result | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | Full source, contract, package, lifecycle, cron, uninstall, performance, and syntax lane; static contracts green including the new fourth-target proof-ledger needle. |
| `composer analyse:phpstan` | Pass | PHPStan 2.2.16 level 3 reported no errors. |
| `composer check:phpstan-total` | Pass | Level-4 total 675 = baseline 675 (enforced inside the release lane). |
| `composer release:verify` (LocalWP + M4 remote evidence) | Pass | Source gate, PHPStan, level-4 total ratchet, LocalWP real-site smoke (58 lifecycle assertions, `WP_PATH` + site MySQL socket), and packaged Plugin Check with zero errors. The local Docker daemon was unavailable, so per the release runbook the duplicate local Docker leg was replaced by revision-bound M4 evidence generated on the remote M4 host (Docker 29.7.2; WordPress 6.9.4/PHP 8.0 and WordPress 7.0/PHP 8.5 profiles, 478 smoke assertions), bound to `dd4b42f035ffc1d672ff3dbd5bd019539772e96a` in `build/m4-wordpress-smoke-evidence.json`. |
| `git diff --check` | Pass | No whitespace errors. |

## Publication

- WordPress.org SVN commit **r3737495** on 2026-10-10 (trunk update plus
  `tags/0.5.10`, source commit `dd4b42f`), committed non-interactively
  with the maintainer's keychain-cached credentials.
- Git tag `0.5.10` pushed at `dd4b42f`.
- Live-state verification: trunk readme `Stable tag: 0.5.10`;
  `tags/0.5.10/` present on the SVN remote.

## Findings During Verification

- **First 0.5.x release verified through the M4 evidence substitution.**
  The local Docker daemon was down at verification time; the runbook's
  remote path (`composer smoke:wp-m4` against the LAN M4 host) produced
  the required minimum and current WordPress profiles without deferring
  any other gate, and the evidence JSON names the exact HEAD. The
  substitution replaces only the duplicate local Docker leg; the source,
  PHPStan, LocalWP, and packaged Plugin Check gates still ran locally.
