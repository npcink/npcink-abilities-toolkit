#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="${PLUGIN_SLUG:-magick-ai-abilities}"
PACKAGE_REF="${PACKAGE_REF:-HEAD}"
WP_CLI_BIN="${WP_CLI:-wp}"
WP_CLI_PHP="${WP_CLI_PHP:-php}"
WP_CLI_ERROR_REPORTING="${WP_CLI_ERROR_REPORTING:-}"
WP_CLI_MYSQL_SOCKET="${WP_CLI_MYSQL_SOCKET:-}"

if [[ "$WP_CLI_BIN" != *.phar ]] && ! command -v "$WP_CLI_BIN" >/dev/null 2>&1; then
	echo "WP-CLI was not found. Set WP_CLI to a wp-cli.phar path or install a global wp command." >&2
	exit 127
fi

tmpdir="$(mktemp -d)"
cleanup() {
	rm -rf "$tmpdir"
}
trap cleanup EXIT

package_dir="$tmpdir/$PLUGIN_SLUG"
(
	cd "$ROOT_DIR"
	git archive --format=tar --prefix="$PLUGIN_SLUG/" "$PACKAGE_REF" | tar -x -C "$tmpdir"
)

for excluded_path in \
	".github" \
	".gitattributes" \
	".gitignore" \
	".DS_Store" \
	"CHANGELOG.md" \
	"README.md" \
	"composer.json" \
	"composer.lock" \
	"dist" \
	"docs" \
	"examples" \
	"phpstan.neon.dist" \
	"scripts" \
	"sj" \
	"tests" \
	"vendor"
do
	if [[ -e "$package_dir/$excluded_path" ]]; then
		echo "Packaged plugin includes excluded path: $excluded_path" >&2
		exit 1
	fi
done

wp_args=()
if [[ -n "${WP_PATH:-}" ]]; then
	wp_args+=(--path="$WP_PATH")
fi

php_args=()
if [[ "$WP_CLI_BIN" == *.phar ]]; then
	if [[ -n "$WP_CLI_ERROR_REPORTING" ]]; then
		php_args+=("-d" "error_reporting=$WP_CLI_ERROR_REPORTING")
	fi
	if [[ -n "$WP_CLI_MYSQL_SOCKET" ]]; then
		php_args+=("-d" "mysqli.default_socket=$WP_CLI_MYSQL_SOCKET")
	fi
fi

if [[ "$WP_CLI_BIN" == *.phar ]]; then
	output="$("$WP_CLI_PHP" "${php_args[@]}" "$WP_CLI_BIN" "${wp_args[@]}" plugin check "$package_dir" --mode=update --format=strict-json)"
else
	output="$("$WP_CLI_BIN" "${wp_args[@]}" plugin check "$package_dir" --mode=update --format=strict-json)"
fi

printf '%s\n' "$output"

if grep -q '"type":"ERROR"' <<< "$output"; then
	echo "Plugin Check found errors in packaged plugin." >&2
	exit 1
fi

echo "Packaged Plugin Check passed without errors for $PLUGIN_SLUG ($PACKAGE_REF)."
