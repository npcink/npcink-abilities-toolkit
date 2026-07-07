# Ability Metadata Reference Notes - 2026-07

Status: benchmark notes for future Abilities Toolkit contract decisions.

Date: 2026-07-06.

This note is the Abilities Toolkit pass in the cross-project reference plugin
benchmark. It compares the current Toolkit contract posture against the
official WordPress AI and Abilities API direction. It is not an implementation
plan, dependency decision, or request to add new abilities.

## Question

Which official WordPress ability and AI patterns should
`npcink-abilities-toolkit` learn from before changing ability metadata,
schemas, registration helpers, or host-governed write contracts?

## Short Answer

The Toolkit is already closest to an ability pack and contract catalog. It
should keep moving toward the boring official shape:

- stable `namespace/name` ability ids;
- explicit categories;
- human labels and descriptions;
- object input and output schemas;
- permission callbacks;
- REST-visible metadata for clients;
- read/write/destructive risk annotations;
- dry-run defaults for write-like actions;
- host-owned approval, audit, model routing, MCP transport, and final commit
  authorization.

The Toolkit should not copy the broader official AI plugin product surface. The
official stack may own AI workflows, connector approvals, request logs, model
clients, and MCP transport. Toolkit should remain a standalone provider of
WordPress abilities and reviewable dry-run contracts.

## Reference Sources

| Reference | Relevant pattern | Toolkit learning |
| --- | --- | --- |
| [WordPress Abilities API](https://developer.wordpress.org/apis/abilities-api/) | Ability registration, categories, labels, descriptions, schemas, callbacks, permission checks, and REST discovery/execution. | Keep Toolkit ability contracts close to the official ability primitive rather than a private catalog shape. |
| [WordPress Abilities API repository](https://github.com/WordPress/abilities-api) | Canonical package/source for the Abilities API while it stabilizes. | Treat compatibility drift as a contract issue to investigate before adding local abstractions. |
| [WordPress AI plugin](https://github.com/WordPress/ai) and [plugin directory page](https://wordpress.org/plugins/ai/) | Reference product surface for Abilities Explorer, AI request logging, connector approvals, and AI workflows. | Learn discoverability and operator language, but do not import product/runtime ownership. |
| [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) | MCP transport layer for WordPress abilities. | Keep MCP-facing metadata useful, while leaving gateway policy and transport outside Toolkit. |
| [WordPress PHP AI Client](https://github.com/WordPress/php-ai-client) and [WP AI Client](https://github.com/WordPress/wp-ai-client) | Provider/model client layer. | Do not move model clients, credentials, routing, or prompt selection into Toolkit. |

## Current Toolkit Baseline

Current project docs and guards already preserve the core boundary:

- `README.md` positions Toolkit as an independent Abilities API capability
  package.
- `docs/official-wordpress-ai-stack-compatibility.md` states that Toolkit is
  closest to an ability pack and contract catalog, not an AI product surface.
- `docs/ability-contract.md` defines `input_schema`, `output_schema`,
  permissions, REST metadata, agent usage guidance, risk level, and approval
  fields.
- `docs/schema-boundary-audit.md` records `dry_run=true`, `commit=false`,
  `idempotency_key`, bounded write schemas, and approval flags for
  write/destructive contracts.
- `docs/security-and-governance-gates.md` makes stable capabilities, risk
  metadata, dry-run defaults, and approval context requirements local security
  responsibilities.
- `composer check:official-stack` checks official-stack compatibility shape.

These notes therefore look for clarity and compatibility improvements only.
They do not ask Toolkit to expand ownership.

## Similar Capability Matrix

| Toolkit surface | Similar official pattern | Borrow | Do not borrow |
| --- | --- | --- | --- |
| Ability registration helpers | `wp_register_ability()` and `wp_register_ability_category()` | Keep helper output easy to map to official ability registration and category concepts. | Do not turn helper inspection APIs into a second authoritative ability registry. |
| Ability ids | Official namespaced ability ids | Preserve stable `namespace/name` ids and reject legacy/private ids from new contracts. | Do not introduce product-specific aliases as canonical ids. |
| Human metadata | Ability labels and descriptions | Make ability labels/descriptions useful in explorers, host catalogs, and MCP tooling. | Do not encode product workflow instructions inside labels. |
| Schemas | Official input/output JSON-schema posture | Keep input and output schemas object-shaped, bounded, and client-discoverable. | Do not use loose schemas as a backdoor for unreviewed runtime payloads. |
| Permission callbacks | WordPress capability checks before execution | Keep permissions meaningful before both previews and commits so previews do not leak data. | Do not delegate local WordPress permission checks entirely to Core or hosts. |
| REST visibility | `show_in_rest` / `wp-abilities/v1` discovery | Keep discoverable abilities visible to official REST clients when safe. | Do not confuse REST visibility with MCP-public exposure or final write permission. |
| Risk metadata | Read/write/destructive metadata and annotations | Keep `risk_level`, `requires_approval`, MCP risk, and Npcink metadata aligned. | Do not let risk fields drift across REST, MCP, Npcink, and annotations. |
| Write-like abilities | Dry-run preview and host approval posture | Keep `dry_run=true`, `commit=false`, preview artifacts, and host approval context for writes. | Do not add silent commits, approval storage, audit truth, or host policy inside Toolkit. |
| Agent guidance | Static agent/explorer hints | Use `agent_usage` to guide safe selection and non-goals for priority or high-risk abilities. | Do not make `agent_usage` a prompt registry, model policy, or workflow runtime contract. |
| Workflow recipe helpers | Static host-side composition metadata | Keep recipes read-only, declarative, and host-composed. | Do not add workflow state, queues, retries, leases, approval policy, or final write authority. |

## Borrow

Future Toolkit contract work should borrow these official-stack patterns:

- Boring ability ids: every public ability should remain a stable
  `namespace/name` contract.
- Explorer-friendly metadata: labels and descriptions should read well in a
  generic ability explorer, not only in Npcink UI.
- Schema discipline: clients should be able to inspect object-shaped input and
  output schemas before running an ability.
- Permission clarity: permissions should be local WordPress checks that happen
  before any preview result leaks sensitive data.
- REST-first discovery: official `wp-abilities/v1` routes should stay the
  default discovery/execution surface for external clients.
- Separate exposure layers: REST-visible, MCP-public, and Npcink-projected are
  different decisions and should stay explicitly represented.
- Host-governed writes: write/destructive abilities may prepare dry-run
  previews and review artifacts, but final approval and audit live in the host.

## Do Not Borrow

Do not copy these official or ecosystem surfaces into Toolkit:

- AI request logs;
- connector approval stores;
- model/provider credentials;
- model routing;
- prompt or preset ownership;
- MCP gateway admission policy;
- approval records;
- audit records;
- quotas or billing;
- workflow runtime state;
- queues, leases, retries, or schedulers;
- product workbenches or all-in-one AI dashboards.

If a future idea needs those surfaces, route it to Core, Adapter, Toolbox,
Cloud Addon, Cloud, or a separate runtime owner before implementation.

## Candidate Improvements

These notes are implementation candidates. The first compact official-stack
alignment checklist and `meta.annotations` guard were implemented as a minimal
contract-hardening slice after the branch was repurposed for Abilities API
metadata alignment. Remaining items still need their own scoped decision before
implementation.

### P1 - Preserve

Keep these existing Toolkit choices:

- `npcink_abilities_toolkit_get_registered()` stays an inspection helper, not
  an authoritative replacement for WordPress Abilities API discovery.
- `meta.show_in_rest` defaults to true for discoverable abilities.
- `meta.mcp.public` stays separate from REST visibility.
- `risk_level`, `requires_approval`, MCP risk, and Npcink metadata stay
  aligned by contract guards.
- Write/destructive abilities keep `dry_run`, `commit`, and `idempotency_key`
  controls.
- Workflow definition helpers stay static read-only recipe metadata.

### P1 - Clarify

Potential future documentation or admin-surface improvements:

- Maintain the compact "official-stack alignment" checklist in ability review
  docs: id, category, label, description, schemas, permission, REST visibility,
  official annotations, risk, and host-governed write posture.
- Make the admin Available Abilities technical details explicitly distinguish
  REST visibility, MCP public exposure, and Npcink projection.
- In provider-facing docs, emphasize that `show_in_rest=true` does not mean the
  ability is safe for unattended agents or public MCP exposure.
- In high-risk ability docs, put `agent_usage` guidance beside risk metadata so
  explorer-style tools can explain safe use.

### P2 - Investigate Later

Only after a real official-stack smoke:

- Whether Toolkit needs a small compatibility note for Abilities Explorer
  display copy.
- Whether `agent_usage` guidance should have a stricter documentation template.
- Whether provider docs should include one more example that registers a
  write-proposal ability with dry-run preview and host-governed commit
  language.

These are not current implementation items.

## Suggested Next Artifact

The next artifact should be a design note or issue, not code:

```text
Title: Compare Toolkit ability metadata against official Abilities API and WordPress AI explorer expectations

Scope:
- Review a representative read ability, write-proposal ability, and workflow recipe.
- Compare id, category, label, description, schemas, permission callback, REST visibility, risk metadata, MCP metadata, and agent_usage.
- Propose no more than three documentation or presentation-only improvements.

Non-goals:
- No new abilities.
- No schema changes.
- No write execution changes.
- No model/provider routing.
- No MCP gateway policy.
- No workflow runtime.
```

## Decision Gate

Before any Toolkit implementation derived from this note, answer yes to all of
these:

1. Does it improve official-stack compatibility or operator/client
   understanding?
2. Does it preserve Toolkit as an ability package, not a host runtime?
3. Does it avoid new provider/model/prompt/approval/audit ownership?
4. Does it keep WordPress permission callbacks and host-governed write posture
   intact?
5. Can it be verified with the existing Toolkit gates, especially
   `composer test:all`, `composer analyse:phpstan`, and `composer check:boundary`
   when governance text changes?

If any answer is no, do not implement it inside Toolkit.
