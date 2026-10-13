# 0.6.0 Release Verification

Status: release candidate prepared on `codex/release-0.6.0`; the final
verification must run against the merged release commit before tagging or
publishing to WordPress.org.

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

## Release Records To Fill After Verification

- Release commit:
- Git tag: `0.6.0`
- WordPress.org SVN revision:
- Default Local smoke assertions:
- Light Local smoke assertions:
- Minimum WordPress/PHP smoke assertions:
- Current WordPress/PHP smoke assertions:
- Packaged Plugin Check result:
- Known warnings or accepted advisory-review gaps:

Do not tag or commit WordPress.org credentials from the repository or chat.
Type SVN credentials only in the user's terminal when the release candidate
has passed the full gate.
