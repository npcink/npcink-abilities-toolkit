# Ability Contract Readiness 0.5

Status: active implementation plan for the `0.5.x` line.

This line turns the current ability catalog from a broad migrated surface into a
stable, agent-ready WordPress Abilities API package. The priority is contract
quality, consumer proof, and boundary discipline. It is not a feature expansion
line.

## Objective

`npcink-abilities-toolkit` should be the local ability truth source for WordPress
agents and product plugins:

- stable ability ids;
- bounded input and output schemas;
- strict permission callbacks;
- explicit read/write/destructive risk metadata;
- host-governed write previews;
- recipe discovery that describes composition without owning execution.

## Ownership Boundary

This package owns:

- ability categories;
- normalized ability contracts;
- read-only WordPress context abilities;
- deterministic proposal and host-governed write/destructive ability contracts;
- workflow recipe discovery metadata;
- optional thin Magick AI compatibility projection metadata.

This package does not own:

- model routing;
- AI Client or Connectors provider setup;
- API keys, billing, quota, or token accounting;
- proposal storage, approval, preflight, or audit;
- workflow runtime, queues, retries, or schedules;
- MCP server/runtime ownership;
- OpenClaw-specific tool registries;
- final WordPress mutation policy.

## 0.5 Work Items

1. Keep the current ability surface in freeze/observe mode.
2. Run a first-party contract audit against the registered ability catalog.
3. Keep workflow recipes declarative and verify recipe ability references.
4. Add static `agent_usage` guidance only to priority entry, proposal, write,
   destructive, and diagnostics abilities where misuse risk is high.
5. Add new abilities only after a failed Core, Adapter, or host workflow proof
   exposes a concrete schema, metadata, or contract gap.
6. When a fourth-batch ability is justified, limit it to three to five entries,
   prefer read-only context, and keep write outputs as proposals.

## Contract Audit Gate

The `composer check:contracts` command must pass before release work. It checks
the registered first-party surface after default boot, not only exported JSON
fixtures.

The audit intentionally fails on:

- mismatched `ability_id` keys;
- missing human metadata;
- non-object input/output schemas;
- missing callable callbacks;
- unsupported risk levels;
- read abilities that expose write controls;
- write/destructive abilities that lack dry-run, commit, or idempotency
  controls;
- inconsistent REST, MCP, Magick, or annotation risk metadata;
- missing `agent_usage` on the small required priority ability set;
- workflow recipes that reference missing abilities.

## Candidate Ability Rule

Potential future abilities from product planning are candidates only. Examples
such as AI readiness, agent readiness, content compliance context, search
indexing health, or content compliance proposals must first be proven as gaps by
a consumer workflow.

Do not add product execution abilities such as China environment repair, CDN
flush, provider setup, payment, WeChat configuration, or model execution here.
Those belong in product, provider, adapter, or host plugins.

## Exit Criteria

- `composer check:contracts` passes.
- `composer check:boundary` passes.
- `composer check:consumer` passes.
- `composer check:catalog` passes.
- `composer check:wporg` passes.
- `composer check:plugin-package:local` passes before any WordPress.org upload.
- `composer test:all` passes.
- `composer smoke:wp` result is recorded when a Local WordPress site is
  available.
- `docs/wordpress-org-review-lessons.md` has been checked against any current
  review email under `sj/q/`.
- `docs/ability-acceptance-matrix.md` remains the gate for any new ability.
- `docs/core-governance-handoff-guide.md` remains accurate for Core consumers.
