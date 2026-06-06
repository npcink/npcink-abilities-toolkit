# Testing Strategy

Status: active
Date: 2026-06-07

This package is a foundation layer for WordPress Abilities API contracts. Its
tests should protect stable ability discovery, schema shape, risk metadata,
host-governed write boundaries, and consumer handoff behavior. They should not
grow into broad product workflow automation or coverage-driven test volume.

## Default Gate

Run this before release work and before merging changes that affect registered
abilities, schemas, metadata, workflow recipes, projection, diagnostics, or
host-governed write behavior:

```bash
composer test:all
```

`composer test:all` is the local release gate. It runs composer validation,
project boundary checks, contract readiness, consumer handoff, workflow consumer
proof, official stack compatibility, MCP exposure, provider demo compatibility,
catalog audit, WordPress.org static review checks, performance smoke,
lightweight regression tests, and PHP syntax linting.

Run targeted gates while iterating:

```bash
composer test
composer check:boundary
composer check:contracts
composer check:consumer
composer check:workflow-consumer
composer check:official-stack
composer check:mcp-exposure
composer check:provider-demo
composer check:catalog
composer perf:smoke
```

Use `composer smoke:wp` only when a real WordPress site is available. Use
`composer e2e:official-stack` when changing assumptions about the official
Abilities API, AI plugin, or MCP Adapter stack.

## What To Test

Prefer focused contract and invariant tests. Add or update coverage when a
change can affect:

- public helper behavior;
- ability id, category, label, description, and contract version stability;
- input and output schema normalization;
- read/write/destructive risk metadata;
- `requires_confirm`, `requires_approval`, `dry_run`, `commit`, and
  `idempotency_key` behavior;
- REST, MCP, and Npcink metadata consistency;
- MCP public exposure allowlists and server routing;
- workflow recipe discovery, replay fixture parity, and read-only entrypoints;
- consumer proposal payload handoff;
- provider demo compatibility with public helpers;
- diagnostic redaction and bounded performance behavior.

## What Not To Test Here

Do not add broad tests for host-owned runtime concerns. This package does not
own model routing, prompt selection, workflow execution, approval storage, audit
truth, quota, billing, MCP gateway policy, cloud execution, or product-specific
Toolbox UX.

Do not create a full matrix for every ability and every input combination. For
large ability packs, test shared normalization and high-risk invariants once,
then add focused assertions only for ability-specific contracts that would break
real consumers.

Do not introduce PHPUnit, browser automation, or Docker-based E2E as the default
local loop unless the existing lightweight gate cannot express the contract
being protected. Heavy tests should remain explicit smoke or E2E commands.

## Adding A New Ability

Before adding or promoting a first-party ability, prove that the existing
ability chain cannot satisfy the host workflow through composition. If a new
ability is still justified, add the smallest reusable WordPress-only contract
and update the relevant contract, replay, smoke, boundary, and performance gates
before release.
