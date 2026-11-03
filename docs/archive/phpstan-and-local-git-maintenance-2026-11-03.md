# Classified PHPStan Batch And Historical Git Exceptions

Date: 2026-11-03 (Asia/Shanghai). Source baseline:
`fdf0a2e32a940f7c21750a2966b0838e18ce9082`.
This record captures the focused batch and pre-publication cleanup evidence;
the containing pull request records final reviewed head, required CI and merge.
It does not certify release readiness or Git steady state.

## Scope And Results

Only Toolkit source analysis and local historical Git exceptions were handled.
Human repeat-use validation and formal 0.8.1 publication remain deferred.
No release/tag, SVN publication, real article or SEO write is part of this work.
No public ability, permission, output schema or host authority changed.

`composer install` made no dependency changes. PHPStan 2.3.4 completed both
level-4 JSON reports with zero analyzer errors: **645 → 640 diagnostics**.
The previous 645 snapshot reproduced; no tool/config/source drift had to be
explained. These are diagnostic counts, not confirmed runtime defects.
The total ceiling falls to 640 without suppressions, invented types or new casts.

The single-file batch proves five redundant checks in locally constructed
Gutenberg catalog/profile and inspection-surface arrays. Existing string casts
on filterable surface values remain; required keys are not a claim that filters
always return strings. Seven findings in that
file remain at WordPress object/parser boundaries. The other 633 remaining
findings have refreshed file/rule ownership, but are not semantically adjudicated.
[Debt record](../phpstan-level4-debt.md) and
[classification JSON](../evidence/phpstan-level4-debt-2026-11-03.json) distinguish
proven invariants, retained defenses and pending provenance review.

Existing catalog/composer, inspection, permission-denial and pilot behavior
checks are reused; no implementation-mirroring test is added. `test:core` and
`test:all` passed (17,191 main assertions, 40 flagged-media checks, 26 audit
regressions and the other source guards); ordinary PHPStan reports no errors,
the total ratchet passes at 640/640, and diff/link/classification checks pass.
The committed-diff ratchet selected the changed trait but reported 0/0 when
analyzing it alone. A fresh complete tracked baseline tree with its own
`composer install`, compared with committed HEAD over all configured paths,
confirms 12/7 for this trait and unchanged counts in every other file. Both
complete reports have zero analyzer errors. Local evidence is retained in
`committed-full-comparison.json` and its companion reports. The initial
`git archive` attempt omitted analyzer/Composer files due to `export-ignore`;
that failed attempt is retained and is not counted as successful evidence.
The containing PR carries final revision-bound gates and local AI review.
No new Local runtime, release gate or production acceptance is claimed.

## Ignored Materials And Worktrees

The main checkout and old detached checkout were clean at the same baseline.
The Local WordPress plugin symlink resolves to the main checkout, not the old
worktree. App inventory still associates the old directory with the idle chat
“查找项目定位”; this task has no managed attachment for it. Preserve that other
chat's workspace; no worktree removal or app archive was attempted.

Before considering removal, copied old `build/` and `dist/` into the main
checkout's ignored `build/maintenance-2026-11-03/preserved-old-worktree/`.
Verified **555 file/symlink entries, 157,344,676 file bytes**, including 108
`.svn` entries, by SHA-256, file mode and symlink target. Also copied and hashed
the only other ignored non-vendor file: the generated composer-pilot report.
Raw private backups retain their modes, inside a mode-0700 preservation root.
No ignored source was deleted. ZIPs, SVN working copies, raw review receipts,
backups and failed evidence remain local; they were not published to Git.
Git bundles/app snapshots cannot substitute for this ignored-file copy.

## Historical Ref Reconciliation

Rechecked GitHub PR inventory and local objects against fetched `origin/master`.
For 18 refs, exact head matches a merged master PR, its merge commit is reachable
from master, and all files changed by that merge have equal blobs at the topic
head and merge. For three screenshot aliases, every changed path since the
merge base is a screenshot whose blob equals current master. No unmatched
experiment or unknown branch was found. This proves delivery of the changes;
it does not preserve squash ancestry.

| Local branch | Exact head | Delivered PR / merge or blob proof |
| --- | --- | --- |
| `codex/ability-integration-contracts` | `5f340a67c5d783721727e91496de1291bbe9d5bd` | #270 / `4dd05144146570039a024e71be9b79d63f045d39` |
| `codex/admin-locale-assets` | `d1504c47b3b4619e64ef795cf3d539da46cca0b3` | All 4 changed screenshot blobs equal master |
| `codex/admin-locale-assets-all` | `d1504c47b3b4619e64ef795cf3d539da46cca0b3` | All 4 changed screenshot blobs equal master |
| `codex/admin-locales-complete` | `60fab0f92818480369e87c848ef29a18c32f41ed` | #278 / `420dba45d489be099360c7f001505da3e5cea03a` |
| `codex/admin-overview-composition` | `a78d64c0b0ec11f0434ca235a1fdd7222de92d7d` | #273 / `b4d416cda374900d63e0a6544a4758e2eab6043c` |
| `codex/admin-screenshot-1` | `182893c21912a0bf0beee3e531340a8775e44ede` | All 1 changed screenshot blobs equal master |
| `codex/audit-correctness-fixes` | `39499959f6f2c77cd58a04d82d951996dbc5f692` | #268 / `6bfbb62af24fcea53b8405d1fcfc83a904f98212` |
| `codex/cleanup-query-evidence` | `47301f619beba89de839fc00b4e19e720adb4002` | #281 / `b44daa54e41361aa5f549bbb16cdee5861bf8ac4` |
| `codex/configured-review-policy` | `cc1cd8e51f16cf07ec76fe3cd64ea399708673a0` | #279 / `76011b18c4957911582f80441da8736dc2866632` |
| `codex/maintenance-closeout-notes` | `4bc6cc3a4de8e72bcb581551fafc862a71fba4d5` | #287 / `fdf0a2e32a940f7c21750a2966b0838e18ce9082` |
| `codex/phpstan-calendar-counts` | `469e1dba762478b820f4145ac5ffeacd40786a01` | #285 / `2b133ea53ac31b2a57ece4ee492db94fc716abd3` |
| `codex/phpstan-proven-invariants` | `aedd8ec8a3e01ec4dd4f00d70d4ac63084c1333a` | #282 / `ebabbe4f32c49b3e73fce89f1240ce22978acd25` |
| `codex/phpstan-taxonomy-arrays` | `7fdde9a8e692e8876860a5283753a4eadeb0161e` | #283 / `7559add49b1b84a23c110111a282c581be4317f3` |
| `codex/plugin-check-closeout` | `8c8091aed02b7d54bc608ea4d7597e143c7744e3` | #269 / `bfe015252574b9ea5a5129cb1ec09a40e35528fc` |
| `codex/preflight-content-boundary` | `9c7d240ddec618a1b5b22981a0502b897606b395` | #280 / `b8e284e05f1b78fb892059052424a263efd093a5` |
| `codex/release-0-8-0` | `89f9bfe7510294f493771557f9fa92bae60bbf6f` | #272 / `22fe6de45453d1365779bc603c1cee202678187b` |
| `codex/release-0-8-1` | `108047d9096a5272307877e82716830dedc36bc5` | #286 / `f23fb535c939bbdebd379cfa19f076d2e5ae1d77` |
| `codex/release-080-closeout` | `cc5207ba126de21e0b30440a61b290221a29859a` | #274 / `48bfdd626b796de6d640600c060d2d8f53e386b9` |
| `codex/require-local-review` | `499d9215872a6bef2c3c105a07dec8588b4ba218` | #275 / `f769ced3c98ca3774166d05870914ff42372d258` |
| `codex/zh-cn-publishing-slug` | `8ad76f95bdc3d86d7b0e8ae6c9c51c709c3a16b4` | #284 / `a9424fc331b0c1b24707947b05fb3a7327cd1942` |
| `ui/quiet-admin-surface` | `3d6a4ce6036e2e29c54250b09c77af4c88f755c1` | #271 / `3b8a7789ba2078ee14564af3af8e091f04a522aa` |

All 21 `git branch -d` attempts were rejected as not fully merged. **Zero
historical refs were deleted**. No `-D`, ref rewrite or ancestry manipulation
was used. Before those attempts, created and verified an all-refs bundle:
`build/maintenance-2026-11-03/all-refs-before.bundle`.

Bundle SHA-256: `e35bc4be5eec83a7d79c0b2ed5dec1e3be8958771b8a40e34ed14a746edd5be7`.

`git bundle verify` confirms complete history; `bundle-heads.txt` lists retained
identities. Full PR/merge/blob reconciliation and each deletion refusal are in
`branch-reconciliation.json` and `safe-deletion-attempts.json` alongside it.
Deleting these delivered refs would remove local names for original squash
heads, not useful source from master; bundles retain recovery identities.
Force deletion still requires a separate explicit maintainer exception under
`AGENTS.md` and the audit standard. This session has no such authorization.

At inventory time remote had only master, no open PR, and the two worktrees
above. The new batch topic is additional temporary state until its protected
merge; inspect it again at final closeout. Run `composer closeout:check` after
returning main to synchronized master. Retained squash refs prevent a claim
that all cleanup is complete; the old occupied worktree is an explicit exception.

## Continuation

Stop after this small batch is accepted; a second batch needs a new focused
provenance decision. Prefer internal required fields; preserve external/filter,
cache, filesystem and third-party defenses until separately proved. Roll back
source with a focused revert PR. Recover local names/materials from the verified
bundle and ignored-file copy; do not relabel old release evidence for a new HEAD.
