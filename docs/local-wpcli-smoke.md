# Local WP-CLI Smoke Test

Use the WordPress smoke test against a Local site after the plugin is symlinked or installed in `wp-content/plugins`.

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
Smoke OK: 41 assertions
```

The smoke test verifies Abilities API availability, authenticated REST catalog access, the demo ability, the standalone diagnostics ability, migrated core read ability registration, ability execution, and anonymous REST blocking.
