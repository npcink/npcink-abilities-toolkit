# Npcink Abilities Toolkit Migration Inventory

This inventory tracks the migration from `npcink-abilities-toolkit/includes/abilities` into this standalone Abilities API package plugin.

## Added After 0.2

These abilities are new standalone first-party abilities, not migrated from the
Npcink AI plugin. They are official `npcink-abilities-toolkit` package capabilities
and are projected to the Npcink AI catalog as `wp_ability` consumers.

| Ability id | Source | New owner | Npcink AI-owned | Host-governed |
| --- | --- | --- | --- | --- |
| `npcink-abilities-toolkit/get-post-context` | New standalone Content Context Pack ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-content-publishing-checklist` | New standalone Publishing Pack read-support ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-content-inventory-health` | New standalone Content Context Pack inventory audit ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-bulk-publishing-checklist` | New standalone Publishing Pack read-support ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-internal-link-opportunity-report` | New standalone SEO/GEO Support Pack read-support ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-media-inventory-health` | New standalone Content Context Pack media inventory audit ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-post-seo-geo-readiness` | New standalone SEO/GEO Support Pack readiness ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-site-topic-coverage-report` | New standalone SEO/GEO Support Pack topic coverage ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-taxonomy-inventory-health` | New standalone Content Context Pack taxonomy governance audit ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-revision-change-risk-report` | New standalone Publishing Pack read-support revision risk ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-comment-queue-health` | New standalone Comment Compliance Pack queue health ability | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` | No | No |
| `npcink-abilities-toolkit/get-site-operations-dashboard` | New standalone Content Context Pack operations triage ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-post-publish-risk-report` | New standalone Publishing Pack read-support risk ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-article-publish-preflight-context` | New standalone Publishing Pack read-only workflow context bundle | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-content-refresh-opportunities` | New standalone SEO/GEO Support Pack refresh opportunity ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-old-article-refresh-context` | New standalone SEO/GEO Support Pack read-only workflow context bundle | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-internal-link-graph-health` | New standalone SEO/GEO Support Pack internal-link graph audit ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-media-cleanup-opportunities` | New standalone Content Context Pack media cleanup opportunity ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/build-media-inventory-fix-plan` | New standalone Content Context Pack read-only media remediation planning ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions` | New standalone Content Context Pack taxonomy consolidation suggestion ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-comment-action-priority-queue` | New standalone Comment Compliance Pack priority queue ability | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` | No | No |
| `npcink-abilities-toolkit/get-comment-compliance-handoff` | New standalone Comment Compliance Pack read-only workflow context bundle | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` | No | No |
| `npcink-abilities-toolkit/get-page-structure-health` | New standalone Content Context Pack page structure health ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-seo-geo-gap-report` | New standalone SEO/GEO Support Pack gap report ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-site-style-baseline` | New standalone SEO/GEO Support Pack style baseline ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/build-article-workflow-context` | New standalone SEO/GEO Support Pack workflow context ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-publishing-calendar-context` | New standalone Publishing Pack read-support calendar ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/propose-post-taxonomy-terms` | New standalone taxonomy assignment proposal helper for existing terms | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/get-nonproduction-content-inventory` | New standalone Content Context Pack nonproduction-content inventory ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan` | New standalone Content Context Pack read-only cleanup planning ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |
| `npcink-abilities-toolkit/build-content-inventory-fix-plan` | New standalone Content Context Pack read-only remediation planning ability | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` | No | No |

## Migrated in 0.1

| Ability id | Source in Npcink AI | New owner |
| --- | --- | --- |
| `npcink-abilities-toolkit/site-info` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-post-types` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-taxonomies` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/count-posts` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-pages-tree` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-posts` | `includes/abilities/config-read-posts.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/get-post` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/resolve-url-to-post` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/get-post-blocks` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-post-revisions` | `includes/abilities/config-read-posts.php` and `abilities-read-posts.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-media` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-terms` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-taxonomy-terms` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-categories` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-tags` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/get-term` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/propose-post-excerpt` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/resolve-post-metadata-plan` | `includes/abilities/config-tools/registry/article-ops.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-users` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-comments` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-menus` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/get-menu` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/search-posts` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/resolve-internal-link-targets` | `includes/abilities/config-tools/registry/media.php` and `config-tools/analysis-and-media.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/build-inline-image-blocks` | `includes/abilities/config-tools/registry/media.php` and `config-tools/article-media/inline-image-blocks.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/build-media-seo-assets` | `includes/abilities/config-tools/registry/media.php` and `config-tools/article-media/inline-image-blocks.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/geo-analyze` | `includes/abilities/config-tools/registry/media.php` and `config-tools/analysis-and-media.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/optimize-media-metadata` | `includes/abilities/config-tools/registry/media.php` and `config-tools/analysis-and-media.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/position-inline-image-blocks` | `includes/abilities/config-tools/registry/media.php` and `config-tools/article-media/inline-image-blocks.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/build-article-optimization-report` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/article-suggest.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/seo-report-context` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/read-post-optimization-context` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/build-article-single-optimization-suggest` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/article-suggest.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/build-article-optimization-apply-plan` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/article-suggest.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/compose-article-optimization-apply-result` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/article-suggest.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/extract-reference-post-style` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/style-extraction.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/extract-style-baseline` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/style-extraction.php`; Npcink AI cron now dispatches the migrated ability through `wp_get_ability()` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/build-article-production-fingerprint` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/check-article-production-duplicate` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/review-article-output-light` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/compose-article-production-result` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/compose-article-draft-result` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/resolve-article-publication-decision` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/build-article-style-profile` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/get-post-stats` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-revisions` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/get-post-meta` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-pages` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/get-page` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/inspect-page-structure` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |

## Migrated Deterministic Comment Helpers in 0.1

These abilities are read-only comment workflow helpers. `npcink-abilities-toolkit` owns the schemas, callbacks, deterministic suggestion/summary behavior, and WordPress comment reads. Npcink AI keeps the workflow definitions and execution governance that consume these ability ids.

| Ability id | Source in Npcink AI | New owner |
| --- | --- | --- |
| `npcink-abilities-toolkit/build-comment-moderation-suggest` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` |
| `npcink-abilities-toolkit/compose-comment-moderation-result` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` |
| `npcink-abilities-toolkit/build-comment-mention-reply-suggest` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` |
| `npcink-abilities-toolkit/read-comment-trigger-queue` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` |
| `npcink-abilities-toolkit/compose-comment-mention-reply-result` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` |
| `npcink-abilities-toolkit/build-comment-moderation-batch-suggest` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` |
| `npcink-abilities-toolkit/compose-comment-moderation-batch-result` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Comment_Package` |

## Migrated Host-Governed Writes in 0.1

These abilities are pure WordPress write operations. `npcink-abilities-toolkit` owns the schemas, callbacks, dry-run previews, and WordPress mutations. Final commit remains host-governed: direct clients receive dry-run previews by default, and commit requires approval context from a host runtime such as Npcink AI. The old Npcink AI config rows and local callback functions are removed for this migrated set.

| Ability id | Source in Npcink AI | New owner |
| --- | --- | --- |
| `npcink-abilities-toolkit/create-draft` | `includes/abilities/config-write.php`, `abilities-write.php`, `write/post-crud.php`, `write/content-formatting.php`, and `write-formatting-helpers.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/update-post` | `includes/abilities/config-write.php`, `abilities-write.php`, `write/post-crud.php`, `write/content-formatting.php`, and `write-formatting-helpers.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/set-post-seo-meta` | `includes/abilities/config-write.php` and `write/post-props.php`; Npcink AI may still provide SEO adapter filters | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/patch-post-content` | `includes/abilities/config-write.php`, `abilities-write.php`, and `write-formatting-helpers.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/update-post-blocks` | `includes/abilities/config-write.php`, `abilities-write.php`, and `write-formatting-helpers.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/set-post-slug` | `includes/abilities/config-write.php` and `write/post-props.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/set-post-author` | `includes/abilities/config-write.php` and `write/post-props.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/set-post-template` | `includes/abilities/config-write.php` and `write/post-props.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/set-post-format` | `includes/abilities/config-write.php` and `write/post-props.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/create-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/update-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/set-post-terms` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/update-media-details` | `includes/abilities/config-write.php` and `write/media.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/upload-media-from-url` | `includes/abilities/config-write.php` and `write/media.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/set-post-featured-image` | `includes/abilities/config-write.php` and `write/media.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/schedule-post` | `includes/abilities/config-write.php` and `write/post-props.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/publish-post` | `includes/abilities/config-write.php` and `write/post-crud.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/restore-post` | `includes/abilities/config-write.php` and `write/post-crud.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/approve-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |
| `npcink-abilities-toolkit/reply-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Write_Package` |

## Migrated Host-Governed Destructive Abilities in 0.1

These abilities are destructive WordPress operations. `npcink-abilities-toolkit` owns the schemas, callbacks, dry-run previews, and WordPress mutations. Final commit remains host-governed: direct clients receive dry-run previews by default, and commit requires approval context from a host runtime such as Npcink AI. The old Npcink AI config rows and local callback functions are removed for this migrated set.

| Ability id | Source in Npcink AI | New owner |
| --- | --- | --- |
| `npcink-abilities-toolkit/delete-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package` |
| `npcink-abilities-toolkit/merge-terms` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package` |
| `npcink-abilities-toolkit/bulk-update-post-terms` | `includes/abilities/config-write.php` and `write/bulk-taxonomy.php` | `Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package` |
| `npcink-abilities-toolkit/spam-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package` |
| `npcink-abilities-toolkit/trash-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package` |
| `npcink-abilities-toolkit/delete-media-permanently` | `includes/abilities/config-write.php` and `write/media.php` | `Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package` |
| `npcink-abilities-toolkit/trash-post` | `includes/abilities/config-write.php` and `write/post-crud.php` | `Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package` |
| `npcink-abilities-toolkit/delete-post-permanently` | `includes/abilities/config-write.php` and `write/post-crud.php` | `Npcink_Abilities_Toolkit\Packages\Core_Destructive_Package` |

## New Standalone Abilities in 0.1

These abilities are owned by this standalone plugin and are not migrated Npcink AI runtime abilities:

| Ability id | Purpose | Owner |
| --- | --- | --- |
| `npcink-abilities-toolkit/wp-diagnostics-summary` | Redacted WordPress-only diagnostics summary for Abilities API clients | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/wp-ops-diagnostics-detail` | Bounded redacted WordPress operations diagnostics for support flows | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/list-workflow-recipes` | Read-only workflow recipe definition manifest for host runtime discovery | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |
| `npcink-abilities-toolkit/get-workflow-recipe` | Read-only single workflow recipe definition lookup for host runtime discovery | `Npcink_Abilities_Toolkit\Packages\Core_Read_Package` |

## Next Read-Only Candidates

These should be evaluated before write-like abilities:

- additional low-risk WordPress diagnostics details that do not depend on Npcink AI, MCP, runtime, raw database identifiers, filesystem paths, log contents, or operations state

## Later Proposal-Only Candidates

These can move only as proposal generators or host-governed write abilities. Commit authorization must stay in a governed host path:

- draft proposal helpers
- post update proposal helpers
- taxonomy change proposal helpers
- media metadata proposal helpers
- additional deterministic workflow handoff normalizers that have no Npcink AI runtime, model, bridge, audit, or operations dependency

## Remaining Write Candidates

None for generic WordPress writes in the current migration scope. SEO metadata writing now lives in `npcink-abilities-toolkit`; Npcink AI can still provide provider-specific SEO adapter behavior through filters.

## Current Post-Cleanup Audit

As of the current cleanup pass, Npcink AI's local ability config registry contains 33 `npcink-abilities-toolkit/*` abilities. The standalone package registry contains 85 `npcink-abilities-toolkit/*` package abilities. The two sets have no duplicate ability ids.

The remaining Npcink AI-owned ability ids are:

- semantic/model/runtime abilities: `npcink-abilities-toolkit/translate`, `npcink-abilities-toolkit/summarize`, `npcink-abilities-toolkit/image-generate`, `npcink-abilities-toolkit/tts`, `npcink-abilities-toolkit/extract`, `npcink-abilities-toolkit/generate-excerpt`, `npcink-abilities-toolkit/generate-title-suggestions`, `npcink-abilities-toolkit/seo-meta-generate`, `npcink-abilities-toolkit/generate-meta-description`, `npcink-abilities-toolkit/generate-content-summary`, `npcink-abilities-toolkit/review-content-block`, `npcink-abilities-toolkit/generate-image-prompt`, `npcink-abilities-toolkit/generate-alt`, `npcink-abilities-toolkit/embed-text`, `npcink-abilities-toolkit/vector-search`, `npcink-abilities-toolkit/classify-post-taxonomy`, `npcink-abilities-toolkit/seo-analyze`, and `npcink-abilities-toolkit/detect-ai-slop`
- external/service bridge abilities: `npcink-abilities-toolkit/media-bridge`, `npcink-abilities-toolkit/seo-bridge`, `npcink-abilities-toolkit/mail-bridge`, `npcink-abilities-toolkit/web-search`, and `npcink-abilities-toolkit/extract-url`
- article workflow/model-selection abilities: `npcink-abilities-toolkit/resolve-image-source`, `npcink-abilities-toolkit/resolve-references`, `npcink-abilities-toolkit/seo-analysis`, `npcink-abilities-toolkit/geo-analysis`, `npcink-abilities-toolkit/quality-scoring`, and `npcink-abilities-toolkit/apply-editor-feedback`
- Npcink AI operations resources: `npcink-abilities-toolkit/site-diagnostics`, `npcink-abilities-toolkit/mcp-workflow-summary-resource`, `npcink-abilities-toolkit/mcp-prompt-summary-resource`, and `npcink-abilities-toolkit/mcp-site-overview-resource`

These are intentionally not migrated in this pass because they depend on Npcink AI model routing, runtime bridges, vector/provider execution, workflow semantics, MCP/resource state, or operations diagnostics. Future extraction of a semantic/model package would require a separate ADR because it would no longer be a pure WordPress ability package.

## Keep in Npcink AI

These are operational/runtime concerns and should not move into this plugin:

- Agent Gateway and Open API controllers
- app key authentication
- scope, quota, audit, and telemetry enforcement
- full `npcink-abilities-toolkit/site-diagnostics` while it includes Npcink AI, MCP, REST probe, database, filesystem, and operations-state collection
- model routing and workflow/skill runtime
- runtime bridge execution
- two-phase confirmation tokens
- final commit authorization context for Npcink AI runs
- performance snapshots and operations dashboards

## Transition Rule

For the current no-user development profile, the standalone plugin is the required owner for migrated read abilities. Npcink AI should remove fallback definitions and callback files for the migrated set instead of keeping duplicate owners.

If a future release profile needs fallback definitions again, document that as a new ADR and keep duplicate registration avoidance explicit.
