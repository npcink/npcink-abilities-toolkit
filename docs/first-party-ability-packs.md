# First-Party Ability Packs

This document groups the built-in abilities by product purpose. It does not
change runtime ownership. The current implementation remains in
`includes/Packages/*`, with sub-pack classifiers split into small
`Core_*_Pack_Classifier` classes so host package gating can evolve without
changing ability ids, schemas, or callbacks.

Recommended cross-pack compositions are documented in
[Workflow Recipes](workflow-recipes.md). Those recipes are host-side
consumption guidance, not runtime ownership in this package.

## Content Context Pack

Purpose: give agents and host runtimes structured WordPress content context
without requiring direct database or internal API access.

Risk: read.

Writable: no.

Host approval: no final commit approval required.

Primary consumers: Magick AI, WP Magick Toolbox, direct Abilities API clients,
and third-party hosts that need WordPress content discovery.

Representative abilities:

- `magick-ai/site-info`
- `magick-ai/list-post-types`
- `magick-ai/list-taxonomies`
- `magick-ai/count-posts`
- `magick-ai/list-pages-tree`
- `magick-ai/list-posts`
- `magick-ai/get-post`
- `magick-ai/get-post-context`
- `magick-ai/get-content-inventory-health`
- `magick-ai/get-test-content-inventory`
- `magick-ai/build-test-content-cleanup-plan`
- `magick-ai/build-content-inventory-fix-plan`
- `magick-ai/get-site-operations-dashboard`
- `magick-ai/resolve-url-to-post`
- `magick-ai/get-post-blocks`
- `magick-ai/list-post-revisions`
- `magick-ai/list-media`
- `magick-ai/resolve-media-attachment-by-url`
- `magick-ai/get-media-inventory-health`
- `magick-ai/inspect-media-asset`
- `magick-ai/list-media-backups`
- `magick-ai/build-media-derivative-cloud-request`
- `magick-ai/build-media-optimization-plan`
- `magick-ai/build-media-rename-plan`
- `magick-ai/get-media-cleanup-opportunities`
- `magick-ai/build-media-inventory-fix-plan`
- `magick-ai/get-taxonomy-inventory-health`
- `magick-ai/get-taxonomy-consolidation-suggestions`
- `magick-ai/propose-post-taxonomy-terms`
- `magick-ai/list-terms`
- `magick-ai/list-taxonomy-terms`
- `magick-ai/list-categories`
- `magick-ai/list-tags`
- `magick-ai/get-term`
- `magick-ai/list-users`
- `magick-ai/list-comments`
- `magick-ai/list-menus`
- `magick-ai/get-menu`
- `magick-ai/search-posts`
- `magick-ai/search-post-meta`
- `magick-ai/get-post-stats`
- `magick-ai/list-revisions`
- `magick-ai/get-post-meta`
- `magick-ai/list-pages`
- `magick-ai/get-page`
- `magick-ai/inspect-page-structure`
- `magick-ai/get-page-structure-health`

For media `format_attention` handling, keep inventory plans read-only and use
[Media Format Attention Boundary](media-format-attention-boundary.md) before
adding file-asset write actions.

## Publishing Pack

Purpose: expose generic WordPress publishing context and mutations through
read-only support helpers plus host-governed dry-run and commit contracts.

Risk: read for publishing support helpers; write or destructive for final
mutations.

Writable: read-support helpers are not writable; mutation abilities are writable.

Host approval: not required for read-support helpers; required for final commit.

Primary consumers: Magick AI and other host runtimes that can provide caller
identity, approval, audit, quota, and idempotency context.

Representative write abilities:

- `magick-ai/create-draft`
- `magick-ai/update-post`
- `magick-ai/set-post-seo-meta`
- `magick-ai/patch-post-content`
- `magick-ai/update-post-blocks`
- `magick-ai/set-post-slug`
- `magick-ai/set-post-author`
- `magick-ai/set-post-template`
- `magick-ai/set-post-format`
- `magick-ai/create-term`
- `magick-ai/update-term`
- `magick-ai/set-post-terms`
- `magick-ai/update-media-details`
- `magick-ai/upload-media-from-url`
- `magick-ai/optimize-media-asset`
- `magick-ai/replace-media-file`
- `magick-ai/restore-media-backup`
- `magick-ai/rename-media-file`
- `magick-ai/adopt-cloud-media-derivative`
- `magick-ai/set-post-featured-image`
- `magick-ai/schedule-post`
- `magick-ai/publish-post`
- `magick-ai/restore-post`

Media filename policy: `magick-ai/upload-media-from-url` and
`magick-ai/adopt-cloud-media-derivative` may accept an approved `file_name` for
new local media files. `magick-ai/rename-media-file` may rename an existing
attachment main file within its current uploads directory after approval; use
`magick-ai/build-media-rename-plan` so exact post-content references to the old
uploads URL are patched in the same governed proposal.
`magick-ai/update-media-details` updates metadata only and must not physically
rename existing attachment files.

Representative read-only publishing support abilities:

- `magick-ai/get-content-publishing-checklist`
- `magick-ai/get-bulk-publishing-checklist`
- `magick-ai/get-revision-change-risk-report`
- `magick-ai/get-post-publish-risk-report`
- `magick-ai/get-article-publish-preflight-context`
- `magick-ai/get-publishing-calendar-context`

Representative destructive abilities:

- `magick-ai/delete-term`
- `magick-ai/merge-terms`
- `magick-ai/bulk-update-post-terms`
- `magick-ai/delete-media-permanently`
- `magick-ai/trash-post`
- `magick-ai/delete-post-permanently`

## Comment Compliance Pack

Purpose: support comment moderation, reply handoff, and batch review workflows
without moving workflow orchestration into this package.

Risk: read for suggestion and composition helpers; write/destructive for final
WordPress comment mutations.

Writable: suggestions are not writable; final comment actions are host-governed
write/destructive abilities.

Host approval: required for final approve, reply, spam, or trash actions.

Primary consumers: Magick AI comment workflows, compliance helpers, and host
runtimes that need deterministic comment handoffs.

Representative abilities:

- `magick-ai/list-comments`
- `magick-ai/build-comment-moderation-suggest`
- `magick-ai/compose-comment-moderation-result`
- `magick-ai/build-comment-mention-reply-suggest`
- `magick-ai/read-comment-trigger-queue`
- `magick-ai/get-comment-queue-health`
- `magick-ai/get-comment-action-priority-queue`
- `magick-ai/get-comment-compliance-handoff`
- `magick-ai/compose-comment-mention-reply-result`
- `magick-ai/build-comment-moderation-batch-suggest`
- `magick-ai/compose-comment-moderation-batch-result`
- `magick-ai/approve-comment`
- `magick-ai/reply-comment`
- `magick-ai/spam-comment`
- `magick-ai/trash-comment`

## Diagnostics Pack

Purpose: provide redacted WordPress-only diagnostics for agents and host
runtimes without exposing Magick AI runtime, MCP, database, filesystem, or
secret state.

Risk: read.

Writable: no.

Host approval: no final commit approval required.

Primary consumers: direct Abilities API clients, Magick AI, Toolbox support
flows, and local smoke checks.

Abilities:

- `npcink-abilities-toolkit/wp-diagnostics-summary`
- `npcink-abilities-toolkit/wp-ops-diagnostics-detail`

The detail ability is the support-facing follow-up surface for bounded plugin
rows, caller permissions, log severity summaries, optional structured log
contents, log source summaries with message fingerprints and safe PHAR basename
hints, top repeated log messages, cache/rewrite/HTTPS, database/cron/server
state, content type/role/widget/block-theme inventories, search/integration
hints, and SEO/security/performance summaries. Inactive plugin rows are omitted
by default and can be requested explicitly for deeper conflict diagnosis.

## Workflow Definition Pack

Purpose: expose read-only workflow recipe definitions for hosts that need
runtime discovery of recommended ability composition without turning this
package into a workflow engine.

Risk: read.

Writable: no.

Host approval: no final commit approval required.

Primary consumers: Magick AI Core, Agent Gateway, MCP adapters, and other hosts
that need recipe discovery while keeping execution, approval, audit, quota, model
routing, and final writes in the host runtime.

Abilities:

- `npcink-abilities-toolkit/list-workflow-recipes`
- `npcink-abilities-toolkit/get-workflow-recipe`

## SEO/GEO Support Pack

Purpose: provide deterministic context, planning, and review helpers for SEO,
GEO, article optimization, media metadata, and article production workflows
without owning model routing or final publication policy.

Risk: read.

Writable: no for helpers in this pack. Applying changes belongs to the
Publishing Pack or a host workflow.

Host approval: no approval for context/suggestion helpers; approval is required
only when a host later executes write/destructive abilities.

Primary consumers: Magick AI article workflows, content products, SEO/GEO
assistants, and host runtimes that compose suggestions with model output.

Representative abilities:

- `magick-ai/propose-post-excerpt`
- `magick-ai/resolve-post-metadata-plan`
- `magick-ai/resolve-internal-link-targets`
- `magick-ai/get-internal-link-opportunity-report`
- `magick-ai/get-content-refresh-opportunities`
- `magick-ai/get-internal-link-graph-health`
- `magick-ai/get-post-seo-geo-readiness`
- `magick-ai/get-site-topic-coverage-report`
- `magick-ai/get-seo-geo-gap-report`
- `magick-ai/get-site-style-baseline`
- `magick-ai/build-article-workflow-context`
- `magick-ai/get-old-article-refresh-context`
- `magick-ai/build-inline-image-blocks`
- `magick-ai/build-media-seo-assets`
- `magick-ai/geo-analyze`
- `magick-ai/optimize-media-metadata`
- `magick-ai/position-inline-image-blocks`
- `magick-ai/build-article-optimization-report`
- `magick-ai/seo-report-context`
- `magick-ai/read-post-optimization-context`
- `magick-ai/build-article-single-optimization-suggest`
- `magick-ai/build-article-optimization-apply-plan`
- `magick-ai/compose-article-optimization-apply-result`
- `magick-ai/extract-reference-post-style`
- `magick-ai/extract-style-baseline`
- `magick-ai/build-article-production-fingerprint`
- `magick-ai/check-article-production-duplicate`
- `magick-ai/review-article-output-light`
- `magick-ai/compose-article-production-result`
- `magick-ai/compose-article-draft-result`
- `magick-ai/resolve-article-publication-decision`
- `magick-ai/build-article-style-profile`
