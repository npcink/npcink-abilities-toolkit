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
- redaction in diagnostics and operational summaries.

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

Run these before release work:

```bash
composer check:contracts
composer check:boundary
composer check:consumer
composer test:all
```

Run this when changing performance-sensitive read chains:

```bash
composer perf:smoke
```

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
- REST, MCP, Npcink, or annotation risk metadata drift apart;
- required `agent_usage` guidance is missing from priority or high-risk
  abilities;
- workflow recipes reference missing or non-read expanded abilities.

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

## Workflow Recipe Security

Workflow definitions may list write or destructive abilities only as
`disallowed_default_ability_ids` or optional host-governed dry-run follow-ups.
They must not select write/destructive abilities as default task entrypoints,
own approval policy, store workflow state, or define final write authority.
