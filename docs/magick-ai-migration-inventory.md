# Magick AI Abilities Migration Inventory

This inventory tracks the migration from `magick-ai/includes/abilities` into this standalone Abilities API package plugin.

## Migrated in 0.1

| Ability id | Source in Magick AI | New owner |
| --- | --- | --- |
| `magick-ai/site-info` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-post-types` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-taxonomies` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/count-posts` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-pages-tree` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-posts` | `includes/abilities/config-read-posts.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-post` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/resolve-url-to-post` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-post-blocks` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-post-revisions` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-media` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-terms` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-taxonomy-terms` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-categories` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-tags` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-term` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/propose-post-excerpt` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-users` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-comments` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-menus` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-menu` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/search-posts` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-post-stats` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-revisions` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-post-meta` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-pages` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-page` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/inspect-page-structure` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |

## Migrated Host-Governed Writes in 0.1

These abilities are pure WordPress write operations. `magick-ai-abilities` owns the schemas, callbacks, dry-run previews, and WordPress mutations. Final commit remains host-governed: direct clients receive dry-run previews by default, and commit requires approval context from a host runtime such as Magick AI. The old Magick AI config rows and local callback functions are removed for this migrated set.

| Ability id | Source in Magick AI | New owner |
| --- | --- | --- |
| `magick-ai/set-post-slug` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-author` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-template` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-format` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/create-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/update-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/update-media-details` | `includes/abilities/config-write.php` and `write/media.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/approve-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/reply-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |

## Migrated Host-Governed Destructive Abilities in 0.1

These abilities are destructive WordPress operations. `magick-ai-abilities` owns the schemas, callbacks, dry-run previews, and WordPress mutations. Final commit remains host-governed: direct clients receive dry-run previews by default, and commit requires approval context from a host runtime such as Magick AI. The old Magick AI config rows and local callback functions are removed for this migrated set.

| Ability id | Source in Magick AI | New owner |
| --- | --- | --- |
| `magick-ai/delete-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
| `magick-ai/merge-terms` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
| `magick-ai/spam-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
| `magick-ai/trash-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
| `magick-ai/delete-media-permanently` | `includes/abilities/config-write.php` and `write/media.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
| `magick-ai/trash-post` | `includes/abilities/config-write.php` and `write/post-crud.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
| `magick-ai/delete-post-permanently` | `includes/abilities/config-write.php` and `write/post-crud.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |

## New Standalone Abilities in 0.1

These abilities are owned by this standalone plugin and are not migrated Magick AI runtime abilities:

| Ability id | Purpose | Owner |
| --- | --- | --- |
| `magick-ai-abilities/wp-diagnostics-summary` | Redacted WordPress-only diagnostics summary for Abilities API clients | `Magick_AI_Abilities\Packages\Core_Read_Package` |

## Next Read-Only Candidates

These should be evaluated before write-like abilities:

- additional low-risk WordPress diagnostics details that do not depend on Magick AI, MCP, runtime, database, filesystem, or operations state

## Later Proposal-Only Candidates

These can move only as proposal generators or host-governed write abilities. Commit authorization must stay in a governed host path:

- draft proposal helpers
- post update proposal helpers
- taxonomy change proposal helpers
- media metadata proposal helpers

## Remaining Write Candidates

These remain in Magick AI after the destructive migration and should be reviewed in separate batches:

- `magick-ai/create-draft`, `magick-ai/update-post`, `magick-ai/patch-post-content`, and `magick-ai/update-post-blocks`: content-write callbacks include Magick AI article workflow semantics and formatting rules.
- `magick-ai/set-post-terms`: generic enough to migrate later, but should move with the remaining taxonomy write batch.
- `magick-ai/bulk-update-post-terms`: batch governance operation with broader blast radius; keep separate from single-object destructive migration.
- `magick-ai/upload-media-from-url` and `magick-ai/set-post-featured-image`: media ingest/attachment writes with remote URL and workflow usage boundaries; migrate after media safety tests are split.
- `magick-ai/schedule-post`, `magick-ai/publish-post`, and `magick-ai/restore-post`: publication lifecycle writes; migrate as a dedicated publication package.
- `magick-ai/set-post-seo-meta`: depends on SEO adapter bridge behavior and should remain with Magick AI until adapter boundaries are standalone.

## Keep in Magick AI

These are operational/runtime concerns and should not move into this plugin:

- Agent Gateway and Open API controllers
- app key authentication
- scope, quota, audit, and telemetry enforcement
- full `magick-ai/site-diagnostics` while it includes Magick AI, MCP, REST probe, database, filesystem, and operations-state collection
- model routing and workflow/skill runtime
- runtime bridge execution
- two-phase confirmation tokens
- final commit authorization context for Magick AI runs
- performance snapshots and operations dashboards

## Transition Rule

For the current no-user development profile, the standalone plugin is the required owner for migrated read abilities. Magick AI should remove fallback definitions and callback files for the migrated set instead of keeping duplicate owners.

If a future release profile needs fallback definitions again, document that as a new ADR and keep duplicate registration avoidance explicit.
