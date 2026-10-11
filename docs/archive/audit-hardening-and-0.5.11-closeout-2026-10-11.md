# Audit Hardening and 0.5.11 Closeout - 2026-10-11

Status: historical closeout record with durable rules.
Date range: 2026-10-10 to 2026-10-11.

This document closes out the security/performance audit arc: the full-repo
audit, the six-phase hardening plan, the second defense-in-depth round
driven by maintainer decisions, and the 0.5.11 WordPress.org release that
shipped it all (r3738863; verification details in
`release-0.5.11-verification.md`).

## What Landed

| Change | Pull request |
| --- | --- |
| Per-post edit gates in publishing workflow reports (M1/M2) | #222 |
| Catalog fingerprint skipped on anonymous frontend contexts (P1) | #223 |
| Contract projection + ETag cached in a bounded transient (P3), hardened per advisory review | #224 |
| Read-cache version bump deduplicated across all invalidation hooks (P4) | #225 |
| Defense-in-depth batch: autoloader dot rejection, realpath fail-closed, merge-terms object authorization, uninstall preserve default | #226 |
| Pages-tree single full-object query (P5) | #227 |
| Unreleased changelog section for the batch | #228 |
| Built-in package registration skipped on anonymous frontend requests (P2) with host opt-out filter | #229 |
| Duplicate-check per-post gate + per-segment media path validation on both sides (F4/F5) | #230 |
| Host approval contract scoping section + `host_approval_unconditional` health-notice probe | #231 |
| Eleven site-level callbacks re-check capability + dispatch-layer capability re-check with `abilities.callback.denied` event (F7) | #232 |
| Release preparation 0.5.11 | #233 |
| 0.5.11 publication record | #234 |

All thirteen pull requests merged; no open pull requests and no unresolved
review threads remained at closeout. Cross-repo verification: the
`npcink-workflow-toolbox` quality matrix passed for this repository,
`npcink-governance-core`, and `npcink-ai-client-adapter` against the release
master.

## Deliberately Open Items

- **P6, media fingerprint laziness**: the media inventory list endpoints hash
  whole files per attachment. Laziness changes response content (schema and
  catalog-snapshot cascade) and a mtime+size-keyed cache adds a cache layer
  to the largest trait at its line ceiling. Deferred until agent-driven
  inventory polling shows up in profiling (rationale in #227).
- **F3, aggregate count oracles**: dashboard/status counts and search totals
  stay site-wide (count-only, no titles). Accepted as residual, documented
  in #222.
- Toolbox-repo housekeeping (October AI-review metrics) is tracked in the
  platform repository, not here.

## Root Causes And Lessons

1. **REST requests are indistinguishable from frontend at `init`.** The
   first boot-context-gate iteration tested `REST_REQUEST`, which WordPress
   defines only during `parse_request` — after `init` — while `is_admin()`
   is false on REST requests, so every REST request would have skipped
   package registration and served an empty catalog. The advisory
   OpenCodeReview run caught this as critical before merge (#229). Durable
   rule: any init-time context decision must use `wp_is_rest_endpoint()`
   (available since WP 6.5); `REST_REQUEST` is only valid from
   `rest_api_init` onward. This is also why the `else` arm of a compound
   boot condition deserves suspicion: the same iteration cleared the backup
   cleanup cron on frontend contexts because the branch was attached to the
   context gate rather than the package-enable check.
2. **A green `analyse:phpstan` does not imply the level-4 changed-file
   ratchet passes.** Level 4 counts `alreadyNarrowedType` findings —
   defensive `is_array()`/`is_string()` checks that PHPDocs make provably
   true — which the repository's analyse level suppresses (#224 hit this in
   CI). Durable rule: over PHPDoc-typed values prefer casts
   (`count( (array) $x )`) over always-true assertions, and run
   `composer check:phpstan-ratchet` locally whenever `includes/` files
   change, not only when files are added.
3. **Line-neutral editing is workable at any file size.** Every `includes/`
   change in this arc landed at or below its structure-ratchet ceiling by
   pairing additions with guard-clause blank-line removals in the same
   file. The mechanical rules that kept it reviewable: only remove blanks
   inside method bodies (never between methods), verify each deletion's
   context before deleting, and delete in descending line order. The one
   failure mode encountered was off-by-one line numbers from tooling
   (`awk`'s `getline` advances `NR`), which silently deleted a docblock's
   closing `*/` — a comment that swallows a method yet still parses, so
   `php -l` stays green. Verify a touched file's method inventory after
   bulk line deletions, not just its syntax.
4. **The dispatch layer beats sprinkled guards.** The F7 round started as
   per-callback capability re-checks; the advisory review pushed it to the
   registrar's observed dispatch wrapper, which covers all ~165 abilities
   uniformly. Per-callback checks remain only where they protect direct
   programmatic calls that bypass registration.
5. **The advisory review loop earns its keep when findings reach the
   author.** Across #229-#232 the OpenCodeReview runs delivered one
   critical, two high/medium security, and several maintainability
   findings; all were adopted or explicitly justified with replies, and
   every thread was resolved before merge. The publish-then-triage risk is
   real (auto-merge can complete before a review run finishes), so
   re-reading the delivered findings on merge remains necessary.

## Process Notes

- Release 0.5.11 was the first published through the fully agent-assisted
  keychain path documented in the release runbook: cached identity and
  `svn status -u` freshness were verified before the non-interactive
  commit, and no credentials were typed anywhere.
- Two new admin-notice strings shipped with full seven-locale parity using
  the hand-append GNU-wrap workflow; `msgfmt --check` gates the recompile.
- Locale `.po` files conflict on concurrent branches appending at EOF;
  resolution is take-master, re-append, `msgfmt` regenerate (hit during
  the #231/#232 overlap).
