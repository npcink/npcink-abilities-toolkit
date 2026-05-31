# Workflow Recipes

Status: active reference guidance.

This document publishes recommended ways to compose first-party abilities into
useful host workflows. It is a recipe catalog, not a runtime owned by this
package.

The first three stabilization recipes also have a machine-readable consumer
replay fixture at `tests/fixtures/agent-workflow-replay.json`. Host-side tests
can use that fixture to check natural-task routing, preferred bundled ability
selection, and write-boundary behavior without depending on this package for
runtime orchestration.

The machine-readable field contract is documented in
[Workflow Definition Contract](workflow-definition-contract.md).
Hosts that need runtime discovery can use
`magick_ai_abilities_get_workflow_definitions()` or the read-only
`magick-ai-abilities/list-workflow-recipes` ability.

## Core Principle For AI Agents

Workflow definitions in this package may tell a host what ability chain is
recommended, what data should be handed off, and where the write boundary is.
They must not decide, schedule, approve, retry, audit, route models, select
prompts, or commit final WordPress writes.

When an AI agent extends this area, keep the definition read-only and
declarative. The host runtime owns execution state, policy, approval, audit,
quota, model or prompt selection, and final mutation. If a proposed workflow
field would require this package to remember runtime state or enforce host
policy, that field belongs in the consuming host, not in `magick-ai-abilities`.

## Boundary

`magick-ai-abilities` may publish workflow recipes when they remain
documentation-level consumption guidance.

Allowed here:

- stable recipe names for humans and host implementers;
- ability sequences built from registered WordPress Abilities API ids;
- handoff expectations between ability outputs and later host steps;
- risk, dry-run, approval, and failure-handling notes;
- smoke-test targets for validating that ability chains are consumable.

Not allowed here:

- workflow execution state;
- workflow scheduling, retries, queues, or leases;
- model routing, prompt/preset ownership, or semantic runtime selection;
- MCP, Open API, Agent Gateway, quota, audit, or approval governance;
- final WordPress write approval;
- a second ability, workflow, skill, MCP, or projection registry.

Recipe ids such as `workflow/wordpress_article_publish_preflight` are
documentation identifiers only. They are not WordPress ability ids and are not a
registry that hosts must synchronize.

## Recipe Rules

Hosts should implement recipes with these rules:

1. Discover every ability through `/wp-abilities/v1/abilities` or
   `wp_get_ability()` before executing the chain.
2. Treat read-only and suggestion abilities as context or proposal steps.
3. Treat write and destructive abilities as host-governed steps. Direct clients
   should receive dry-run previews unless an approved host context authorizes a
   commit.
4. Keep model calls, prompt selection, user approval, audit, quota, and final
   write decisions in the host runtime.
5. Fail closed when a required ability, permission, scope, approval, or host
   context is missing.
6. Prefer composing existing abilities before adding another ability. Add a new
   ability only when the gap is stable, reusable, and bounded by the ability
   contract.

## Common Handoff Shape

Recipes should pass structured data between steps instead of relying on hidden
runtime state:

- `context`: WordPress content, taxonomy, media, comment, or environment data.
- `suggestion`: deterministic proposed action or review notes.
- `preview`: dry-run mutation summary from a write/destructive ability.
- `approval_request`: host-owned object describing what needs human or policy
  approval.
- `commit_result`: host-governed write/destructive result after approval.

The package owns ability output schemas and dry-run preview behavior. The host
owns persisted workflow state and any approval or commit record.

## Recipe: Site Operations Scan

Recipe id: `workflow/wordpress_site_operations_scan`

Goal: identify site content, media, taxonomy, and page-structure attention
areas without writes.

Ability sequence:

1. `magick-ai/site-info`
2. `magick-ai/get-site-operations-dashboard`
3. `magick-ai/get-content-inventory-health`
4. `magick-ai/get-media-inventory-health`
5. `magick-ai/get-taxonomy-inventory-health`
6. `magick-ai/get-page-structure-health`

Handoff:

- pass inventory summaries and issue counts to the host;
- let the host decide whether to open a follow-up media, taxonomy, article, or
  page workflow.

Governance:

- read-only;
- no final commit approval is required;
- remediation steps must enter a separate host-governed recipe.

## Recipe: Article Draft Handoff

Recipe id: `workflow/wordpress_article_draft`

Goal: compose draft content, metadata context, and SEO/GEO review signals
without creating or publishing a final article unless a host later approves a
write chain.

Ability sequence:

1. `magick-ai/resolve-post-metadata-plan`
2. `magick-ai/resolve-internal-link-targets`
3. `magick-ai/build-inline-image-blocks`
4. `magick-ai/build-media-seo-assets`
5. `magick-ai/review-article-output-light`
6. `magick-ai/compose-article-draft-result`

Handoff:

- pass `draft_content`, resolved metadata, internal-link targets, media SEO
  assets, and review findings to the host;
- if the host wants a real WordPress draft, continue with
  `magick-ai/create-draft` in dry-run mode first.

Governance:

- recipe context and review steps are read/proposal steps;
- creating a WordPress draft is a host-governed write step.

## Recipe: Article Publish Preflight

Recipe id: `workflow/wordpress_article_publish_preflight`

Goal: decide whether an existing draft is ready for host-approved scheduling or
publication.

Ability sequence:

1. Preferred bundle: `magick-ai/get-article-publish-preflight-context`
2. Expanded sequence when the host needs individual calls:
   `magick-ai/get-post-context`,
   `magick-ai/get-content-publishing-checklist`,
   `magick-ai/get-post-publish-risk-report`,
   `magick-ai/build-article-workflow-context` with `workflow=publish`, and
   `magick-ai/get-publishing-calendar-context`
3. Optional dry-run: `magick-ai/schedule-post` or `magick-ai/publish-post`

Handoff:

- pass post context, checklist status, risk report, workflow context, and
  calendar pressure to the host;
- pass any write preview to the host approval surface before commit.

Governance:

- preflight is read-only;
- schedule or publish commits require host approval context.

## Recipe: Article Production Mainline

Recipe id: `workflow/wordpress_article_production`

Goal: carry an article candidate through duplicate checks, lightweight review,
media handoff, and publication decision without bypassing host approval.

Ability sequence:

1. `magick-ai/extract-style-baseline`
2. `magick-ai/build-article-production-fingerprint`
3. `magick-ai/check-article-production-duplicate`
4. `magick-ai/review-article-output-light`
5. `magick-ai/build-media-seo-assets`
6. `magick-ai/resolve-article-publication-decision`
7. `magick-ai/compose-article-production-result`

Handoff:

- pass duplicate guard, review findings, media assets, publication decision, and
  next action to the host;
- if mutation is needed, branch into Publishing Pack write abilities with
  dry-run previews.

Governance:

- this recipe does not own model output generation or publication policy;
- final draft creation, patching, scheduling, or publishing remains
  host-governed.

## Recipe: Existing Article Optimization

Recipe id: `workflow/wordpress_article_optimization`

Goal: read an existing post, build an optimization suggestion, and prepare a
reviewable apply plan.

Ability sequence:

1. `magick-ai/read-post-optimization-context`
2. `magick-ai/seo-report-context`
3. `magick-ai/build-article-single-optimization-suggest`
4. `magick-ai/build-article-optimization-apply-plan`
5. `magick-ai/compose-article-optimization-apply-result`
6. Optional dry-run: `magick-ai/patch-post-content`,
   `magick-ai/set-post-seo-meta`, or `magick-ai/update-post-blocks`

Handoff:

- pass optimization context, suggested changes, apply plan, and write previews
  to the host;
- use host approval to decide which patch operations may commit.

Governance:

- suggestions and apply plans are read/proposal outputs;
- WordPress mutations are host-governed writes.

## Recipe: Old Article Refresh Discovery

Recipe id: `workflow/wordpress_old_article_refresh_discovery`

Goal: identify articles that deserve refresh work and provide enough context for
an agent or host product to choose candidates.

Ability sequence:

1. Preferred bundle: `magick-ai/get-old-article-refresh-context`
2. Expanded sequence when the host needs individual calls:
   `magick-ai/get-content-refresh-opportunities`,
   `magick-ai/get-seo-geo-gap-report`,
   `magick-ai/get-site-style-baseline`,
   `magick-ai/get-internal-link-graph-health`, and
   `magick-ai/get-internal-link-opportunity-report` for a selected post

Handoff:

- pass candidate posts, gap signals, style baseline, and link opportunities to
  the host;
- continue into `workflow/wordpress_article_optimization` for selected posts.

Governance:

- read-only discovery;
- any post update remains a separate host-governed write chain.

## Recipe: Comment Compliance Handoff

Recipe id: `workflow/wordpress_comment_compliance_handoff`

Goal: triage comments, prepare moderation or reply suggestions, and keep final
comment actions under host approval.

Ability sequence:

1. Preferred bundle: `magick-ai/get-comment-compliance-handoff`
2. Expanded sequence when the host needs individual calls:
   `magick-ai/get-comment-queue-health`,
   `magick-ai/get-comment-action-priority-queue`,
   `magick-ai/build-comment-moderation-suggest`, and
   `magick-ai/build-comment-mention-reply-suggest` when reply handling is
   needed
3. `magick-ai/compose-comment-moderation-result`
4. Optional dry-run: `magick-ai/approve-comment`, `magick-ai/reply-comment`,
   `magick-ai/spam-comment`, or `magick-ai/trash-comment`

Handoff:

- pass queue health, priority rows, suggested actions, and composed moderation
  result to the host;
- pass write/destructive previews to host approval before commit.

Governance:

- suggestion and composition steps are read/proposal steps;
- approve, reply, spam, and trash actions remain host-governed.

## Recipe: Diagnostics Triage

Recipe id: `workflow/wordpress_diagnostics_triage`

Goal: gather redacted WordPress-only diagnostics for support or agent triage.

Ability sequence:

1. `magick-ai-abilities/wp-diagnostics-summary`
2. `magick-ai-abilities/wp-ops-diagnostics-detail`
3. `magick-ai/site-info`

Handoff:

- pass redacted runtime, theme, plugin, caller capability, PHP extension, object
  cache, rewrite, HTTPS, server, REST, Abilities API, cron, update, database
  estimate, log-availability, content type, role, widget, block-theme, search,
  SEO, security, and performance summaries to the host or support flow.

Governance:

- read-only;
- diagnostics must not expose Magick AI settings, MCP settings, API keys,
  database names, table prefixes, table names, filesystem paths, unredacted
  error log contents, cron argument values, or external HTTP probes.

## Validation

The first validation targets are documented in
[Agent Workflow Validation](agent-workflow-validation.md). Smoke coverage should
prove recipes through WordPress Abilities API discovery and execution, not by
requiring package internals.
