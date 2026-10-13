# 0.6.0 Release Verification

Status: candidate gate passed on commit `6f7c2c9`; repeat the exact-revision
M4 evidence check after this record is committed, then merge and tag from the
protected release commit before publishing to WordPress.org.

## Scope

0.6.0 packages the operator read-only mode implemented in #242 and #243. The
AI Ability Set Overview now lets a site owner disable the `core_write` and
`core_destructive` packages with one narrowing-only, per-site option. The host
package filter remains an independent narrowing input; read packages, dry-run
and commit defaults, host approval, ability ids, schemas, and final write
authorization are unchanged. The release also includes the locale terminology
alignment from #241.

Gitee mirror enablement remains deferred and is not part of this release.

## Verification Plan

Run from the clean merged release commit:

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

## Verification Result (candidate commit `6f7c2c9`)

- `composer release:verify` passed with exact-revision M4 evidence.
- Default Local smoke: 479 assertions; light profile: 58 assertions.
- M4 WordPress 6.9.4/PHP 8.0: 478 assertions; WordPress 7.0/PHP 8.5: 478 assertions.
- Packaged Plugin Check passed with no errors; the existing accepted warning set remains.
- Known non-blocking test warning: the media test fixture omits
  `post_modified_gmt` at `includes/Packages/Read_Traits/Media_Read_Methods.php:4203`.
- Advisory review has no release-blocking requirement; #247's required checks passed,
  while its OpenCodeReview provider returned HTTP 400 with zero findings.

## Release Records To Fill After Verification

- Release commit: protected merge commit (to be recorded after PR merge)
- Git tag: `0.6.0` (create only from the protected release commit)
- WordPress.org SVN revision: pending publication
- Default Local smoke assertions:
- Light Local smoke assertions:
- Minimum WordPress/PHP smoke assertions:
- Current WordPress/PHP smoke assertions:
- Packaged Plugin Check result:
- Known warnings or accepted advisory-review gaps:

Do not tag or commit WordPress.org credentials from the repository or chat.
Type SVN credentials only in the user's terminal when the release candidate
has passed the full gate.
