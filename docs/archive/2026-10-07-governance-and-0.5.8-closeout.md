# 2026-10-07 Governance Program And 0.5.8 Closeout

Status: closed. This milestone record covers the solo-development health
review, the sixteen pull requests that followed (#192, #193, #196-#209), and
the 0.5.8 WordPress.org publication. Dated record; lives in `archive/` per
the documentation cadence.

## What Shipped

Three waves, each independently merged and gated:

1. **Governance (#196-#200)**: Dependabot unblocked (PR body contract
   exemption keyed on the PR author), the structure ratchet and the
   PHPStan level-4 total ratchet, 27 dated records archived with a slimmed
   evergreen index and the per-milestone closeout cadence plus the
   meta-work budget rule, `sj/` renamed to `marketing/` with
   `composer closeout:check`, and the `composer test:core` iteration tier.
2. **Structural slices and level-4 chunks (#201-#204)**:
   `Media_Read_Methods.php` reduced 6,612 -> 5,190 through two
   byte-for-byte trait extractions with purity proofs; 62 dead
   null-coalescing removals; the changed-files ratchet hardened three
   times along the way.
3. **Release (#205-#209)**: 0.5.8 prepared, verified on the full release
   lane, published at SVN r3732350, WordPress.org serving stable 0.5.8;
   the previously unpublished 0.5.7 gap discovered, recorded, and its tag
   backfilled at `9e9f9ea`; the `.distignore` marketing contamination
   caught at staging and fixed before any public revision carried it.

Headline numbers: level-4 backlog 739 -> 675 (baseline lowered at release
per convention); largest file 6,612 -> 5,190 lines with ceilings locked by
the structure ratchet; docs root 92 -> 53 evergreen files plus 27 archived.

## Durable Lessons

These are the rules this milestone established; each already lives in an
active document — this section is the rationale trail.

1. **Adversarial review of gates pays for itself.** The advisory OpenCodeReview
   workflow found fourteen real defects in the new gate scripts across six
   rounds and correctly pushed back twice on over-aggressive dead-`??`
   removals (filterable surfaces, harness fixture-fed reads). The working
   protocol: reply "Fixed in <sha>" or "Declined with rationale + evidence"
   per thread, resolve, never let the local run block (demoted in
   `AGENTS.md`). See `archive/user-experience-hardening-closeout-2026-10-02.md`
   for the loop mechanics.
2. **Ratchets have move-blind spots until proven otherwise.** Three were
   found and fixed in `scripts/check-phpstan-ratchet.sh`: relocated
   findings in a new file (conservation rule), rename/delete source sides
   (`--no-renames` plus the deleted-side base total), and newness keyed on
   base-revision existence rather than count output. Each fix was verified
   with a synthetic probe (uncompensated growth fails; a pure rename of a
   27-finding file passes at total equality). Gates deserve the same
   adversarial review as product code.
3. **The dead-`??` boundary, settled over three chunks.** Safe to remove on
   core-constructor object properties (`WP_Post`, `WP_Term`) and in-file
   array shapes. Keep on filterable surfaces (`wp_get_attachment_metadata()`
   sizes, cron options, `wp_upload_dir()`, theme mods) and on every read fed
   by the unit harness's partial fixture objects (`get_post()` and
   `get_post_type_object()` stubs return partials) — there the `??`
   defaults are live guards for an intentionally supported input shape.
   Future chunks: `Core_Write_Package` first, category-filtered per line.
4. **A rename's blast radius includes packaging exclusion lists.** The
   `sj/` -> `marketing/` rename updated `.gitattributes` and the asset path
   but missed `.distignore`, staging 44 marketing files into the 0.5.8 SVN
   trunk; the runbook's "review the SVN status" step caught it. Checklist
   for directory renames: `.gitattributes`, `.distignore`, scripts'
   hardcoded paths, docs links, and the packaged zip contents.
5. **"Prepared" is not "published".** 0.5.7 was verified, merged, and
   changelogged but never tagged or committed to SVN, and nothing noticed
   for four days. Release verification notes now carry an explicit
   publication-completion record (see
   `archive/release-0.5.8-verification.md`), and the maintainer-takeover
   notes in the runbook anchor recovery on the newest verification note.
6. **Local gate runs need vendor-synced tooling.** The changed-files ratchet
   measures HEAD with the local vendor and base in a fresh `composer
   install` worktree; a stale local vendor (phpstan 2.2.7 vs locked 2.2.16)
   produced a false GREW verdict. `AGENTS.md` now says to resync before
   trusting a local ratchet failure.

## Where The New Rules Live

- Gate ladder and tiers: `AGENTS.md` (Verification), `composer test:core`.
- Structure and total ratchets: `composer check:structure-ratchet`,
  `composer check:phpstan-total`, baselines under `tests/fixtures/`.
- Documentation cadence and meta-work budget:
  `docs/solo-ai-development-workflow.md`.
- Post-merge steady state: `composer closeout:check`.
- Release publication and takeover: `docs/wordpress-org-release-runbook.md`.
- Slice mechanics and burn-down boundary: `docs/structural-split-plan.md`,
  issue #89.

## Deferred (Recorded, Not Forgotten)

- Product iteration direction — maintainer's call; the split plan works
  evidence-triggered, so the next slice waits for real change pressure
  (candidates: media inventory/inspection cluster; admin `Test_Page.php`
  alongside a confirmed admin iteration).
- `Core_Write_Package` dead-`??` chunk — 72 findings, safe subset
  demonstrably smaller; lowest priority.
- Six older locale machine-translation polish — recorded in the 0.5.7
  verification note.
- OCR zero-findings summary comment and the three monthly OCR metrics —
  toolbox-first template work, outside this repository.
