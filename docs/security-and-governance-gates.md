# Security And Governance Gates

Status: active
Date: 2026-06-07

This document defines the security and governance checks that belong inside
`npcink-abilities-toolkit`. It is intentionally narrower than host security,
MCP governance, audit storage, quota enforcement, or product administration.

## Owned Here

`npcink-abilities-toolkit` owns security through ability contract discipline:

- stable WordPress capability checks;
- permission callbacks before previews and commits;
- read/write/destructive risk metadata;
- REST, MCP, and Npcink metadata consistency;
- dry-run defaults for write and destructive abilities;
- host approval context requirements before commit;
- bounded input schemas;
- redaction in diagnostics and operational summaries;
- uploads path containment for package-owned media file writes and moves.

## Not Owned Here

This package must not become the owner of:

- approval records or audit logs;
- app keys, user admission, quota, or billing;
- MCP gateway policy;
- model/provider credentials;
- provider routing;
- security firewall behavior;
- site backup/restore orchestration;
- CDN purge execution;
- product UX for approvals, audits, or operations.

Those concerns belong to Npcink AI Core, Toolbox, Adapter, Cloud Addon, or
another consuming host.

## Required Local Gates

The scope rules for when to add lightweight contract, smoke, performance, or E2E
coverage are documented in [Testing Strategy](testing-strategy.md).

Run these before release work:

```bash
composer check:contracts
composer check:boundary
composer check:consumer
composer check:workflow-consumer
composer check:official-stack
composer check:mcp-exposure
composer check:provider-demo
composer test:all
```

Run this when changing performance-sensitive read chains:

```bash
composer perf:smoke
```

`composer test:all` also runs `composer perf:smoke` so the local release gate
catches accidental scan or aggregator cost regressions.

Run this when a Local WordPress site is available:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

## Contract Assertions

The contract audit must fail when:

- an ability id does not use `namespace/name`;
- human metadata is missing;
- input or output schemas are not object schemas;
- callbacks are not callable;
- risk level is unsupported;
- read abilities expose write controls;
- write or destructive abilities lack `dry_run`, `commit`, or
  `idempotency_key`;
- write-like input schemas accept undeclared fields without a documented
  exception;
- input schema `additionalProperties` extension points are added without the
  contract audit allowlist;
- REST, MCP, Npcink, or annotation risk metadata drift apart;
- MCP-public read abilities drift outside the ADR 0004 entrypoint allowlist;
- required `agent_usage` guidance is missing from priority or high-risk
  abilities;
- workflow recipes reference missing or non-read expanded abilities.
- workflow recipe fixtures drift from the production provider;
- official-stack compatibility metadata drifts from the Abilities API, MCP
  Adapter, or explorer-facing contract shape;
- MCP public exposure, server metadata, risk metadata, annotations, or
  dry-run/write boundaries drift;
- the demo provider plugin stops proving the public helper contract.

## Diagnostic Redaction

Diagnostics and support abilities may expose bounded WordPress-only operational
summaries. They must not expose:

- API keys, access tokens, credentials, or secrets;
- database names, table names, or table prefixes;
- filesystem paths;
- Npcink AI, MCP, provider, or cloud settings;
- unredacted error log contents;
- cron argument values;
- external HTTP probe results unless a future ability contract explicitly
  accepts that scope.

`npcink-abilities-toolkit/wp-ops-diagnostics-detail` defaults to
`profile=summary`. Heavier plugin rows, cron events, log inspection, database
state, roles, widgets, block-theme, search, integration, and current-user
sections require `profile=detail`, `profile=forensics`, or explicit include
flags. Log contents remain redacted and tail-bounded.

## Workflow Recipe Security

Workflow definitions may list write or destructive abilities only as
`disallowed_default_ability_ids` or optional host-governed dry-run follow-ups.
They must not select write/destructive abilities as default task entrypoints,
own approval policy, store workflow state, or define final write authority.
