# 0.4 Candidate Verification

Status: internal candidate evidence recorded.
Date: 2026-05-29.

This receipt records the verification state after adding read-only workflow
definition runtime discovery. It does not create a tag or publish a release.

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

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer test` | Pass | `OK: 2195 assertions`. |
| `composer test:all` | Pass | Composer validation, project boundary, tests, and PHP lint passed; `Linted 29 PHP files`. |
| `composer perf:smoke` | Pass | Content inventory, SEO/GEO cached report, publish preflight, old article refresh, and comment compliance handoff were within budget. |
| `git diff --check` | Pass | No whitespace errors. |
| `composer smoke:wp` full profile | Pass | `Smoke OK: 169 assertions`. |
| `composer smoke:wp` light profile | Pass | `Smoke OK: 14 assertions`; workflow definition discovery is disabled in the light profile. |
| `magick-ai-core composer test:all` | Pass | PHP lint and static contracts passed. |
| `magick-ai-core composer smoke:wp` | Pass | Core discovered capabilities from `magick-ai-abilities` and found shared workflow definitions at runtime. |

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

- Decide whether these changes ship as `0.4.0` or are held in the unreleased
  line.
- Build and inspect the release zip before tagging.
- Run the same smoke commands against any additional target WordPress/PHP
  profiles required by the release owner.
