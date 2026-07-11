#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_CLI_BIN="${WP_CLI:-wp}"
WP_CLI_PHP="${WP_CLI_PHP:-php}"
WP_CLI_MYSQL_SOCKET="${WP_CLI_MYSQL_SOCKET:-}"
WP_PATH="${WP_PATH:-}"
ARTIFACT_PATH="${BLOCK_THEME_HOST_PROOF_ARTIFACT:-$ROOT_DIR/build/block-theme-host-proof.json}"

if [[ -z "$WP_PATH" ]]; then
	echo "Set WP_PATH to a real WordPress installation before running the block-theme host proof." >&2
	exit 2
fi

if [[ "$WP_CLI_BIN" != */* ]]; then
	WP_CLI_BIN="$(command -v "$WP_CLI_BIN")"
fi

php_args=()
if [[ -n "$WP_CLI_MYSQL_SOCKET" ]]; then
	php_args+=("-d" "mysqli.default_socket=$WP_CLI_MYSQL_SOCKET")
	php_args+=("-d" "pdo_mysql.default_socket=$WP_CLI_MYSQL_SOCKET")
fi

mkdir -p "$(dirname "$ARTIFACT_PATH")"

BLOCK_THEME_HOST_PROOF_ARTIFACT="$ARTIFACT_PATH" \
	"$WP_CLI_PHP" "${php_args[@]}" "$WP_CLI_BIN" \
	--path="$WP_PATH" eval-file "$ROOT_DIR/tests/block-theme-host-proof.php"

echo "Block-theme host proof artifact: $ARTIFACT_PATH"
