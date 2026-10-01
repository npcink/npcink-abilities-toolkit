# Delete Attachment Ability Candidate

Status: under revision after the 2026-10-02 acceptance review; see the review
section at the end before implementing.

## Host Workflow Proof

`npcink-workflow-toolbox` now surfaces a bounded, suggestion-only flagged
media review set (`docs/flagged-media-review.md` in that repository, merged
2026-09-30). When an operator confirms a flagged attachment, the governed
write path falls short today: the existing
`npcink-abilities-toolkit/delete-media-permanently` ability performs a plain
forced deletion with a minimal preview and no deletion policy branches, no
referenced-by summary for reference cleanup, no backup branch, no operator
reason, and no idempotency key. The untracked fallbacks the host wants to
avoid (manual administration without audit trail, branch policy, or reference
cleanup) remain. That failed host workflow is the motivation for this
candidate, per the candidate rule recorded in
`docs/ability-candidates/npcink-ai-core-ability-candidates.md`.

## Proposed Ability

`npcink-abilities-toolkit/delete-attachment`

A write-like WordPress ability with a mandatory dry-run preview, owned here as
a reusable contract and callback, executed only after host approval through
the existing governed handoff (Core proposal, Adapter profile, Toolkit
callback).

## Input Schema

| Field | Type | Rules |
| --- | --- | --- |
| `attachment_id` | int | Required, must reference an existing attachment. |
| `deletion_policy_branch` | enum | `illegal_content_no_backup` or `governed_with_backup`. Required. |
| `reason` | string | Required, bounded length, operator-entered review reason. |
| `idempotency_key` | string | Required, unique per requested deletion. |

## Dry-Run Preview

The dry-run preview must return, without changing anything:

- resolved attachment identity (id, file path, size, MIME type, upload date);
- a referenced-by summary from the existing reference helpers (posts embedding
  the URL, settings storing the URL) so the host can group reference-cleanup
  actions into one proposal;
- for `governed_with_backup`: the concrete backup plan evidence fields that
  Core preflight will require (backup location, retention window, restore
  token contract);
- for `illegal_content_no_backup`: an explicit no-backup attestation field the
  operator must confirm, stating no backup, local copy, or Cloud artifact
  retention will be created.

## Branch Behaviors

- `illegal_content_no_backup`: delete the file and the attachment record
  without creating any backup artifact. The audit record must describe what
  was deleted and why without retaining the content itself. Reporting or
  referral to authorities stays a manual operator responsibility outside this
  ability.
- `governed_with_backup`: reuse the existing Toolkit backup, lineage,
  verification, and restore machinery before deletion, matching the Media
  Library Optimization precedent. Restore is available only on this branch and
  only within the retention window.

## Execution Posture

- Write-like: callback performs the deletion only after host approval; the
  ability itself never approves, schedules, retries, or queues.
- Restore belongs to the existing restore ability; this candidate adds no
  second restore path.
- The branch is chosen by the reviewing operator; this contract records and
  validates the choice, it does not classify content.

## Out Of Scope

- content-safety classification or detection (Cloud runtime responsibility);
- automatic branch selection, automatic proposal creation, or auto-approval;
- bulk deletion loops (each attachment is one bounded action);
- report/referral workflows for illegal content;
- any second backup implementation, media registry, or audit store.

## Acceptance Checklist

Before this candidate becomes an implemented ability:

- [ ] schema and dry-run preview registered through the standard Toolkit
      registration helpers;
- [ ] `governed_with_backup` dry-run carries the backup evidence fields Core
      preflight will require;
- [ ] `illegal_content_no_backup` dry-run carries the no-backup attestation
      field and the callback path provably creates no backup artifacts;
- [ ] audit integration records deletion descriptions without content
      retention;
- [ ] restore coverage exists only for `governed_with_backup`;
- [ ] the cross-repo boundary matrix and Core governance catalog record the
      new ability id.

## Acceptance Review (2026-10-02)

Verdict: **revise before acceptance.** The failed-host-workflow motivation is
verified and real, but the original premise ("no reusable WordPress ability
exists for attachment deletion") was factually incorrect and has been
corrected above. The candidate must be re-argued as a delta over the existing
ability before it can be accepted.

Verified facts:

- `npcink-abilities-toolkit/delete-media-permanently` exists in
  `Core_Destructive_Package`: host-approved, dry-run-by-default forced
  deletion via `wp_delete_attachment( $id, true )`, gated on `delete_posts`
  plus the `media.write` scope, with a minimal preview (attachment id and
  parent post only).
- The toolbox evidence document exists as claimed
  (`npcink-workflow-toolbox/docs/flagged-media-review.md`) and already
  specifies the dual-branch policy as the intended input for this contract.
- The genuine gaps the failed workflow needs, none of which the existing
  ability provides: deletion policy branches (`illegal_content_no_backup` /
  `governed_with_backup`), a referenced-by summary for grouping reference
  cleanup into one proposal, backup evidence fields or a no-backup
  attestation in the preview, an operator-entered reason, and an idempotency
  key.

Required revisions before acceptance:

1. Re-argue the candidate from the delta above, not from absence.
2. Decide the contract vehicle with issue #116 (duplicate ability
   registration and default destructive package policy) in view:
   - new `npcink-abilities-toolkit/delete-attachment` id with the governed
     contract, leaving `delete-media-permanently` as the plain force-delete
     granularity (mirroring how `trash-post` and `delete-post-permanently`
     coexist), or
   - extend `delete-media-permanently` in place — noting that making
     `deletion_policy_branch` and `reason` required breaks existing callers
     and therefore effectively requires a new contract version anyway.
3. Keep the existing acceptance checklist unchanged; it already binds
   implementation to the no-backup proof, audit-without-content-retention,
   and restore-coverage constraints.
