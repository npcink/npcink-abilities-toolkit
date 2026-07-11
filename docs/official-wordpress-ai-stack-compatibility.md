# Official WordPress AI Stack Compatibility

Status: active compatibility guidance
Date: 2026-06-07

This document records how `npcink-abilities-toolkit` should line up with the
official WordPress AI building blocks without taking ownership of their runtime
responsibilities.

## Official References

- WordPress Abilities API documentation:
  <https://developer.wordpress.org/apis/abilities-api/>
- WordPress Abilities API repository:
  <https://github.com/WordPress/abilities-api>
- WordPress MCP Adapter repository:
  <https://github.com/WordPress/mcp-adapter>
- WordPress AI plugin directory page:
  <https://wordpress.org/plugins/ai/>
- WordPress AI plugin repository:
  <https://github.com/WordPress/ai>
- WordPress PHP AI Client repository:
  <https://github.com/WordPress/php-ai-client>
- WordPress WP AI Client repository:
  <https://github.com/WordPress/wp-ai-client>

## Positioning Against The Official Stack

`npcink-abilities-toolkit` is closest to an ability pack and contract catalog.
It should register stable WordPress abilities, normalize schemas and metadata,
publish declarative workflow recipes, and provide host-governed dry-run
contracts.

It should not duplicate these official layers:

- Abilities API: core registration, discovery, permission, and execution
  primitive.
- MCP Adapter: MCP transport and tool bridge for WordPress abilities.
- AI plugin: reference product surface, Abilities Explorer, request logging,
  connector approvals, and user-facing AI workflows.
- AI Client and provider plugins: model/provider client and credential layer.

## Local Compatibility Gate

Run:

```bash
composer check:official-stack
```

The check verifies the local contract shape that official Abilities API clients,
MCP Adapter, and explorer-style tools need:

- the plugin remains a standalone `wordpress-plugin` package;
- official AI plugin and MCP Adapter are not runtime dependencies;
- every registered ability has a stable `namespace/name` id;
- every ability has label, description, category, callable callbacks, object
  input/output schemas, and REST visibility metadata;
- `meta.annotations` mirrors the normalized WordPress Abilities API annotation
  values for read/write/destructive posture;
- `meta.mcp.risk` mirrors ability risk;
- `meta.npcink.wp_ability_id` preserves the canonical WordPress ability id;
- priority discovery abilities and the workflow recipe manifest remain present.

This is a contract-shape gate. It does not replace a real WordPress site smoke
test with the official plugins installed.

## Manual Official Stack Smoke

Use the reusable Docker workflow first:

```bash
composer e2e:official-stack
```

See [Official Stack E2E Environment](official-stack-e2e.md) for reuse, reset,
and setup-only options.

For a disposable or local WordPress site with WordPress 6.9+ and PHP 8.0+:

1. Install and activate `npcink-abilities-toolkit`.
2. Install and activate the official Abilities API package only if the target
   WordPress build does not already provide the Abilities API.
3. Install and activate the official MCP Adapter.
4. Install and activate the official AI plugin.
5. Confirm `/wp-json/wp-abilities/v1/abilities` includes
   `npcink-abilities-toolkit/site-info`,
   `npcink-abilities-toolkit/list-workflow-recipes`, and at least one
   host-governed write ability such as `npcink-abilities-toolkit/create-draft`.
6. In the AI plugin's ability/explorer surface, confirm the same abilities show
   labels, descriptions, input schemas, and risk annotations.
7. In the MCP Adapter surface, confirm the approved read entrypoints route to
   the read server metadata and write/destructive abilities route to the
   governed write server metadata with dry-run defaults.
8. Run this repository's real-site smoke:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

## Expected Result

The official stack should treat this plugin as a provider of abilities and
schemas. It should not need this plugin to provide MCP transport, model routing,
connector credentials, request logs, approvals, audit records, quotas, queues,
or workflow runtime state.

If a compatibility failure appears, classify it first:

- Contract gap here: missing schema, metadata, permission callback, risk
  metadata, or declarative recipe information.
- Adapter/plugin gap outside this package: MCP server policy, connector setup,
  model routing, approval UX, request logging, or tool execution governance.

Only the first category should create work in this repository.
