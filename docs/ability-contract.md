# Ability Contract

Every public ability should define a stable machine-readable contract. The
contract must be clear enough for WordPress Abilities API clients, Npcink AI,
MCP-oriented hosts, and tests to understand the same behavior without reading
package internals.

## Identity

- `ability_id`: stable `namespace/name`. The namespace should identify the
  provider or compatibility surface. The name should describe the operation.
- Ability ids are lowercased and stripped to machine-safe characters during
  normalization.
- Ability ids without `/` are rejected by the registrar.
- Published ids should not be reused for different behavior.

## Required Human Metadata

- `label`
- `description`

Labels and descriptions should describe the operation, not the UI that happens
to expose it.

## Category

- `category`: stable category id.
- Read-only abilities default to `npcink-abilities-toolkit-read`.
- Write-like abilities default to `npcink-abilities-toolkit-write`.
- First-party migrated abilities may preserve legacy categories such as
  `npcink-abilities-toolkit-data`, `npcink-abilities-toolkit-pages`, `npcink-abilities-toolkit-comments`, and
  `npcink-abilities-toolkit-write` for compatibility.

## Schemas

- `input_schema`: JSON Schema object
- `output_schema`: JSON Schema object

Schemas are normalized before registration. Shorthand property values such as
`'string'` may be expanded by the schema normalizer, but published examples
should prefer explicit JSON Schema arrays.

Write-like definitions receive common host-governed input fields:

- `dry_run` (`boolean`, default `true`): request a preview without mutating
  WordPress.
- `commit` (`boolean`): request a final commit attempt. Host approval context
  is still required. The default is `false`.
- `idempotency_key` (`string`): optional host-provided replay/audit key.
  Implementations bound this field to 190 characters for storage/index safety.

Write-like definitions also receive common output fields:

- `dry_run` (`boolean`): whether the result is a preview.
- `host_governed` (`boolean`): whether final mutation belongs to a host path.
- `commit_required` (`boolean`): whether approval/commit is still needed.
- `preview` (`object`): reviewable summary of the intended mutation.

## Execution And Permission

- `execute_callback`
- `permission_callback` or `capability`

If `permission_callback` is absent, the toolkit builds one from `capability`.
The default capability is `manage_options`. Provider plugins should choose the
least broad WordPress capability that matches the operation.

## REST, MCP, And Magick Metadata

- `meta.show_in_rest`
- `meta.mcp.annotations.readonly`
- `meta.mcp.annotations.destructive`
- `meta.mcp.annotations.idempotent`
- `meta.npcink.channels` when Npcink AI compatibility metadata is needed
- `agent_usage` / `meta.agent_usage` for static agent and MCP selection
  guidance on priority entry or high-risk abilities

Rules:

- `meta.show_in_rest` defaults to `true`.
- `meta.mcp.public` is not the same as `meta.show_in_rest`.
- Default MCP-public read abilities are limited to the approved entrypoint
  allowlist recorded in [ADR 0004](adr/0004-default-mcp-exposure-policy.md).
- `meta.npcink.channels` is not the same as REST exposure.
- `project_to_npcink_catalog` controls Npcink AI compatibility projection and
  defaults to `false` for provider abilities.
- Projection is metadata only. Execution still goes through the WordPress
  Abilities API path.
- `agent_usage` is static descriptive guidance only. It must not define model
  routing, approval policy, channel-local execution, or workflow runtime rules.
  See [Agent Usage Metadata](agent-usage-metadata.md).

## Risk And Governance

- `risk_level`
- `requires_confirm`
- `requires_approval`

Risk levels:

- `read`: data retrieval or deterministic proposal/context generation.
- `write`: non-destructive mutation or write proposal.
- `destructive`: delete, trash, merge, spam, or other destructive operation.

`requires_confirm` and its governance-consumer alias `requires_approval` are
`false` for read abilities and `true` for write-like abilities.
Host-governed writes and destructive abilities may live in this package only
when they are generic WordPress operations. Direct clients receive dry-run
previews by default; final commit requires host approval context.

Write-like input schemas should reject undeclared fields unless a specific
ability has a documented reason to accept extension data. Permission checks must
run before both dry-run previews and final commits so previews do not leak data
to callers who could not perform the corresponding WordPress operation.

## Scopes And Versioning

- `required_scope`
- `required_scopes`
- `contract_version`
- `deprecated`
- `successor`

Read-only abilities may return data directly.

Write-proposal abilities must return a proposal, preview, diff, or other reviewable artifact. They must not commit destructive WordPress changes directly.

Host-governed commit abilities are not public third-party registration helpers
in 0.1. They are reserved for first-party package abilities and host runtime
contracts. If a future release exposes third-party host-governed registration,
that must be recorded in a new ADR first.
