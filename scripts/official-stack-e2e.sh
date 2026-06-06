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
RUN_MCP_HTTP_PROBE="${OFFICIAL_STACK_RUN_MCP_HTTP_PROBE:-1}"
MCP_APP_PASSWORD_NAME="Npcink Official Stack MCP Probe"
MCP_DESTRUCTIVE_FIXTURE_POST_ID=""
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
  OFFICIAL_STACK_RUN_MCP_HTTP_PROBE=0
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

delete_mcp_probe_app_passwords() {
	local uuids
	uuids="$(
		wp user application-password list "$ADMIN_USER" --fields=uuid,name --format=json | tail -n 1 | php -r '
			$name = $argv[1];
			$rows = json_decode(stream_get_contents(STDIN), true);
			if (!is_array($rows)) {
				exit(0);
			}
			foreach ($rows as $row) {
				if (($row["name"] ?? "") === $name && !empty($row["uuid"])) {
					echo $row["uuid"], PHP_EOL;
				}
			}
		' "$MCP_APP_PASSWORD_NAME"
	)"

	if [[ -z "$uuids" ]]; then
		return
	fi

	while IFS= read -r uuid; do
		[[ -n "$uuid" ]] || continue
		wp user application-password delete "$ADMIN_USER" "$uuid" >/dev/null || true
	done <<< "$uuids"
}

delete_mcp_destructive_fixture_post() {
	if [[ -z "$MCP_DESTRUCTIVE_FIXTURE_POST_ID" ]]; then
		return
	fi

	wp post delete "$MCP_DESTRUCTIVE_FIXTURE_POST_ID" --force >/dev/null 2>&1 || true
	MCP_DESTRUCTIVE_FIXTURE_POST_ID=""
}

cleanup_mcp_http_probe() {
	delete_mcp_probe_app_passwords || true
	delete_mcp_destructive_fixture_post || true
}

count_mcp_probe_app_passwords() {
	wp user application-password list "$ADMIN_USER" --fields=name --format=json | tail -n 1 | php -r '
		$name = $argv[1];
		$rows = json_decode(stream_get_contents(STDIN), true);
		if (!is_array($rows)) {
			echo "0";
			exit(0);
		}
		$count = 0;
		foreach ($rows as $row) {
			if (($row["name"] ?? "") === $name) {
				++$count;
			}
		}
		echo $count;
	' "$MCP_APP_PASSWORD_NAME"
}

count_mcp_destructive_fixture_posts() {
	wp post list --post_type=post --post_status=any --name=mcp-e2e-destructive-fixture --fields=ID --format=json | tail -n 1 | php -r '
		$rows = json_decode(stream_get_contents(STDIN), true);
		echo is_array($rows) ? count($rows) : 0;
	'
}

json_rpc_request() {
	local app_password="$1"
	local session_id="$2"
	local payload_file="$3"
	local body_file="$4"
	local header_file="$5"
	local mcp_url="$WP_URL/?rest_route=/mcp/mcp-adapter-default-server"

	if [[ -n "$session_id" ]]; then
		curl -fsS \
			-u "$ADMIN_USER:$app_password" \
			-H 'Content-Type: application/json' \
			-H 'Accept: application/json' \
			-H "Mcp-Session-Id: $session_id" \
			-D "$header_file" \
			-o "$body_file" \
			-d @"$payload_file" \
			"$mcp_url"
	else
		curl -fsS \
			-u "$ADMIN_USER:$app_password" \
			-H 'Content-Type: application/json' \
			-H 'Accept: application/json' \
			-D "$header_file" \
			-o "$body_file" \
			-d @"$payload_file" \
			"$mcp_url"
	fi
}

validate_mcp_http_probe() {
	local initialize_body="$1"
	local tools_body="$2"
	local discover_body="$3"
	local get_info_body="$4"
	local execute_read_body="$5"
	local execute_write_body="$6"
	local execute_destructive_body="$7"
	local destructive_fixture_post_id="$8"
	local summary_file="$9"

	php -r '
		[$initialize_file, $tools_file, $discover_file, $get_info_file, $execute_read_file, $execute_write_file, $execute_destructive_file, $destructive_fixture_post_id, $summary_file] = array_slice($argv, 1);

		$read_json = static function ($file, $label) {
			$data = json_decode((string) file_get_contents($file), true);
			if (!is_array($data)) {
				fwrite(STDERR, "{$label} did not return JSON\n");
				exit(1);
			}
			return $data;
		};

		$initialize = $read_json($initialize_file, "MCP initialize");
		$tools = $read_json($tools_file, "MCP tools/list");
		$discover = $read_json($discover_file, "MCP discover abilities");
		$get_info = $read_json($get_info_file, "MCP get ability info");
		$execute_read = $read_json($execute_read_file, "MCP execute read ability");
		$execute_write = $read_json($execute_write_file, "MCP execute write ability");
		$execute_destructive = $read_json($execute_destructive_file, "MCP execute destructive ability");

		if (($initialize["result"]["serverInfo"]["name"] ?? "") !== "MCP Adapter Default Server") {
			fwrite(STDERR, "MCP initialize did not return the default server info\n");
			exit(1);
		}

		$tool_names = array_map(
			static fn($tool) => $tool["name"] ?? "",
			$tools["result"]["tools"] ?? array()
		);
		foreach (array("mcp-adapter-discover-abilities", "mcp-adapter-get-ability-info", "mcp-adapter-execute-ability") as $required_tool) {
			if (!in_array($required_tool, $tool_names, true)) {
				fwrite(STDERR, "Missing MCP tool: {$required_tool}\n");
				exit(1);
			}
		}

		$discover_text = $discover["result"]["content"][0]["text"] ?? "";
		$discover_payload = json_decode((string) $discover_text, true);
		if (!is_array($discover_payload)) {
			fwrite(STDERR, "MCP discover abilities did not return a JSON text payload\n");
			exit(1);
		}

		$abilities = $discover_payload["abilities"] ?? array();
		$npcink_abilities = array_values(array_filter(
			$abilities,
			static fn($ability) => 0 === strpos((string) ($ability["name"] ?? ""), "npcink-abilities-toolkit/")
		));
		$ability_names = array_map(static fn($ability) => $ability["name"] ?? "", $npcink_abilities);
		$expected_read_entrypoints = array(
			"npcink-abilities-toolkit/site-info",
			"npcink-abilities-toolkit/list-post-types",
			"npcink-abilities-toolkit/list-taxonomies",
			"npcink-abilities-toolkit/list-workflow-recipes",
			"npcink-abilities-toolkit/get-workflow-recipe",
		);
		foreach (array_merge($expected_read_entrypoints, array("npcink-abilities-toolkit/create-draft", "npcink-abilities-toolkit/delete-post-permanently")) as $required_ability) {
			if (!in_array($required_ability, $ability_names, true)) {
				fwrite(STDERR, "MCP discover abilities did not include {$required_ability}\n");
				exit(1);
			}
		}

		$get_info_structured = $get_info["result"]["structuredContent"] ?? array();
		if (empty($get_info_structured)) {
			$get_info_text = $get_info["result"]["content"][0]["text"] ?? "";
			$get_info_structured = json_decode((string) $get_info_text, true);
		}
		if (!is_array($get_info_structured) || ($get_info_structured["name"] ?? "") !== "npcink-abilities-toolkit/delete-post-permanently") {
			fwrite(STDERR, "MCP get ability info did not return delete-post-permanently details\n");
			exit(1);
		}
		if (!isset($get_info_structured["input_schema"]["properties"]["post_id"])) {
			fwrite(STDERR, "MCP get ability info did not expose delete-post-permanently post_id input schema\n");
			exit(1);
		}
		if (($get_info_structured["meta"]["mcp"]["risk"] ?? "") !== "destructive") {
			fwrite(STDERR, "MCP get ability info did not expose destructive MCP risk metadata\n");
			exit(1);
		}

		$read_structured = $execute_read["result"]["structuredContent"] ?? array();
		if (true !== ($read_structured["success"] ?? null)) {
			fwrite(STDERR, "MCP execute read ability did not return success=true\n");
			exit(1);
		}
		foreach (array("name", "home_url", "site_url") as $required_field) {
			if (!is_string($read_structured["data"][$required_field] ?? null) || "" === $read_structured["data"][$required_field]) {
				fwrite(STDERR, "MCP execute read ability did not return {$required_field}\n");
				exit(1);
			}
		}

		$write_structured = $execute_write["result"]["structuredContent"] ?? array();
		if (true !== ($write_structured["success"] ?? null)) {
			fwrite(STDERR, "MCP execute write ability did not return success=true\n");
			exit(1);
		}
		if (true !== ($write_structured["data"]["dry_run"] ?? null)) {
			fwrite(STDERR, "MCP execute write ability did not preserve dry_run=true\n");
			exit(1);
		}
		if (true !== ($write_structured["data"]["commit_required"] ?? null)) {
			fwrite(STDERR, "MCP execute write ability did not report commit_required=true\n");
			exit(1);
		}

		$destructive_structured = $execute_destructive["result"]["structuredContent"] ?? array();
		if (true !== ($destructive_structured["success"] ?? null)) {
			fwrite(STDERR, "MCP execute destructive ability did not return success=true\n");
			exit(1);
		}
		if ((int) $destructive_fixture_post_id !== (int) ($destructive_structured["data"]["post_id"] ?? 0)) {
			fwrite(STDERR, "MCP execute destructive ability did not target the fixture post\n");
			exit(1);
		}
		if (false !== ($destructive_structured["data"]["deleted"] ?? null)) {
			fwrite(STDERR, "MCP execute destructive ability did not preserve deleted=false during dry run\n");
			exit(1);
		}
		if (true !== ($destructive_structured["data"]["dry_run"] ?? null)) {
			fwrite(STDERR, "MCP execute destructive ability did not preserve dry_run=true\n");
			exit(1);
		}
		if (true !== ($destructive_structured["data"]["commit_required"] ?? null)) {
			fwrite(STDERR, "MCP execute destructive ability did not report commit_required=true\n");
			exit(1);
		}

		file_put_contents(
			$summary_file,
			json_encode(
				array(
					"schema_version" => "official-stack-mcp-http-summary/v1",
					"server" => $initialize["result"]["serverInfo"],
					"tool_count" => count($tool_names),
					"ability_count" => count($abilities),
					"npcink_ability_count" => count($npcink_abilities),
					"expected_read_entrypoints" => $expected_read_entrypoints,
					"inspected_ability" => "npcink-abilities-toolkit/delete-post-permanently",
					"executed_read_ability" => "npcink-abilities-toolkit/site-info",
					"executed_write_ability" => "npcink-abilities-toolkit/create-draft",
					"executed_destructive_ability" => "npcink-abilities-toolkit/delete-post-permanently",
					"destructive_fixture_post_id" => (int) $destructive_fixture_post_id,
					"dry_run" => $write_structured["data"]["dry_run"] ?? null,
					"destructive_dry_run" => $destructive_structured["data"]["dry_run"] ?? null,
					"commit_required" => $write_structured["data"]["commit_required"] ?? null,
					"destructive_commit_required" => $destructive_structured["data"]["commit_required"] ?? null,
					"tools" => array(
						"count" => count($tool_names),
						"required" => array("mcp-adapter-discover-abilities", "mcp-adapter-get-ability-info", "mcp-adapter-execute-ability"),
					),
					"discovery" => array(
						"ability_count" => count($abilities),
						"npcink_ability_count" => count($npcink_abilities),
						"expected_read_entrypoints" => $expected_read_entrypoints,
						"required_public_abilities" => array_merge($expected_read_entrypoints, array("npcink-abilities-toolkit/create-draft", "npcink-abilities-toolkit/delete-post-permanently")),
					),
					"scenarios" => array(
						"inspect_destructive_ability" => array(
							"tool" => "mcp-adapter-get-ability-info",
							"ability" => "npcink-abilities-toolkit/delete-post-permanently",
							"passed" => true,
						),
						"execute_read_entrypoint" => array(
							"tool" => "mcp-adapter-execute-ability",
							"ability" => "npcink-abilities-toolkit/site-info",
							"passed" => true,
						),
						"execute_governed_write_dry_run" => array(
							"tool" => "mcp-adapter-execute-ability",
							"ability" => "npcink-abilities-toolkit/create-draft",
							"dry_run" => $write_structured["data"]["dry_run"] ?? null,
							"commit_required" => $write_structured["data"]["commit_required"] ?? null,
							"passed" => true,
						),
						"execute_destructive_dry_run" => array(
							"tool" => "mcp-adapter-execute-ability",
							"ability" => "npcink-abilities-toolkit/delete-post-permanently",
							"fixture_post_id" => (int) $destructive_fixture_post_id,
							"dry_run" => $destructive_structured["data"]["dry_run"] ?? null,
							"commit_required" => $destructive_structured["data"]["commit_required"] ?? null,
							"deleted" => $destructive_structured["data"]["deleted"] ?? null,
							"passed" => true,
						),
					),
				),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			) . PHP_EOL
		);
	' "$initialize_body" "$tools_body" "$discover_body" "$get_info_body" "$execute_read_body" "$execute_write_body" "$execute_destructive_body" "$destructive_fixture_post_id" "$summary_file"
}

append_mcp_http_probe_cleanup_summary() {
	local summary_file="$1"
	local app_password_count="$2"
	local fixture_post_count="$3"

	php -r '
		[$summary_file, $app_password_count, $fixture_post_count] = array_slice($argv, 1);
		$data = json_decode((string) file_get_contents($summary_file), true);
		if (!is_array($data)) {
			fwrite(STDERR, "MCP HTTP summary did not return JSON before cleanup append\n");
			exit(1);
		}
		$data["cleanup"] = array(
			"application_passwords_remaining" => (int) $app_password_count,
			"destructive_fixture_posts_remaining" => (int) $fixture_post_count,
			"passed" => 0 === (int) $app_password_count && 0 === (int) $fixture_post_count,
		);
		if (true !== $data["cleanup"]["passed"]) {
			fwrite(STDERR, "MCP HTTP probe cleanup did not pass\n");
			exit(1);
		}
		file_put_contents($summary_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
	' "$summary_file" "$app_password_count" "$fixture_post_count"
}

run_mcp_http_probe() {
	if [[ "$INSTALL_MCP" != "1" || "$RUN_MCP_HTTP_PROBE" != "1" ]]; then
		log "Skipping MCP HTTP probe."
		return
	fi

	need_command curl
	need_command php
	mkdir -p "$ARTIFACT_DIR"

	local payload_dir="$ARTIFACT_DIR/mcp-http-payloads"
	mkdir -p "$payload_dir"

	local app_password
	log "Creating temporary application password for MCP HTTP probe"
	delete_mcp_probe_app_passwords
	app_password="$(wp user application-password create "$ADMIN_USER" "$MCP_APP_PASSWORD_NAME" --porcelain | tail -n 1)"
	trap cleanup_mcp_http_probe EXIT

	local initialize_payload="$payload_dir/initialize.request.json"
	local tools_payload="$payload_dir/tools-list.request.json"
	local discover_payload="$payload_dir/discover-abilities.request.json"
	local get_info_payload="$payload_dir/get-delete-post-permanently-info.request.json"
	local execute_read_payload="$payload_dir/execute-site-info.request.json"
	local execute_write_payload="$payload_dir/execute-create-draft.request.json"
	local execute_destructive_payload="$payload_dir/execute-delete-post-permanently.request.json"
	local initialize_body="$payload_dir/initialize.response.json"
	local tools_body="$payload_dir/tools-list.response.json"
	local discover_body="$payload_dir/discover-abilities.response.json"
	local get_info_body="$payload_dir/get-delete-post-permanently-info.response.json"
	local execute_read_body="$payload_dir/execute-site-info.response.json"
	local execute_write_body="$payload_dir/execute-create-draft.response.json"
	local execute_destructive_body="$payload_dir/execute-delete-post-permanently.response.json"
	local headers="$payload_dir/headers.txt"
	local summary="$ARTIFACT_DIR/mcp-http-summary.json"

	log "Creating temporary post fixture for MCP destructive dry-run probe"
	MCP_DESTRUCTIVE_FIXTURE_POST_ID="$(wp post create --post_type=post --post_status=draft --post_title='MCP E2E Destructive Fixture' --post_content='Temporary fixture for MCP destructive dry-run validation.' --porcelain | tail -n 1)"
	[[ -n "$MCP_DESTRUCTIVE_FIXTURE_POST_ID" ]] || fail "Could not create MCP destructive dry-run fixture post."

	cat > "$initialize_payload" <<'EOF'
{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"npcink-official-stack-e2e","version":"0.1.0"}}}
EOF
	cat > "$tools_payload" <<'EOF'
{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}
EOF
	cat > "$discover_payload" <<'EOF'
{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"mcp-adapter-discover-abilities","arguments":{}}}
EOF
	cat > "$get_info_payload" <<'EOF'
{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"mcp-adapter-get-ability-info","arguments":{"ability_name":"npcink-abilities-toolkit/delete-post-permanently"}}}
EOF
	cat > "$execute_read_payload" <<'EOF'
{"jsonrpc":"2.0","id":5,"method":"tools/call","params":{"name":"mcp-adapter-execute-ability","arguments":{"ability_name":"npcink-abilities-toolkit/site-info","parameters":{}}}}
EOF
	cat > "$execute_write_payload" <<'EOF'
{"jsonrpc":"2.0","id":6,"method":"tools/call","params":{"name":"mcp-adapter-execute-ability","arguments":{"ability_name":"npcink-abilities-toolkit/create-draft","parameters":{"post_type":"post","title":"MCP E2E Draft","content":"MCP E2E dry run content","dry_run":true}}}}
EOF
	php -r '
		$path = $argv[1];
		$post_id = (int) $argv[2];
		file_put_contents(
			$path,
			json_encode(
				array(
					"jsonrpc" => "2.0",
					"id" => 7,
					"method" => "tools/call",
					"params" => array(
						"name" => "mcp-adapter-execute-ability",
						"arguments" => array(
							"ability_name" => "npcink-abilities-toolkit/delete-post-permanently",
							"parameters" => array(
								"post_id" => $post_id,
								"dry_run" => true,
							),
						),
					),
				),
				JSON_UNESCAPED_SLASHES
			) . PHP_EOL
		);
	' "$execute_destructive_payload" "$MCP_DESTRUCTIVE_FIXTURE_POST_ID"

	log "Running MCP HTTP initialize"
	json_rpc_request "$app_password" "" "$initialize_payload" "$initialize_body" "$headers"
	local session_id
	session_id="$(awk -F': ' 'tolower($1) == "mcp-session-id" {gsub(/\r/, "", $2); print $2; exit}' "$headers")"
	[[ -n "$session_id" ]] || fail "MCP HTTP initialize did not return a session id."

	log "Running MCP HTTP tools/list"
	json_rpc_request "$app_password" "$session_id" "$tools_payload" "$tools_body" "$headers"

	log "Running MCP HTTP discover abilities"
	json_rpc_request "$app_password" "$session_id" "$discover_payload" "$discover_body" "$headers"

	log "Running MCP HTTP get ability info"
	json_rpc_request "$app_password" "$session_id" "$get_info_payload" "$get_info_body" "$headers"

	log "Running MCP HTTP read entrypoint execute"
	json_rpc_request "$app_password" "$session_id" "$execute_read_payload" "$execute_read_body" "$headers"

	log "Running MCP HTTP governed write dry-run execute"
	json_rpc_request "$app_password" "$session_id" "$execute_write_payload" "$execute_write_body" "$headers"

	log "Running MCP HTTP destructive dry-run execute"
	json_rpc_request "$app_password" "$session_id" "$execute_destructive_payload" "$execute_destructive_body" "$headers"

	validate_mcp_http_probe "$initialize_body" "$tools_body" "$discover_body" "$get_info_body" "$execute_read_body" "$execute_write_body" "$execute_destructive_body" "$MCP_DESTRUCTIVE_FIXTURE_POST_ID" "$summary"
	cleanup_mcp_http_probe
	append_mcp_http_probe_cleanup_summary "$summary" "$(count_mcp_probe_app_passwords)" "$(count_mcp_destructive_fixture_posts)"
	trap - EXIT

	log "MCP HTTP probe complete. Summary: $summary"
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
run_mcp_http_probe
run_project_smoke

log "Official stack E2E complete. Artifacts: $ARTIFACT_DIR"
log "WordPress URL: $WP_URL/wp-admin/ ($ADMIN_USER / $ADMIN_PASSWORD)"
