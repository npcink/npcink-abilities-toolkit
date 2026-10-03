# Structural Split Plan

Status: paused; resume only from concrete change evidence.
Date: 2026-07-11.

This plan reduces review cost and change collisions without combining structural
moves with ability changes. Every slice must preserve public ability ids,
schemas, annotations, callbacks, lazy loading, dry-run defaults, and host-owned
approval and final authorization.

## Stabilization Checkpoint

The first two slices established the write-trait pattern and reduced
`Core_Write_Package` from 8,183 to 7,485 lines without changing its public
contracts. Source gates, PHPStan, bootstrap performance, real WordPress smoke,
and the real block-theme host proof passed after those moves.

The remaining sequence is intentionally paused after the 0.5.3 release. That
release hardened remote-media temporary-file validation, upload handling, and
write safety. Moving the media lifecycle immediately after that hardening would
add structural risk without a current consumer defect, feature requirement, or
measured review bottleneck. File size alone is not sufficient evidence to
resume the refactor.

Keep issue #89 open as an evidence-triggered maintenance record. Resume only
when at least one of these conditions is observed:

- repeated pull requests change the same responsibility and cause review or
  merge conflicts;
- a concrete bug or approved feature must cross unrelated responsibilities in
  one oversized module;
- security review cannot isolate remote input, file lifecycle, rollback, or
  reference-repair behavior;
- tests cannot independently prove the responsibility being changed;
- a maintainer or agent repeatedly modifies unrelated behavior because the
  current ownership boundary is unclear.

Do not resume merely to reduce line counts, complete this sequence, or make the
directory tree more symmetrical. During the pause, prioritize release
stability and evidence from Core, Adapter, Toolbox, and real WordPress hosts.

## Baseline Inventory

The first inventory measured the largest PHP files before the initial slice:

| File | Baseline lines | Primary responsibility |
| --- | ---: | --- |
| `includes/Packages/Core_Write_Package.php` | 8,183 | Write definitions, callbacks, shared guards, and media operations. |
| `includes/Packages/Read_Traits/Media_Read_Methods.php` | 7,771 | Media analysis and planning reads. |
| `tests/run.php` | 7,761 | Source-level contract and behavior assertions. |
| `includes/Packages/Core_Read_Package.php` | 5,680 | Read definitions plus shared read helpers. |
| `includes/Packages/Read_Traits/Page_Pattern_Read_Methods.php` | 4,599 | Page pattern composition and review. |
| `includes/Packages/Read_Traits/Block_Theme_Read_Methods.php` | 3,196 | Block-theme inspection and planning. |

Line count is a triage signal, not an architectural goal. Split only when a
cohesive responsibility can move without changing its public contract.

## Accepted Sequence

1. **Post attribute writes — completed in the first slice.**
   `set-post-slug`, `set-post-author`, `set-post-template`, and
   `set-post-format` live in `Post_Attribute_Write_Methods`. The owning class
   still registers the definitions and supplies shared governance helpers.
2. **Site Editor and Gutenberg writes — completed in the second slice.**
   `update-post-blocks`, `update-template-blocks`, `upsert-template-blocks`, and
   `update-template-part-blocks` now use implementations from
   `Site_Editor_Write_Methods`. The trait also owns their private block-document
   and template lookup helpers. `Core_Write_Package` remains the definition and
   composition owner, and the extraction preserves host-governed dry-run and
   final authorization behavior.
3. **Media write lifecycle — one reference-discovery slice completed; remaining
   work deferred pending independent evidence.** Evaluate remote intake,
   derivative materialization, file replacement/rollback, and reference repair
   independently. These paths have different security and rollback risks and
   must not move as one campaign.
4. **Definition providers — deferred pending callback pressure.** Move large
   read/write definition arrays only when a concrete definition change is made
   harder by current ownership and callback ownership is already stable.
   Definitions must continue to bind to the same object callbacks and metadata.
5. **Test suites — resumed and completed 2026-10-03 on test-maintenance
   evidence.** `tests/run.php` had grown from the 7,761-line baseline to
   9,937 lines (surpassing `Core_Write_Package.php` as the repository's
   largest PHP file), which met the repeated-collision resume condition. It
   is now an ordered aggregator over eleven `tests/run/` part files cut along
   the original execution order (registry/boot, package definitions, post
   write flows, block-theme/comment flows, read planning, media file
   operations, cloud media backups, media restore/alt plans, media
   inventory/article plans, content intent routing, page patterns/closeout).
   Because the suite shares fixtures through top-level variables and
   `$GLOBALS` WordPress stubs, the include order is load-bearing and the
   split deliberately regroups nothing; behavior preservation is proven by
   the identical 16,657-assertion result. The single `composer test`
   entrypoint and aggregate assertion result are unchanged.

Do not execute the remaining sequence automatically. When evidence justifies a
resume, open one focused pull request for the affected responsibility, verify
it, and return to stabilization unless the next responsibility has independent
evidence of its own.

## 2026-07-14 Evidence-Triggered Slice

Since 2026-06-01, non-merge history records 54 commits changing
`Core_Read_Package.php` and 31 changing `Media_Read_Methods.php`. That
concentrated definition and callback pressure satisfies the repeated-change
trigger for one narrow maintenance slice.

This slice resumes only the media governance read definition provider:
`Core_Read_Package` remains the composition and callback owner, while the 23
existing media definitions move unchanged to a stateless provider. Media method
implementations, ability contracts, ordering, host governance, and runtime
ownership do not move. This evidence does not unpause the broader structural
sequence; every later slice still requires its own concrete trigger.

The ALT/caption callback family has independent evidence. Since 2026-06-01,
four non-merge commits changed that concentrated chain: the initial review-set
ability, deterministic candidate scoring, preview-only quality guidance, and
the governed missing-ALT apply contract. Those changes repeatedly crossed the
same two public callbacks and their private candidate-quality helpers inside
the broader media trait.

The second slice therefore unfreezes only
`Media_Alt_Caption_Read_Methods`: `build_media_alt_caption_review_set`,
`build_media_alt_apply_plan`, and their 29 `media_alt_caption_*` private helpers
move together without wrappers. Other media methods, media definitions,
ability ids, schemas, callbacks, permissions, outputs, and runtime ownership do
not move. This evidence does not authorize another media-method extraction.

## 2026-07-30 Media Reference Discovery Slice

Recent media integrity and derivative-adoption changes repeatedly crossed the
same content-reference discovery helpers in `Core_Write_Package`. That satisfies
the repeated-change and security-isolation triggers for one bounded write-side
slice.

`Media_Reference_Discovery_Write_Methods` now owns only the private helpers that
derive old-to-new URL/file pairs, discover bounded candidate posts, and build
search needles. `Core_Write_Package` remains the ability definition, callback,
permission, mutation, rollback, and verification owner. No public ability id,
schema, annotation, callback, dry-run default, or final authorization changes.
The broader media lifecycle remains paused and requires independent evidence
before another extraction.

## 2026-09-30 Media Backup Lifecycle Slice

Since the pause, four merged pull requests each crossed the same media backup
lifecycle responsibilities inside the oversized write module: #107 hardened
backup restore transactions, #110 recorded replacement lineage and drift
guards, #113 added confirmed expired-backup cleanup, and #114 added the
administrator cleanup policy. `cleanup_expired_media_backups` changed six times
and the restore/lineage helpers two to three times each. That concentration
satisfies the repeated-change trigger for one bounded write-side slice, so the
maintainer approved resuming exactly this responsibility.

`Media_Backup_Write_Methods` now owns the 24 methods for backup retention
cleanup (including its cron cursor and policy helpers), backup restore
transactions, and file-replacement lineage/pointer state. `Core_Write_Package`
shrinks from 9,263 to 8,220 lines and remains the composition root, definition
owner, and owner of the shared commit guards, verification helpers, and cloud
media transaction machinery. No public ability id, schema, annotation,
callback, dry-run default, or final authorization changes; the media
fingerprint, lineage, and version-change source assertions now span the class
plus this trait.

This slice does not unfreeze the broader structural sequence. Remote intake,
derivative materialization, cloud transaction machinery, and the remaining
write responsibilities still require independent evidence before any further
extraction.

## 2026-09-30 Cloud Media Transaction Slice

Since the pause, the cloud derivative adoption and compare-and-swap transaction
machinery has been crossed by at least five merged pull requests: the artifact
integrity binding, the governed derivative adoption hardening, the analysis and
runtime gate hardening, the backup restore transaction hardening (#107 shares
the CAS and batch-manifest machinery), and the 0.5.4 release candidate. That
concentration, plus explicit maintainer authorization to continue after the
media backup slice, satisfies the repeated-change trigger for one bounded
write-side slice.

`Cloud_Media_Write_Methods` now owns the 38 methods for cloud derivative
adoption planning, artifact materialization, exclusive cloud file writes,
created-file manifests, atomic compare-and-swap post meta and post field
mutations with locked-row rollback, and adoption commit verification.
`Core_Write_Package` shrinks from 8,220 to 6,588 lines and remains the
composition root, definition owner, and owner of the shared commit guards,
verification helpers, and media filesystem core; the cloud transaction state
properties stay on the class. No public ability id, schema, annotation,
callback, dry-run default, or final authorization changes; the media
fingerprint, lineage, version-change, and Cloud Addon seam source assertions
now span the class plus this trait (and the backup trait where applicable).

This slice completes the media lifecycle responsibilities named in the paused
sequence item 3 except remote intake (upload pipeline), which stays in the
class. The broader structural sequence remains paused; the remaining clusters
(post workflow, taxonomy, comments, settings, content formatting, uploads) have
no post-pause change concentration and require independent evidence before any
further extraction.

## 2026-10-01 Evidence Inventory For Future Slices

This section is the refreshable evidence snapshot for the next session that
considers a slice. Re-measure before acting; do not trust these numbers as
current. Refresh commands:

```bash
# change concentration, last two months, per file
for f in $(find includes tests/run.php -name '*.php'); do \
  c=$(git log --since="$(date -v-2m +%Y-%m-%d)" --oneline -- "$f" | wc -l); \
  [ "$c" -gt 2 ] && echo "$c $f"; done | sort -rn

# current sizes
find includes tests/run.php -name '*.php' | xargs wc -l | sort -rn | head -16

# next analysis-level cost before ratcheting PHPStan
vendor/bin/phpstan analyse -l <next-level> --memory-limit=4G --no-progress
```

Ranked candidates as measured on 2026-10-01:

| Candidate | Size | Concentration since 2026-08-01 | Verdict |
| --- | ---: | ---: | --- |
| `includes/Admin/Test_Page.php` | 1,365 | 16 PRs (the #144-#160 admin iteration series) | Strongest current evidence. Slice shape: separate data assembly, rendering, and the scenario catalog behind a composition root, one focused PR, same gates. Do it alongside a confirmed next admin iteration, not speculatively. |
| `tests/run.php` | 9,630 | 18 PRs | Concentration is high, but item 5's trigger is observed collision or isolation pain, not raw count. When that pain is recorded, split by contract surface while preserving the single `composer test` entrypoint and the aggregate assertion count. |
| `includes/Packages/Read_Traits/Media_Read_Methods.php` | 6,612 | 3 PRs | Insufficient. The 2026-07-14 ALT/caption slice is the carving precedent when evidence arrives. |
| Definition providers (item 4) | ~75 inline ids in `Core_Read_Package`, ~63 / ~1,220-line block in `Core_Write_Package` | 2 PRs (read), slice work (write) | Trigger is a concrete definition change made harder by current ownership; not observed. The read side already has the `Read_Definitions/` seam and three extracted domains. |
| Remaining write clusters (post workflow, taxonomy, comments, settings, content formatting, remote intake) | — | 0 | Wait for evidence. |

Adjacent analysis ceiling, decided 2026-09-30: PHPStan stays at level 3.
Level 4 measured 726 findings; re-measure and ratchet only when the next level
costs single-digit fixes (see the write-package closeout lesson on ratcheting).
`Core_Destructive_Package` (1,473 lines) and `Core_Comment_Package` (1,188
lines) carry no change pressure and are not candidates.

For the mechanics of any future slice — moved-method ownership assertions in
`tests/run.php`, reconstruction and whitespace-only purity proofs, and the
advisory-reviewer timeout on large new files — read
[Write-Package Structural Slices Closeout - 2026-09-30](write-package-structural-slices-closeout-2026-09-30.md).

## Gate Per Slice

Each extraction must pass:

```bash
composer test:all
composer analyse:phpstan
composer check:boundary
composer perf:bootstrap
git diff --check
```

Run the relevant real WordPress smoke when a moved callback depends on WordPress
runtime behavior. Block-theme or Site Editor slices must also run
`composer smoke:block-theme-host-proof`.

## Ownership Rule

Traits own cohesive method implementations only. `Core_Read_Package` and
`Core_Write_Package` remain the package composition roots and definition owners.
No trait may introduce workflow runtime, routing decisions, approval storage,
audit storage, queues, schedules, retries, leases, or final authorization.
