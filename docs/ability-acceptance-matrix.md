# Ability Acceptance Matrix

Status: active for the 0.3 stabilization line.

This matrix translates the first-party ability packs into real agent tasks. It
is intentionally task-oriented: adding another ability only helps when it closes
a gap in one of these acceptance rows.

## Gate

Before adding a new batch of first-party abilities:

- the target task must appear in this matrix or be added here first;
- the existing ability chain must be tried through WordPress Abilities API
  discovery and execution;
- any missing capability must be described as a workflow gap, not just as a new
  helper name;
- write/destructive completion must remain host-governed and outside this
  package's public third-party API.

## Acceptance Rules

Each accepted ability must satisfy these rules:

- stable namespaced ability id;
- bounded input schema for list or scan operations;
- success envelope with `success`, `data`, optional `meta`, and `message`;
- explicit risk level through the normalized contract;
- no Magick AI runtime, model routing, quota, audit, or workflow ownership;
- REST discovery and execution through WordPress Abilities API;
- smoke coverage when the ability is part of a top-level workflow.
- assignment to a built-in package or sub-pack so hosts can disable optional
  workflow helpers without losing the generic WordPress read surface.

## Matrix

| Task surface | Primary abilities | Acceptance criteria | Automated coverage | Status |
| --- | --- | --- | --- | --- |
| Site operations scan | `magick-ai/get-site-operations-dashboard`, `magick-ai/get-content-inventory-health`, `magick-ai/get-media-inventory-health`, `magick-ai/get-taxonomy-inventory-health`, `magick-ai/get-page-structure-health` | Agent can identify content, media, taxonomy, and page-structure attention areas without writes. | `composer test`, `composer smoke:wp` single-ability runs. | Accepted for 0.3 stabilization. |
| Content remediation planning | `magick-ai/get-test-content-inventory`, `magick-ai/build-test-content-cleanup-plan`, `magick-ai/build-content-inventory-fix-plan`, `magick-ai/set-post-seo-meta`, `magick-ai/set-post-slug`, `magick-ai/update-post`, `magick-ai/set-post-featured-image`, `magick-ai/trash-post`, `magick-ai/delete-term`, `magick-ai/trash-comment` | Agent can turn detected test content and content inventory gaps into bounded, reviewable dry-run actions that reuse existing governed write/destructive abilities without committing. | `composer test:all`, `composer smoke:wp` planning-side runs. | Read and planning side accepted. Final mutation execution remains host-governed. |
| Article publish preflight | `magick-ai/get-article-publish-preflight-context`, `magick-ai/get-post-context`, `magick-ai/get-content-publishing-checklist`, `magick-ai/get-post-publish-risk-report`, `magick-ai/build-article-workflow-context`, `magick-ai/get-publishing-calendar-context` | Agent can assemble publish readiness, risk, and calendar context; final publish/schedule remains host-governed. | `composer test`, `composer perf:smoke`, `composer smoke:wp` workflow assertion. | Accepted for workflow validation. |
| Old article refresh discovery | `magick-ai/get-old-article-refresh-context`, `magick-ai/get-content-refresh-opportunities`, `magick-ai/get-seo-geo-gap-report`, `magick-ai/get-site-style-baseline`, `magick-ai/get-internal-link-graph-health`, `magick-ai/get-internal-link-opportunity-report` | Agent can identify refresh candidates, SEO/GEO gaps, style baseline, and link opportunities without mutating posts. | `composer test`, `composer perf:smoke`, `composer smoke:wp` workflow assertion. | Accepted for workflow validation. |
| Comment compliance handoff | `magick-ai/get-comment-compliance-handoff`, `magick-ai/get-comment-queue-health`, `magick-ai/get-comment-action-priority-queue`, `magick-ai/build-comment-moderation-suggest`, `magick-ai/build-comment-mention-reply-suggest`, `magick-ai/compose-comment-moderation-result` | Agent can prioritize comments and prepare moderation/reply suggestions; approve, reply, spam, and trash remain host-governed. | `composer test`, `composer perf:smoke`, `composer smoke:wp` workflow assertion. | Accepted for workflow validation. |
| Workflow recipe discovery | `npcink-abilities-toolkit/list-workflow-recipes`, `npcink-abilities-toolkit/get-workflow-recipe` | Host can discover read-only workflow recipe definitions at runtime without this package owning workflow execution, approval, audit, quota, model routing, prompts, or final writes. | `composer test`, `composer smoke:wp` discovery runs. | Accepted for runtime discovery. |
| Media governance | `magick-ai/get-media-cleanup-opportunities`, `magick-ai/build-media-inventory-fix-plan`, `magick-ai/build-media-seo-assets`, `magick-ai/optimize-media-metadata`, `magick-ai/resolve-media-attachment-by-url`, `magick-ai/inspect-media-asset`, `magick-ai/list-media-backups`, `magick-ai/build-media-derivative-cloud-request`, `magick-ai/build-media-derivative-batch-plan`, `magick-ai/build-media-optimization-plan`, `magick-ai/build-media-rename-plan`, `magick-ai/update-media-details`, `magick-ai/optimize-media-asset`, `magick-ai/replace-media-file`, `magick-ai/restore-media-backup`, `magick-ai/rename-media-file`, `magick-ai/adopt-cloud-media-derivative`, `magick-ai/delete-media-permanently` | Agent can separate read-only cleanup/SEO/format recommendations, local uploads URL-to-attachment candidate resolution, reviewable dry-run media remediation plans, recorded backup listing, Cloud derivative request contracts with optional image or text watermark instructions, bounded batch derivative candidate plans, rename plans with hash guards and exact post-content reference patches, and host-approved metadata updates, derivative generation, recorded-derivative replacement, original-URL media backup restore, main-file rename, Cloud derivative adoption with dedicated uploads backup/rollback metadata, or destructive deletes. | `composer test`, `composer smoke:wp` single-ability runs for read, planning, and governed dry-run context. | Read and planning side accepted; write/destructive side remains host approval only. |
| Taxonomy consolidation | `magick-ai/get-taxonomy-consolidation-suggestions`, `magick-ai/propose-post-taxonomy-terms`, `magick-ai/create-term`, `magick-ai/update-term`, `magick-ai/set-post-terms`, `magick-ai/merge-terms`, `magick-ai/delete-term` | Agent can propose taxonomy cleanup from read-only signals, build a bounded post taxonomy assignment proposal, and hand final mutations to host-governed write/destructive abilities. | `composer test`, `composer smoke:wp` read/proposal-side runs; `magick-ai-core` taxonomy terms preview consumer smoke. | Read and proposal side accepted. Core consumer proof found no additional schema, metadata, or ability contract gap. Final mutation execution remains host-governed. |
| Diagnostics and support | `npcink-abilities-toolkit/wp-diagnostics-summary`, `npcink-abilities-toolkit/wp-ops-diagnostics-detail`, `magick-ai/site-info` | Agent can inspect redacted WordPress-only environment state, bounded plugin groups, caller/runtime details, log severity and safe source summaries with optional structured contents, object/page-cache state, rewrite/HTTPS/server/database/cron details, integration hints, and bounded SEO/security/performance operations summaries without leaking secrets, paths, unredacted log contents, database names, or Magick AI internals. | `composer test:all`, `composer perf:smoke`, `composer smoke:wp`, targeted WP-CLI ability sampling. | Accepted. |

## Gap Policy

Use this policy before creating a new ability:

1. Prefer composing existing abilities in the host workflow.
2. Add a read-only aggregator only when repeated consumers need the same
   bounded context bundle.
3. Add a write-proposal helper only when the output is still a proposal and
   does not commit.
4. Do not expose new third-party host-governed commit helpers without a new ADR.

Current known gaps:

- No active fourth-batch ability gap is open after the taxonomy terms preview
  consumer proof. Keep this package in freeze/observe mode until Core or a host
  workflow reports a concrete schema, metadata, or contract gap.
- Final host-side execution validation for taxonomy write/destructive chains
  belongs in Magick AI or another host runtime, not this package.
- Media write/destructive approval UX belongs in the host runtime.
- A future fourth ability batch should be limited to gaps discovered while
  validating these workflows, ideally three to five abilities at most.
- The broad `magick-ai/*` compatibility namespace remains in place for migrated
  ids. Future namespace cleanup needs explicit deprecation/successor metadata
  and host migration tests before renaming.
