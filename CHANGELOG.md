# Changelog

## 0.3.0 - Unreleased

- Added `magick-ai/get-post-context`, a read-only post context bundle for agent workflows.
- Added `magick-ai/get-content-publishing-checklist`, a read-only pre-publish readiness checklist.
- Added `magick-ai/get-content-inventory-health`, a bounded read-only inventory health scan.
- Added `magick-ai/get-bulk-publishing-checklist`, a batched wrapper around the publishing checklist.
- Added `magick-ai/get-internal-link-opportunity-report`, a post-id based internal link opportunity report.
- Added `magick-ai/get-media-inventory-health`, a bounded read-only media metadata health scan.
- Added `magick-ai/get-post-seo-geo-readiness`, a deterministic single-post SEO/GEO readiness snapshot.
- Added `magick-ai/get-site-topic-coverage-report`, a bounded topic coverage summary for site content.
- Added `magick-ai/get-taxonomy-inventory-health`, a bounded read-only taxonomy term governance scan.
- Added `magick-ai/get-revision-change-risk-report`, a read-only revision change-risk summary for pre-write review.
- Added `magick-ai/get-comment-queue-health`, a read-only moderation queue health summary.
- Added `magick-ai/get-site-operations-dashboard`, a read-only site operations summary for Agent triage.
- Added `magick-ai/get-post-publish-risk-report`, a read-only per-post publish risk report.
- Added `magick-ai/get-content-refresh-opportunities`, a read-only refresh opportunity scanner.
- Added `magick-ai/get-internal-link-graph-health`, a bounded internal-link graph health report.
- Added `magick-ai/get-media-cleanup-opportunities`, a read-only media cleanup opportunity scanner.
- Added `magick-ai/get-taxonomy-consolidation-suggestions`, a read-only taxonomy consolidation suggestion report.
- Added `magick-ai/get-comment-action-priority-queue`, a prioritized read-only comment handoff queue.
- Added `magick-ai/get-page-structure-health`, a read-only page structure health report.
- Added `magick-ai/get-seo-geo-gap-report`, a read-only SEO/GEO gap report.
- Added `magick-ai/get-site-style-baseline`, a read-only site style baseline wrapper.
- Added `magick-ai/build-article-workflow-context`, a read-only article workflow context bundle.
- Added `magick-ai/get-publishing-calendar-context`, a read-only publishing calendar context report.
- Updated first-party pack documentation, migration inventory, smoke coverage, and lightweight tests for the new abilities.

## 0.2.0 - 2026-05-28

- Stabilized the public helper contract and documented parameters, defaults, failure modes, and examples.
- Documented the ability contract for ids, categories, schemas, annotations, risk levels, scopes, Magick AI metadata, MCP metadata, deprecation, and successor fields.
- Added host-governed write/destructive semantics for `dry_run`, `commit`, `idempotency_key`, `requires_confirm`, `preview`, and `commit_required`.
- Kept third-party public registration limited to category, readonly, write-proposal, schema/annotation normalization, and registered ability inspection helpers.
- Added first-party ability pack grouping for content context, publishing, comment compliance, diagnostics, and SEO/GEO support.
- Added Magick AI consumer verification evidence, including duplicate-id audit expectations and `wp_ability` projection checks.
- Expanded lightweight tests for write controls, output schema controls, invalid ability ids, provider projection defaults, and Magick catalog projection behavior.
- Verified Local WP smoke coverage and recorded 0.2 candidate evidence.

## 0.1.0 - 2026-05-28

- Established Magick AI Abilities as a standalone WordPress Abilities API capability-package plugin.
- Added public helpers for category registration, readonly abilities, write-proposal abilities, schema normalization, annotation normalization, and registered ability inspection.
- Added the migrated WordPress read-only package: `site-info`, `list-post-types`, `list-taxonomies`, `count-posts`, `list-pages-tree`, `list-posts`, `get-post`, `resolve-url-to-post`, `get-post-blocks`, `list-post-revisions`, `list-media`, `list-terms`, `list-taxonomy-terms`, `list-categories`, `list-tags`, `get-term`, `propose-post-excerpt`, `list-users`, `list-comments`, `list-menus`, `get-menu`, `search-posts`, `get-post-stats`, `list-revisions`, `get-post-meta`, `list-pages`, `get-page`, and `inspect-page-structure`.
- Added the migrated deterministic comment helper package: `build-comment-moderation-suggest`, `compose-comment-moderation-result`, `build-comment-mention-reply-suggest`, `read-comment-trigger-queue`, `compose-comment-mention-reply-result`, `build-comment-moderation-batch-suggest`, and `compose-comment-moderation-batch-result`.
- Added migrated host-governed WordPress write and destructive packages with dry-run defaults and approval-context commit gating.
- Added standalone redacted WordPress diagnostics ability: `magick-ai-abilities/wp-diagnostics-summary`.
- Added a wp-admin test page under Tools -> Abilities API Packages.
- Added environment checks for Abilities API functions, REST routes, REST nonce usage, and Magick App Key non-usage.
- Added optional compatibility projection into the Magick AI catalog for provider abilities.
- Added integration rules for optional Magick AI consumption and a 0.1 public API freeze document.
- Added a WP-CLI smoke test for real WordPress environments.
- Added lightweight regression tests and PHP syntax linting.
