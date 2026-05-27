# ADR 0002: Standalone Owner and Magick AI Cleanup

Status: accepted
Date: 2026-05-27

## Context

`magick-ai-abilities` has moved beyond being a Magick AI helper module. It is now the standalone WordPress Abilities API capability-package plugin:

- It should provide reusable WordPress abilities for Magick AI and other plugins.
- It should own generic ability contracts, schemas, categories, registration helpers, and WordPress-only callbacks.
- It should not inherit Magick AI's runtime, gateway, quota, audit, approval, or operations-control responsibilities.

The current Magick AI product is still in development and has no production users. Keeping duplicated migrated ability definitions in the main plugin now adds more cost than compatibility value:

- Duplicate config rows hide the real ownership boundary.
- Duplicate callbacks increase maintenance pressure in the main plugin.
- Guard-based deferral can mask accidental reintroduction of moved abilities.
- Tests and documents should point future work to the new owner instead of preserving the old source layout.

## Decision

Make `magick-ai-abilities` the primary owner of the migrated generic WordPress ability packages.

For the current development stage, Magick AI will no longer keep fallback copies of migrated read-only ability configs or callbacks. Sites that need those generic abilities should install and activate `magick-ai-abilities`.

Magick AI keeps only abilities that are still part of its own runtime, governance, or operations surface.

## Current Cleanup Scope

Remove these migrated read-only definitions from Magick AI:

- `magick-ai/site-info`
- `magick-ai/list-post-types`
- `magick-ai/list-taxonomies`
- `magick-ai/list-pages-tree`
- `magick-ai/count-posts`
- `magick-ai/list-posts`
- `magick-ai/get-post`
- `magick-ai/get-post-blocks`
- `magick-ai/list-post-revisions`
- `magick-ai/resolve-url-to-post`
- `magick-ai/propose-post-excerpt`
- `magick-ai/list-users`
- `magick-ai/list-comments`
- `magick-ai/list-media`
- `magick-ai/list-terms`
- `magick-ai/list-taxonomy-terms`
- `magick-ai/list-menus`
- `magick-ai/get-menu`
- `magick-ai/get-term`
- `magick-ai/list-categories`
- `magick-ai/list-tags`
- `magick-ai/search-posts`
- `magick-ai/get-post-stats`
- `magick-ai/list-revisions`
- `magick-ai/get-post-meta`
- `magick-ai/list-pages`
- `magick-ai/get-page`
- `magick-ai/inspect-page-structure`

Remove the old Magick AI callback/config files that existed only for the migrated packages:

- `includes/abilities/abilities-read-posts.php`
- `includes/abilities/abilities-read-others.php`
- `includes/abilities/abilities-pages.php`
- `includes/abilities/config-read-others.php`
- `includes/abilities/config-pages.php`

Deleting `config-pages.php` also removes the old `magick-ai/create-page` and `magick-ai/update-page` placeholders from Magick AI. They are not migrated into `magick-ai-abilities` in this batch because they are write-like page management abilities and did not have valid Magick AI callback ownership at cleanup time. Future generic page-write support should be redesigned as proposal-only abilities before any standalone migration.

Keep `magick-ai/site-diagnostics` in Magick AI because it currently includes Magick AI, MCP, runtime, filesystem, database, REST-probe, and operations-state details.

## Write-Like Ability Boundary

Do not migrate Magick AI write/destructive abilities as one unreviewed bulk move.

`magick-ai-abilities` may own generic WordPress write abilities when they remain host-governed. Direct clients get discovery and dry-run previews; final commit requires approval context from a host runtime. In that model, `magick-ai-abilities` owns the generic WordPress callback and mutation, while Magick AI or another host owns admission, approval, audit, and execution context.

The first host-governed write migration includes:

- `magick-ai/set-post-slug`
- `magick-ai/set-post-author`
- `magick-ai/set-post-template`
- `magick-ai/set-post-format`
- `magick-ai/create-term`
- `magick-ai/update-term`
- `magick-ai/update-media-details`
- `magick-ai/approve-comment`
- `magick-ai/reply-comment`

The first host-governed destructive migration includes:

- `magick-ai/delete-term`
- `magick-ai/merge-terms`
- `magick-ai/spam-comment`
- `magick-ai/trash-comment`
- `magick-ai/delete-media-permanently`
- `magick-ai/trash-post`
- `magick-ai/delete-post-permanently`

`magick-ai-abilities` may also later own generic WordPress proposal abilities, such as:

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

For Magick AI, that host runtime is the Magick AI plugin, not `magick-ai-abilities`. `magick-ai-abilities` executes only the generic WordPress mutation after the host has authorized the commit.

The Magick AI cleanup for the first host-governed write migration also removes the old local callback functions for:

- `magick_ai_ability_set_post_slug`
- `magick_ai_ability_set_post_author`
- `magick_ai_ability_set_post_template`
- `magick_ai_ability_set_post_format`
- `magick_ai_ability_create_term`
- `magick_ai_ability_update_term`
- `magick_ai_ability_update_media_details`
- `magick_ai_ability_approve_comment`
- `magick_ai_ability_reply_comment`
- `magick_ai_ability_delete_term`
- `magick_ai_ability_merge_terms`
- `magick_ai_ability_spam_comment`
- `magick_ai_ability_trash_comment`
- `magick_ai_ability_delete_media_permanently`
- `magick_ai_ability_trash_post`
- `magick_ai_ability_delete_post_permanently`

## Consequences

- Magick AI's Abilities surface becomes smaller and more operations-focused.
- `magick-ai-abilities` becomes the development home for generic WordPress ability packages.
- Duplicate registration guards in Magick AI are no longer the main compatibility mechanism for migrated read-only abilities.
- Accidental reintroduction of migrated read-only ability configs should be caught by review, smoke tests, and inventory checks rather than silently deferred.
- Magick AI unit tests that directly require deleted ability package files should be retired or ported to `magick-ai-abilities` tests.
- If a future deployment needs backwards-compatible fallback behavior, that should be an explicit new ADR and release-mode decision, not the default development posture.
