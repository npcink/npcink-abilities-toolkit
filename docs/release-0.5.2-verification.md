# 0.5.2 Release Verification

Status: release candidate verification.
Date: 2026-06-21.

This note records the `0.5.2` maintenance patch line. The release adds
read-only candidate review artifacts and keeps final write governance outside
Toolkit.

## Scope

- Added read-only review artifacts for image candidates, internal link
  candidates, taxonomy suggestions, and comment reply suggestions.
- Kept these helpers suggestion-only and host-governed: they do not create
  proposals, approve work, execute writes, own runtime state, or contact
  external providers.
- Updated acceptance contracts, first-party pack documentation, and static
  checks for the new handoff artifacts.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | Local source gate passed before version bump. |
| `composer analyse:phpstan` | Pass | PHPStan completed successfully. |
| `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" composer release:verify` | Pass | Packaged Plugin Check passed without errors for the worktree. |
| `composer acceptance:cross-repo-release` from Core | Pass | Cross-repository Core + Adapter + Toolkit acceptance passed before version bump. |

## Release Decision

Tag `0.5.2` from the verified maintenance commit after the local source gate,
PHPStan, real-site smoke, and cross-repository release acceptance pass. Do not
move the historical `0.5.1` tag.
