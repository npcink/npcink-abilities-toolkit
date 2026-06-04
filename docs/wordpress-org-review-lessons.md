# WordPress.org Review Lessons

Status: active release gate.

This document records the WordPress.org review failure received on 2026-06-03
for the `npcink-abilities-toolkit` submission and turns it into repeatable
release rules.

## What Failed

The review was not a functional failure. Local contract checks, smoke tests, and
package behavior can pass while WordPress.org still rejects the plugin for
review-policy issues.

The review identified four blocking classes:

- direct loading or path construction for `wp-admin/includes/*`;
- admin request parameters such as `maa_tab` without a nonce check;
- inline admin CSS or JS emitted from PHP instead of static enqueued assets;
- generated output that must remain escaped at the final echo point.

An earlier automated pre-review email had already pointed at raw admin
`<script>` and `<style>` output. That signal must be treated as a test case, not
as advisory noise.

## Required Release Gate

Before uploading a package to WordPress.org, the release owner must run:

```sh
composer test:all
composer check:plugin-package:local
```

`composer test:all` includes `composer check:wporg`, a static guard for the
patterns above. `composer check:plugin-package:local` runs WordPress Plugin
Check against the packaged plugin in the local WordPress environment.

If local WP-CLI or Plugin Check is unavailable, do not mark the package ready.
Record the missing tool as a blocker instead of substituting functional smoke
tests.

## Coding Rules

- Use `plugins_url()` or plugin path constants for package-owned assets.
- Use static files under `assets/` for admin CSS and JS.
- Put runtime values needed by JavaScript in escaped `data-*` attributes or
  localized data only when Plugin Check accepts the output.
- Add nonces to admin GET links and forms before reading custom query args.
- Read custom admin query args only through nonce-verified helpers.
- Do not construct paths under `wp-admin/includes/` from `ABSPATH`.
- If a WordPress admin helper is not loaded, degrade gracefully or redesign the
  feature around public APIs; do not load admin include files from package code.
- Escape variables at the final output point with the context-specific escaping
  function.

## Review-Email Feedback Loop

Every WordPress.org email under `sj/q/` must be processed as follows:

1. Decode the top-level current message, excluding quoted older threads.
2. Extract every file and line example.
3. Search the current worktree for the exact pattern and adjacent variants.
4. Fix the whole pattern class, not only the cited line.
5. Add or update a local guard when the pattern can be checked statically.
6. Record the result in the release verification note.

This is part of the release process. Do not upload a corrected package until the
feedback-loop steps are complete.
