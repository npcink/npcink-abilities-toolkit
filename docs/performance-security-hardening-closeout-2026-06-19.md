# Performance/Security Hardening Closeout

Status: merged, no release planned
Date: 2026-06-19

This note records the closeout for PR #57:

```text
https://github.com/muze-page/npcink-abilities-toolkit/pull/57
```

The work was merged to `master` as:

```text
4381729 Merge pull request #57 from muze-page/codex/perf-security-hardening
```

This was a performance and security hardening pass only. It did not start a
new release, add a new ability surface, or change Toolkit ownership boundaries.

## Summary

The merged work hardened six areas:

- Catalog observability now caches the catalog fingerprint per request and
  skips duplicate shutdown catalog checks when no catalog change occurred.
- Diagnostics log reading now prefers a bounded seeked tail read before falling
  back to WordPress filesystem reads, avoiding full large-log loads for bounded
  support output.
- Remote media sideloading now streams to a temporary file when WordPress
  provides temp-file support, then moves the file into uploads before MIME
  validation and attachment creation.
- Package-owned media file writes, copies, and moves now enforce containment
  under the WordPress uploads basedir.
- `npcink-abilities-toolkit/wp-ops-diagnostics-detail` now defaults to a
  lighter `profile=summary`; heavier plugin rows, cron events, log inspection,
  integration, database, role/widget, and current-user details require
  `profile=detail`, `profile=forensics`, or explicit include flags.
- The contract audit now has an explicit input-schema `additionalProperties`
  allowlist, so future schema loosening must be intentional and reviewed.

## Boundary Decision

Keep this as a Toolkit-local hardening change.

The change stays inside Toolkit-owned responsibilities:

- ability registration observability;
- bounded diagnostics and redaction behavior;
- media file operation safety under uploads;
- schema and contract discipline;
- smoke/performance/packaging gates.

It does not move approval storage, audit truth, model/provider routing,
workflow runtime execution, final write authorization, quota, billing, or MCP
gateway policy into Toolkit.

## Verification

The following source gates passed before merge:

```bash
composer test:all
composer analyse:phpstan
git diff --check
```

GitHub Actions passed for the PR:

```text
php (8.0): pass
php (8.3): pass
```

The Local WordPress smoke passed against:

```text
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public"
```

Results:

```text
default profile: Smoke OK: 359 assertions
light_core_read profile: Smoke OK: 56 assertions
```

The full release-facing gate also passed:

```bash
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" composer release:verify
```

This included packaged Plugin Check:

```text
Success: Checks complete. No errors found.
Packaged Plugin Check passed without errors for npcink-abilities-toolkit (WORKTREE).
```

## Local Environment Note

The Local WordPress site already had the `plugin-check` plugin installed but it
was inactive. It was activated so `composer release:verify` could run
`wp plugin check`.

Current relevant Local WP state from this closeout:

- `npcink-abilities-toolkit` is active and symlinked to this checkout.
- `plugin-check` is active so release-facing package checks can run.

If a future smoke or release verification reports:

```text
Error: 'check' is not a registered subcommand of 'plugin'.
```

verify that the `plugin-check` plugin is installed and active in the target
WordPress site.

## Post-Merge Cleanup

Cleanup was completed after merge:

- local checkout is on `master`;
- `master` is aligned with `origin/master`;
- worktree is clean;
- remote branch `origin/codex/perf-security-hardening` was pruned;
- no local `codex/*` branch remains;
- only one worktree remains for this repository.

Confirmed final HEAD:

```text
4381729 (HEAD -> master, origin/master, origin/HEAD) Merge pull request #57 from muze-page/codex/perf-security-hardening
```

## Release Decision

No release is planned for this closeout.

Do not update the plugin header version, `NPCINK_ABILITIES_TOOLKIT_VERSION`,
`readme.txt`, `CHANGELOG.md`, tags, or WordPress.org SVN for this work unless a
separate release task is opened.

The correct state after this note is to stop here and observe the merged
hardening on `master`.
