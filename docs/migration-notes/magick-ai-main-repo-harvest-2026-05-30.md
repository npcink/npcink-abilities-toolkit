# Npcink AI Main Repo Harvest Checkpoint

Status: active checkpoint for `0.5.0` freeze/observe.

Date: 2026-05-30.

Source: `/Users/muze/gitee/magick-ai-root/magick-ai` content, media, comment,
batch, settings, provider, and Agent Gateway cleanup candidates.

This checkpoint records what `npcink-abilities-toolkit` should absorb from the main
repo and what it should reject. It is intentionally a contract note, not a new
ability batch.

## Decision

Do not add a new ability from the main repo cleanup list until a consuming host
reports a concrete schema, metadata, or ability contract gap.

The main repo is useful as a source of WordPress-only task intent and handoff
shapes. It is not a source for runtime ownership. This package should absorb
only stable Abilities API contracts, deterministic WordPress callbacks, dry-run
preview shapes, and documentation-only recipes.

## Current Coverage

| Main repo signal | Current abilities coverage | Result |
| --- | --- | --- |
| Draft creation governance | `npcink-abilities-toolkit/create-draft` | Covered. Core has already proven proposal, approval, and preflight intake without final write execution. |
| SEO metadata governance | `npcink-abilities-toolkit/resolve-post-metadata-plan`, `npcink-abilities-toolkit/set-post-seo-meta` | Covered. Core has already proven field-level proposal intake and preflight context. |
| Comment approval governance | `npcink-abilities-toolkit/get-comment-compliance-handoff`, `npcink-abilities-toolkit/build-comment-moderation-suggest`, `npcink-abilities-toolkit/approve-comment` | Covered. Core has already proven comment proposal intake without changing comment status during preflight. |
| Taxonomy assignment preview | `npcink-abilities-toolkit/propose-post-taxonomy-terms`, `npcink-abilities-toolkit/set-post-terms` | Covered. Core consumer proof found no additional ability contract gap. |
| Publish preflight | `npcink-abilities-toolkit/get-article-publish-preflight-context`, `npcink-abilities-toolkit/get-content-publishing-checklist`, `npcink-abilities-toolkit/get-post-publish-risk-report`, `npcink-abilities-toolkit/get-publishing-calendar-context` | Covered as read-only context and workflow recipe composition. |
| Media inventory and media SEO handoff | `npcink-abilities-toolkit/list-media`, `npcink-abilities-toolkit/get-media-inventory-health`, `npcink-abilities-toolkit/get-media-cleanup-opportunities`, `npcink-abilities-toolkit/build-media-seo-assets`, `npcink-abilities-toolkit/optimize-media-metadata`, `npcink-abilities-toolkit/update-media-details` | Covered on read/proposal/dry-run surfaces. Host still owns model calls, batch selection, scheduling, and approved write execution. |
| Page structure inspection | `npcink-abilities-toolkit/inspect-page-structure`, `npcink-abilities-toolkit/get-page-structure-health` | Covered as read-only WordPress context. |
| Site diagnostics | `npcink-abilities-toolkit/wp-diagnostics-summary`, `npcink-abilities-toolkit/wp-ops-diagnostics-detail`, `npcink-abilities-toolkit/site-info`, `npcink-abilities-toolkit/get-site-operations-dashboard` | Covered at the redacted WordPress-only level with bounded operations detail. Npcink AI/MCP/runtime diagnostics still stay in the host. |

## Absorb As Documentation Only

Keep the following main repo surfaces as recipe or host workflow inspiration,
not as package runtime code:

- content tag completion;
- content summary and SEO completion;
- content pre-publish report;
- article single optimization, optimization report, and optimization apply;
- article draft, article media, and article production workflows;
- media alt single suggest, media alt completion, media SEO enrichment, and
  nightly media optimization;
- comment moderation, comment batch suggestion, and mention reply suggestion;
- taxonomy quality governance;
- page structure remediation;
- local analytics, usage trends, and router performance trends.

For each surface, preserve only:

- natural-language goal;
- required existing ability ids;
- bounded proposal or dry-run handoff shape;
- target write/destructive ability id when one exists;
- fail-closed conditions for missing schema, missing ability, or missing host
  approval context.

## Do Not Absorb

Do not move these main repo concepts into `npcink-abilities-toolkit`:

- Agent Gateway catalogs, task templates, tool projection, or app auth;
- MCP runtime, MCP governance, server routing, or approval/audit correlation;
- workflow runtime, queues, schedulers, retries, leases, batch consoles, or run
  stores;
- model routing, prompt/preset selection, provider connectors, cloud offload,
  billing, quota, or operator surfaces;
- Settings defaults for article/comment automation;
- final write approval, final commit execution policy, or audit persistence;
- generic AI tools that are not WordPress governance tasks.

## Next Ability Gate

A future ability batch may start only when all of these are true:

1. A consuming host first tries the existing abilities through the WordPress
   Abilities API.
2. The host proof fails because a specific schema, metadata, callback, or
   proposal handoff is missing.
3. The missing piece is WordPress-only, deterministic, and reusable outside one
   product workflow.
4. The new ability can stay read-only or proposal/dry-run-only.
5. Final write/destructive execution remains host-governed.

Candidate names alone are not enough. The acceptance evidence must describe the
failed host workflow and why existing abilities cannot compose the same result.
