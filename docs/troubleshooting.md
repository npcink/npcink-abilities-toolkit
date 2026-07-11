# Troubleshooting

Use this guide when another plugin, host product, or REST client cannot discover
or run abilities from `npcink-abilities-toolkit`.

## Quick Checks

Run these checks before debugging deeper:

```bash
wp plugin status npcink-abilities-toolkit --path=/path/to/wordpress
wp eval 'var_dump(function_exists("wp_register_ability"), function_exists("wp_register_ability_category"));' --path=/path/to/wordpress
wp eval 'echo rest_url();' --path=/path/to/wordpress
```

Expected baseline:

- the plugin is active;
- both Abilities API functions exist;
- `/wp-json/` is reachable;
- `/wp-json/wp-abilities/v1/abilities` is present for an authenticated user.

When WP-CLI is available, run the real-site smoke:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

## Common Issues

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| `/wp-json/wp-abilities/v1/*` returns 404 | WordPress Abilities API is missing or permalinks/routes need refresh | Use WordPress 6.9+ with Abilities API available, activate the compatibility plugin when needed, then flush rewrite rules if the site requires it. |
| Catalog request returns 401 | No valid REST authentication | Use a logged-in REST nonce from wp-admin or an Application Password for server-to-server checks. |
| Catalog request returns 403 | Current user lacks required capability | Test with an administrator first, then lower privileges only after confirming the ability's `capability` or `permission_callback`. |
| Ability id is missing | Package/filter profile disabled it, provider registered too early, or duplicate ownership was removed | Check `npcink_abilities_toolkit_enabled_packages`, read/comment sub-pack filters, and provider hook timing. Register provider abilities on `plugins_loaded` after checking public helper functions. |
| Provider ability does not appear in Npcink AI | Provider projection is opt-in | Set `project_to_npcink_catalog => true` only when the provider intentionally wants Npcink AI compatibility projection. The ability remains available through WordPress Abilities API either way. |
| Write ability returns dry-run/proposal output | This is expected for host-governed writes | Final mutation requires approval context from a host runtime. Generic clients should keep write/destructive abilities in dry-run mode. |
| Anonymous request is blocked | This is expected | Abilities are not public anonymous endpoints. Use authenticated REST requests and WordPress capabilities. |
| WP-CLI cannot connect to a Local database | Local's MySQL socket differs from system PHP defaults | Pass `WP_CLI_MYSQL_SOCKET`, `WP_CLI`, `WP_CLI_PHP`, and `WP_PATH` as shown in [Local WP-CLI Smoke Test](local-wpcli-smoke.md). |
| Official AI or MCP plugin compatibility fails | The failure may belong outside this package | Classify with [Official Stack E2E Environment](official-stack-e2e.md). This package owns schema, metadata, registration, and dry-run contract shape, not MCP transport, connector credentials, request logs, or approval UX. |

## Provider Plugin Debugging

Provider plugins should follow [Provider Plugin Guide](third-party-plugin-guide.md).

Checklist:

- call public helper functions only;
- do not include files from `npcink-abilities-toolkit/includes/`;
- register after `plugins_loaded`;
- use stable `namespace/name` ability ids;
- provide `label`, `description`, `input_schema`, `output_schema`, and
  `execute_callback`;
- use the least broad WordPress capability that matches the operation;
- keep write-like providers proposal-only.

Run:

```bash
composer check:provider-demo
```

The demo check proves this repository's public provider contract has not
drifted. It does not validate every third-party plugin's business logic.

## Host Consumer Debugging

Host products should discover abilities through WordPress Abilities API or the
public helper surface, then apply their own governance.

Checklist:

- fetch the catalog before selecting abilities;
- reject duplicate ability owners instead of shadowing them;
- treat `meta.show_in_rest`, `meta.mcp.public`, and `meta.npcink.channels` as
  separate signals;
- fail closed when host approval is missing;
- keep quota, audit, app keys, model routing, MCP policy, and final commit
  authorization outside this package.

Run:

```bash
composer check:consumer
composer check:workflow-consumer
composer check:official-stack
composer check:mcp-exposure
```

For Docker-based compatibility with official WordPress AI stack components,
run:

```bash
composer e2e:official-stack -- --skip-full-smoke
```

## REST Client Debugging

Start with [REST Client Quickstart](rest-client-quickstart.md).

Checklist:

- confirm the base site URL and REST URL are the same origin expected by the
  client;
- authenticate before fetching the catalog;
- inspect the ability schema before sending input;
- pass only declared input fields unless the ability schema explicitly allows
  extension data;
- use `dry_run=true` and `commit=false` for write-like checks;
- surface WordPress error codes and messages instead of replacing them with a
  generic client error.

## Safe Failure Posture

When uncertain, fail closed:

- missing ability: report the missing id;
- permission failure: report the required WordPress capability or host scope;
- missing host approval: keep dry-run/proposal mode;
- duplicate owner: remove the duplicate registration or add an ADR before
  changing ownership;
- missing official stack component: disable that integration path instead of
  weakening this package's contract.
