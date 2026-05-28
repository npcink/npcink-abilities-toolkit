#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_CLI_BIN="${WP_CLI:-wp}"
WP_CLI_PHP="${WP_CLI_PHP:-php}"
WP_CLI_PHP_ARGS="${WP_CLI_PHP_ARGS:-}"
WP_CLI_ERROR_REPORTING="${WP_CLI_ERROR_REPORTING:-}"
WP_CLI_MYSQL_SOCKET="${WP_CLI_MYSQL_SOCKET:-}"
PLUGIN_SLUG="${PLUGIN_SLUG:-magick-ai-abilities}"

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
run_wp plugin activate "$PLUGIN_SLUG" >/dev/null
run_wp option update magick_ai_abilities_demo_enabled 1 >/dev/null
run_wp eval-file "$ROOT_DIR/tests/smoke-wp.php"
