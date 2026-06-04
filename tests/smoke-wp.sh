#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_CLI_BIN="${WP_CLI:-wp}"
WP_CLI_PHP="${WP_CLI_PHP:-php}"
WP_CLI_PHP_ARGS="${WP_CLI_PHP_ARGS:-}"
WP_CLI_ERROR_REPORTING="${WP_CLI_ERROR_REPORTING:-}"
WP_CLI_MYSQL_SOCKET="${WP_CLI_MYSQL_SOCKET:-}"
PLUGIN_SLUG="${PLUGIN_SLUG:-npcink-abilities-toolkit}"

if [[ "$WP_CLI_BIN" != *.phar ]] && ! command -v "$WP_CLI_BIN" >/dev/null 2>&1; then
	cat >&2 <<'EOF'
WP-CLI was not found.

Set WP_CLI to a wp-cli.phar path, or install a global `wp` command.
For the shared Local site, use:

WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP=/opt/homebrew/bin/php \
WP_CLI_ERROR_REPORTING=8191 \
WP_CLI_MYSQL_SOCKET="/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" \
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" \
composer smoke:wp
EOF
	exit 127
fi

wp_args=()
if [[ -n "${WP_PATH:-}" ]]; then
	wp_args+=(--path="$WP_PATH")
fi

run_wp() {
	if [[ "$WP_CLI_BIN" == *.phar ]]; then
		php_args=()
		if [[ -n "$WP_CLI_ERROR_REPORTING" ]]; then
			php_args+=("-d" "error_reporting=$WP_CLI_ERROR_REPORTING")
		fi
		if [[ -n "$WP_CLI_MYSQL_SOCKET" ]]; then
			php_args+=("-d" "mysqli.default_socket=$WP_CLI_MYSQL_SOCKET")
		fi
		if [[ -n "$WP_CLI_PHP_ARGS" ]]; then
			extra_php_args=()
			read -r -a extra_php_args <<< "$WP_CLI_PHP_ARGS"
			php_args+=("${extra_php_args[@]}")
		fi
		if [[ ${#wp_args[@]} -gt 0 ]]; then
			"$WP_CLI_PHP" "${php_args[@]}" "$WP_CLI_BIN" "${wp_args[@]}" "$@"
		else
			"$WP_CLI_PHP" "${php_args[@]}" "$WP_CLI_BIN" "$@"
		fi
		return
	fi

	if [[ ${#wp_args[@]} -gt 0 ]]; then
		"$WP_CLI_BIN" "${wp_args[@]}" "$@"
	else
		"$WP_CLI_BIN" "$@"
	fi
}

run_wp core is-installed >/dev/null
if ! run_wp plugin is-active "$PLUGIN_SLUG" >/dev/null 2>&1; then
	run_wp plugin activate "$PLUGIN_SLUG" >/dev/null
fi
run_wp option update magick_ai_abilities_demo_enabled 1 >/dev/null
MAGICK_AI_ABILITIES_SMOKE_PROFILE=default run_wp eval-file "$ROOT_DIR/tests/smoke-wp.php"

mu_plugin_dir="$(run_wp eval 'echo WPMU_PLUGIN_DIR;' 2>/dev/null || true)"
if [[ -z "$mu_plugin_dir" ]]; then
	echo "Unable to resolve WPMU_PLUGIN_DIR for light profile smoke." >&2
	exit 1
fi
mkdir -p "$mu_plugin_dir"
light_profile_mu_plugin="$mu_plugin_dir/npcink-abilities-toolkit-light-profile-smoke.php"
cleanup_light_profile() {
	rm -f "$light_profile_mu_plugin"
}
trap cleanup_light_profile EXIT
cat > "$light_profile_mu_plugin" <<'PHP'
<?php
add_filter(
	'magick_ai_abilities_enabled_packages',
	static function ( $packages ) {
		$packages['core_read']             = true;
		$packages['core_write']            = false;
		$packages['core_destructive']      = false;
		$packages['core_comment']          = false;
		$packages['magick_catalog_bridge'] = false;
		$packages['admin_test_page']       = false;
		$packages['read_cache_hooks']      = false;

		return $packages;
	}
);
add_filter(
	'magick_ai_abilities_enabled_read_packs',
	static function () {
		return array( 'core_wordpress_read' );
	}
);
PHP
MAGICK_AI_ABILITIES_SMOKE_PROFILE=light_core_read run_wp eval-file "$ROOT_DIR/tests/smoke-wp.php"
