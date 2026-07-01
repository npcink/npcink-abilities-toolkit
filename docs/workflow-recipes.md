# Workflow Recipes

Status: active reference guidance.

This file must remain declarative only. Reject additions that introduce
workflow state, scheduling, retries, queues, leases, approval fields, audit
fields, model routing, prompt ownership, or final write authority.

This document publishes recommended ways to compose first-party abilities into
useful host workflows. It is a reference recipe list, not a runtime owned by this
package.

The first three stabilization recipes also have a machine-readable consumer
replay fixture at `tests/fixtures/agent-workflow-replay.json`. Host-side tests
can use that fixture to check natural-task routing, preferred bundled ability
selection, and write-boundary behavior without depending on this package for
runtime orchestration.

The machine-readable field contract is documented in
[Workflow Definition Contract](workflow-definition-contract.md).
Hosts that need runtime discovery can use
`npcink_abilities_toolkit_get_workflow_definitions()` or the read-only
`npcink-abilities-toolkit/list-workflow-recipes` ability.
This is contract discovery for static recipe metadata, not synchronization with
a host workflow registry and not an execution obligation.

## Core Principle For AI Agents

Workflow definitions in this package may tell a host what ability chain is
recommended, what data should be handed off, and where the write boundary is.
They must not decide, schedule, approve, retry, audit, route models, select
prompts, or commit final WordPress writes.

When an AI agent extends this area, keep the definition read-only and
declarative. The host runtime owns execution state, policy, approval, audit,
quota, model or prompt selection, and final mutation. If a proposed workflow
field would require this package to remember runtime state or enforce host
policy, that field belongs in the consuming host, not in `npcink-abilities-toolkit`.

## Boundary

`npcink-abilities-toolkit` may publish workflow recipes when they remain
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

Recipe ids such as `npcink-abilities-toolkit/recipes/article-publish-preflight` are
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

Recipe id: `npcink-abilities-toolkit/recipes/site-operations-scan`

Goal: identify site content, media, taxonomy, and page-structure attention
areas without writes.

Ability sequence:

1. `npcink-abilities-toolkit/site-info`
2. `npcink-abilities-toolkit/get-site-operations-dashboard`
3. `npcink-abilities-toolkit/get-content-inventory-health`
4. `npcink-abilities-toolkit/get-media-inventory-health`
5. `npcink-abilities-toolkit/get-taxonomy-inventory-health`
6. `npcink-abilities-toolkit/get-page-structure-health`

Handoff:

- pass inventory summaries and issue counts to the host;
- let the host decide whether to open a follow-up media, taxonomy, article, or
  page workflow.

Governance:

- read-only;
- no final commit approval is required;
- remediation steps must enter a separate host-governed recipe.

## Recipe: Article Draft Handoff

Recipe id: `npcink-abilities-toolkit/recipes/article-draft`

Goal: compose draft content, metadata context, and SEO/GEO review signals
without creating or publishing a final article unless a host later approves a
write chain.

Ability sequence:

1. `npcink-abilities-toolkit/resolve-post-metadata-plan`
2. `npcink-abilities-toolkit/resolve-internal-link-targets`
3. `npcink-abilities-toolkit/build-inline-image-blocks`
4. `npcink-abilities-toolkit/build-media-seo-assets`
5. `npcink-abilities-toolkit/review-article-output-light`
6. `npcink-abilities-toolkit/compose-article-draft-result`

Handoff:

- pass `draft_content`, resolved metadata, internal-link targets, media SEO
  assets, and review findings to the host;
- if the host wants a real WordPress draft, continue with
  `npcink-abilities-toolkit/create-draft` in dry-run mode first.

Governance:

- recipe context and review steps are read/proposal steps;
- creating a WordPress draft is a host-governed write step.

## Recipe: Article Publish Preflight

Recipe id: `npcink-abilities-toolkit/recipes/article-publish-preflight`

Goal: decide whether an existing draft is ready for host-approved scheduling or
publication.

Ability sequence:

1. Preferred bundle: `npcink-abilities-toolkit/get-article-publish-preflight-context`
2. Expanded sequence when the host needs individual calls:
   `npcink-abilities-toolkit/get-post-context`,
   `npcink-abilities-toolkit/get-content-publishing-checklist`,
   `npcink-abilities-toolkit/get-post-publish-risk-report`,
   `npcink-abilities-toolkit/build-article-workflow-context` with `workflow=publish`, and
   `npcink-abilities-toolkit/get-publishing-calendar-context`
3. Optional dry-run: `npcink-abilities-toolkit/schedule-post` or `npcink-abilities-toolkit/publish-post`

Handoff:

- pass post context, checklist status, risk report, workflow context, and
  calendar pressure to the host;
- pass any write preview to the host approval surface before commit.

Governance:

- preflight is read-only;
- schedule or publish commits require host approval context.

## Recipe: Article Production Mainline

Recipe id: `npcink-abilities-toolkit/recipes/article-production`

Goal: carry an article candidate through duplicate checks, lightweight review,
media handoff, and publication decision without bypassing host approval.

Ability sequence:

1. `npcink-abilities-toolkit/extract-style-baseline`
2. `npcink-abilities-toolkit/build-article-production-fingerprint`
3. `npcink-abilities-toolkit/check-article-production-duplicate`
4. `npcink-abilities-toolkit/review-article-output-light`
5. `npcink-abilities-toolkit/build-media-seo-assets`
6. `npcink-abilities-toolkit/resolve-article-publication-decision`
7. `npcink-abilities-toolkit/compose-article-production-result`

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

Recipe id: `npcink-abilities-toolkit/recipes/article-optimization`

Goal: read an existing post, build an optimization suggestion, and prepare a
reviewable apply plan.

Ability sequence:

1. Preferred entrypoint: `npcink-abilities-toolkit/read-post-optimization-context`
2. Expanded sequence when the host needs individual calls:
   `npcink-abilities-toolkit/read-post-optimization-context`,
   `npcink-abilities-toolkit/seo-report-context`,
   `npcink-abilities-toolkit/build-article-single-optimization-suggest`,
   `npcink-abilities-toolkit/build-article-optimization-apply-plan`, and
   `npcink-abilities-toolkit/compose-article-optimization-apply-result`
3. Optional dry-run: `npcink-abilities-toolkit/patch-post-content`,
   `npcink-abilities-toolkit/set-post-seo-meta`, or `npcink-abilities-toolkit/update-post-blocks`

Handoff:

- pass optimization context, suggested changes, apply plan, and write previews
  to the host;
- use host approval to decide which patch operations may commit.

Governance:

- suggestions and apply plans are read/proposal outputs;
- WordPress mutations are host-governed writes.

## Recipe: Article Media Handoff

Recipe id: `npcink-abilities-toolkit/recipes/article-media-handoff`

Goal: prepare article media assets, inline image blocks, and placement guidance
before a host imports media or applies metadata writes.

Ability sequence:

1. Preferred entrypoint: `npcink-abilities-toolkit/build-media-seo-assets`
2. Expanded sequence when the host needs individual calls:
   `npcink-abilities-toolkit/get-post-context`,
   `npcink-abilities-toolkit/build-inline-image-blocks`,
   `npcink-abilities-toolkit/build-media-seo-assets`, and
   `npcink-abilities-toolkit/position-inline-image-blocks`
3. Optional dry-run: `npcink-abilities-toolkit/upload-media-from-url`,
   `npcink-abilities-toolkit/update-media-details`, or
   `npcink-abilities-toolkit/set-post-featured-image`

Handoff:

- pass post context, media SEO assets, inline blocks, placement output, and any
  write previews to the host;
- let the host own source-image policy, copyright/license policy, upload
  approval, and metadata commit decisions.

Governance:

- media asset and block steps are read/proposal outputs;
- uploads, attachment metadata updates, and featured-image writes remain
  host-governed.

## Recipe: Old Article Refresh Discovery

Recipe id: `npcink-abilities-toolkit/recipes/old-article-refresh-discovery`

Goal: identify articles that deserve refresh work and provide enough context for
an agent or host product to choose candidates.

Ability sequence:

1. Preferred bundle: `npcink-abilities-toolkit/get-old-article-refresh-context`
2. Expanded sequence when the host needs individual calls:
   `npcink-abilities-toolkit/get-content-refresh-opportunities`,
   `npcink-abilities-toolkit/get-seo-geo-gap-report`,
   `npcink-abilities-toolkit/get-site-style-baseline`,
   `npcink-abilities-toolkit/get-internal-link-graph-health`, and
   `npcink-abilities-toolkit/get-internal-link-opportunity-report` for a selected post

Handoff:

- pass candidate posts, gap signals, style baseline, and link opportunities to
  the host;
- continue into `npcink-abilities-toolkit/recipes/article-optimization` for selected posts.

Governance:

- read-only discovery;
- any post update remains a separate host-governed write chain.

## Recipe: Comment Compliance Handoff

Recipe id: `npcink-abilities-toolkit/recipes/comment-compliance-handoff`

Goal: triage comments, prepare moderation or reply suggestions, and keep final
comment actions under host approval.

Ability sequence:

1. Preferred bundle: `npcink-abilities-toolkit/get-comment-compliance-handoff`
2. Expanded sequence when the host needs individual calls:
   `npcink-abilities-toolkit/get-comment-queue-health`,
   `npcink-abilities-toolkit/get-comment-action-priority-queue`,
   `npcink-abilities-toolkit/build-comment-moderation-suggest`, and
   `npcink-abilities-toolkit/build-comment-mention-reply-suggest` when reply handling is
   needed
3. `npcink-abilities-toolkit/compose-comment-moderation-result`
4. Optional dry-run: `npcink-abilities-toolkit/approve-comment`, `npcink-abilities-toolkit/reply-comment`,
   `npcink-abilities-toolkit/spam-comment`, or `npcink-abilities-toolkit/trash-comment`

Handoff:

- pass queue health, priority rows, suggested actions, and composed moderation
  result to the host;
- pass write/destructive previews to host approval before commit.

Governance:

- suggestion and composition steps are read/proposal steps;
- approve, reply, spam, and trash actions remain host-governed.

## Recipe: Diagnostics Triage

Recipe id: `npcink-abilities-toolkit/recipes/diagnostics-triage`

Goal: gather redacted WordPress-only diagnostics for support or agent triage.

Ability sequence:

1. `npcink-abilities-toolkit/wp-diagnostics-summary`
2. `npcink-abilities-toolkit/wp-ops-diagnostics-detail`
3. `npcink-abilities-toolkit/site-info`

Handoff:

- pass redacted runtime, theme, plugin, caller capability, PHP extension, object
  cache, rewrite, HTTPS, server, REST, Abilities API, cron, update, database
  estimate, log-availability, content type, role, widget, block-theme, search,
  SEO, security, and performance summaries to the host or support flow.

Governance:

- read-only;
- diagnostics must not expose Npcink AI settings, MCP settings, API keys,
  database names, table prefixes, table names, filesystem paths, unredacted
  error log contents, cron argument values, or external HTTP probes.

## Validation

The first validation targets are documented in
[Agent Workflow Validation](agent-workflow-validation.md). Smoke coverage should
prove recipes through WordPress Abilities API discovery and execution, not by
requiring package internals.
