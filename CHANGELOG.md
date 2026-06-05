# Changelog

## 0.5.0 - Unreleased

- Updated `magick-ai/adopt-cloud-media-derivative` and `magick-ai/replace-media-file` to preview and commit exact post-content media URL repairs, including old intermediate-size image URLs, when an approved replacement switches an attachment to a new local file.
- Added `magick-ai/propose-post-taxonomy-terms`, a deterministic taxonomy assignment proposal helper that targets `magick-ai/set-post-terms` without mutating posts or creating terms.
- Added a 0.5 unreleased verification note that records the taxonomy terms Core consumer proof and keeps the package in freeze/observe mode until a concrete workflow gap appears.
- Added a Magick AI main repo harvest checkpoint that maps content/media/comment/batch cleanup signals to existing abilities and confirms that no new ability batch should start from candidate lists alone.
- Expanded the Core consumer handoff check to assert the five harvested surfaces and their corresponding host-governed write targets expose Abilities API discovery metadata, schemas, dry-run controls, and approval metadata.

## 0.4.0 - 2026-05-30

- Added a Core governance catalog snapshot fixture covering draft creation, SEO metadata, comment approval, and workflow definition discovery contracts.
- Added Core consumer handoff and catalog audit checks for release-candidate governance validation.
- Added permission matrix and schema boundary audit documentation for first-party write/destructive abilities.
- Added a Core governance consumer example that discovers ability contracts and prepares a proposal payload without moving Core governance into this package.
- Hardened write-like contract metadata with `requires_approval`, dry-run and commit defaults, and bounded idempotency keys.
- Expanded WordPress smoke coverage to assert REST-exposed governance metadata and schemas for Core handoff abilities.
- Tightened `set-post-seo-meta` so omitted metadata fields do not overwrite existing values, and added permission coverage for comment approval dry-runs.
- Added read-only workflow definition discovery through PHP helpers and Abilities API abilities without introducing workflow runtime ownership.
- Added host profile guidance for full governed hosts and light core-read integrations.
- Added workflow definition contract tests that keep the replay fixture aligned with the production provider and reject runtime/governance fields.
- Split the standalone WordPress diagnostics read ability definition into a dedicated read definitions provider while preserving registration order and behavior.
- Split built-in comment helper ability definitions into a dedicated comment definitions provider while preserving callback ownership, sub-pack classification, and registration behavior.
- Split generic `core_wordpress_read` ability definitions into a dedicated read definitions provider while preserving callback ownership, sub-pack classification, and registration order.

## 0.3.0 - 2026-05-29

- Added the 0.3 ability acceptance matrix, agent workflow validation plan, and stabilization scope documents.
- Added package and sub-pack filters for host-selectable built-in package composition while preserving full default registration.
- Added explicit read/comment sub-pack maps as the stable entry point for future definition-provider extraction.
- Kept public third-party helpers read-only/write-proposal oriented and documented that final commit authorization belongs to host runtimes.
- Made Magick AI catalog projection thin by default and added a single projected-row filter for host-owned compatibility expansion.
- Preserved migrated `magick-ai/*` ids as compatibility ids and documented the deprecation/successor rule for any future namespace cleanup.
- Verified Magick AI local Catalog compatibility: authenticated catalog page returned HTTP 200, the capabilities endpoint returned 198 entries, and projected rows omitted host-owned runtime policy fields.
- Added workflow recipe guidance that documents host-side ability composition without moving workflow runtime ownership into this package.
- Added read-only workflow context bundles for article publish preflight, old article refresh discovery, and comment compliance handoff.
- Added short-TTL read caching for selected bounded reports and a `composer perf:smoke` performance smoke command.
- Expanded WordPress smoke coverage from individual ability execution to the publish preflight, content refresh discovery, and comment compliance workflow chains.
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

- Established Npcink Abilities Toolkit as a standalone WordPress Abilities API capability-package plugin.
- Added public helpers for category registration, readonly abilities, write-proposal abilities, schema normalization, annotation normalization, and registered ability inspection.
- Added the migrated WordPress read-only package: `site-info`, `list-post-types`, `list-taxonomies`, `count-posts`, `list-pages-tree`, `list-posts`, `get-post`, `resolve-url-to-post`, `get-post-blocks`, `list-post-revisions`, `list-media`, `list-terms`, `list-taxonomy-terms`, `list-categories`, `list-tags`, `get-term`, `propose-post-excerpt`, `list-users`, `list-comments`, `list-menus`, `get-menu`, `search-posts`, `get-post-stats`, `list-revisions`, `get-post-meta`, `list-pages`, `get-page`, and `inspect-page-structure`.
- Added the migrated deterministic comment helper package: `build-comment-moderation-suggest`, `compose-comment-moderation-result`, `build-comment-mention-reply-suggest`, `read-comment-trigger-queue`, `compose-comment-mention-reply-result`, `build-comment-moderation-batch-suggest`, and `compose-comment-moderation-batch-result`.
- Added migrated host-governed WordPress write and destructive packages with dry-run defaults and approval-context commit gating.
- Added standalone redacted WordPress diagnostics ability: `npcink-abilities-toolkit/wp-diagnostics-summary`.
- Added a wp-admin test page under Tools -> Abilities API Packages.
- Added environment checks for Abilities API functions, REST routes, REST nonce usage, and Magick App Key non-usage.
- Added optional compatibility projection into the Magick AI catalog for provider abilities.
- Added integration rules for optional Magick AI consumption and a 0.1 public API freeze document.
- Added a WP-CLI smoke test for real WordPress environments.
- Added lightweight regression tests and PHP syntax linting.
