# Toolkit Maintenance And Release Preparation Closeout

Date: 2026-11-03 (Asia/Shanghai).
Snapshot source: `f23fb535c939bbdebd379cfa19f076d2e5ae1d77` (PR #286).
Status: useful source delivered; 0.8.1 prepared but not published; local Git
cleanup exceptions preserved. This is dated evidence, not a live-state promise.

## Scope And Delivered Work

Toolkit remains the reusable ability-contract and static-definition owner.
No model routing, workflow runtime, approval storage or final write authority
was added. The existing preflight report, scoring, approval steps and UI are
retained at the operator's request while repeated-use evidence accumulates.

| Historical concern | Resolution and evidence |
| --- | --- |
| Admin UI integration and safety fixes | Quiet admin/locales/screenshots landed through #271, #273, #276–#278. Nonce, array-input and redirect safeguards are documented in [Plugin Check Decisions](../plugin-check-decisions.md). |
| Retired cloud review conflicted with local policy | #279 aligned Toolkit with central locally initiated Qwen/DeepSeek policy. The publisher requires successful exact-base/head/diff receipts and triage; missing or failed review blocks publication. |
| Preflight could include body-derived data despite content omission | #280 hardened omission; subsequent independent HTTP proof confirmed no body or block HTML in the tested response. |
| Cleanup query had only a returned-count bound | #281 recorded 100 synthetic combinations at 100k/1m postmeta rows. The EXISTS alternative regressed on dense data; retain SQL/index. [Benchmark](../media-backup-query-benchmark.md) distinguishes emitted count from scan/latency bounds and records environment limits. |
| Level-four diagnostic debt | #282, #283 and #285 reduced 659→653→651→645, with ceiling 645. [Classification](../phpstan-level4-debt.md) explains provenance and retained guards; diagnostics are not runtime defect counts. |
| Chinese Slug mistranslation | #284 changed the status to “固定链接别名已设置。” and regenerated the MO. Catalog compilation and runtime translation checks passed. |
| Historical smoke content affected link recommendations | Two source/run/content-matched published fixtures were backed up and moved to trash; a draft with comments was retained. Independent preflight no longer returned those two targets. No permanent deletion or broad title filter; lifecycle root cause is not claimed fixed. |
| Screenshot candidates appeared undelivered | All four current 1366×920 exports match preserved candidate blobs. Admin/assets source has no layout changes since #278. Assets are already in master; only branch references remain. No fresh browser capture is claimed. |
| Next release | #286 prepared 0.8.1 metadata, changelog and [candidate note](release-0.8.1-verification.md). No Git tag, GitHub release or SVN commit was created. |

All Toolkit PRs #279–#286 passed their final local AI review, required CI and
protected squash merge. Related central policy was delivered in Toolbox #373;
that repository remains the policy owner. Review calls with the newly configured
`deepseek-v4.1-flash-1017` succeeded. This does not prove the old Qwen quota
recovered or isolate whether changing the key or model resolved HTTP 429.

## Runtime And Release Evidence

The existing explicitly approved recipe/preflight inputs were reused through
normal Adapter/Core read authorization. A separate TLS curl client fetched the
recipe and ran the same real draft preflight three times with content omitted
and a 30-day calendar. All returned HTTP 200 in 310.669, 279.944 and 281.065 ms;
five sections and semantic results were equal. Temporary application credentials
were revoked and their absence checked. Article, metadata and taxonomy
fingerprints remained unchanged. No write proposal or publication occurred.

The report still has six passing checks and three advisories for excerpt and
SEO title/description, risk 34, and `ready_for_host_approval=false`. No supported
SEO plugin or bridge detector was active; fallback metadata alone cannot prove
the site's actual SEO output is wrong. Do not write metadata without a confirmed
consumer. These are same-agent/site/draft consecutive samples, not human task
completion, recommendation relevance, production latency or third-party adoption.

Candidate head `108047d9096a5272307877e82716830dedc36bc5` and merged snapshot
source have equal Git trees. The merged source passed:

- `composer release:verify`: source gates, ordinary PHPStan, level-four total
  645 within ceiling 645, Local full/light smoke and packaged Plugin Check.
- Exact-source M4 WordPress 6.9.4/PHP 8.0 and WordPress 7.0/PHP 8.5: 478 smoke
  assertions per profile, plus independent provider/host proofs (31/151/43).
- Local full/light: 479/58 assertions. CLI environment was WordPress 7.1.4,
  PHP 8.4.21; the earlier HTTP samples used nginx PHP 8.5.12.

Local plugin mount, active-plugin/MU-plugin state and retained content were
restored or unchanged, with no smoke-media leak. The post-gate snapshot wrapper
initially rejected its new evidence filename; its explicit filename allowlist
was corrected and only the read-only snapshot rerun. The release gate itself
had exited zero; the original wrapper failure was preserved locally.

The targeted Toolkit/Core/Adapter source matrix passed 3/3 at candidate
`108047d`, Core `37705d59bd91b9056652f120d56cdae90c122046`, and Adapter
`11b7991869541d573bf80b8671156c1a4afbd92a`. Core was one commit behind its
fetched upstream; this is local-source evidence, not deployment acceptance.

The 98-file ZIP rebuilt from merged source has SHA-256
`5d99c10e61f396cca91f32fe47872ee9d04d4c299aebe5e20a753a5d545b20c3`, equal to
the candidate ZIP. A separate fresh SVN checkout stages only trunk, assets and
new tags/0.8.1: plugin files match the ZIP, assets match source exports, and the
recorded remote freshness check has no stale markers. All seven bundled PO/MO
message maps agree. Private fixtures and local evidence are excluded from ZIP.

## Git And Session Handoff

At this snapshot, main was clean on master equal to origin/master; remote had
only master and no open Toolkit PR. The clean task worktree was detached at the
same commit. Two worktrees and 20 topic refs remained. Ordinary `git branch -d`
refused the new squash-merged heads; no force deletion or ancestry manipulation
was used. A verified all-refs bundle preserves the heads. `composer closeout:check`
exited 1 for retained local refs and unavailable exact-head screenshot-alias
receipts; it did not certify the target cleanup steady state.

Closing the conversation does not remove its managed worktree. Do not remove
the app-primary task directory while it holds release staging, local evidence
and recovery bundles. If archiving later, first preserve needed ignored files
outside it: Git snapshots do not preserve ignored build/dist artifacts.

On this machine, raw logs, review receipts, PR metadata, backup and manifests
remain under the task worktree's `build/next-stage-2026-11-03/` and
`build/followup-2026-11-03/`. The latter contains `status.json`, `closeout.md`,
`toolkit-final-all-local-refs.bundle`, `release-merged-verification-state.json`,
and `wporg-svn-wc/`. Raw fixture backups stay local with mode 0600. This tracked
summary records decisions without copying private article content or secrets.

## Remaining Work And Resume Triggers

1. Accumulate real operator repeat-use, recommendation adoption and failure
   data. Keep the report unchanged until that evidence justifies a change.
2. Continue one file/rule PHPStan batch at a time for the remaining 645
   diagnostics. Preserve external/filterable value and filesystem guards.
3. Obtain an explicit maintainer exception before any force deletion of refs
   rejected by `-d`. Backups and squash receipts do not grant that permission.
4. Decide whether to publish 0.8.1. Before tagging/publishing, recheck the chosen
   HEAD, existing tags, SVN freshness and exact-source release evidence. This
   documentation follow-up changes HEAD after the verified snapshot; old runtime
   receipts cannot be relabeled for it even if package bytes remain identical.
   Use the [release runbook](../wordpress-org-release-runbook.md); credentials
   stay in the maintainer's terminal and publication needs separate acceptance.

Reusable lessons are incorporated into the existing
[audit standard](../system-audit-and-closeout-standard.md#follow-up-evidence-lessons).
This record closes delivered work with explicit residuals; it does not declare
all historical debt, production acceptance or Git cleanup complete.
