# ADR 0002: Standalone Owner and Npcink AI Cleanup

Status: accepted
Date: 2026-05-27

## Context

`npcink-abilities-toolkit` has moved beyond being a Npcink AI helper module. It is now the standalone WordPress Abilities API capability-package plugin:

- It should provide reusable WordPress abilities for Npcink AI and other plugins.
- It should own generic ability contracts, schemas, categories, registration helpers, and WordPress-only callbacks.
- It should not inherit Npcink AI's runtime, gateway, quota, audit, approval, or operations-control responsibilities.

The current Npcink AI product is still in development and has no production users. Keeping duplicated migrated ability definitions in the main plugin now adds more cost than compatibility value:

- Duplicate config rows hide the real ownership boundary.
- Duplicate callbacks increase maintenance pressure in the main plugin.
- Guard-based deferral can mask accidental reintroduction of moved abilities.
- Tests and documents should point future work to the new owner instead of preserving the old source layout.

## Decision

Make `npcink-abilities-toolkit` the primary owner of the migrated generic WordPress ability packages.

For the current development stage, Npcink AI will no longer keep fallback copies of migrated read-only ability configs or callbacks. Sites that need those generic abilities should install and activate `npcink-abilities-toolkit`.

Npcink AI keeps only abilities that are still part of its own runtime, governance, or operations surface.

## Current Cleanup Scope

Remove these migrated read-only definitions from Npcink AI:

- `npcink-abilities-toolkit/site-info`
- `npcink-abilities-toolkit/list-post-types`
- `npcink-abilities-toolkit/list-taxonomies`
- `npcink-abilities-toolkit/list-pages-tree`
- `npcink-abilities-toolkit/count-posts`
- `npcink-abilities-toolkit/list-posts`
- `npcink-abilities-toolkit/get-post`
- `npcink-abilities-toolkit/get-post-blocks`
- `npcink-abilities-toolkit/list-post-revisions`
- `npcink-abilities-toolkit/resolve-url-to-post`
- `npcink-abilities-toolkit/propose-post-excerpt`
- `npcink-abilities-toolkit/list-users`
- `npcink-abilities-toolkit/list-comments`
- `npcink-abilities-toolkit/list-media`
- `npcink-abilities-toolkit/list-terms`
- `npcink-abilities-toolkit/list-taxonomy-terms`
- `npcink-abilities-toolkit/list-menus`
- `npcink-abilities-toolkit/get-menu`
- `npcink-abilities-toolkit/get-term`
- `npcink-abilities-toolkit/list-categories`
- `npcink-abilities-toolkit/list-tags`
- `npcink-abilities-toolkit/resolve-post-metadata-plan`
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
- `npcink-abilities-toolkit/build-comment-moderation-suggest`
- `npcink-abilities-toolkit/compose-comment-moderation-result`
- `npcink-abilities-toolkit/build-comment-mention-reply-suggest`
- `npcink-abilities-toolkit/read-comment-trigger-queue`
- `npcink-abilities-toolkit/compose-comment-mention-reply-result`
- `npcink-abilities-toolkit/build-comment-moderation-batch-suggest`
- `npcink-abilities-toolkit/compose-comment-moderation-batch-result`

Remove the old Npcink AI callback/config files that existed only for the migrated packages:

- `includes/abilities/abilities-read-posts.php`
- `includes/abilities/abilities-read-others.php`
- `includes/abilities/abilities-pages.php`
- `includes/abilities/config-read-others.php`
- `includes/abilities/config-pages.php`
- `includes/abilities/config-tools/article-media/style-extraction.php`
- `includes/abilities/config-tools/comment.php`
- `includes/abilities/config-tools/registry/comment.php`

Deleting `config-pages.php` also removes the old `npcink-abilities-toolkit/create-page` and `npcink-abilities-toolkit/update-page` placeholders from Npcink AI. They are not migrated into `npcink-abilities-toolkit` in this batch because they are write-like page management abilities and did not have valid Npcink AI callback ownership at cleanup time. Future generic page-write support should be redesigned as proposal-only abilities before any standalone migration.

Keep `npcink-abilities-toolkit/site-diagnostics` in Npcink AI because it currently includes Npcink AI, MCP, runtime, filesystem, database, REST-probe, and operations-state details.

## Write-Like Ability Boundary

Do not migrate Npcink AI write/destructive abilities as one unreviewed bulk move.

`npcink-abilities-toolkit` may own generic WordPress write abilities when they remain host-governed. Direct clients get discovery and dry-run previews; final commit requires approval context from a host runtime. In that model, `npcink-abilities-toolkit` owns the generic WordPress callback and mutation, while Npcink AI or another host owns admission, approval, audit, and execution context.

The first host-governed write migration includes:

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

The first host-governed destructive migration includes:

- `npcink-abilities-toolkit/delete-term`
- `npcink-abilities-toolkit/merge-terms`
- `npcink-abilities-toolkit/bulk-update-post-terms`
- `npcink-abilities-toolkit/spam-comment`
- `npcink-abilities-toolkit/trash-comment`
- `npcink-abilities-toolkit/delete-media-permanently`
- `npcink-abilities-toolkit/trash-post`
- `npcink-abilities-toolkit/delete-post-permanently`

`npcink-abilities-toolkit` may also later own generic WordPress proposal abilities, such as:

- draft proposal generation
- page/post update proposal generation
- taxonomy or metadata change proposal generation

But final write ownership remains in the host runtime that provides:

- authentication and caller identity
- scope and capability admission
- quota and rate limits
- audit trail
- two-phase confirmation
- approval decision storage
- commit authorization context
- rollback or incident response hooks

For Npcink AI, that host runtime is the Npcink AI plugin, not `npcink-abilities-toolkit`. `npcink-abilities-toolkit` executes only the generic WordPress mutation after the host has authorized the commit.

The Npcink AI cleanup for the first host-governed write migration also removes the old local callback functions for:

- `npcink_ai_ability_create_draft`
- `npcink_ai_ability_update_post`
- `npcink_ai_ability_patch_post_content`
- `npcink_ai_ability_update_post_blocks`
- `npcink_ai_ability_set_post_slug`
- `npcink_ai_ability_set_post_author`
- `npcink_ai_ability_set_post_template`
- `npcink_ai_ability_set_post_format`
- `npcink_ai_ability_create_term`
- `npcink_ai_ability_update_term`
- `npcink_ai_ability_set_post_terms`
- `npcink_ai_ability_update_media_details`
- `npcink_ai_ability_upload_media_from_url`
- `npcink_ai_ability_set_post_featured_image`
- `npcink_ai_ability_schedule_post`
- `npcink_ai_ability_publish_post`
- `npcink_ai_ability_restore_post`
- `npcink_ai_ability_approve_comment`
- `npcink_ai_ability_reply_comment`
- `npcink_ai_ability_delete_term`
- `npcink_ai_ability_merge_terms`
- `npcink_ai_ability_bulk_update_post_terms`
- `npcink_ai_ability_spam_comment`
- `npcink_ai_ability_trash_comment`
- `npcink_ai_ability_delete_media_permanently`
- `npcink_ai_ability_trash_post`
- `npcink_ai_ability_delete_post_permanently`

## Consequences

- Npcink AI's Abilities surface becomes smaller and more operations-focused.
- `npcink-abilities-toolkit` becomes the development home for generic WordPress ability packages.
- After the current cleanup pass, Npcink AI and `npcink-abilities-toolkit` have no duplicate `npcink-abilities-toolkit/*` package ability ids in their local registries.
- The remaining Npcink AI-owned abilities are runtime/model, bridge, MCP resource, workflow-semantic, or operations-diagnostics abilities; moving those would require a separate semantic/runtime extraction decision, not a continuation of this pure WordPress package migration.
- Duplicate registration guards in Npcink AI are no longer the main compatibility mechanism for migrated read-only abilities.
- Accidental reintroduction of migrated read-only ability configs should be caught by review, smoke tests, and inventory checks rather than silently deferred.
- Npcink AI unit tests that directly require deleted ability package files should be retired or ported to `npcink-abilities-toolkit` tests.
- If a future deployment needs backwards-compatible fallback behavior, that should be an explicit new ADR and release-mode decision, not the default development posture.
