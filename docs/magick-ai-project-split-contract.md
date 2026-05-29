# Magick AI Project Split Contract

Status: active
Version: magick-ai-project-split-v1

This document defines how `magick-ai-abilities` is developed after it is split from the Magick AI plugin.

## Project Role

`magick-ai-abilities` is an independent WordPress Abilities API package plugin. It owns reusable WordPress ability definitions that can be consumed by Magick AI, other plugins, or direct Abilities API clients.

Magick AI is an optional consumer. This plugin must remain installable, testable, and useful without Magick AI.

## Boundary Summary For Agents

Use this project as a generic WordPress Abilities API package layer, not as a
Magick AI admin/runtime submodule.

`magick-ai-abilities` answers:

- what reusable WordPress abilities can be registered;
- what schemas, annotations, and categories those abilities expose;
- what WordPress-only read or host-governed dry-run/write callbacks do;
- how opted-in abilities may expose lightweight compatibility metadata.

Magick AI answers:

- which abilities appear in the Magick AI product catalog;
- which channels, scopes, quotas, audits, and approvals apply;
- how Agent Gateway, Open API, MCP, workflow runtime, model routing, and final
  WordPress write governance operate.

If code needs Magick AI runtime state, settings-shell UI state, MCP/Open API
exposure state, quota/audit state, workflow orchestration, model routing, or
final approval context, it does not belong in this project.

## Owned Here

- WordPress Abilities API categories and ability definitions.
- Input and output schemas for reusable WordPress abilities.
- WordPress-only read callbacks.
- Host-governed WordPress write and destructive callbacks.
- Dry-run previews for write and destructive abilities.
- Compatibility projection into Magick AI only through optional filters.
- Local admin test page and smoke tests for this plugin.

## Not Owned Here

- Magick AI model routing, provider selection, vector runtime, or semantic runtime.
- Agent Gateway, Open API, MCP governance, quota, audit, approval storage, or app-key authentication.
- Magick AI workflow orchestration and operations dashboards.
- Final commit authorization for host-governed writes.
- Magick AI site diagnostics that include runtime, MCP, filesystem, database, REST probe, or operations state.
- Magick AI settings pages, settings-shell JavaScript, or admin REST endpoints such as `/wp-json/magick-ai/v1/admin/settings/capabilities`.
- The Magick AI Capability Library / catalog page at `plugins.php?page=magick-ai-settings&tab=catalog`.
- Magick AI product catalog row shaping, summary-snapshot caching, channel exposure display, or settings performance gates.

## Integration Protocol

Magick AI or another host should consume this plugin through WordPress Abilities API discovery and execution:

- discover abilities through WordPress Abilities API or `wp_get_ability()`;
- call the registered WordPress ability id instead of requiring internal package files;
- keep admission, caller identity, quota, audit, approval, and commit authorization in the host runtime;
- install this plugin when migrated generic WordPress ability ids are required.

The optional Magick AI compatibility bridge may project opted-in abilities into the Magick AI catalog. Projection is metadata only. It must not introduce a second runtime, second approval system, or direct dependency on Magick AI internals.

Compatibility projection must stay thin:

- allowed: optional filters that expose stable ability metadata to a host;
- allowed: explicit `project_to_magick_catalog` metadata for opted-in abilities;
- allowed: `executor_type=wp_ability`, the WordPress ability id, schemas,
  annotations, risk level, confirmation requirement, and lightweight
  compatibility metadata;
- forbidden: Magick AI settings UI, admin REST handlers, product catalog caching,
  MCP/Open API/Agent Gateway governance, quota/audit, or workflow execution.
- forbidden: projection fields that make this package the owner of Magick AI
  routing policy, backend priority, Open API exposure, catalog fallback, or tool
  policy.

The Magick AI settings Capability Library may consume this project through
WordPress Abilities API discovery or optional projection. The page itself and
its `/admin/settings/capabilities` endpoint stay in Magick AI because they are
consumer/product governance surfaces, not generic Abilities API package code.

## Built-In Package Gating

The built-in packages are enabled by default to preserve compatibility with the
current migrated ability set. Hosts can narrow the registration surface when
they want a lighter, generic WordPress Abilities API package:

- `magick_ai_abilities_enabled_packages` gates top-level packages such as
  `core_read`, `core_write`, `core_destructive`, `core_comment`,
  `magick_catalog_bridge`, `admin_test_page`, and `read_cache_hooks`.
- `magick_ai_abilities_enabled_read_packs` gates read sub-packs such as
  `core_wordpress_read`, `wordpress_diagnostics`, `article_workflow_context`,
  `content_operations`, `media_governance`, `taxonomy_governance`,
  `page_governance`, `seo_geo_support`, and `comment_workflow_context`.
- `magick_ai_abilities_enabled_comment_packs` gates standalone comment helper
  sub-packs such as `comment_queue_context` and `comment_handoff_context`.

Package gating is the preferred way to keep this project from becoming too
large in a host installation. It is not a reason to move Magick AI catalog UI,
workflow runtime, or final approval state into this repository.

The read and comment sub-pack classifiers are implementation details that make
the gating contract explicit. Future file splits should move definitions by
sub-pack only when tests prove no ability id, schema, callback, risk, or default
registration behavior changed.

## Legacy ID Policy

Many migrated first-party abilities still use `magick-ai/*` ids for backward
compatibility with existing host references. Those ids are stable compatibility
ids, not proof that Magick AI owns the callback implementation.

New generic provider examples should use their own namespace. New first-party
abilities in this package may keep the legacy namespace only when they extend a
migrated compatibility surface already consumed by Magick AI. A future rename
from `magick-ai/*` to a standalone namespace must be handled as a deprecation
and successor migration, not a silent replacement.

## Workflow Recipe Rule

This project may document recommended workflow recipes that compose registered
WordPress abilities. Recipes are allowed only as reference guidance for hosts,
agents, MCP clients, and tests.

Workflow recipes in this project may define:

- documentation-only recipe ids such as `workflow/wordpress_article_draft`;
- ability sequences and handoff expectations;
- dry-run, approval, risk, and failure-handling guidance;
- smoke-test targets for proving ability chains through WordPress Abilities API.

Workflow recipes in this project must not define:

- workflow runtime state, scheduling, retries, queues, or leases;
- model routing, prompt/preset ownership, quota, audit, or approval truth;
- MCP, Open API, Agent Gateway, Cloud, or final WordPress write governance;
- a second ability, workflow, skill, MCP, or projection registry.

The canonical recipe guidance lives in
[workflow-recipes.md](workflow-recipes.md). Host runtimes may implement those
recipes, but this package remains the ability contract owner, not the workflow
engine.

## Write And Destructive Rule

Write and destructive abilities may live here only when they are generic WordPress operations.

Direct clients receive dry-run previews by default. A real commit requires host approval context. For Magick AI, that context comes from the Magick AI plugin. For other hosts, the host must provide an equivalent authorization envelope.

## Duplicate Registration Rule

When an ability id has moved to `magick-ai-abilities`, Magick AI must not keep a duplicate local config row or duplicate callback implementation for that id.

During local cross-repo development, Magick AI should run its duplicate-id audit against this project. This plugin's own CI must not require the Magick AI repository to be present.

## Dependency Rule

Production package code in this plugin must not require files from the Magick AI repository and must not call Magick AI runtime execution functions.

Allowed integration is limited to optional WordPress hooks and filters, for example the catalog projection filter implemented by the compatibility bridge.

## Version Compatibility

- `magick-ai-abilities` keeps SemVer-style public API discipline for registration helpers and built-in ability ids.
- Magick AI should document the minimum recommended `magick-ai-abilities` version in its own integration contract.
- If a future release needs Magick AI fallback definitions, that must be recorded in a new ADR before fallback code is reintroduced.

## Required Checks

For this project:

- `composer test:all`
- `composer smoke:wp` in a WordPress site where the plugin is installed
- `composer check:boundary`

For Magick AI:

- main PHP contract tests
- duplicate ability id audit
- Agent Gateway projection tests
- externalized ability contract tests
