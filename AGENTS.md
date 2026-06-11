# Agent Development Guide

This repository is usually developed by one maintainer working with AI agents.
Keep the process light, but preserve reviewable changes, stable contracts, and
WordPress.org release safety.

## Startup Checklist

Before changing files:

1. Run `git status --short --branch`.
2. Identify the current branch and compare it with `origin/master` when the work
   is intended for a pull request.
3. Do not include unrelated local edits in your commit or pull request.
4. Read `CONTRIBUTING.md` and the relevant docs before changing ability
   contracts, governance boundaries, or release tooling.

## Project Boundaries

Npcink Abilities Toolkit owns ability registration helpers, category helpers,
schema and annotation normalization, first-party ability contracts, permission
callbacks, risk metadata, diagnostics redaction, and bounded performance checks.

This package does not own model routing, prompt selection, workflow runtime
execution, final write authorization, approval storage, billing, quota, cloud
execution, or MCP gateway policy.

Do not turn this plugin into a second workflow registry, second ability registry,
or second WordPress control plane. Write-like behavior must stay dry-run or
host-governed where the existing contracts require it.

## Branch And PR Discipline

- Use topic branches, normally `codex/short-description`.
- Use pull requests for `master`.
- Draft PRs are preferred while AI-generated changes are still being checked.
- Do not enable or require extra human reviewers by default; this is mainly a
  solo-maintainer repository.
- Required CI and required conversation resolution are the main merge gates.

## When To Open Issues

Do not create an issue for every small fix. Open issues for durable work that
needs tracking across more than one short session:

- WordPress.org review feedback;
- release tasks;
- ability contract changes;
- security or governance-boundary risks;
- multi-day or cross-repository work.

Small local fixes can go straight to a branch and pull request.

## Verification

Use the smallest relevant gate while iterating, then run the broader gate before
handoff or merge.

Default source gate:

```bash
composer test:all
composer analyse:phpstan
git diff --check
```

Release-facing gate:

```bash
WP_PATH=/path/to/wordpress composer release:verify
```

For this device, WP-CLI is installed globally:

```bash
/opt/homebrew/bin/wp
```

WP-CLI is only a command-line tool. It still needs a real WordPress site target
through `WP_PATH` or `--path`. Local.app WordPress sites are useful and should be
kept for smoke tests, Plugin Check, and release verification.

For Local.app database socket issues, pass the site-specific socket through
`WP_CLI_MYSQL_SOCKET`. See `docs/wordpress-org-release-runbook.md` for the
current release verification example.

## Release Rules

- Do not retag historical releases.
- Release work should update the plugin header version,
  `NPCINK_ABILITIES_TOOLKIT_VERSION`, `readme.txt`, `CHANGELOG.md`, and a
  release verification note.
- Use `composer release:verify` before WordPress.org publishing.
- Use `VERSION=X.Y.Z composer release:prepare-wporg` to prepare the local SVN
  working copy.
- WordPress.org SVN credentials must be typed only into the user's terminal.

## Prompt Replacement

The maintainer should not need to paste a long project prompt every time. Treat
this file as the reusable prompt for future AI sessions. If a tool does not read
`AGENTS.md` automatically, the first instruction should be only: "Read
`AGENTS.md` first."
