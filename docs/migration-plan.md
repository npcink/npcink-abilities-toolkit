# Project Development Plan

The product positioning and next-stage priority plan are documented in [project-positioning-and-next-stage-plan.md](project-positioning-and-next-stage-plan.md). This plan should be interpreted through that boundary: `npcink-abilities-toolkit` is the WordPress AI Agent ability infrastructure layer, not the end-user AI product or Npcink AI runtime.

## Phase 1: Standalone Abilities API Plugin

- Keep this plugin independent from the Npcink AI runtime.
- Provide public registration functions.
- Register categories and abilities only through WordPress Abilities API hooks.
- Keep Magick AI integration optional and filter-based when Magick AI is present.

## Phase 2: Stabilize Registration Helpers

Stabilize helper behavior inside this standalone project:

- category registration helper
- schema normalizer
- annotation normalizer
- read-only registration helper
- write-proposal registration helper

Current 0.1 status: these helpers now exist in this project and form the first stable public surface for provider plugins.

Do not move model routing, workflow runtime, skills runtime, Cloud execution, quota, billing, or approval commit ownership.

The migration boundary is frozen in [ADR 0001](adr/0001-migrate-abilities-from-magick-ai.md).
The current standalone-owner cleanup policy is frozen in [ADR 0002](adr/0002-standalone-owner-and-magick-ai-cleanup.md).
The per-ability migration inventory is tracked in [magick-ai-migration-inventory.md](magick-ai-migration-inventory.md).

## Phase 3: Migrate Low-Risk Abilities

Start with read-only abilities:

- site diagnostics summaries
- post context reads
- catalog/discovery helpers

Current migrated core read package:

- `npcink-abilities-toolkit/site-info`
- `npcink-abilities-toolkit/wp-diagnostics-summary` as a new standalone WordPress-only diagnostics ability, not a migrated Npcink AI runtime diagnostic
- `npcink-abilities-toolkit/list-post-types`
- `npcink-abilities-toolkit/list-taxonomies`
- `npcink-abilities-toolkit/count-posts`
- `npcink-abilities-toolkit/list-pages-tree`
- `npcink-abilities-toolkit/list-posts`
- `npcink-abilities-toolkit/get-post`
- `npcink-abilities-toolkit/resolve-url-to-post`
- `npcink-abilities-toolkit/get-post-blocks`
- `npcink-abilities-toolkit/list-post-revisions`
- `npcink-abilities-toolkit/list-media`
- `npcink-abilities-toolkit/list-terms`
- `npcink-abilities-toolkit/list-taxonomy-terms`
- `npcink-abilities-toolkit/list-categories`
- `npcink-abilities-toolkit/list-tags`
- `npcink-abilities-toolkit/get-term`
- `npcink-abilities-toolkit/propose-post-excerpt`
- `npcink-abilities-toolkit/resolve-post-metadata-plan`
- `npcink-abilities-toolkit/list-users`
- `npcink-abilities-toolkit/list-comments`
- `npcink-abilities-toolkit/list-menus`
- `npcink-abilities-toolkit/get-menu`
- `npcink-abilities-toolkit/search-posts`
- `npcink-abilities-toolkit/resolve-internal-link-targets`
- `npcink-abilities-toolkit/build-inline-image-blocks`
- `npcink-abilities-toolkit/build-media-seo-assets`
- `npcink-abilities-toolkit/geo-analyze`
- `npcink-abilities-toolkit/optimize-media-metadata`
- `npcink-abilities-toolkit/position-inline-image-blocks`
- `npcink-abilities-toolkit/build-article-optimization-report`
- `npcink-abilities-toolkit/seo-report-context`
- `npcink-abilities-toolkit/read-post-optimization-context`
- `npcink-abilities-toolkit/build-article-single-optimization-suggest`
- `npcink-abilities-toolkit/build-article-optimization-apply-plan`
- `npcink-abilities-toolkit/compose-article-optimization-apply-result`
- `npcink-abilities-toolkit/extract-reference-post-style`
- `npcink-abilities-toolkit/extract-style-baseline`
- `npcink-abilities-toolkit/build-article-production-fingerprint`
- `npcink-abilities-toolkit/check-article-production-duplicate`
- `npcink-abilities-toolkit/review-article-output-light`
- `npcink-abilities-toolkit/compose-article-production-result`
- `npcink-abilities-toolkit/compose-article-draft-result`
- `npcink-abilities-toolkit/resolve-article-publication-decision`
- `npcink-abilities-toolkit/build-article-style-profile`
- `npcink-abilities-toolkit/get-post-stats`
- `npcink-abilities-toolkit/list-revisions`
- `npcink-abilities-toolkit/get-post-meta`
- `npcink-abilities-toolkit/list-pages`
- `npcink-abilities-toolkit/get-page`
- `npcink-abilities-toolkit/inspect-page-structure`

Current migrated deterministic comment helper package:

- `npcink-abilities-toolkit/build-comment-moderation-suggest`
- `npcink-abilities-toolkit/compose-comment-moderation-result`
- `npcink-abilities-toolkit/build-comment-mention-reply-suggest`
- `npcink-abilities-toolkit/read-comment-trigger-queue`
- `npcink-abilities-toolkit/compose-comment-mention-reply-result`
- `npcink-abilities-toolkit/build-comment-moderation-batch-suggest`
- `npcink-abilities-toolkit/compose-comment-moderation-batch-result`

Current migrated host-governed write package:

- `npcink-abilities-toolkit/create-draft`
- `npcink-abilities-toolkit/update-post`
- `npcink-abilities-toolkit/set-post-seo-meta`
- `npcink-abilities-toolkit/patch-post-content`
- `npcink-abilities-toolkit/update-post-blocks`
- `npcink-abilities-toolkit/set-post-slug`
- `npcink-abilities-toolkit/set-post-author`
- `npcink-abilities-toolkit/set-post-template`
- `npcink-abilities-toolkit/set-post-format`
- `npcink-abilities-toolkit/create-term`
- `npcink-abilities-toolkit/update-term`
- `npcink-abilities-toolkit/set-post-terms`
- `npcink-abilities-toolkit/update-media-details`
- `npcink-abilities-toolkit/upload-media-from-url`
- `npcink-abilities-toolkit/set-post-featured-image`
- `npcink-abilities-toolkit/schedule-post`
- `npcink-abilities-toolkit/publish-post`
- `npcink-abilities-toolkit/restore-post`
- `npcink-abilities-toolkit/approve-comment`
- `npcink-abilities-toolkit/reply-comment`

Current migrated host-governed destructive package:

- `npcink-abilities-toolkit/delete-term`
- `npcink-abilities-toolkit/merge-terms`
- `npcink-abilities-toolkit/bulk-update-post-terms`
- `npcink-abilities-toolkit/spam-comment`
- `npcink-abilities-toolkit/trash-comment`
- `npcink-abilities-toolkit/delete-media-permanently`
- `npcink-abilities-toolkit/trash-post`
- `npcink-abilities-toolkit/delete-post-permanently`

Write-like abilities may move when they are pure WordPress operations and remain host-governed: direct clients get dry-run previews by default, while final commit requires approval context from Magick AI or another host runtime. The generic WordPress mutation can live in `npcink-abilities-toolkit`; Magick AI remains responsible for admission, approval, audit, and runtime context.

## Phase 4: Consumer Integrations

Update consumers, including the Npcink AI plugin, to consume this plugin through public functions, WordPress Abilities API discovery, and optional filters.

Avoid direct `require_once` calls into this plugin's internal `includes/` files.

Current Magick AI development rule: remove migrated read-only, deterministic comment helper, migrated host-governed write, and migrated host-governed destructive configs/callbacks from the main plugin. Do not keep fallback copies unless a later release-mode ADR explicitly reintroduces them.
