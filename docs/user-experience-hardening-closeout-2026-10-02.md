# User Experience Hardening Closeout - 2026-10-02

Status: historical closeout record with durable rules.
Date: 2026-10-02.

This document closes out the user-experience hardening batch: a four-lens
audit of the plugin from its users' perspective (site operators, REST
callers, integrating developers, and failure-time behavior), the three
delivery batches that followed, and the ten-round advisory review
conversation that accompanied them. It records what landed, the durable
operating rules extracted from the review loop, and the declines with
their reasoning.

## What Landed

| Change | Pull request |
| --- | --- |
| Batch 1 - silent-failure surfacing: watched post-meta writes invalidate the read cache; dismissible admin health notices for missing Abilities API functions and an empty catalog; uninstall removes history meta and the backups tree behind two preserve filters; read-only Ability packages section with a visible write-safeguard note | [#178](https://github.com/npcink/npcink-abilities-toolkit/pull/178) |
| Batch 2 - contract honesty: `ability_catalog_available` runtime-detected from the live catalog route; quoted sha256 `ETag` with empty-304 `If-None-Match` handling and a filterable `Cache-Control` (default `private, no-cache`); response-shape convergence decision recorded in `docs/ability-response-shapes.md` | [#178](https://github.com/npcink/npcink-abilities-toolkit/pull/178) |
| Batch 3 - facade alignment: readme.txt Installation section, canonical repository URL, current screenshot descriptions with an SVN refresh reminder; README built-in ability list summarized against the canonical pack doc; admin hover-only titles made visible, copy-button restore, request button busy states, scenario empty state, translated page titles | [#178](https://github.com/npcink/npcink-abilities-toolkit/pull/178) |
| Post-merge review follow-ups: non-fatal `wp_verify_nonce` dismissal flow with redirect-back and surfaced expired-link errors; `X-WP-Nonce` suppression under shared-cache policies; HTTP status labeling; symlink-safe uninstall hardening details | [#179](https://github.com/npcink/npcink-abilities-toolkit/pull/179) |

Merged revisions: `c8b0af6` (#178, squash) and `5342cf2` (#179, squash).
No ability id, schema, dry-run default, approval ownership, or final
authorization semantics changed.

## Durable Rules From The Review Loop

The advisory OpenCodeReview workflow re-reviews the entire pull-request
diff on every push, so fixing findings generates new findings. The loop
converges (observed: 11, 7, 5, 4, 9, 5, 7, 3, 1, 2 findings per round
across the two pull requests, roughly 45 total). These rules kept it
converging instead of cycling:

1. **Batch a round, then push.** Pushing per-finding multiplies CI and
   review rounds. Collect one round of actionable findings, fix them in a
   single commit, push once.
2. **Fetch authoritative thread ids before resolving.** Reusing a
   previous round's thread id posts the reply into the correct thread
   (replies target comment ids) but silently resolves the wrong thread;
   this once left four threads unresolved and blocked auto-merge until
   re-fetched. The working protocol is: query unresolved threads, reply
   by comment id, resolve by the freshly fetched thread id - every round.
3. **Triage as fix or decline-with-rationale, never silently.** Declines
   recorded in the threads, each with its reasoning:
   - Weak ETag comparison for `If-None-Match` (declined four rounds in a
     row): RFC 7232 section 2.3 mandates the weak comparison function for
     If-None-Match, and this endpoint serves one deterministic JSON
     payload with no ranges or partial transforms.
   - Uninstall deleting the shared-prefix history meta by default:
     WordPress.org convention is that uninstall removes plugin-owned
     data, and this plugin's write methods are the lineage writers;
     predecessor integration is the explicit preserve-filter case.
   - Rendering filter-removed package slugs as `Off`: a slug absent from
     the resolved map already means `is_package_enabled()` returns false,
     so `Off` is the truthful operator state.
   - Re-emitting CORS headers on the 304 path: the contract endpoint is
     admin-authenticated and same-origin; cross-origin browser polling is
     not a supported integration.
   - Guarding against a poisoned `wp_upload_dir()` during uninstall: a
     host-level integrity failure that would break every plugin's
     uninstall equally.
4. **Scope cache invalidation hooks; do not maximize coverage.** A
   site-wide post-meta hook thrashes bounded report caches on stores with
   high-frequency meta writers. The working shape is a watched-key
   allowlist (the keys the cached reports actually read, extendable by
   filter), a once-per-request bump debounce, and a memoized filter
   result.
5. **REST short-circuits must account for what core would have emitted.**
   Serving 304 from `rest_pre_serve_request` skips core's headers; keep
   `ETag`/`Cache-Control` alive, re-emit `X-WP-Nonce` for cookie-auth
   rotation - but suppress it under publicly cacheable policies
   (`public` or `s-maxage`) so shared caches can never capture a
   user-bound nonce.
6. **Destructive file cleanup treats symlinks as files.** Check
   `is_link()` before `is_dir()` for every entry and for the root
   directory itself; a link is unlinked, never traversed.
7. **Fail-loud admin surfaces must not depend on the admin page package.**
   Health notices boot outside `admin_test_page`, and their status-page
   link renders only when that package actually registered the page.
8. **The WordPress.org static guard bans direct `$_GET[` reads in
   `includes/`.** Reading `$_REQUEST` instead matches the
   `check_admin_referer` convention, keeps admin-post GET parameters
   reachable, and stays CLI-testable under the unit harness.

## Verification Evidence

Recorded for the final merged revision (`5342cf2`):

| Evidence layer | Result |
| --- | --- |
| Repository source gate | `composer test:all`; 16,657 assertions plus repository guards |
| Static analysis | PHPStan; no errors |
| Diff integrity | `git diff --check`; pass |
| Boundary and WordPress.org static guards | Pass |
| Required CI on both pull requests | php (8.0), php (8.3), wordpress-smoke (minimum), wordpress-smoke (current), PR body contract; pass |
| Advisory AI review | All findings fixed or declined with recorded rationale; every review thread resolved |

## Known Follow-Ups

- Regenerate the POT template and backfill the bundled locale packs for
  the new admin strings (~25) before the next tagged release.
- Refresh the WordPress.org SVN screenshot assets to the Overview +
  Developer Tools layout; `readme.txt` carries the reminder next to the
  screenshot descriptions.

The follow-ups are release-time tasks; neither blocks maintenance merges.
