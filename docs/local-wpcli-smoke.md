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

Verification status on 2026-05-28:

- `curl -k -I https://magick-ai.local/` returned HTTP 200.
- `https://magick-ai.local/wp-json/` exposed `/wp-abilities/v1`.
- WP admin login with username `1` and password `1` succeeded.
- `wp plugin status magick-ai-abilities` reported the plugin as active, version `0.1.0`.
- `composer smoke:wp` passed with `Smoke OK: 98 assertions`.

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
Smoke OK: 98 assertions
```

The smoke test verifies Abilities API availability, authenticated REST catalog access, the demo ability, the standalone diagnostics ability, migrated core read/comment/write/destructive ability registration, ability execution, and anonymous REST blocking.
