# Official Stack E2E Environment

Status: active local workflow
Date: 2026-06-07

This workflow creates a reusable Docker WordPress site for repeatable checks
against the official WordPress AI stack. It is separate from `composer
test:all` because it depends on Docker, network downloads, WordPress startup,
and current official plugin release artifacts.

## Why Docker First

Use Docker for frequent compatibility testing because it is isolated,
repeatable, and cheap to reset. The shared Local site should stay available for
manual UI inspection and product-flow checks, but it should not be the default
place where official plugins are repeatedly installed and removed.

## Main Command

```bash
composer e2e:official-stack
```

The command:

- starts WordPress and MariaDB through
  `docker-compose.official-stack.yml`;
- mounts this checkout as
  `wp-content/plugins/npcink-abilities-toolkit`;
- installs WordPress if the Docker volume is empty;
- installs the Abilities API compatibility plugin only when the active
  WordPress build does not provide `wp_register_ability()`;
- downloads and caches the latest official MCP Adapter release zip;
- downloads and caches the latest official AI plugin release zip;
- activates this plugin plus the official plugins;
- records active plugin and REST route artifacts under
  `build/official-stack-e2e/`;
- runs the official-stack probes and the project WordPress smoke.

## Reuse And Reset

Default runs reuse containers, volumes, and cached plugin zips:

```bash
composer e2e:official-stack
```

For the normal development loop, use the fast probes first and run the full
smoke before handoff:

```bash
composer e2e:official-stack -- --skip-full-smoke
composer e2e:official-stack
```

Reset the site and database:

```bash
composer e2e:official-stack -- --fresh
```

Install and activate everything, then stop before smoke checks:

```bash
composer e2e:official-stack -- --setup-only
```

Run only the faster official-stack probes:

```bash
composer e2e:official-stack -- --skip-full-smoke
```

Stop containers without deleting volumes:

```bash
composer e2e:official-stack -- --down
```

Show container status:

```bash
composer e2e:official-stack -- --status
```

## Useful Overrides

```bash
OFFICIAL_STACK_HTTP_PORT=8899 composer e2e:official-stack
OFFICIAL_STACK_PROJECT_NAME=npcink_abilities_official_stack composer e2e:official-stack
OFFICIAL_STACK_INSTALL_AI=0 composer e2e:official-stack
OFFICIAL_STACK_INSTALL_MCP=0 composer e2e:official-stack
OFFICIAL_STACK_REINSTALL_OFFICIAL_PLUGINS=1 composer e2e:official-stack
```

Use a local plugin zip instead of downloading the current GitHub release:

```bash
OFFICIAL_STACK_MCP_ADAPTER_ZIP=/path/to/mcp-adapter.zip composer e2e:official-stack
OFFICIAL_STACK_AI_PLUGIN_ZIP=/path/to/ai.zip composer e2e:official-stack
OFFICIAL_STACK_ABILITIES_API_ZIP=/path/to/abilities-api.zip composer e2e:official-stack
```

The script caches downloads in `build/official-stack-cache/`, which is ignored
by Git.

## Local Verification

Last verified on 2026-06-07 with the reusable Docker environment:

- `composer e2e:official-stack -- --setup-only --skip-full-smoke` installed
  WordPress, this plugin, MCP Adapter, and the AI plugin.
- `composer e2e:official-stack -- --skip-full-smoke` reused the installed site
  and cached plugin zips, then passed the official-stack probes.
- `composer e2e:official-stack` reused the same environment, passed the
  official-stack probes, and completed project smoke with `Smoke OK: 265
  assertions`.

## Local App Role

Use Local for browser-visible checks after Docker proves the contract path:

- AI plugin Abilities Explorer display;
- Request Logging behavior;
- Connector Approvals screens;
- settings-page copy and admin notices;
- screenshots or browser automation.

Do not use the shared Local site for default automated official-stack testing
unless the task specifically needs its existing Npcink AI/Core/Toolbox context.

## What Counts As A Failure Here

Failures that usually belong in this repository:

- our abilities do not register through the Abilities API;
- REST catalog discovery omits required ability ids;
- schema, risk, MCP metadata, or dry-run defaults are missing;
- project smoke fails after official plugins are active.

Failures that usually belong outside this repository:

- MCP transport policy;
- AI connector credentials;
- Request Logging storage;
- Connector Approvals UX;
- model/provider routing;
- prompt or workflow runtime behavior.
