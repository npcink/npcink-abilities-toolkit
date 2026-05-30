# Magick AI Core Ability Candidates

Status: reference notes for future rewrites.

This document records high-value WordPress ability candidates found while
shrinking Magick AI Core back to a governance layer. It is intentionally not an
implementation plan and does not move Magick AI runtime code into this package.

Latest harvest checkpoint:
`docs/migration-notes/magick-ai-main-repo-harvest-2026-05-30.md`.

As of the 2026-05-30 checkpoint, the main content/media/comment/batch cleanup
signals do not open a new `magick-ai-abilities` implementation batch. Current
coverage already includes the reusable WordPress-only read, proposal, dry-run,
and host-governed write surfaces needed by the validated Core handoff scenarios.
Future candidates must start from a failed host workflow proof, not from the
existence of a product workflow in the main repo.

## Boundary

`magick-ai-abilities` may own reusable WordPress Abilities API definitions,
schemas, callbacks, dry-run previews, and documentation-only recipes.

It must not own:

- Magick AI Open Platform routes, app keys, scopes, quotas, audit, or logs;
- Agent Gateway catalogs, task templates, Settings UI, or MCP runtime;
- model routing, prompt/preset selection, provider calls, cloud offload, or
  article-product UX;
- workflow queues, scheduling, retries, leases, approval records, or final
  commit authorization.

When a candidate requires a model call or product workflow, keep the reusable
WordPress context or deterministic helper here and let a host such as Magick AI
or Content Assistant own the model and write governance.

## Strong Ability Candidates

These are reusable across host products and fit the Abilities API boundary.
Several are already present in this package; the note captures why they should
remain here when Magick AI Core drops the corresponding product surface.

| Area | Ability ids or future ids | Why it belongs here | Host responsibility |
| --- | --- | --- | --- |
| Content context | `magick-ai/get-post-context`, `magick-ai/list-posts`, `magick-ai/get-post`, `magick-ai/get-post-blocks`, `magick-ai/get-post-meta` | Generic WordPress reads with stable schemas. | Choose task, model, prompt, and approval surface. |
| Publishing readiness | `magick-ai/get-content-publishing-checklist`, `magick-ai/get-post-publish-risk-report`, `magick-ai/get-article-publish-preflight-context`, `magick-ai/get-publishing-calendar-context` | Read-only publishing context is reusable beyond Magick AI. | Decide whether to schedule, publish, or request edits. |
| Content refresh | `magick-ai/get-content-refresh-opportunities`, `magick-ai/get-old-article-refresh-context`, `magick-ai/get-revision-change-risk-report` | Discovery and risk signals are WordPress context, not Magick AI governance. | Pick candidates and run model-assisted rewrite flows. |
| SEO/GEO context | `magick-ai/get-post-seo-geo-readiness`, `magick-ai/get-seo-geo-gap-report`, `magick-ai/get-site-topic-coverage-report`, `magick-ai/get-internal-link-opportunity-report`, `magick-ai/get-internal-link-graph-health` | Deterministic context and gap reports are useful to any content host. | Generate final copy and approve writes. |
| Metadata planning | `magick-ai/resolve-post-metadata-plan`, `magick-ai/propose-post-taxonomy-terms` | WordPress taxonomy and metadata plans can be schema-first proposal helpers. | Validate editorial policy and commit taxonomy/meta writes. |
| Excerpt and summary handoff | `magick-ai/propose-post-excerpt`, future deterministic summary handoff helpers | Proposal-only helpers can provide bounded editorial handoff without owning generation runtime. | Run model calls when needed and commit with `update-post` or `patch-post-content`. |
| Media inventory | `magick-ai/list-media`, `magick-ai/get-media-inventory-health`, `magick-ai/get-media-cleanup-opportunities` | Reusable media reads and cleanup signals. | Select remediation workflow and approve media writes/deletes. |
| Media SEO assets | `magick-ai/build-media-seo-assets`, `magick-ai/optimize-media-metadata`, `magick-ai/build-inline-image-blocks`, `magick-ai/position-inline-image-blocks` | Deterministic media handoff and metadata proposal helpers are portable. | Source images, call models, and approve media metadata writes. |
| Comments | `magick-ai/list-comments`, `magick-ai/get-comment-queue-health`, `magick-ai/get-comment-action-priority-queue`, `magick-ai/get-comment-compliance-handoff`, `magick-ai/build-comment-moderation-suggest`, `magick-ai/build-comment-mention-reply-suggest` | Comment queue reads and deterministic suggestions are reusable compliance helpers. | Approve, reply, spam, trash, and log final actions. |
| Page structure | `magick-ai/inspect-page-structure`, `magick-ai/get-page-structure-health` | Read-only page structure inspection can be consumed by agents without Magick AI. | Decide remediation and run theme/page write flows elsewhere. |
| Diagnostics | `magick-ai-abilities/wp-diagnostics-summary`, `magick-ai/site-info`, `magick-ai/get-site-operations-dashboard` | Redacted WordPress-only diagnostics are useful outside Magick AI. | Any Magick AI/MCP/runtime diagnosis stays in the host. |
| Host-governed publishing writes | `magick-ai/create-draft`, `magick-ai/update-post`, `magick-ai/patch-post-content`, `magick-ai/update-post-blocks`, `magick-ai/set-post-seo-meta`, `magick-ai/set-post-terms`, `magick-ai/schedule-post`, `magick-ai/publish-post` | Generic WordPress writes can expose dry-run previews and commit callbacks. | Provide caller identity, approval, audit, idempotency, quota, and commit authorization. |
| Host-governed media/comment writes | `magick-ai/update-media-details`, `magick-ai/upload-media-from-url`, `magick-ai/set-post-featured-image`, `magick-ai/approve-comment`, `magick-ai/reply-comment`, `magick-ai/spam-comment`, `magick-ai/trash-comment` | The mutation is generic WordPress behavior. | Govern all final write/destructive commits. |

## Keep As Documentation Only

The following Magick AI Core workflow surfaces contain useful sequencing ideas
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

These Magick AI Core concepts are deliberately out of scope:

- `workflow/*` runtime registration, workflow-as-ability execution, and skill
  manifest projection;
- direct `confirm_token`, `write_confirmed`, or old confirmation compatibility;
- Agent Gateway task templates, catalog rows, or MCP server routing;
- cloud/billing/operator/batch consoles;
- local Settings surfaces and observability dashboards;
- model/provider bridge execution such as `magick-ai/generate-excerpt`,
  `magick-ai/seo-meta-generate`, `magick-ai/generate-alt`,
  `magick-ai/seo-analyze`, `magick-ai/resolve-image-source`, or
  `magick-ai/resolve-references`.

If a future package wants to own semantic/model abilities, write a new ADR for a
separate semantic package. Do not blend it into this WordPress abilities package.
