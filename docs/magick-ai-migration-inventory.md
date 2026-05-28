# Magick AI Abilities Migration Inventory

This inventory tracks the migration from `magick-ai/includes/abilities` into this standalone Abilities API package plugin.

## Added After 0.2

These abilities are new standalone first-party abilities, not migrated from the
Magick AI plugin. They are official `magick-ai-abilities` package capabilities
and are projected to the Magick AI catalog as `wp_ability` consumers.

| Ability id | Source | New owner | Magick AI-owned | Host-governed |
| --- | --- | --- | --- | --- |
| `magick-ai/get-post-context` | New standalone Content Context Pack ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-content-publishing-checklist` | New standalone Publishing Pack read-support ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-content-inventory-health` | New standalone Content Context Pack inventory audit ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-bulk-publishing-checklist` | New standalone Publishing Pack read-support ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-internal-link-opportunity-report` | New standalone SEO/GEO Support Pack read-support ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-media-inventory-health` | New standalone Content Context Pack media inventory audit ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-post-seo-geo-readiness` | New standalone SEO/GEO Support Pack readiness ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-site-topic-coverage-report` | New standalone SEO/GEO Support Pack topic coverage ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-taxonomy-inventory-health` | New standalone Content Context Pack taxonomy governance audit ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-revision-change-risk-report` | New standalone Publishing Pack read-support revision risk ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-comment-queue-health` | New standalone Comment Compliance Pack queue health ability | `Magick_AI_Abilities\Packages\Core_Comment_Package` | No | No |
| `magick-ai/get-site-operations-dashboard` | New standalone Content Context Pack operations triage ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-post-publish-risk-report` | New standalone Publishing Pack read-support risk ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-content-refresh-opportunities` | New standalone SEO/GEO Support Pack refresh opportunity ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-internal-link-graph-health` | New standalone SEO/GEO Support Pack internal-link graph audit ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-media-cleanup-opportunities` | New standalone Content Context Pack media cleanup opportunity ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-taxonomy-consolidation-suggestions` | New standalone Content Context Pack taxonomy consolidation suggestion ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |
| `magick-ai/get-comment-action-priority-queue` | New standalone Comment Compliance Pack priority queue ability | `Magick_AI_Abilities\Packages\Core_Comment_Package` | No | No |
| `magick-ai/get-page-structure-health` | New standalone Content Context Pack page structure health ability | `Magick_AI_Abilities\Packages\Core_Read_Package` | No | No |

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
| `magick-ai/resolve-post-metadata-plan` | `includes/abilities/config-tools/registry/article-ops.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-users` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-comments` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-menus` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-menu` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/search-posts` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/resolve-internal-link-targets` | `includes/abilities/config-tools/registry/media.php` and `config-tools/analysis-and-media.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/build-inline-image-blocks` | `includes/abilities/config-tools/registry/media.php` and `config-tools/article-media/inline-image-blocks.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/build-media-seo-assets` | `includes/abilities/config-tools/registry/media.php` and `config-tools/article-media/inline-image-blocks.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/geo-analyze` | `includes/abilities/config-tools/registry/media.php` and `config-tools/analysis-and-media.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/optimize-media-metadata` | `includes/abilities/config-tools/registry/media.php` and `config-tools/analysis-and-media.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/position-inline-image-blocks` | `includes/abilities/config-tools/registry/media.php` and `config-tools/article-media/inline-image-blocks.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/build-article-optimization-report` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/article-suggest.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/seo-report-context` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/comment.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/read-post-optimization-context` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/comment.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/build-article-single-optimization-suggest` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/article-suggest.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/build-article-optimization-apply-plan` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/article-suggest.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/compose-article-optimization-apply-result` | `includes/abilities/config-tools/registry/article-ops.php` and `config-tools/article-suggest.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/extract-reference-post-style` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/style-extraction.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/extract-style-baseline` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/style-extraction.php`; Magick AI cron now dispatches the migrated ability through `wp_get_ability()` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/build-article-production-fingerprint` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/check-article-production-duplicate` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/review-article-output-light` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/compose-article-production-result` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/compose-article-draft-result` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/resolve-article-publication-decision` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/build-article-style-profile` | `includes/abilities/config-tools/registry/article-production.php` and `config-tools/article-media/article-production.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-post-stats` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-revisions` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-post-meta` | `includes/abilities/config-read-others.php` and `abilities-read-others.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/list-pages` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/get-page` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |
| `magick-ai/inspect-page-structure` | `includes/abilities/config-pages.php` and `abilities-pages.php` | `Magick_AI_Abilities\Packages\Core_Read_Package` |

## Migrated Deterministic Comment Helpers in 0.1

These abilities are read-only comment workflow helpers. `magick-ai-abilities` owns the schemas, callbacks, deterministic suggestion/summary behavior, and WordPress comment reads. Magick AI keeps the workflow definitions and execution governance that consume these ability ids.

| Ability id | Source in Magick AI | New owner |
| --- | --- | --- |
| `magick-ai/build-comment-moderation-suggest` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Magick_AI_Abilities\Packages\Core_Comment_Package` |
| `magick-ai/compose-comment-moderation-result` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Magick_AI_Abilities\Packages\Core_Comment_Package` |
| `magick-ai/build-comment-mention-reply-suggest` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Magick_AI_Abilities\Packages\Core_Comment_Package` |
| `magick-ai/read-comment-trigger-queue` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Magick_AI_Abilities\Packages\Core_Comment_Package` |
| `magick-ai/compose-comment-mention-reply-result` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Magick_AI_Abilities\Packages\Core_Comment_Package` |
| `magick-ai/build-comment-moderation-batch-suggest` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Magick_AI_Abilities\Packages\Core_Comment_Package` |
| `magick-ai/compose-comment-moderation-batch-result` | `includes/abilities/config-tools/comment.php` and `config-tools/registry/comment.php` | `Magick_AI_Abilities\Packages\Core_Comment_Package` |

## Migrated Host-Governed Writes in 0.1

These abilities are pure WordPress write operations. `magick-ai-abilities` owns the schemas, callbacks, dry-run previews, and WordPress mutations. Final commit remains host-governed: direct clients receive dry-run previews by default, and commit requires approval context from a host runtime such as Magick AI. The old Magick AI config rows and local callback functions are removed for this migrated set.

| Ability id | Source in Magick AI | New owner |
| --- | --- | --- |
| `magick-ai/create-draft` | `includes/abilities/config-write.php`, `abilities-write.php`, `write/post-crud.php`, `write/content-formatting.php`, and `write-formatting-helpers.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/update-post` | `includes/abilities/config-write.php`, `abilities-write.php`, `write/post-crud.php`, `write/content-formatting.php`, and `write-formatting-helpers.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-seo-meta` | `includes/abilities/config-write.php` and `write/post-props.php`; Magick AI may still provide SEO adapter filters | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/patch-post-content` | `includes/abilities/config-write.php`, `abilities-write.php`, and `write-formatting-helpers.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/update-post-blocks` | `includes/abilities/config-write.php`, `abilities-write.php`, and `write-formatting-helpers.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-slug` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-author` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-template` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-format` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/create-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/update-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-terms` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/update-media-details` | `includes/abilities/config-write.php` and `write/media.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/upload-media-from-url` | `includes/abilities/config-write.php` and `write/media.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/set-post-featured-image` | `includes/abilities/config-write.php` and `write/media.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/schedule-post` | `includes/abilities/config-write.php` and `write/post-props.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/publish-post` | `includes/abilities/config-write.php` and `write/post-crud.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/restore-post` | `includes/abilities/config-write.php` and `write/post-crud.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/approve-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |
| `magick-ai/reply-comment` | `includes/abilities/config-write.php` and `write/comment.php` | `Magick_AI_Abilities\Packages\Core_Write_Package` |

## Migrated Host-Governed Destructive Abilities in 0.1

These abilities are destructive WordPress operations. `magick-ai-abilities` owns the schemas, callbacks, dry-run previews, and WordPress mutations. Final commit remains host-governed: direct clients receive dry-run previews by default, and commit requires approval context from a host runtime such as Magick AI. The old Magick AI config rows and local callback functions are removed for this migrated set.

| Ability id | Source in Magick AI | New owner |
| --- | --- | --- |
| `magick-ai/delete-term` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
| `magick-ai/merge-terms` | `includes/abilities/config-write.php` and `write/taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
| `magick-ai/bulk-update-post-terms` | `includes/abilities/config-write.php` and `write/bulk-taxonomy.php` | `Magick_AI_Abilities\Packages\Core_Destructive_Package` |
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
- additional deterministic workflow handoff normalizers that have no Magick AI runtime, model, bridge, audit, or operations dependency

## Remaining Write Candidates

None for generic WordPress writes in the current migration scope. SEO metadata writing now lives in `magick-ai-abilities`; Magick AI can still provide provider-specific SEO adapter behavior through filters.

## Current Post-Cleanup Audit

As of the current cleanup pass, Magick AI's local ability config registry contains 33 `magick-ai/*` abilities. The standalone package registry contains 85 `magick-ai/*` package abilities. The two sets have no duplicate ability ids.

The remaining Magick AI-owned ability ids are:

- semantic/model/runtime abilities: `magick-ai/translate`, `magick-ai/summarize`, `magick-ai/image-generate`, `magick-ai/tts`, `magick-ai/extract`, `magick-ai/generate-excerpt`, `magick-ai/generate-title-suggestions`, `magick-ai/seo-meta-generate`, `magick-ai/generate-meta-description`, `magick-ai/generate-content-summary`, `magick-ai/review-content-block`, `magick-ai/generate-image-prompt`, `magick-ai/generate-alt`, `magick-ai/embed-text`, `magick-ai/vector-search`, `magick-ai/classify-post-taxonomy`, `magick-ai/seo-analyze`, and `magick-ai/detect-ai-slop`
- external/service bridge abilities: `magick-ai/media-bridge`, `magick-ai/seo-bridge`, `magick-ai/mail-bridge`, `magick-ai/web-search`, and `magick-ai/extract-url`
- article workflow/model-selection abilities: `magick-ai/resolve-image-source`, `magick-ai/resolve-references`, `magick-ai/seo-analysis`, `magick-ai/geo-analysis`, `magick-ai/quality-scoring`, and `magick-ai/apply-editor-feedback`
- Magick AI operations resources: `magick-ai/site-diagnostics`, `magick-ai/mcp-workflow-summary-resource`, `magick-ai/mcp-prompt-summary-resource`, and `magick-ai/mcp-site-overview-resource`

These are intentionally not migrated in this pass because they depend on Magick AI model routing, runtime bridges, vector/provider execution, workflow semantics, MCP/resource state, or operations diagnostics. Future extraction of a semantic/model package would require a separate ADR because it would no longer be a pure WordPress ability package.

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
