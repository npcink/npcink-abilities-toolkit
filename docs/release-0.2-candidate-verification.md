# 0.2 Candidate Verification

Status: internal candidate evidence recorded.
Date: 2026-05-28.

This receipt records the cross-repository verification state for preparing a
`npcink-abilities-toolkit` 0.2 release candidate. It does not create a tag or publish
a release.

## Candidate Baseline

- Release candidate version: `0.2.0`
- `npcink-abilities-toolkit`: `0981ca3 固化能力基础设施契约和本地验证流程`
- Magick AI consumer: `287f8d358 open-platform: docs 强化 abilities 拆分边界审计`
- Local WordPress smoke site: `https://magick-ai.local`
- WordPress path: `/Users/muze/Local Sites/magick-ai/app/public`
- WP-CLI: `/tmp/wp-cli.phar`
- PHP for WP-CLI: `/opt/homebrew/bin/php`
- MySQL socket: `/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock`

## Standalone Release Checklist

| Check | Result | Evidence |
| --- | --- | --- |
| Documentation updated | Pass | Public API, ability contract, provider guide, Magick AI integration contract, first-party pack grouping, local WP smoke notes, and this candidate receipt are present. |
| Migration inventory updated | Pass | `docs/magick-ai-migration-inventory.md` reflects the migrated ability ownership model for the current candidate baseline. |
| Public API reviewed | Pass | `docs/public-api-freeze-0.1.md` keeps third-party public helpers limited to category, readonly, write-proposal, normalize, and get-registered helpers. |
| `composer validate:composer` | Pass | `./composer.json is valid`. |
| `composer check:boundary` | Pass | `npcink-abilities-toolkit project boundary: ok`. |
| `composer test` | Pass | `OK: 1518 assertions`. |
| `composer lint:php` | Pass | `Linted 22 PHP files`. |
| `composer test:all` | Pass | Composer validation, project boundary, tests, and PHP lint all passed. |
| `composer smoke:wp` | Pass | `Smoke OK: 98 assertions` against the local WordPress site. |
| Local plugin version | Pass | `wp plugin status npcink-abilities-toolkit` reports version `0.2.0`. |

Smoke command:

```bash
WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP=/opt/homebrew/bin/php \
WP_CLI_ERROR_REPORTING=8191 \
WP_CLI_MYSQL_SOCKET="/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" \
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" \
composer smoke:wp
```

Smoke note: WP-CLI emits PHP 8.5 deprecation notices from
`php-cli-tools/lib/cli/Colors.php`, but the smoke command exits successfully.

## Magick AI Consumer Verification

The Magick AI repository was verified as a consumer without requiring internal
files from `npcink-abilities-toolkit`.

| Check | Result | Evidence |
| --- | --- | --- |
| `pnpm run check:abilities:split-boundary` | Pass | `npcink-abilities-toolkit split boundary: ok (39 main ids, 85 standalone ids, 0 duplicates)`. |
| `node --check scripts/check-npcink-abilities-toolkit-split.js` | Pass | Script syntax check passed. |
| Target PHP consumer contracts | Pass | `PHP unit checks passed (5 files, lane all)`. |
| Local WP consumer E2E | Pass | `site-info` read run returned 200; `create-draft` write dry-run returned 200 with `dry_run=true`, `host_governed=true`, and `commit_required=true`; Magick AI catalog entries used `executor_type=wp_ability`. |

Target PHP consumer contracts:

```bash
pnpm run check:unit:php:files -- \
  tests/unit/ability-readonly-config-contracts.php \
  tests/unit/ability-write-governance-contracts.php \
  tests/unit/wp-ability-write-two-phase-contracts.php \
  tests/unit/agent-gateway-wp-abilities-projection-boundary-contracts.php \
  tests/unit/open-api-governance-wp-ability-path-contracts.php
```

## Acceptance

- Migrated read, comment, write, and destructive package abilities are registered
  by `npcink-abilities-toolkit`.
- Magick AI consumes migrated abilities through WordPress Abilities API discovery,
  catalog projection metadata, and `executor_type=wp_ability` runtime dispatch.
- Duplicate Magick AI-owned and standalone `magick-ai/*` ability ids were not
  found in the checked repositories.
- Write and destructive commits remain host-governed by Magick AI or another
  host runtime; third-party public registration remains limited to readonly and
  write-proposal helpers.
- No fallback definitions or direct internal `require_once` path were reintroduced
  for migrated standalone package abilities.

## Package Evidence

- Package path: `dist/npcink-abilities-toolkit-0.2.0.zip`
- Build source commit: `7ecda76c3c13bea0c40f5d9109afafffb4f92147`
- SHA-256: `082fac1af345fc4050345fa0b166dd4fa6e86185e39c93cc75449704c8a55eb9`
- Package inspection: `unzip -l` reports 48 files under the `npcink-abilities-toolkit/` prefix.
- Version inspection: packaged `npcink-abilities-toolkit.php` reports `Version: 0.2.0` and `MAGICK_AI_ABILITIES_VERSION` is `0.2.0`; packaged `readme.txt` reports `Stable tag: 0.2.0`.

## Remaining Before Tagging

- Run `git diff --check` after any final release-note edits.
- Tag `0.2.0` only after confirming the packaged zip should be treated as the release artifact.
