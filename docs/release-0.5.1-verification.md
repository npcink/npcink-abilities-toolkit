# 0.5.1 Release Verification

Status: release verification.
Date: 2026-06-07.

This note records the `0.5.1` maintenance patch line. The release does not add
or change first-party ability behavior. It publishes the GitHub handoff and
continuous gate hardening after the `0.5.0` release.

## Scope

- Added `composer audit:composer` and wired Composer dependency advisory audit
  into the default `composer test:all` source gate.
- Aligned GitHub Actions and PHPStan with the package runtime floor,
  `php >=8.0`.
- Published the canonical public GitHub repository:
  `https://github.com/muze-page/npcink-abilities-toolkit`.
- Removed the old Gitee remote from the local checkout and made GitHub the
  local `origin`.
- Documented the publication handoff and continuous gate baseline in
  [GitHub Publication And Continuous Gates](github-publication-and-continuous-gates.md).
- Upgraded GitHub Actions checkout from `actions/checkout@v4` to
  `actions/checkout@v5` to avoid the Node 20 runner deprecation path.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer validate --no-check-publish` | Pass | Composer metadata validated successfully. |
| `composer audit:composer` | Pass | No security vulnerability advisories found. |
| `composer test:all` | Pass | Composer validation, dependency advisory audit, project boundary, contract, consumer, workflow, official-stack, MCP, provider demo, catalog, WordPress.org guard, performance, lightweight tests, and PHP lint passed; `OK: 3757 assertions`. |
| `composer analyse:phpstan` | Pass | PHPStan completed successfully with PHP version target `80000`. |
| `composer smoke:wp` | Pass | Shared Local site smoke passed; default profile reported `Smoke OK: 266 assertions`, light profile reported `Smoke OK: 21 assertions`. |
| GitHub Actions `master` CI | Pass | Run `27071537457` passed on GitHub for commit `8bae2aa Prepare 0.5.1 maintenance release` across PHP 8.0 and 8.3. |

## Release Decision

Tag `0.5.1` from the verified maintenance commit after the local source gate,
PHPStan, real-site smoke, and GitHub Actions pass. Do not move the historical
`0.5.0` tag; its GitHub Actions failure is limited to the old PHP 7.2 matrix in
that historical workflow.
