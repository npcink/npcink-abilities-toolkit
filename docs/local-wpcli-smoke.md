# Local WP-CLI Smoke Test

Use the WordPress smoke test against a Local site after the plugin is symlinked or installed in `wp-content/plugins`.

## Current Local Development Site

Use this shared Local site for repeatable manual and smoke verification:

- Site URL: `https://magick-ai.local`
- WordPress path: `/Users/muze/Local Sites/magick-ai/app/public`
- Local MySQL socket: `/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock`
- WP-CLI phar: `/tmp/wp-cli.phar`
- PHP binary: `/opt/homebrew/bin/php`
- Test admin username: `1`
- Test admin password: `1`
- Installed plugin slug: `magick-ai-abilities`
- Admin test page: `https://magick-ai.local/wp-admin/tools.php?page=magick-ai-abilities-test`

Verification status through 2026-05-29:

- `curl -k -I https://magick-ai.local/` returned HTTP 200.
- `https://magick-ai.local/wp-json/` exposed `/wp-abilities/v1`.
- WP admin login with username `1` and password `1` succeeded.
- `wp plugin status magick-ai-abilities` reported the plugin as active, version `0.2.0` during the release-candidate verification pass.
- `composer smoke:wp` passed with `Smoke OK: 98 assertions`.
- On 2026-05-28, the same Local site smoke passed with `Smoke OK: 117 assertions`
  after adding `magick-ai/get-post-context`,
  `magick-ai/get-content-publishing-checklist`,
  `magick-ai/get-content-inventory-health`,
  `magick-ai/get-bulk-publishing-checklist`, and
  `magick-ai/get-internal-link-opportunity-report`,
  `magick-ai/get-media-inventory-health`,
  `magick-ai/get-post-seo-geo-readiness`, and
  `magick-ai/get-site-topic-coverage-report`.
- On 2026-05-28, the same Local site smoke passed with `Smoke OK: 124 assertions`
  after adding `magick-ai/get-taxonomy-inventory-health`,
  `magick-ai/get-revision-change-risk-report`, and
  `magick-ai/get-comment-queue-health`.
- On 2026-05-28, the same Local site smoke passed with `Smoke OK: 132 assertions`
  after adding `magick-ai/get-site-operations-dashboard`,
  `magick-ai/get-post-publish-risk-report`,
  `magick-ai/get-content-refresh-opportunities`, and
  `magick-ai/get-internal-link-graph-health`.
- On 2026-05-28, the same Local site smoke passed with `Smoke OK: 141 assertions`
  after adding `magick-ai/get-media-cleanup-opportunities`,
  `magick-ai/get-taxonomy-consolidation-suggestions`,
  `magick-ai/get-comment-action-priority-queue`, and
  `magick-ai/get-page-structure-health`.
- On 2026-05-28, the same Local site smoke passed with `Smoke OK: 149 assertions`
  after adding `magick-ai/get-seo-geo-gap-report`,
  `magick-ai/get-site-style-baseline`,
  `magick-ai/build-article-workflow-context`, and
  `magick-ai/get-publishing-calendar-context`.
- On 2026-05-28, the same Local site smoke passed with `Smoke OK: 156 assertions`
  after adding workflow-chain assertions for article publish preflight, content
  refresh discovery, and comment compliance handoff.
- On 2026-05-29, the same Local site smoke passed with `Smoke OK: 162 assertions`
  after adding `magick-ai/get-article-publish-preflight-context`,
  `magick-ai/get-old-article-refresh-context`, and
  `magick-ai/get-comment-compliance-handoff`.
- On 2026-05-29, `composer smoke:wp` passed the default profile with
  `Smoke OK: 162 assertions` and the light `core_wordpress_read` profile with
  `Smoke OK: 13 assertions` after adding package/sub-pack gating and thin
  projection compatibility checks.
- On 2026-05-29, Magick AI local Catalog verification passed after removing a
  stale Magick AI settings loader require: authenticated
  `plugins.php?page=magick-ai-settings&tab=catalog` returned HTTP 200, the
  Magick AI capabilities endpoint returned 198 entries, and the projected rows
  did not include `open_api_enabled`, `backend_priority`, `tool_policy`,
  `skip_catalog_manifest_fallback`, or `write_mode`.
- On 2026-05-29, `wp plugin status magick-ai-abilities` reported the plugin as
  active with version `0.3.0`.
- On 2026-05-29, `composer smoke:wp` passed after splitting diagnostics and
  comment helper definitions into dedicated providers: default profile
  `Smoke OK: 162 assertions`, light `core_wordpress_read` profile
  `Smoke OK: 13 assertions`.

The current WP-CLI phar emits PHP 8.5 deprecation notices from
`vendor/wp-cli/php-cli-tools/lib/cli/Colors.php`. Those notices come from the
WP-CLI phar and do not fail the smoke test.

## Standard WP-CLI

```bash
WP_PATH="/path/to/site/app/public" composer smoke:wp
```

## Local Sites With MySQL Socket

Local can set `DB_HOST` to `localhost`, while the system PHP used by WP-CLI looks for a different default MySQL socket. In that case, pass the Local site socket through `WP_CLI_PHP_ARGS`.

For the `https://magick-ai.local/` test site:

```bash
WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP=/opt/homebrew/bin/php \
WP_CLI_ERROR_REPORTING=8191 \
WP_CLI_MYSQL_SOCKET="/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" \
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" \
composer smoke:wp
```

Expected result:

```text
Smoke OK: 162 assertions
Smoke OK: 13 assertions
```

The default smoke profile verifies Abilities API availability, authenticated
REST catalog access, the demo ability, the standalone diagnostics ability,
migrated core read/comment/write/destructive ability registration, individual
ability execution, workflow-chain execution, and anonymous REST blocking. The
light profile verifies that a host can keep only generic `core_wordpress_read`
abilities while disabling workflow helpers, diagnostics, write/destructive
abilities, comment helpers, catalog bridge, admin test page, and cache hooks.
