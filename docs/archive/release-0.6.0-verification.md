# 0.6.0 Release Verification

Status: released from protected merge commit `9d94274`.
Git tag `0.6.0` and WordPress.org SVN publication are complete. PR #249
subsequently removed the unit-fixture warning recorded during the release gate.

## Scope

0.6.0 packages the operator read-only mode implemented in #242 and #243. The
AI Ability Set Overview now lets a site owner disable the `core_write` and
`core_destructive` packages with one narrowing-only, per-site option. The host
package filter remains an independent narrowing input; read packages, dry-run
and commit defaults, host approval, ability ids, schemas, and final write
authorization are unchanged. The release also includes the locale terminology
alignment from #241.

Gitee mirror enablement remains deferred and is not part of this release.

## Verification Result

- `composer release:verify` passed against the protected release merge commit
  `9d94274` with exact-revision M4 evidence.
- Local smoke: 479 assertions; light profile: 58 assertions.
- M4 WordPress 6.9.4/PHP 8.0: 478 assertions; WordPress 7.0/PHP 8.5: 478 assertions.
- Packaged Plugin Check passed with no errors; the existing accepted warning set remains.
- The unit suite remains at 16,954 assertions. The repeated `post_modified_gmt`
  fixture warning seen during release verification was removed by #249; the
  follow-up `composer test:core` is clean.
- Advisory OpenCodeReview runs for the release/docs PRs returned provider HTTP
  400 with zero findings; this check is advisory and outside the required
  merge contexts. Required CI checks passed.

## Verification Commands

Run from a clean checkout of the protected release commit:

```bash
composer check:boundary
composer check:wporg
composer test:all
composer analyse:phpstan
composer check:phpstan-total
git diff --check
WP_PATH=/path/to/wordpress composer release:verify
```

For the current Local.app site, use the live socket listed by
`docs/local-wpcli-smoke.md`. If the local Docker daemon is unavailable, create
exact-revision M4 evidence with `composer smoke:wp-m4` and pass it to
`release:verify` using `NPCINK_TOOLKIT_WORDPRESS_SMOKE_EVIDENCE` as described in
`docs/wordpress-org-release-runbook.md`.

## Release Records

- Release commit: `9d94274` (`release: prepare 0.6.0`, PR #248)
- Git tag: `0.6.0` (create only from the protected release commit)
- WordPress.org SVN revision: `3742960`
- Default Local smoke assertions: 479
- Light Local smoke assertions: 58
- Minimum WordPress/PHP smoke assertions: 478 (WordPress 6.9.4/PHP 8.0)
- Current WordPress/PHP smoke assertions: 478 (WordPress 7.0/PHP 8.5)
- Packaged Plugin Check result: passed with no errors; accepted warnings remain
- Known warnings: no unit-test warning after #249; only the documented Plugin Check warning set

Do not tag or commit WordPress.org credentials from the repository or chat.
Type SVN credentials only in the user's terminal when the release candidate
has passed the full gate.
