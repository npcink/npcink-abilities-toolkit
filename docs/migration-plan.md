# Project Development Plan

## Phase 1: Standalone Abilities API Plugin

- Keep this plugin independent from the Magick AI runtime.
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

- `magick-ai/site-info`
- `magick-ai-abilities/wp-diagnostics-summary` as a new standalone WordPress-only diagnostics ability, not a migrated Magick AI runtime diagnostic
- `magick-ai/list-post-types`
- `magick-ai/list-taxonomies`
- `magick-ai/count-posts`
- `magick-ai/list-pages-tree`
- `magick-ai/list-posts`
- `magick-ai/get-post`
- `magick-ai/resolve-url-to-post`
- `magick-ai/get-post-blocks`
- `magick-ai/list-post-revisions`
- `magick-ai/list-media`
- `magick-ai/list-terms`
- `magick-ai/list-taxonomy-terms`
- `magick-ai/list-categories`
- `magick-ai/list-tags`
- `magick-ai/get-term`
- `magick-ai/propose-post-excerpt`
- `magick-ai/list-users`
- `magick-ai/list-comments`
- `magick-ai/list-menus`
- `magick-ai/get-menu`
- `magick-ai/search-posts`
- `magick-ai/get-post-stats`
- `magick-ai/list-revisions`
- `magick-ai/get-post-meta`
- `magick-ai/list-pages`
- `magick-ai/get-page`
- `magick-ai/inspect-page-structure`

Current migrated host-governed write package:

- `magick-ai/set-post-slug`
- `magick-ai/set-post-author`
- `magick-ai/set-post-template`
- `magick-ai/set-post-format`
- `magick-ai/create-term`
- `magick-ai/update-term`
- `magick-ai/update-media-details`
- `magick-ai/approve-comment`
- `magick-ai/reply-comment`

Current migrated host-governed destructive package:

- `magick-ai/delete-term`
- `magick-ai/merge-terms`
- `magick-ai/spam-comment`
- `magick-ai/trash-comment`
- `magick-ai/delete-media-permanently`
- `magick-ai/trash-post`
- `magick-ai/delete-post-permanently`

Write-like abilities may move when they are pure WordPress operations and remain host-governed: direct clients get dry-run previews by default, while final commit requires approval context from Magick AI or another host runtime. The generic WordPress mutation can live in `magick-ai-abilities`; Magick AI remains responsible for admission, approval, audit, and runtime context.

## Phase 4: Consumer Integrations

Update consumers, including the Magick AI plugin, to consume this plugin through public functions, WordPress Abilities API discovery, and optional filters.

Avoid direct `require_once` calls into this plugin's internal `includes/` files.

Current Magick AI development rule: remove migrated read-only, migrated host-governed write, and migrated host-governed destructive configs/callbacks from the main plugin. Do not keep fallback copies unless a later release-mode ADR explicitly reintroduces them.
