# Advisory Review Hardening Closeout - 2026-10-05

Status: historical closeout record with durable rules.
Date range: 2026-10-04 to 2026-10-05.

This document closes out the advisory OpenCodeReview hardening arc: the
usage audit that started it, the failure-notice template work, the
runner-image revalidation, the family-wide re-sync, and the two blocked
repositories that were unblocked along the way. Platform-level rules and
the canonical decline record live in
`npcink-workflow-toolbox` `docs/platform/ai-code-review-standard-v1.md`;
the git-transport fallback techniques used throughout live in
`npcink-workflow-toolbox` `docs/platform/git-transport-fallback-playbook-v1.md`.

## What Landed

| Change | Pull request |
| --- | --- |
| Failure-notice template + delivery-confirmation rule + `ubuntu-24.04` pin (template) | npcink-workflow-toolbox #180 |
| `issues: write` fix, marker dedup, pagination, bot-author match, success cleanup | npcink-workflow-toolbox #182 |
| Re-sync of the above into this repository + closeout-standard and CI-gates documentation | #188 |
| Runner canary workflow (manual, docs-only range, SHA-pinned action) | #189, #190, #191 |
| Template moves to `ubuntu-26.04` + commit-SHA action pin; family re-sync | npcink-workflow-toolbox #190; #194; governance-core #86/#90; ai-client-adapter #65/#72; cloud-addon #210/#215; eval-lab #102/#103; ai-cloud #1060; device-inventory #7/#9 |
| device-inventory npm audit recovery (axios 1.20.0, brace-expansion ^5.0.12, prod-blocking/dev-advisory audit split, warning annotation) | npcink-device-inventory #8, #10 |

All nineteen pull requests merged; no open follow-up pull requests and no
unresolved review threads remained at closeout.

## Root Causes Found

1. **PR #185 silent gap**: the OpenCodeReview run failed provider-side
   (`comments: 0`, "all N file review(s) failed" in the run artifact),
   posted nothing, and the advisory workflow blocked nothing - the pull
   request merged unreviewed. Fixed by the failure marker, the
   success-path cleanup, and the delivery-confirmation rule.
2. **ai-cloud contract failures**: not toolchain drift. The repository's
   checked-in `deploy/image-lock/authoritative-not-affected.json` CVE
   verification window (max 7 days, renewed by routine) expired around
   2026-10-03 and the contract tests fail closed on stale evidence by
   design; the repository's own weekly renewal (#1059) restored master.
   Lesson: before diagnosing environment drift, check date-windowed
   fixtures in supply-chain gates.
3. **device-inventory audit gate**: 12 new high-severity axios advisories
   (plus a brace-expansion advisory that swallowed the existing override
   pin) landed in the npm advisory database against a zero-commit
   dependency tree. The unfixable remainder (`braces`, build-time only,
   no patched release in any version) is carried as a non-blocking
   warning annotation until the tailwind 4 migration.

## Durable Rules From The Review Loop

The full canonical decline record (workflow-level `issues: write`,
cancelled-run markers, hardcoded bot login, canary nits) is in the
platform standard; the loop behavior worth restating here:

1. **The advisory reviewer catches real defects in CI changes.** Across
   this arc it independently found the `issues: write` 403 (would have
   disabled the notification exactly when needed), the
   `core.getInput`-versus-`context.payload.inputs` confusion in
   `workflow_dispatch` handlers, and the missing pagination. Budget one
   fix round for any new workflow code before expecting convergence.
2. **Round three of a diff is where nits start regenerating.** Rounds
   one and two produced defects; round three produced only declines.
   Stopping pushes at that point (per the recorded loop rules) merged
   every pull request without further cycles.
3. **Delivery confirmation is now procedural.** A green or absent
   `code-review` check proves nothing; a delivered review round is
   posted comments. Failed runs are retried with `/open-code-review`
   (observed twice failing provider-side on consecutive days; both
   recovered on retry).
4. **Pinned runner images and SHA-pinned actions are the steady state.**
   The family runs `ubuntu-26.04` deliberately (canary run 37258146682)
   and pins `alibaba/open-code-review@579b931` (v1.12.10). Image or pin
   changes go through the canary, never implicit drift.

## Verification Evidence

Final state, verified by byte comparison at closeout: all seven enrolled
repositories' `.github/workflows/ocr-review.yml` equal the canonical
template (cloud-addon deliberately ahead by one first-party bump:
`actions/github-script@v9` via its #213); the template itself at
`docs/platform/ai-code-review-workflow.yml` matches.

## Known Follow-Ups

- Fold `actions/github-script` v7 -> v9 into the next platform template
  touch (validated by cloud-addon #213; also retires part of the Node 20
  deprecation noise).
- Bump the review action pin when upstream ships the `upload-artifact`
  Node 20 fix; record the new tag-to-commit pair in the template
  comment.
- Watch the first real failed review run: the marker comment and its
  success-path cleanup get their first live exercise then.
- ai-cloud's CVE verification window renews weekly by its own routine;
  expect contract-test red if a renewal is late - that is fail-closed
  behavior, not an incident.
