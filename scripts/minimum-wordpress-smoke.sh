#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_NAME="${MINIMUM_WP_PROJECT_NAME:-npcink_abilities_wp69}"
HTTP_PORT="${MINIMUM_WP_HTTP_PORT:-8901}"
WORDPRESS_VERSION="${MINIMUM_WP_VERSION:-6.9.4}"
COMPOSE_FILE="$ROOT_DIR/docker-compose.official-stack.yml"
CACHE_ROOT="$ROOT_DIR/build/official-stack-cache/wordpress-$WORDPRESS_VERSION"
ZIP_PATH="$CACHE_ROOT/wordpress-$WORDPRESS_VERSION.zip"
SOURCE_DIR="$CACHE_ROOT/wordpress"

cleanup() {
	COMPOSE_PROJECT_NAME="$PROJECT_NAME" \
	OFFICIAL_STACK_HTTP_PORT="$HTTP_PORT" \
		docker compose -f "$COMPOSE_FILE" down -v --remove-orphans >/dev/null 2>&1 || true
}

trap cleanup EXIT

if [[ ! -f "$SOURCE_DIR/wp-settings.php" ]]; then
	mkdir -p "$CACHE_ROOT"
	curl --fail --location --retry 3 --connect-timeout 15 --max-time 180 \
		"https://wordpress.org/wordpress-$WORDPRESS_VERSION.zip" \
		--output "$ZIP_PATH"
	unzip -tq "$ZIP_PATH" >/dev/null
	rm -rf "$SOURCE_DIR"
	unzip -q "$ZIP_PATH" -d "$CACHE_ROOT"
fi

OFFICIAL_STACK_PROJECT_NAME="$PROJECT_NAME" \
OFFICIAL_STACK_HTTP_PORT="$HTTP_PORT" \
OFFICIAL_STACK_WP_URL="http://localhost:$HTTP_PORT" \
OFFICIAL_STACK_WORDPRESS_VERSION="$WORDPRESS_VERSION" \
OFFICIAL_STACK_WORDPRESS_SOURCE_DIR="/official-stack-cache/wordpress-$WORDPRESS_VERSION/wordpress" \
OFFICIAL_STACK_INSTALL_AI=0 \
OFFICIAL_STACK_INSTALL_MCP=0 \
OFFICIAL_STACK_RUN_MCP_HTTP_PROBE=0 \
OFFICIAL_STACK_ARTIFACT_DIR="$ROOT_DIR/build/minimum-wordpress-smoke" \
	bash "$ROOT_DIR/scripts/official-stack-e2e.sh" --fresh

echo "Minimum WordPress smoke passed on WordPress $WORDPRESS_VERSION."
