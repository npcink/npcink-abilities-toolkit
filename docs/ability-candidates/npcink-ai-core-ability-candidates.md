# Npcink AI Core Ability Candidates

Status: reference notes for future rewrites.

This document records high-value WordPress ability candidates found while
shrinking Npcink AI Core back to a governance layer. It is intentionally not an
implementation plan and does not move Npcink AI runtime code into this package.

Latest harvest checkpoint:
`docs/migration-notes/magick-ai-main-repo-harvest-2026-05-30.md`.

As of the 2026-05-30 checkpoint, the main content/media/comment/batch cleanup
signals do not open a new `npcink-abilities-toolkit` implementation batch. Current
coverage already includes the reusable WordPress-only read, proposal, dry-run,
and host-governed write surfaces needed by the validated Core handoff scenarios.
Future candidates must start from a failed host workflow proof, not from the
existence of a product workflow in the main repo.

## Boundary

`npcink-abilities-toolkit` may own reusable WordPress Abilities API definitions,
schemas, callbacks, dry-run previews, and documentation-only recipes.

It must not own:

- Npcink AI Open Platform routes, app keys, scopes, quotas, audit, or logs;
- Agent Gateway catalogs, task templates, Settings UI, or MCP runtime;
- model routing, prompt/preset selection, provider calls, cloud offload, or
  article-product UX;
- workflow queues, scheduling, retries, leases, approval records, or final
  commit authorization.

When a candidate requires a model call or product workflow, keep the reusable
WordPress context or deterministic helper here and let a host such as Npcink AI
or Content Assistant own the model and write governance.

## Strong Ability Candidates

These are reusable across host products and fit the Abilities API boundary.
Several are already present in this package; the note captures why they should
remain here when Npcink AI Core drops the corresponding product surface.

| Area | Ability ids or future ids | Why it belongs here | Host responsibility |
| --- | --- | --- | --- |
| Content context | `npcink-abilities-toolkit/get-post-context`, `npcink-abilities-toolkit/list-posts`, `npcink-abilities-toolkit/get-post`, `npcink-abilities-toolkit/get-post-blocks`, `npcink-abilities-toolkit/get-post-meta` | Generic WordPress reads with stable schemas. | Choose task, model, prompt, and approval surface. |
| Publishing readiness | `npcink-abilities-toolkit/get-content-publishing-checklist`, `npcink-abilities-toolkit/get-post-publish-risk-report`, `npcink-abilities-toolkit/get-article-publish-preflight-context`, `npcink-abilities-toolkit/get-publishing-calendar-context` | Read-only publishing context is reusable beyond Npcink AI. | Decide whether to schedule, publish, or request edits. |
| Content refresh | `npcink-abilities-toolkit/get-content-refresh-opportunities`, `npcink-abilities-toolkit/get-old-article-refresh-context`, `npcink-abilities-toolkit/get-revision-change-risk-report` | Discovery and risk signals are WordPress context, not Npcink AI governance. | Pick candidates and run model-assisted rewrite flows. |
| SEO/GEO context | `npcink-abilities-toolkit/get-post-seo-geo-readiness`, `npcink-abilities-toolkit/get-seo-geo-gap-report`, `npcink-abilities-toolkit/get-site-topic-coverage-report`, `npcink-abilities-toolkit/get-internal-link-opportunity-report`, `npcink-abilities-toolkit/get-internal-link-graph-health` | Deterministic context and gap reports are useful to any content host. | Generate final copy and approve writes. |
| Metadata planning | `npcink-abilities-toolkit/resolve-post-metadata-plan`, `npcink-abilities-toolkit/propose-post-taxonomy-terms` | WordPress taxonomy and metadata plans can be schema-first proposal helpers. | Validate editorial policy and commit taxonomy/meta writes. |
| Excerpt and summary handoff | `npcink-abilities-toolkit/propose-post-excerpt`, future deterministic summary handoff helpers | Proposal-only helpers can provide bounded editorial handoff without owning generation runtime. | Run model calls when needed and commit with `update-post` or `patch-post-content`. |
| Media inventory | `npcink-abilities-toolkit/list-media`, `npcink-abilities-toolkit/get-media-inventory-health`, `npcink-abilities-toolkit/get-media-cleanup-opportunities` | Reusable media reads and cleanup signals. | Select remediation workflow and approve media writes/deletes. |
| Media SEO assets | `npcink-abilities-toolkit/build-media-seo-assets`, `npcink-abilities-toolkit/optimize-media-metadata`, `npcink-abilities-toolkit/build-inline-image-blocks`, `npcink-abilities-toolkit/position-inline-image-blocks` | Deterministic media handoff and metadata proposal helpers are portable. | Source images, call models, and approve media metadata writes. |
| Comments | `npcink-abilities-toolkit/list-comments`, `npcink-abilities-toolkit/get-comment-queue-health`, `npcink-abilities-toolkit/get-comment-action-priority-queue`, `npcink-abilities-toolkit/get-comment-compliance-handoff`, `npcink-abilities-toolkit/build-comment-moderation-suggest`, `npcink-abilities-toolkit/build-comment-mention-reply-suggest` | Comment queue reads and deterministic suggestions are reusable compliance helpers. | Approve, reply, spam, trash, and log final actions. |
| Page structure | `npcink-abilities-toolkit/inspect-page-structure`, `npcink-abilities-toolkit/get-page-structure-health` | Read-only page structure inspection can be consumed by agents without Npcink AI. | Decide remediation and run theme/page write flows elsewhere. |
| Diagnostics | `npcink-abilities-toolkit/wp-diagnostics-summary`, `npcink-abilities-toolkit/wp-ops-diagnostics-detail`, `npcink-abilities-toolkit/site-info`, `npcink-abilities-toolkit/get-site-operations-dashboard` | Redacted WordPress-only diagnostics are useful outside Npcink AI. | Any Npcink AI/MCP/runtime diagnosis stays in the host. |
| Host-governed publishing writes | `npcink-abilities-toolkit/create-draft`, `npcink-abilities-toolkit/update-post`, `npcink-abilities-toolkit/patch-post-content`, `npcink-abilities-toolkit/update-post-blocks`, `npcink-abilities-toolkit/set-post-seo-meta`, `npcink-abilities-toolkit/set-post-terms`, `npcink-abilities-toolkit/schedule-post`, `npcink-abilities-toolkit/publish-post` | Generic WordPress writes can expose dry-run previews and commit callbacks. | Provide caller identity, approval, audit, idempotency, quota, and commit authorization. |
| Host-governed media/comment writes | `npcink-abilities-toolkit/update-media-details`, `npcink-abilities-toolkit/upload-media-from-url`, `npcink-abilities-toolkit/set-post-featured-image`, `npcink-abilities-toolkit/approve-comment`, `npcink-abilities-toolkit/reply-comment`, `npcink-abilities-toolkit/spam-comment`, `npcink-abilities-toolkit/trash-comment` | The mutation is generic WordPress behavior. | Govern all final write/destructive commits. |

## Keep As Documentation Only

The following Npcink AI Core workflow surfaces contain useful sequencing ideas
but should not be copied as runtime code:

- content tag completion;
- content summary and SEO suggestion;
- pre-publish report;
- article single optimization, optimization report, and optimization apply;
- article draft, article media, and article production;
- media alt single suggestion, alt completion, SEO enrichment, and nightly
  media optimization;
- comment moderation, batch suggestion, and mention reply suggestion;
- site operations scan, page structure inspection, old draft cleanup, and
  taxonomy quality governance.

For each one, preserve only:

- natural-language goal;
- required WordPress context abilities;
- proposal or dry-run handoff shape;
- write/destructive abilities that require host approval;
- failure cases that should fail closed.

Do not preserve old workflow ids as required runtime ids. In this package,
recipe ids remain documentation identifiers only.

## Do Not Migrate

These Npcink AI Core concepts are deliberately out of scope:

- `workflow/*` runtime registration, workflow-as-ability execution, and skill
  manifest projection;
- direct `confirm_token`, `write_confirmed`, or old confirmation compatibility;
- Agent Gateway task templates, catalog rows, or MCP server routing;
- cloud/billing/operator/batch consoles;
- local Settings surfaces and observability dashboards;
- model/provider bridge execution such as `npcink-abilities-toolkit/generate-excerpt`,
  `npcink-abilities-toolkit/seo-meta-generate`, `npcink-abilities-toolkit/generate-alt`,
  `npcink-abilities-toolkit/seo-analyze`, `npcink-abilities-toolkit/resolve-image-source`, or
  `npcink-abilities-toolkit/resolve-references`.

If a future package wants to own semantic/model abilities, write a new ADR for a
separate semantic package. Do not blend it into this WordPress abilities package.
