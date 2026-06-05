# Core Governance Catalog Snapshot

Status: active contract snapshot.

The machine-readable snapshot lives at
`tests/fixtures/core-governance-catalog-snapshot.json`. It is intentionally
small: it locks only the high-value handoff abilities that `npcink-ai-core`
uses to validate proposal governance.

## Snapshot Scope

- `npcink-abilities-toolkit/create-draft`
- `npcink-abilities-toolkit/set-post-seo-meta`
- `npcink-abilities-toolkit/approve-comment`
- `npcink-abilities-toolkit/list-workflow-recipes`
- `npcink-abilities-toolkit/get-workflow-recipe`

## Locked Fields

The snapshot records:

- category;
- `risk_level`;
- `requires_confirm`;
- `requires_approval`;
- WordPress capability;
- required scopes;
- bounded input schema fingerprint;
- output schema fingerprint;
- REST/MCP exposure metadata needed by consumers.

The lightweight regression test rebuilds this snapshot from normalized package
definitions and fails if a locked field drifts. Intentional changes should
update this document, the fixture, and the release verification note in the
same commit.

## Non-Goals

This is not a full catalog export, a runtime registry, an MCP manifest, or a
workflow engine. Hosts should continue to discover the live catalog through the
WordPress Abilities API at runtime.
