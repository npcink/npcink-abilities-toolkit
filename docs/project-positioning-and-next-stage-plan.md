# Project Positioning And Next Stage Plan

Status: active
Date: 2026-05-28

## Positioning

`npcink-abilities-toolkit` is the WordPress AI Agent ability contract and
registration infrastructure layer.

It should be developed as an Abilities Pack SDK plus first-party WordPress ability packages: a stable way to define, normalize, register, and discover reusable WordPress ability contracts and bounded callbacks that the WordPress Abilities API or a host may invoke.

It is not the end-user AI product, not the China-focused site-owner toolbox, and
not the Npcink AI runtime. Its workflow recipe helpers are read-only metadata
for host-side composition, not a workflow registry or execution layer. Npcink
AI, Npcink AI Toolbox, external MCP hosts, and third-party plugins are consumers
of this project.

If Npcink AI is absent, this package must still not create fallback workflow
runtime, approval storage, audit logs, quota enforcement, MCP gateway policy, or
product catalog governance. A standalone install can expose abilities and local
checks, but it does not become the host control plane.

## Why This Positioning

The WordPress AI direction is moving away from each plugin owning its own model calls and toward shared platform primitives: AI Client, Connectors API, Abilities API, client-side abilities, and MCP-oriented ability exposure. In that environment, the defensible layer for this project is not another prompt UI. It is the machine-readable WordPress operation catalog that upper layers can safely compose.

The China-market research points to a different product layer: site owners need domestic environment fixes, configuration wizards, Baidu/WeChat/CDN/ICP workflows, compliance help, documentation, and low-friction packaging. Those are important product opportunities, but they belong in Npcink AI Toolbox or another end-user product, not in this infrastructure package.

The project boundary already matches this conclusion:

- this project owns Abilities API categories, schemas, metadata, reusable WordPress callbacks, dry-run previews, and optional compatibility projection;
- host runtimes own caller identity, admission, quota, audit, approval, final commit authorization, workflow orchestration, model routing, MCP governance, and cloud execution;
- Npcink AI is an optional consumer, not the owner of this package.

## Layering Model

### `npcink-abilities-toolkit`

Owns:

- public helper APIs for provider plugins;
- ability category registration;
- read-only ability registration;
- host-governed write and destructive ability registration;
- schema, annotation, metadata, risk, and MCP-facing normalization;
- reusable WordPress-only ability callbacks;
- dry-run preview behavior for write and destructive abilities;
- local admin REST-check surface;
- optional Npcink AI compatibility projection by filters.

Does not own:

- model/provider routing;
- API key storage or provider billing;
- MCP gateway policy;
- user-facing workflow builders;
- final write approval;
- audit persistence;
- quota enforcement;
- cloud execution;
- broad China toolbox UI.

### Npcink AI

Owns:

- model routing and semantic runtime;
- workflow and skill runtime;
- Agent Gateway and Open API;
- MCP governance and resource exposure;
- app-key authentication;
- quota, audit, approval storage, and final commit authorization;
- cloud execution enhancement;
- operations dashboards.

### Npcink AI Toolbox Or Other End-User Products

Own:

- domestic WordPress environment repair;
- configuration wizards and one-click checks;
- Baidu/IndexNow/search-engine submission UX;
- WeChat login/share/payment UX;
- CDN/object-storage setup UX;
- ICP/security/compliance UX;
- documentation, onboarding, pricing, and commercial packaging.

They may consume `npcink-abilities-toolkit` to expose their operations to agents, but their market value is the end-user experience.

## Principal Contradiction

The current strategic tension is:

`market demand for complete toolbox and AI product experiences` vs `this repository's best role as a stable ability infrastructure layer`.

This is not a conflict that should be solved by moving all product functionality into this repository. It should be solved by keeping this repository narrow and stable, then letting upper-layer products compose and commercialize the abilities.

## Strategic Rules

1. Abilities first, UI second.
2. Stable contracts over feature volume.
3. Generic WordPress operations may live here; product-specific workflows stay in the consuming product.
4. Direct clients get dry-run previews by default for writes and destructive actions.
5. Final commit always requires host approval context.
6. No duplicate ownership for migrated ability ids.
7. No production dependency on Npcink AI internals.
8. Keep the public API small, boring, and versioned.

## Next Stage Goals

### P0: Stabilize The Ability Foundation

Goal: make this package safe for Npcink AI, Npcink AI Toolbox, and third-party plugins to depend on.

Deliverables:

- freeze and document registration helper semantics;
- document the write/destructive dry-run and commit envelope;
- define ability id, category, schema, annotations, required scopes, and risk-level conventions;
- add example provider plugin coverage for third-party read-only and projected abilities;
- keep `composer test:all` and `composer check:boundary` as required checks;
- keep Npcink AI dependency out of package code.

Success signal:

- a third-party plugin can register a useful ability without referencing internal classes;
- Npcink AI can discover and execute migrated abilities without duplicate configs;
- write/destructive abilities cannot silently commit without host context.

### P1: Prove Consumer Integration

Goal: prove that upper-layer products can consume this package as infrastructure.

Deliverables:

- Npcink AI integration contract with minimum supported `npcink-abilities-toolkit` version;
- duplicate ability id audit flow for cross-repo development;
- compatibility projection tests for opted-in provider abilities;
- one consumer-side smoke path that discovers an ability through WordPress Abilities API and executes it through the host runtime;
- documented failure modes for missing Abilities API, missing package, missing host approval, and duplicate ids.

Success signal:

- Npcink AI treats this project as catalog truth for migrated generic WordPress abilities;
- fallback definitions are not reintroduced without a new ADR;
- consumer breakages are diagnosed by contract tests rather than manual inspection.

### P2: Shape First-Party Ability Packs

Goal: turn the existing migrated abilities into coherent, market-relevant packs without turning this repo into a UI product.

Recommended pack order:

1. WordPress content context pack: site, posts, pages, blocks, revisions, media, taxonomies, menus.
2. Host-governed publishing pack: create draft, patch content, update metadata, schedule, publish, restore.
3. Comment and compliance helper pack: comment reads, moderation suggestions, reply handoffs, batch summaries.
4. Diagnostics pack: redacted WordPress-only environment diagnostics.
5. SEO/GEO support pack: ability-level context and suggestion helpers that do not require model routing.

Success signal:

- each pack has a focused README section, test coverage, and a clear ownership boundary;
- pack names and ability ids are understandable to both agents and plugin developers;
- product-specific features remain in Npcink AI or Toolbox.

### P3: Prepare The Ecosystem Surface

Goal: make the package useful beyond internal migration.

Deliverables:

- third-party provider guide with copy-paste examples;
- compatibility notes for WordPress Abilities API versions;
- semver policy for helper functions and built-in ability ids;
- a small admin explorer focused on diagnostics and smoke tests, not full workflow building;
- changelog sections grouped by public API, ability ids, and host integration impacts.

Success signal:

- external developers can understand what belongs here in under 10 minutes;
- ability providers can opt into Npcink AI projection intentionally;
- end-user products can explain this package as an infrastructure dependency, not as a competing UI.

## Recommended 30/60/90 Day Plan

### First 30 Days

Focus on contract stability.

- Finish public API docs for all helper functions.
- Document host approval and dry-run behavior in one canonical place.
- Add or tighten tests around write/destructive schema injection and default dry-run behavior.
- Add a provider-plugin example that registers one read ability and one projected compatibility ability.
- Keep migration inventory current after every ability move.

### Days 31-60

Focus on consumer proof.

- Wire Npcink AI to consume migrated abilities only through discovery/execution contracts.
- Add duplicate-id audit documentation and, if practical, a local script or fixture.
- Add compatibility projection test coverage.
- Define minimum recommended version policy for consuming hosts.
- Validate a WordPress smoke path against a local site.

### Days 61-90

Focus on ecosystem readiness.

- Split documentation around first-party packs by use case.
- Publish third-party provider guidance.
- Add a concise "what belongs here / what does not" matrix to onboarding docs.
- Prepare a 0.2 release checklist around contract stability, test coverage, and consumer integration.
- Identify one vertical pack candidate for later extraction, but do not start it until P0/P1 are stable.

## Defer

Do not prioritize these in this repository in the next stage:

- full AI assistant UI;
- model routing or provider connection UX;
- MCP server governance;
- billing, quota, or app-key systems;
- complete China-market toolbox modules;
- page caching, security firewall, backup suite, WeChat payment, or CDN configuration UI;
- workflow builder;
- analytics dashboards beyond local smoke and diagnostics.

These are valid product opportunities, but they belong above this package.
