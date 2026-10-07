# 0.5.8 Release Verification

Status: verified on branch `codex/0.5.8-release` against master `9803842`
plus the release commit; all gates below passed before the release pull
request merged.

## Scope

0.5.8 is a maintenance and repository-health release. It carries no ability
contract, schema, annotation, or callback behavior changes: the two media
read structural slices preserve the `Core_Read_Package` class surface
(byte-for-byte moves verified by reconstruction and pure-deletion proofs),
and the dead null-coalescing removals are runtime-identical on
non-filterable surfaces. Eleven pull requests since the 0.5.7 preparation
(#196, #192, #193, #197-#204) are folded in, plus the previously unreleased
quality work (#182-#185).

## Publication Gap Discovered During This Verification

**0.5.7 was never published.** The WordPress.org SVN `tags/` directory tops
out at 0.5.6, and no `0.5.7` git tag exists on origin: the 2026-10-03
closeout left tagging and SVN publication as maintainer-terminal steps and
they were not executed. Consequences for 0.5.8:

- WordPress.org users upgrade from **0.5.6** directly to 0.5.8 and receive
  the 0.5.7 content (user-experience hardening, locale backfill) plus this
  release together; `readme.txt` already carries both changelog sections,
  so the WordPress.org changelog reads coherently.
- Optionally backfill the `0.5.7` git tag at the verified 0.5.7 release
  commit `9e9f9ea` (creating the never-existing tag is allowed; the
  no-retag rule concerns moving existing tags). An SVN `tags/0.5.7`
  directory is unnecessary.

## Baseline Actions In This Release

- `tests/fixtures/phpstan-level4-total-baseline.txt`: 739 -> **675**, the
  first downward move of the level-4 total baseline per the release-time
  convention in `docs/structural-split-plan.md`.
- `tests/fixtures/structure-ratchet-baseline.json`: current peaks locked
  (Media_Read_Methods 5,190; Media_Candidate_Adoption_Read_Methods 947;
  Media_Reference_Repair_Read_Methods 509; Core_Read_Package 4,658).

## Boundary

- `composer check:boundary` passed (project boundary guard).
- `composer check:wporg` passed (WordPress.org static review rules guard,
  including locale po/mo pairing; locale files unchanged this cycle).
- No workflow definitions, permission callbacks, risk metadata, or write
  boundaries changed in this cycle beyond what 0.5.7 shipped.

## Verification Matrix

| Gate | Result | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | Full source, contract, package, lifecycle, cron, uninstall, performance, and syntax lane; 16,765 assertions. |
| `composer analyse:phpstan` | Pass | PHPStan 2.2.16 level 3 reported no errors. |
| `composer check:phpstan-total` | Pass | Level-4 total 675 = baseline 675 — the first release-time run of this gate inside `composer release:verify`, after the baseline was lowered from 739 in this release. |
| `composer check:boundary` | Pass | Project boundary guard. |
| `composer check:wporg` | Pass | WordPress.org review rules guard. |
| `composer release:verify` (LocalWP) | Pass | Full lane with `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public"` and the site MySQL socket: source gate, PHPStan, level-4 total ratchet, evidence-free local Docker minimum WordPress 6.9.4/PHP 8.0 smoke (478 assertions), real-site smoke (479 plugin assertions + 58 lifecycle assertions), and packaged Plugin Check with zero errors. |
| `git diff --check` | Pass | No whitespace errors. |

## Environment Notes

- The Local.app MySQL socket run directory rotates; the live socket during
  this verification was
  `~/Library/Application Support/Local/run/s63K4c8XP/mysql/mysqld.sock`
  (siteurl confirmed `https://magick-ai.local` before running the lane).
  The runbook example shows an older run id — discover the current one with
  `ls ~/Library/Application\ Support/Local/run/*/mysql/mysqld.sock`.
- Docker Desktop had to be started for the local minimum-WordPress leg;
  when Docker is unavailable, the runbook's M4 evidence path replaces that
  leg only.

## Remaining Maintainer Steps

The 0.5.8 publication operations stay in the maintainer's terminal where
WordPress.org SVN credentials are typed:

```bash
git switch master && git pull --ff-only origin master
git tag 0.5.7 9e9f9ea && git push origin 0.5.7   # optional backfill; skip if unwanted
git tag 0.5.8 && git push origin 0.5.8
VERSION=0.5.8 composer release:prepare-wporg
SVN_USERNAME=muze233 COMMIT_MESSAGE="Release 0.5.8" build/commit-wporg-release.sh
```

After publication: record the SVN revision in the release issue if one is
open, verify the WordPress.org page serves 0.5.8, and note in this file
that publication completed (this closes the gap that hid the unpublished
0.5.7).
