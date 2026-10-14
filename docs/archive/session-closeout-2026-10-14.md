# Release And Translation Stage Closeout

Date: 2026-10-14. Baseline: `79eb841cc186555b89505c4437e891d980d07b29`.
Status: delivered release and translation work is merged; local branch cleanup
has an explicit preservation exception. This is a dated snapshot, not live state.

## Change Envelope

- Target: Toolkit only; release/translation evidence and existing operating docs.
- Change: correct the PTE status, reconcile current translation guidance, and
  record branch preservation and reusable closeout lessons.
- Non-goals: no ability contracts, runtime, host policy, release artifact,
  Gitee enablement, structural split, fingerprint cache, or sibling edits.
- Public contracts touched: none.
- Expected files: this note, the translation status and PTE records, listing
  copy, release runbook, solo workflow, audit standard, and a dated addendum
  to the 0.7.0 verification note. Protect `includes/`, tests, languages,
  version metadata, CI and release scripts.
- Gates: `test:core`, `test:all`, PHPStan, boundary and `git diff --check`;
  final PR required checks. No new release or release-tier rerun is needed.
- Cross-repo matrix: status-only inventory; no shared contract changes, no
  sibling gate rerun or cleanup. Earlier gate evidence remains dated evidence.
- Rollback: revert the focused documentation commit; preserve recovery bundles.

## Disposition Of Historical Work

| Item | Disposition |
| --- | --- |
| Flagged-media contracts and real REST registration | Merged in #251 and #252; shipped in 0.7.0. |
| 0.7.0 release | Published; #256 and #257 record protected merge, SVN and public package evidence. |
| zh_CN Stable Readme | #258 and #259 record 146 current translations, no pending strings or warnings, and visible Chinese directory text. |
| Translation editor access | Already granted: the July multi-plugin request included Toolkit; the team replied `#zh_CN done.` on July 7. The current project editor list names `muze233`. |
| October PTE request | #260 recorded a published request; this audit discovered it duplicated completed July work. A fresh grant is not a prerequisite. No additional community message or deletion was performed in this audit. |
| Structural work, issue #89 | Intentionally open; resume only on the evidence triggers in the structural split plan. |
| Gitee default experiment | Unpublished and deferred pending mirror/access verification; preserve its local branch. |
| Fingerprint caching and Core convergence | Evidence-triggered maintenance; no current Toolkit contract gap is claimed. |

PTE evidence:

- [July request and completed team response](https://make.wordpress.org/polyglots/2026/07/03/pte-request-for-npcink-workflow-toolbox/#comment-305520)
- [Current project translation editors](https://translate.wordpress.org/locale/zh-cn/default/wp-plugins/npcink-abilities-toolkit/)
- [Updated translation evidence](wordpress-org-zh-cn-translation-status-2026-10-14.md)
- [Release evidence](release-0.7.0-verification.md)
- [Structural maintenance triggers](../structural-split-plan.md)

## Repository Inventory And Preservation

Before this documentation PR, Toolkit had clean `master` aligned with
`origin/master`, one main worktree, only `master` on the remote, and no open
PRs. Sixteen local topic branches remained:

| Local branch | Verified merged PR / disposition |
| --- | --- |
| `codex/refresh-operating-evidence` | #247 |
| `codex/prepare-0-6-0` | #248 |
| `codex/media-fixture-dates` | #249 |
| `codex/closeout-release-060` | #250 |
| `codex/flagged-media-abilities` | #251 |
| `codex/rest-runtime-registration` | #252 |
| `codex/rest-package-bootstrap` | Same exact head as #252 |
| `codex/record-0-6-0-svn-revision` | #253 |
| `codex/docs-closeout-sync` | #254 |
| `codex/docs-release-followup` | #255 |
| `codex/release-0-7-0` | #256 |
| `codex/closeout-release-070` | #257 |
| `codex/translation-matrix-2026-10-14` | #258 |
| `codex/translation-review-closeout-2026-10-14` | #259 |
| `codex/pte-request-closeout-2026-10-14` | #260 |
| `codex/zh-cn-docs-mirror-default` | Unpublished Gitee experiment; preserve |

Each of the fifteen merged heads matches GitHub's merged PR head record, and
each squash merge commit is an ancestor of `origin/master`. Git ancestry does
not mark the topic heads themselves as merged; `git branch -d` refuses them.
No force deletion was authorized or performed. The new documentation branch
also needs classification after its protected merge.

All sixteen pre-audit refs and their complete history are preserved in the
verified local recovery bundle:

`/Users/muze/gitee/.worktree-backups/toolkit-session-topics-2026-10-14.bundle`

The matching `.json` ledger records exact head SHAs, merge SHAs and PR URLs.
`git bundle verify` passed. These are local recovery artifacts, not published
source or an assertion that the deferred experiment is merged.

`composer closeout:check` passed on the baseline but only checked local Git
ancestry. It did not detect the fifteen squash-merged leftovers and therefore
does not prove all branches are cleaned. The manual inventory above is required.

## Cross-Repository Evidence Limits

The status-only central matrix at `2026-10-14T06:29:09+00:00` found Core on
`codex/docs-week-2026-10-13` with four changed files, AI Cloud on
`codex/provider-refresh-advisory` with two changed deployment scripts, and
Workflow Toolbox at a clean detached HEAD. Toolkit, Adapter, Cloud Addon and
Eval were clean. All reported ahead/behind counts were zero.

These sibling changes belong to other work and were preserved. This inventory
does not supersede the earlier seven-repository passing gate run, nor does that
historical run prove the platform is clean now.

PR #258's advisory provider failed with HTTP 400 and delivered no review;
required checks passed. Do not report that failed review as zero findings.
Later PRs #259 and #260 have successful advisory runs.

## Durable Lessons

- Check prior completed requests and the live project editor list before
  contacting Polyglots; a local unsent draft does not prove no prior request.
- Source coverage, import, approval, localized directory visibility and
  runtime language packs are separate claims; record evidence for each.
- Preserve historical release evidence and add dated corrections instead of
  presenting old pending work as today's status.
- Report merged work, intentionally deferred experiments and cleanup exceptions
  separately. Do not close a durable maintenance issue to make an inventory empty.
- Check the limits of the validator: status-only matrices and ancestry-only
  branch checks cannot prove gate success or complete squash-branch cleanup.

These rules are incorporated into the existing
[release runbook](../wordpress-org-release-runbook.md),
[audit standard](../system-audit-and-closeout-standard.md), and
[solo workflow](../solo-ai-development-workflow.md).

## Verification Of This Documentation Change

- `composer test:core` and `composer test:all`: passed; 17,128 main assertions,
  40 flagged-media behavioral checks and 118 PHP files linted.
- `composer analyse:phpstan`: PHPStan 2.3.1, no errors.
- Explicit `composer check:boundary` and `git diff --check`: passed.
- Relative documentation links: verified against existing files.
- Review: checked dates and claims against public request/comment/editor
  evidence and exact GitHub head mappings. Historical pending statements are
  labeled as historical. The change adds no runtime code, dependencies,
  permission behavior or performance cost. No blocking local finding remains.
- Publication uses the protected repository publisher; the final PR records
  its actual CI/advisory outcome and merge revision. No release or retagging.
