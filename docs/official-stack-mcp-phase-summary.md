# Official Stack MCP Phase Summary

Status: phase closed
Date: 2026-06-07

This note records the closure state for the official WordPress AI and MCP
integration phase. Detailed operating instructions remain in
[Official Stack E2E Environment](official-stack-e2e.md), and the default MCP
exposure policy is recorded in
[ADR 0004](adr/0004-default-mcp-exposure-policy.md).

## What Was Completed

- Added a reusable Docker WordPress environment for official stack checks.
- Installed and reused the official Abilities API compatibility plugin when
  needed, the official MCP Adapter, and the official AI plugin.
- Confirmed the official AI plugin Abilities Explorer can inspect and execute
  toolkit abilities.
- Defined the default MCP exposure policy: five read entrypoints plus
  governed write and destructive dry-run abilities.
- Added an MCP HTTP probe that exercises the real default MCP Adapter server:
  `initialize`, `tools/list`, `discover`, `get-ability-info`, read execution,
  governed write dry-run execution, and destructive dry-run execution.
- Versioned the MCP HTTP evidence artifact as
  `official-stack-mcp-http-summary/v1`.

## Current Evidence

The current local verification passed with these commands:

```bash
composer test:all
composer analyse:phpstan
composer e2e:official-stack -- --skip-full-smoke
composer e2e:official-stack
```

The full official-stack run completed project WordPress smoke with:

```text
Smoke OK: 265 assertions
```

The MCP HTTP summary artifact is written to:

```text
build/official-stack-e2e/mcp-http-summary.json
```

The artifact currently records:

- `schema_version: official-stack-mcp-http-summary/v1`
- 3 required MCP Adapter tools
- 39 MCP-public abilities
- 37 Npcink MCP-public abilities
- 5 approved read entrypoints
- successful `site-info` read execution
- successful `create-draft` governed write dry-run
- successful `delete-post-permanently` destructive dry-run
- cleanup success for the temporary application password and destructive
  fixture post

## Reuse Rule

For normal development, use:

```bash
composer e2e:official-stack -- --skip-full-smoke
```

Before handoff, release work, or changes to MCP exposure, run:

```bash
composer e2e:official-stack
```

The Docker site can stay running between checks. Stop it only when local
resources need to be released:

```bash
composer e2e:official-stack -- --down
```

## What Is Intentionally Not Done

- No full 100+ ability MCP E2E matrix.
- No real write commit through MCP.
- No real destructive commit through MCP.
- No dependency on AI connector credentials or model/provider routing.
- No ownership of MCP gateway policy, approval UX, audit storage, quotas, or
  product runtime state.

Those boundaries keep this repository focused on being the WordPress ability
pack and contract layer.

## Reopen Triggers

Reopen this phase only when one of these changes:

- official MCP Adapter response shape changes;
- official AI plugin Abilities Explorer behavior changes;
- the default MCP public exposure policy changes;
- a new read entrypoint, governed write, or destructive ability becomes part of
  the default MCP contract;
- CI starts consuming `mcp-http-summary.json`;
- release verification needs a fresh official-stack receipt.

Until then, this phase can be considered complete.
