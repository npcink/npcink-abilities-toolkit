# 0.5.2 Release Verification

Status: released.
Date: 2026-06-22.

This note records the `0.5.2` maintenance patch line and the final
WordPress.org publication checkpoint. The release adds read-only candidate
review artifacts, reshapes the admin page for ordinary site operators, and
keeps final write governance outside Toolkit.

## Scope

- Added read-only review artifacts for image candidates, internal link
  candidates, taxonomy suggestions, and comment reply suggestions.
- Kept these helpers suggestion-only and host-governed: they do not create
  proposals, approve work, execute writes, own runtime state, or contact
  external providers.
- Updated acceptance contracts, first-party pack documentation, and static
  checks for the new handoff artifacts.
- Reworked the wp-admin surface around the product positioning that ordinary
  site owners need to know whether AI abilities are available, safe to inspect,
  and ready for a host runtime to consume.
- Kept the checks limited to two bounded, read-only checks and added summary
  tables so non-developer users can understand the effect before opening raw
  JSON details.
- Prepared the WordPress.org listing description, FAQ, screenshots, and release
  assets around the same operator-first positioning.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | Local source gate passed before version bump. |
| `composer analyse:phpstan` | Pass | PHPStan completed successfully. |
| `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" composer release:verify` | Pass | Packaged Plugin Check passed without errors for the worktree. |
| `composer acceptance:cross-repo-release` from Core | Pass | Cross-repository Core + Adapter + Toolkit acceptance passed before version bump. |
| WordPress.org SVN publication | Pass | Committed revision 3581125 with `tags/0.5.2` and screenshots 1-4 present under remote assets. |

## Release Decision

Published `0.5.2` after the local source gate, PHPStan, real-site smoke,
Plugin Check, and cross-repository release acceptance passed. Do not move the
historical `0.5.1` tag.

## Stage Summary

This stage moved the plugin's public surface from a developer-first diagnostics
page toward an operator-first Site AI Abilities page. The page now answers four
ordinary-user questions first: whether the package is working, which abilities
are available, which checks are safe to run, and where host/developer clients
can copy discovery values.

The checks remain intentionally narrow. The available abilities check confirms
that WordPress can expose the catalog through the Abilities API, while the
diagnostics check confirms that the local site can return a redacted health
snapshot. They do not prove that an AI model, workflow runner, billing system,
MCP gateway, or final write approval system is configured; those responsibilities
remain outside Toolkit.

The WordPress.org listing material was updated to match that positioning:
description copy, FAQ, screenshot captions, screenshots 1-4, and source asset
notes now describe Toolkit as a capability package and inspection surface for
AI hosts and clients, not as a model runner or workflow control plane.
