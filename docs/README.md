# Documentation Guide

Use this page as the entry point for integration and debugging work. The
repository contains detailed contract and release notes; this guide points each
reader to the smallest useful path first.

## Plugin Authors

Use this path when another WordPress plugin wants to provide abilities through
`npcink-abilities-toolkit`.

1. Start with [Provider Plugin Guide](third-party-plugin-guide.md).
2. Copy from the working demo provider in
   [examples/demo-plugin/npcink-abilities-toolkit-demo.php](../examples/demo-plugin/npcink-abilities-toolkit-demo.php).
3. Check the stable public helper surface in [Public API](public-api.md).
4. Verify provider compatibility with:

```bash
composer check:provider-demo
```

Provider plugins should call the public helper functions only. Do not require
files under `includes/` or instantiate classes in the
`Npcink_Abilities_Toolkit` namespace.

## Host And Product Consumers

Use this path when a host product discovers, filters, runs, or projects
abilities.

1. Read the project ownership boundary in the root [README](../README.md).
2. Choose a deployment shape in [Host Profiles](host-profiles.md).
3. Use [Permission Matrix](permission-matrix.md) for write and destructive
   capability expectations.
4. Use [Core Governance Handoff Guide](core-governance-handoff-guide.md) when
   preparing proposal payloads for a governed host.
5. Use [Npcink AI Integration Contract](npcink-ai-integration.md) only when the
   consuming host is Npcink AI or a compatible projection consumer.

Hosts own caller identity, approval, audit, quota, model routing, MCP transport,
and final write authorization. This package owns ability registration, schema
normalization, metadata, and dry-run contract shape.

## REST And External Clients

Use this path when a client needs to discover or run abilities through the
WordPress REST API.

1. Start with [REST Client Quickstart](rest-client-quickstart.md).
2. Use the admin page endpoint values from `Tools -> Abilities API Packages` or
   `Npcink AI -> Abilities`.
3. Use [Troubleshooting](troubleshooting.md) for common 401, 403, missing route,
   missing ability, and dry-run issues.

REST clients should discover the catalog first, inspect each ability's schema,
risk metadata, and permission requirements, then run only abilities that the
current WordPress user is allowed to execute.

Host runtimes that need a cheap compatibility check before fetching the full
catalog can also read `GET /wp-json/npcink-abilities-toolkit/v1/contract` with
a `manage_options` REST caller for plugin version, contract versions, catalog
hashes, workflow hash, and the host-governed write boundary.

## Maintainers

Use this path when changing this package.

1. Run the lightweight local gates listed in [Security And Governance Gates](security-and-governance-gates.md).
2. Use [Testing Strategy](testing-strategy.md) to decide when to add contract,
   smoke, performance, or E2E coverage.
3. Use [Pattern Page Reference Spike](pattern-page-reference-spike.md) before
   changing `build-pattern-page-plan`, page patterns, or Gutenberg-native page
   composition.
4. Use [Block Theme Context Snapshot Proof](block-theme-context-snapshot-proof.md)
   before changing block theme route context, homepage template planning, or
   template proposal fail-closed behavior.
5. Run the real-site smoke from [Local WP-CLI Smoke Test](local-wpcli-smoke.md)
   when a WordPress site is available.
6. Run [Official Stack E2E Environment](official-stack-e2e.md) before changing
   official Abilities API, MCP, or AI plugin compatibility assumptions.
7. Use [GitHub Publication And Continuous Gates](github-publication-and-continuous-gates.md)
   for the public repository handoff, CI baseline, and post-publication gate
   status.
8. Record release evidence in the relevant release verification document.

Prefer adding focused docs to this guide instead of expanding the root README
with every workflow detail.
