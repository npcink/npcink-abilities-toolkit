#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="$ROOT_DIR/docker-compose.official-stack.yml"
PROJECT_NAME="${OFFICIAL_STACK_PROJECT_NAME:-npcink_abilities_official_stack}"
HTTP_PORT="${OFFICIAL_STACK_HTTP_PORT:-8899}"
CACHE_DIR="${OFFICIAL_STACK_CACHE_DIR:-$ROOT_DIR/build/official-stack-cache}"
ARTIFACT_DIR="${OFFICIAL_STACK_ARTIFACT_DIR:-$ROOT_DIR/build/official-stack-e2e}"
WP_URL="${OFFICIAL_STACK_WP_URL:-http://localhost:$HTTP_PORT}"
ADMIN_USER="${OFFICIAL_STACK_ADMIN_USER:-admin}"
ADMIN_PASSWORD="${OFFICIAL_STACK_ADMIN_PASSWORD:-password}"
ADMIN_EMAIL="${OFFICIAL_STACK_ADMIN_EMAIL:-admin@example.test}"
MCP_ADAPTER_ZIP="${OFFICIAL_STACK_MCP_ADAPTER_ZIP:-}"
AI_PLUGIN_ZIP="${OFFICIAL_STACK_AI_PLUGIN_ZIP:-}"
ABILITIES_API_ZIP="${OFFICIAL_STACK_ABILITIES_API_ZIP:-}"
INSTALL_AI="${OFFICIAL_STACK_INSTALL_AI:-1}"
INSTALL_MCP="${OFFICIAL_STACK_INSTALL_MCP:-1}"
REINSTALL_OFFICIAL_PLUGINS="${OFFICIAL_STACK_REINSTALL_OFFICIAL_PLUGINS:-0}"
RUN_FULL_SMOKE=1
SETUP_ONLY=0
FRESH=0

usage() {
	cat <<'EOF'
Usage: scripts/official-stack-e2e.sh [--fresh] [--reuse] [--setup-only] [--skip-full-smoke] [--down] [--status]

Options:
  --fresh            Stop and remove the Docker volumes before setup.
  --reuse            Reuse existing containers, volumes, and cached downloads. Default.
  --setup-only       Install and activate plugins, then stop before smoke checks.
  --skip-full-smoke  Skip tests/smoke-wp.php and run only official-stack probes.
  --down             Stop containers without removing volumes.
  --status           Print current Docker Compose service status.
  --help             Show this help text.

Useful environment overrides:
  OFFICIAL_STACK_HTTP_PORT=8899
  OFFICIAL_STACK_PROJECT_NAME=npcink_abilities_official_stack
  OFFICIAL_STACK_MCP_ADAPTER_ZIP=/path/to/mcp-adapter.zip
  OFFICIAL_STACK_AI_PLUGIN_ZIP=/path/to/ai.zip
  OFFICIAL_STACK_ABILITIES_API_ZIP=/path/to/abilities-api.zip
  OFFICIAL_STACK_INSTALL_AI=0
  OFFICIAL_STACK_INSTALL_MCP=0
  OFFICIAL_STACK_REINSTALL_OFFICIAL_PLUGINS=1
EOF
}

compose() {
	COMPOSE_PROJECT_NAME="$PROJECT_NAME" OFFICIAL_STACK_HTTP_PORT="$HTTP_PORT" docker compose -f "$COMPOSE_FILE" "$@"
}

wp() {
	compose run --rm cli --allow-root "$@"
}

log() {
	printf '[official-stack] %s\n' "$*" >&2
}

fail() {
	printf '[official-stack] ERROR: %s\n' "$*" >&2
	exit 1
}

need_command() {
	command -v "$1" >/dev/null 2>&1 || fail "Missing required command: $1"
}

github_release_asset_url() {
	local repo="$1"
	local asset_name="$2"
	need_command curl
	need_command php
	curl -fsSL "https://api.github.com/repos/$repo/releases/latest" | php -r '
		$asset_name = $argv[1];
		$data = json_decode(stream_get_contents(STDIN), true);
		if (!is_array($data) || empty($data["assets"]) || !is_array($data["assets"])) {
			exit(2);
		}
		foreach ($data["assets"] as $asset) {
			if (($asset["name"] ?? "") === $asset_name && !empty($asset["browser_download_url"])) {
				echo $asset["browser_download_url"];
				exit(0);
			}
		}
		exit(3);
	' "$asset_name"
}

download_asset() {
	local label="$1"
	local repo="$2"
	local asset_name="$3"
	local override_path="$4"
	local target="$CACHE_DIR/$asset_name"

	mkdir -p "$CACHE_DIR"

	if [[ -n "$override_path" ]]; then
		[[ -r "$override_path" ]] || fail "$label override is not readable: $override_path"
		cp "$override_path" "$target"
		printf '%s\n' "$target"
		return
	fi

	if [[ -s "$target" ]]; then
		log "Reusing cached $label zip: $target"
		printf '%s\n' "$target"
		return
	fi

	local url
	url="$(github_release_asset_url "$repo" "$asset_name")" || fail "Could not resolve latest $label release asset $asset_name from $repo"
	log "Downloading $label from $url"
	need_command curl
	curl -fsSL "$url" -o "$target"
	printf '%s\n' "$target"
}

wait_for_wordpress_files() {
	local attempt
	for attempt in $(seq 1 60); do
		if wp core version >/dev/null 2>&1; then
			return
		fi
		sleep 2
	done

	fail "WordPress files were not ready in the Docker volume."
}

install_wordpress_if_needed() {
	if wp core is-installed >/dev/null 2>&1; then
		log "Reusing installed WordPress at $WP_URL"
		return
	fi

	log "Installing WordPress at $WP_URL"
	wp core install \
		--url="$WP_URL" \
		--title="Npcink Official Stack E2E" \
		--admin_user="$ADMIN_USER" \
		--admin_password="$ADMIN_PASSWORD" \
		--admin_email="$ADMIN_EMAIL" \
		--skip-email >/dev/null
}

install_zip_plugin() {
	local label="$1"
	local zip_path="$2"
	local container_path="/official-stack-cache/$(basename "$zip_path")"

	log "Installing $label from $(basename "$zip_path")"
	wp plugin install "$container_path" --force --activate >/dev/null
}

ensure_abilities_api_available() {
	if wp eval 'exit(function_exists("wp_register_ability") && function_exists("wp_register_ability_category") ? 0 : 1);' >/dev/null 2>&1; then
		log "WordPress Abilities API is available from core or an active plugin."
		return
	fi

	local zip_path
	zip_path="$(download_asset "Abilities API" "WordPress/abilities-api" "abilities-api.zip" "$ABILITIES_API_ZIP")"
	install_zip_plugin "Abilities API" "$zip_path"

	wp eval 'exit(function_exists("wp_register_ability") && function_exists("wp_register_ability_category") ? 0 : 1);' >/dev/null 2>&1 \
		|| fail "Abilities API functions are still unavailable after installing the compatibility plugin."
}

activate_project_plugin() {
	if wp plugin is-active npcink-abilities-toolkit >/dev/null 2>&1; then
		log "Reusing active npcink-abilities-toolkit plugin"
		return
	fi

	log "Activating npcink-abilities-toolkit"
	wp plugin activate npcink-abilities-toolkit >/dev/null
}

ensure_zip_plugin_active() {
	local label="$1"
	local slug="$2"
	local zip_path="$3"
	local force_install="$4"

	if [[ "$force_install" != "1" ]] && wp plugin is-active "$slug" >/dev/null 2>&1; then
		log "Reusing active $label"
		return
	fi

	if [[ "$force_install" != "1" ]] && wp plugin is-installed "$slug" >/dev/null 2>&1; then
		log "Activating installed $label"
		wp plugin activate "$slug" >/dev/null
		return
	fi

	install_zip_plugin "$label" "$zip_path"
}

should_force_official_plugin_install() {
	local override_path="$1"
	[[ -n "$override_path" || "$REINSTALL_OFFICIAL_PLUGINS" == "1" ]]
}

install_official_plugins() {
	if [[ "$INSTALL_MCP" == "1" ]]; then
		local mcp_zip
		local force_mcp_install=0
		mcp_zip="$(download_asset "MCP Adapter" "WordPress/mcp-adapter" "mcp-adapter.zip" "$MCP_ADAPTER_ZIP")"
		if should_force_official_plugin_install "$MCP_ADAPTER_ZIP"; then
			force_mcp_install=1
		fi
		ensure_zip_plugin_active "MCP Adapter" "mcp-adapter" "$mcp_zip" "$force_mcp_install"
	else
		log "Skipping MCP Adapter install because OFFICIAL_STACK_INSTALL_MCP=0"
	fi

	if [[ "$INSTALL_AI" == "1" ]]; then
		local ai_zip
		local force_ai_install=0
		ai_zip="$(download_asset "AI plugin" "WordPress/ai" "ai.zip" "$AI_PLUGIN_ZIP")"
		if should_force_official_plugin_install "$AI_PLUGIN_ZIP"; then
			force_ai_install=1
		fi
		ensure_zip_plugin_active "AI plugin" "ai" "$ai_zip" "$force_ai_install"
	else
		log "Skipping AI plugin install because OFFICIAL_STACK_INSTALL_AI=0"
	fi
}

write_artifact() {
	local name="$1"
	shift
	mkdir -p "$ARTIFACT_DIR"
	"$@" > "$ARTIFACT_DIR/$name"
}

run_official_probes() {
	mkdir -p "$ARTIFACT_DIR"
	log "Writing plugin status artifact"
	write_artifact "plugins.txt" wp plugin list --status=active --fields=name,status,version

	log "Writing REST routes artifact"
	write_artifact "rest-routes.txt" wp eval '
		$routes = array_keys(rest_get_server()->get_routes());
		sort($routes);
		foreach ($routes as $route) {
			if (false !== strpos($route, "wp-abilities") || false !== strpos($route, "mcp")) {
				echo $route . PHP_EOL;
			}
		}
	'

	if [[ "$INSTALL_MCP" == "1" ]]; then
		wp plugin is-active mcp-adapter >/dev/null || fail "MCP Adapter is not active."
		if ! grep -i 'mcp' "$ARTIFACT_DIR/rest-routes.txt" >/dev/null 2>&1; then
			fail "MCP Adapter is active but no MCP REST routes were detected."
		fi
	fi

	if [[ "$INSTALL_AI" == "1" ]]; then
		wp plugin is-active ai >/dev/null || fail "AI plugin is not active."
	fi

	log "Running official-stack catalog probes"
	wp eval '
		wp_set_current_user(1);
		$required = array(
			"npcink-abilities-toolkit/site-info",
			"npcink-abilities-toolkit/list-workflow-recipes",
			"npcink-abilities-toolkit/create-draft",
		);
		foreach ($required as $ability_id) {
			if (!function_exists("wp_has_ability") || !wp_has_ability($ability_id)) {
				fwrite(STDERR, "Missing registered ability: {$ability_id}\n");
				exit(1);
			}
		}
		$routes = rest_get_server()->get_routes();
		if (!isset($routes["/wp-abilities/v1/abilities"])) {
			fwrite(STDERR, "Missing /wp-abilities/v1/abilities REST route\n");
			exit(1);
		}
		echo "official probes: ok\n";
	'
}

run_project_smoke() {
	if [[ "$RUN_FULL_SMOKE" != "1" ]]; then
		log "Skipping full project smoke because --skip-full-smoke was passed."
		return
	fi

	log "Running project WordPress smoke inside Docker"
	NPCINK_ABILITIES_TOOLKIT_SMOKE_PROFILE=default wp eval-file /var/www/html/wp-content/plugins/npcink-abilities-toolkit/tests/smoke-wp.php
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--fresh)
			FRESH=1
			;;
		--reuse)
			FRESH=0
			;;
		--setup-only)
			SETUP_ONLY=1
			;;
		--skip-full-smoke)
			RUN_FULL_SMOKE=0
			;;
		--down)
			compose down --remove-orphans
			exit 0
			;;
		--status)
			compose ps
			exit 0
			;;
		--help|-h)
			usage
			exit 0
			;;
		*)
			usage >&2
			fail "Unknown option: $1"
			;;
	esac
	shift
done

need_command docker

mkdir -p "$CACHE_DIR" "$ARTIFACT_DIR"

if [[ "$FRESH" == "1" ]]; then
	log "Removing existing official-stack containers and volumes for project $PROJECT_NAME"
	compose down -v --remove-orphans
fi

log "Starting reusable official-stack Docker environment on $WP_URL"
compose up -d db wordpress
wait_for_wordpress_files
install_wordpress_if_needed
ensure_abilities_api_available
activate_project_plugin
install_official_plugins

if [[ "$SETUP_ONLY" == "1" ]]; then
	log "Setup complete. WordPress URL: $WP_URL/wp-admin/"
	log "Admin credentials: $ADMIN_USER / $ADMIN_PASSWORD"
	exit 0
fi

run_official_probes
run_project_smoke

log "Official stack E2E complete. Artifacts: $ARTIFACT_DIR"
log "WordPress URL: $WP_URL/wp-admin/ ($ADMIN_USER / $ADMIN_PASSWORD)"
