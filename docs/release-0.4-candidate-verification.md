# 0.4 Candidate Verification

Status: release candidate checklist.
Date: 2026-05-30.

This checklist records the release-candidate scope and gates after adding
read-only workflow definition runtime discovery and Core governance handoff
hardening. It does not create a tag or publish a release.

## Release Candidate Gates

Before tagging `0.4.0`, the release owner should verify:

- [x] Public docs describe the project boundary and keep workflow runtime,
  approval, audit, quota, model routing, and final write governance outside this
  package.
- [x] `composer test:all` includes composer validation, project-boundary checks,
  consumer handoff checks, catalog governance audit, lightweight contract tests,
  and PHP lint.
- [x] Core governance catalog fixture covers representative write, comment, and
  workflow-discovery contracts.
- [x] Consumer acceptance fixture proves hosts can build Core proposal payloads
  from discovered ability metadata without depending on internal classes.
- [x] Duplicate-id and governance drift audit script is available for local
  cross-repo checks.
- [x] Release zip has been built and inspected.
- [x] Final smoke commands have been run against each target WordPress/PHP
  profile required by the release owner.
- [x] The release owner has decided whether the current unreleased line ships
  as `0.4.0`.

## Candidate Scope

- Added `Magick_AI_Abilities\Workflow\Workflow_Definition_Provider` as the
  production source of workflow recipe definitions.
- Added public read-only helpers:
  - `magick_ai_abilities_get_workflow_definitions()`
  - `magick_ai_abilities_get_workflow_definition( $recipe_id )`
- Added read-only Abilities API discovery abilities:
  - `magick-ai-abilities/list-workflow-recipes`
  - `magick-ai-abilities/get-workflow-recipe`
- Kept workflow definitions declarative only: no runtime state, scheduling,
  retries, queues, model routing, prompt ownership, approval store, audit store,
  quota, or final WordPress write authority.
- Updated `magick-ai-core` smoke coverage to prefer the runtime helper and fall
  back to the shared replay fixture only for older local provider versions.
- Added the Core governance catalog snapshot fixture for:
  - `magick-ai/create-draft`
  - `magick-ai/set-post-seo-meta`
  - `magick-ai/approve-comment`
  - workflow definition discovery abilities
- Added permission matrix and schema boundary audit docs for write/destructive
  abilities.
- Added a consumer example that discovers ability metadata and prepares a Core
  proposal payload.
- Hardened write-like contracts with `requires_approval`, explicit dry-run and
  commit defaults, and bounded idempotency keys.
- Expanded smoke coverage for REST-exposed `risk_level`, `requires_approval`,
  `input_schema`, and `output_schema`.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer test` | Pass | `OK: 2562 assertions`; snapshot checks are covered by the lightweight suite. |
| `composer test:all` | Pass | Composer validation, project boundary, consumer handoff, catalog audit, tests, and PHP lint passed; `Linted 32 PHP files`. |
| `composer check:consumer` | Pass | Validates proposal payload construction from discovered contracts for Core governance consumers. |
| `composer check:catalog` | Pass | Validates catalog fixture risk metadata, write controls, approval aliases, and duplicate ids. |
| `composer perf:smoke` | Pass | Content inventory, SEO/GEO cached report, publish preflight, old article refresh, and comment compliance handoff were within budget. |
| `git diff --check` | Pass | No whitespace errors. |
| `composer smoke:wp` full profile | Pass | `Smoke OK: 193 assertions`; includes REST detail checks for governance metadata and schemas. |
| `composer smoke:wp` light profile | Pass | `Smoke OK: 14 assertions`; workflow definition discovery is disabled in the light profile. |
| `magick-ai-core composer test:all` | Pass | PHP lint and static contracts passed. |
| `magick-ai-core composer smoke:wp` | Pass | Core discovered capabilities from `magick-ai-abilities` and found shared workflow definitions at runtime. |

## Package Evidence

- Package path: `dist/magick-ai-abilities-0.4.0.zip`
- Package inspection: `unzip -Z1` reports 60 files and 17 directories under the
  `magick-ai-abilities/` prefix.
- Version inspection: packaged `magick-ai-abilities.php` reports
  `Version: 0.4.0` and `MAGICK_AI_ABILITIES_VERSION` is `0.4.0`; packaged
  `readme.txt` reports `Stable tag: 0.4.0`.

Smoke command:

```bash
WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP=/opt/homebrew/bin/php \
WP_CLI_ERROR_REPORTING=8191 \
WP_CLI_MYSQL_SOCKET="/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" \
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" \
composer smoke:wp
```

Core consumer smoke command:

```bash
WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP=/opt/homebrew/bin/php \
WP_CLI_ERROR_REPORTING=8191 \
WP_CLI_MYSQL_SOCKET="/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" \
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" \
MAGICK_AI_ABILITIES_PATH="/Users/muze/gitee/magick-ai-abilities" \
composer smoke:wp
```

## Remaining Before Release

- Tag `0.4.0` only after confirming the packaged zip should be treated as the
  release artifact.

## Cross-Repo Audit Command

When a consuming host exports its own ability catalog JSON, compare it with this
package snapshot before merging cross-repo governance changes:

```bash
php scripts/audit-ability-catalog.php \
  tests/fixtures/core-governance-catalog-snapshot.json \
  /path/to/host-catalog.json
```
