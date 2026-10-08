# 0.5.9 Release Verification

Status: verified on branch `codex/0.5.9-release` against master `dce0d69`
plus the release commits; all gates below passed before the release pull
request merged.

## Scope

0.5.9 is the user-experience localization and onboarding release. It carries
no ability contract, schema, annotation, or callback behavior changes: the
admin surface renders the same ability catalog, checks, and connection values
as 0.5.8. Two pull requests since 0.5.8 are folded in: #213 (scenario-card
localization with the literal translation map, activation welcome notice,
heading/menu alignment, attention and connection guidance, filter
auto-submit, uninstall user-meta hygiene, seven-locale backfill of 50
msgids) and #214 (AGENTS ratchet pre-check note).

## Baseline Actions In This Release

- `tests/fixtures/structure-ratchet-baseline.json`:
  `includes/Admin/Test_Page.php` 1,452 -> **1,409** (scenario renderer
  extracted), plus the new files `includes/Admin/Scenario_Cards.php` (70)
  and `includes/Admin/Welcome_Notice.php` (227) recorded at introduction.
  `includes/Admin/Scenario_Translations.php` (94) enters under the
  new-file cap on the next baseline regeneration.
- `tests/fixtures/phpstan-level4-total-baseline.txt`: unchanged at **675**;
  the release lane confirms the total did not grow.

## Boundary

- `composer check:boundary` passed (project boundary guard).
- `composer check:wporg` passed (WordPress.org static review rules guard,
  including locale po/mo pairing for all seven bundled locales).
- No workflow definitions, permission callbacks, risk metadata, or write
  boundaries changed in this cycle. Ability ids, schemas, response shapes,
  and the workflow recipe payloads are byte-identical to 0.5.8 (verified by
  `check:contracts`, `check:shapes`, the 12-recipe workflow consumer proof,
  and the catalog audit inside the lane).

## Verification Matrix

| Gate | Result | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | Full source, contract, package, lifecycle, cron, uninstall, performance, and syntax lane; 16,845 assertions (includes the 48-entry scenario localization guard and the literal-map assertions). |
| `composer analyse:phpstan` | Pass | PHPStan 2.2.16 level 3 reported no errors. |
| `composer check:phpstan-ratchet` | Pass | Changed-file level-4 total 0 <= base 0 after the literal translation map replaced the dynamic `esc_html__()` calls (the first attempt counted 4 provably-true-guard findings in `Welcome_Notice` and failed CI on #213; fixed before merge). |
| `composer check:phpstan-total` | Pass | Level-4 total 675 = baseline 675. |
| `composer check:boundary` | Pass | Project boundary guard. |
| `composer check:wporg` | Pass | WordPress.org review rules guard. |
| `composer release:verify` (LocalWP) | Pass | Full lane with `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public"` and the site MySQL socket: source gate, PHPStan, level-4 total ratchet, local Docker minimum WordPress 6.9.4/PHP 8.0 smoke (478 assertions), real-site smoke (479 plugin assertions + 58 lifecycle assertions), official-stack E2E on WordPress 6.9.4/PHP 8.0, and packaged Plugin Check with zero errors and zero new findings. |
| `git diff --check` | Pass | No whitespace errors. |

## Findings During Verification

- **Plugin Check rejected the first localization approach.** Rendering
  recipe text through dynamic `esc_html__()` calls produced two ERROR-level
  `WordPress.WP.I18n.NonSingularStringLiteralText` findings in the packaged
  plugin (the release lane fails on Plugin Check errors). Fixed by routing
  scenario-card localization through `Admin\Scenario_Translations`, a literal
  `__()` map keyed by the English contract text: extraction tooling
  rediscovers every scenario msgid, unknown recipe text falls back to the
  contract string, and the map is guarded by the test suite. The advisory
  review on #213 had flagged the same extraction blind spot from the
  maintainability side.
- **A transient external blocker paused the lane.** The real-site smoke hit
  a fatal `rest_api_init` error from the symlinked in-development
  `npcink-governance-core` working tree on the shared Local site (a
  static-call bug in that repo's uncommitted `fix/ux-round2` edits). The
  edit was corrected in that working tree before the final run; the lane
  then passed end to end. No Toolkit change was involved.

## Environment Notes

- The Local.app MySQL socket run directory rotates; the live socket during
  this verification was
  `~/Library/Application Support/Local/run/s63K4c8XP/mysql/mysqld.sock`
  (siteurl confirmed before running the lane; `s63K4c8XP` is the `magick-ai`
  run id). Discover the current one with
  `ls ~/Library/Application\ Support/Local/run/*/mysql/mysqld.sock`.
- Docker Desktop was started for the local minimum-WordPress leg.

## Screenshots

The WordPress.org screenshot assets from the 0.5.8 refresh remain current
enough: the 0.5.9 visible deltas are copy-level (the page heading now reads
the translated "AI Ability Set", one description line under Connection
values, localized scenario cards on non-English admins) with no layout
change, so the readme's layout-keyed refresh rule is not triggered. Refresh
the four screenshots with the next layout-level admin change.

## Remaining Maintainer Steps

1. Merge the release pull request (auto-merge armed; required checks must
   stay green).
2. Tag and push the release:
   `git tag 0.5.9 <merge-commit> && git push origin 0.5.9`
3. Prepare the local SVN working copy from the tagged commit:
   `VERSION=0.5.9 composer release:prepare-wporg`
4. Commit to WordPress.org SVN from the maintainer's terminal (credentials
   stay in the terminal/Keychain):
   `/Users/muze/gitee/npcink-abilities-toolkit/build/commit-wporg-release.sh`
5. Confirm WordPress.org serves stable 0.5.9 (plugins API updated
   timestamp), then append the publication record to this note.

## Publication Record (2026-10-08)

Published at maintainer delegation through the Keychain-cached SVN
credentials (the 0.5.6 and 0.5.8 delegated-commit precedent; non-interactive
`svn commit --username muze233`, no credential material passed through
chat). Publication sequence and facts:

- Release pull request #215 merged at `a5ef13a`; git tag `0.5.9` created at
  the merge commit and pushed; `VERSION=0.5.9 composer release:prepare-wporg`
  staged trunk plus a single new `tags/0.5.9` directory against release
  source commit `a5ef13a`, with zero `marketing/`/`docs/`/`tests/`
  contamination in the staged paths (checked explicitly after the 0.5.8
  distignore incident).
- Pre-commit freshness re-check: `svn status -u` against remote head
  `r3733633` showed zero out-of-date markers.
- **SVN committed at revision 3733635** (trunk to 0.5.9 plus `tags/0.5.9`).
- WordPress.org verified serving **stable 0.5.9** (plugins API
  `version: 0.5.9`, fetched 2026-10-08 11:45 GMT+8; plugin page HTTP 200).
- Screenshot assets unchanged this release: the visible deltas are
  copy-level only (see the Screenshots section above).
