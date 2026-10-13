# 0.7.0 Release Verification

Status: candidate verified; protected merge and publication pending.
Date: 2026-10-13.

## Change Envelope

- Target repository: `npcink-abilities-toolkit`; sibling repositories provide
  existing signed host evidence and the central matrix.
- Focus: release metadata, ten new runtime translations, ability acceptance
  documentation, packaged file-operation compatibility and release verification.
- Public contracts: document the already merged #251 media contracts and #252
  REST lifecycle fix; no new ids, schemas or authorization. Package verification
  additionally requires WordPress file deletion with verified postconditions.
- Expected files: plugin header, `readme.txt`, `CHANGELOG.md`, `languages/*`,
  focused ability/host-proof docs, the flagged-media backup/restore files and
  focused deletion failure regressions, plus this verification note.
- Non-goals and protected areas: no Gitee enablement, speculative structural
  split, fingerprint cache, host approval policy, runtime, queues or unrelated
  sibling worktree edits.
- Gates: `test:core`, `test:all`, PHPStan, boundary/wporg guards, diff checks,
  revision-bound minimum/current WordPress smoke, Local smoke and packaged
  Plugin Check through `release:verify`.
- Cross-repo matrix: run from Workflow Toolbox against explicit clean Toolkit,
  Core and Adapter revisions; report dirty sibling repositories separately.
- Rollback: revert the focused release change before publication; after
  publication use a new forward release. Never retag historical releases.

## Scope

0.7.0 is an additive contract release: the real-REST package registration fix,
the read-only `build-flagged-media-removal-plan`, the separate governed
`delete-attachment`, and deleted-attachment restoration through the existing
`restore-media-backup` contract. Host authorization remains mandatory. Restored
attachments receive a new id and old references require separate review.

The ten new media labels, descriptions and errors are translated in all seven
bundled locales. The existing WordPress.org listing translation is separate.

## Verification

Completed on the candidate worktree:

- `composer test:all`: passed, 17,128 main assertions and 40 flagged-media
  behavioral checks; 118 PHP files linted.
- `composer analyse:phpstan`: passed with PHPStan 2.3.1.
- `composer check:phpstan-total`: 669 level-4 findings, within baseline 675.
- Packaged Plugin Check: zero errors and 15 existing warnings. They concern
  translation loading, static nonce/input analysis and bounded database query
  analysis; no new broad suppressions were added.
- `git diff --check` and seven runtime `msgfmt --check` compilations: passed.
- Failure regression: the current refusal test fails on the original backup/
  restore implementation from `6dbc2c6` and passes on this candidate. A host
  refusal to delete keeps its manifest and retained bytes recoverable.

Release verification completed against source
`08576aa6ce8789b4a77b1ada8552b192275453bd`:

- Changed-file level-4 ratchet: 0 findings against 0 at `origin/master`.
- M4 exact-revision smoke: WordPress 6.9.4/PHP 8.0 and WordPress 7.0/PHP 8.5,
  478 assertions each, Docker 29.7.2. Archive SHA-256:
  `92f8f18d3b51174257691b1643dcfa05a5f98fc7eb09c06a1ed0f86c6ce31b63`.
- Local WordPress 7.1.1 candidate smoke: 479 default and 58 light assertions;
  26 real flagged-media checks. Candidate version was asserted before the gate;
  the original development symlink was restored afterward.
- Full `composer release:verify`: passed, including the source gates, PHPStan,
  revision-bound compatibility evidence, Local smoke and packaged Plugin Check.
- Central contract matrix: clean Toolkit/Core/Adapter source gates passed.
  Full status also detects unrelated active edits in Toolbox and Cloud Addon;
  this is not a claim that the whole platform is ready for release.
- Deterministic 0.7.0 package SHA-256:
  `7b946a3b13c1f79c62b89e2f58d858b5d5e97e5041990b8ed1faa5a631748009`.

Review checked correctness and failure retention, readability, ownership,
path/authorization security, and bounded file operations. No new dependency,
public authorization bypass or expanded Toolkit ownership was introduced.
POT regeneration changes source references; all seven locales still cover
1,138 messages. Listing PO separately covers 145 current strings, preserves
86 waiting suggestions and has matching HTML/placeholder checks.

The final protected merge must receive fresh revision-bound M4 evidence and
release verification before tagging. Documentation and marketing are excluded
from the deterministic package; recheck the checksum after merge.

## Host Evidence

Adapter's 2026-10-13 real HTTPS media acceptance passed both deletion branches,
governed restore, no mutation before approval, duplicate refusal and fixture
cleanup against Toolkit #251. Its administrator step is a test-context
simulation. The later Adapter release-readiness record separately proves actual
operator browser approval and signed execution for one governed deletion.

## Remaining Maintainer Steps

1. Pass the source and release gates and review the complete release diff.
2. Publish through a protected pull request and verify the merged source.
3. Create a new `0.7.0` tag from the verified release commit.
4. Prepare and inspect the fresh WordPress.org SVN working copy, publish using
   the runbook's terminal/Keychain credential path, then verify remote state.
5. Record publication evidence and perform non-destructive branch closeout.

Gitee remains deferred. Structural debt, file hashing and Core convergence
remain evidence-triggered maintenance work.
