# ADR 0004: Keep Default MCP Exposure Narrow And Entrypoint-Oriented

## Status

Accepted

## Date

2026-06-07

## Context

The official MCP Adapter default server does not list every WordPress ability as
an individual MCP tool. It exposes adapter tools such as
`mcp-adapter-discover-abilities`, `mcp-adapter-get-ability-info`, and
`mcp-adapter-execute-ability`. Only abilities with `meta.mcp.public=true` are
discoverable through that default server.

The package currently exposes the full ability catalog through the official
Abilities REST API and the official AI plugin's Abilities Explorer. That is the
right surface for full inspection. The MCP default server is different: it is a
client-facing discovery and execution surface, so adding all 100+ read abilities
would create noisy tool discovery and expand the default remote-call surface.

At the same time, exposing only write and destructive dry-run abilities through
MCP is not enough. MCP clients need a small number of read-only entrypoints to
understand the site and choose the right governed operation.

## Decision

Keep `meta.mcp.public` separate from `meta.show_in_rest`.

Default MCP exposure is limited to:

- read-only entrypoint abilities that help an MCP client orient itself;
- host-governed write and destructive abilities that default to dry-run and
  require explicit host approval context before commit.

The read-only MCP entrypoint set is:

- `npcink-abilities-toolkit/site-info`
- `npcink-abilities-toolkit/list-post-types`
- `npcink-abilities-toolkit/list-taxonomies`
- `npcink-abilities-toolkit/list-workflow-recipes`
- `npcink-abilities-toolkit/get-workflow-recipe`

The default MCP server should not publish diagnostics, broad content scans,
media inventory scans, user lists, comment queues, or large workflow context
reports by default. Those remain available through authenticated Abilities REST
and official Abilities Explorer surfaces.

## Alternatives Considered

### Publish every read ability through default MCP

- Pros: Maximum discoverability for MCP clients.
- Cons: Large default discovery output, harder client selection, and a broader
  remote-call surface than needed.
- Rejected: REST and Abilities Explorer already provide full catalog
  inspection.

### Keep only write/destructive abilities MCP-public

- Pros: Smallest MCP discovery set.
- Cons: MCP clients cannot orient themselves without out-of-band REST catalog
  knowledge.
- Rejected: A default MCP client should be able to ask for site identity,
  content type shape, taxonomy shape, and workflow recipes.

### Publish diagnostics through default MCP

- Pros: Useful for support and environment troubleshooting.
- Cons: Even redacted diagnostics are operationally sensitive and noisy.
- Rejected for default exposure. Diagnostics stay REST/UI-visible and can be
  made MCP-public later by a focused decision.

## Consequences

- Official Abilities Explorer remains the full catalog inspection surface.
- Default MCP discover returns a small entrypoint set plus governed write
  operations.
- `composer check:mcp-exposure` guards accidental read-side MCP expansion.
- `composer e2e:official-stack` verifies MCP HTTP discovery and execution for
  both a read entrypoint and a governed write dry-run.
