# 0.7.0 Release Verification

Status: published; SVN and the public directory API both report 0.7.0.
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

## Protected Release And Publication

- Protected release PR: [#256](https://github.com/npcink/npcink-abilities-toolkit/pull/256).
- Release commit and Git tag `0.7.0`:
  `46c1405422ae0ac1f8ea6820b391b48d09326a14`.
- All five required CI checks passed; no required gate was bypassed.
- Exact merged revision M4 smoke passed both 478-assertion profiles. The official
  WordPress download stalled; a temporary runner reused ZIP caches after archive
  integrity and embedded version checks, without changing tracked gate code.
  Source archive SHA-256: `35bb983fbc0229b0cfdfd6d2fb7f094068ea93001732f64d6104bac9e709f31e`.
- Full merged-source `release:verify` passed again: 479/58 Local assertions,
  26 flagged-media checks, PHPStan level-4 total 669 and Plugin Check zero errors.
- Final deterministic ZIP checksum matches the candidate checksum above.
- WordPress.org SVN revision: **3744229**. Before commit, freshness/scope were
  checked and trunk plus the new tag matched all 88 ZIP files byte for byte.
  Existing `muze233` Keychain credentials were used non-interactively; no
  credential was read or copied into evidence.
- Remote SVN tag export matches all 88 verified package files. After initial
  cache delay, the public directory API reports 0.7.0 and its versioned download.
  The public download ZIP also matches all 88 verified package files.

Advisory review delivered no verdict: Codex reported review quota exhaustion;
OpenCodeReview attempts 1 and 2 failed at the provider layer (HTTP 400). The
standard publisher's protected auto-merge completed before advisory delivery.
The service exception and independent local review basis are recorded in #256;
these failures are not reported as zero-findings reviews.

## Host Evidence

Adapter's 2026-10-13 real HTTPS media acceptance passed both deletion branches,
governed restore, no mutation before approval, duplicate refusal and fixture
cleanup against Toolkit #251. Its administrator step is a test-context
simulation. The later Adapter release-readiness record separately proves actual
operator browser approval and signed execution for one governed deletion.

## Remaining Maintainer Steps

The following is the October 13 snapshot. October 14 update: listing import,
approval and Chinese directory visibility are complete, and editor access was
already granted in July. See the
[translation status](wordpress-org-zh-cn-translation-status-2026-10-14.md) and
[session closeout](session-closeout-2026-10-14.md) for the corrected permission
evidence, current branch preservation ledger and later matrix inventory.

1. Import the prepared WordPress.org zh_CN listing translations and obtain
   editor approval. The PTE draft is unsent; see the current translation note.
2. Complete branch closeout within `AGENTS.md`'s safe deletion rule. Historical
   squash-merged branches have verified PR head records and a recovery bundle;
   `git branch -d` refuses their ancestry, so they remain until an explicit
   maintainer exception authorizes force deletion.

Gitee remains deferred. Structural debt, file hashing and Core convergence
remain evidence-triggered maintenance work. Current all-platform acceptance is
not claimed: the full matrix retains dirty Toolbox/Cloud Addon rows and the
Cloud Addon settings file exceeds its line ceiling by one line.
