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
- no Npcink AI runtime, model routing, quota, audit, or workflow ownership;
- REST discovery and execution through WordPress Abilities API;
- smoke coverage when the ability is part of a top-level workflow.
- assignment to a built-in package or sub-pack so hosts can disable optional
  workflow helpers without losing the generic WordPress read surface.

## Matrix

| Task surface | Primary abilities | Acceptance criteria | Automated coverage | Status |
| --- | --- | --- | --- | --- |
| Site operations scan | `npcink-abilities-toolkit/get-site-operations-dashboard`, `npcink-abilities-toolkit/get-content-inventory-health`, `npcink-abilities-toolkit/get-media-inventory-health`, `npcink-abilities-toolkit/get-taxonomy-inventory-health`, `npcink-abilities-toolkit/get-page-structure-health` | Agent can identify content, media, taxonomy, and page-structure attention areas without writes. | `composer test`, `composer smoke:wp` single-ability runs. | Accepted for 0.3 stabilization. |
| Content remediation planning | `npcink-abilities-toolkit/get-nonproduction-content-inventory`, `npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan`, `npcink-abilities-toolkit/build-content-inventory-fix-plan`, `npcink-abilities-toolkit/set-post-seo-meta`, `npcink-abilities-toolkit/set-post-slug`, `npcink-abilities-toolkit/update-post`, `npcink-abilities-toolkit/set-post-featured-image`, `npcink-abilities-toolkit/trash-post`, `npcink-abilities-toolkit/delete-term`, `npcink-abilities-toolkit/trash-comment` | Agent can turn detected nonproduction content and content inventory gaps into bounded, reviewable dry-run actions that reuse existing governed write/destructive abilities without committing. | `composer test:all`, `composer smoke:wp` planning-side runs. | Read and planning side accepted. Final mutation execution remains host-governed. |
| Article publish preflight | `npcink-abilities-toolkit/get-article-publish-preflight-context`, `npcink-abilities-toolkit/get-post-context`, `npcink-abilities-toolkit/get-content-publishing-checklist`, `npcink-abilities-toolkit/get-post-publish-risk-report`, `npcink-abilities-toolkit/build-article-workflow-context`, `npcink-abilities-toolkit/get-publishing-calendar-context` | Agent can assemble publish readiness, risk, and calendar context; final publish/schedule remains host-governed. | `composer test`, `composer perf:smoke`, `composer smoke:wp` workflow assertion. | Accepted for workflow validation. |
| Old article refresh discovery | `npcink-abilities-toolkit/get-old-article-refresh-context`, `npcink-abilities-toolkit/get-content-refresh-opportunities`, `npcink-abilities-toolkit/get-seo-geo-gap-report`, `npcink-abilities-toolkit/get-site-style-baseline`, `npcink-abilities-toolkit/get-internal-link-graph-health`, `npcink-abilities-toolkit/get-internal-link-opportunity-report` | Agent can identify refresh candidates, SEO/GEO gaps, style baseline, and link opportunities without mutating posts. | `composer test`, `composer perf:smoke`, `composer smoke:wp` workflow assertion. | Accepted for workflow validation. |
| Comment compliance handoff | `npcink-abilities-toolkit/get-comment-compliance-handoff`, `npcink-abilities-toolkit/get-comment-queue-health`, `npcink-abilities-toolkit/get-comment-action-priority-queue`, `npcink-abilities-toolkit/build-comment-moderation-suggest`, `npcink-abilities-toolkit/build-comment-mention-reply-suggest`, `npcink-abilities-toolkit/compose-comment-moderation-result` | Agent can prioritize comments and prepare moderation/reply suggestions; approve, reply, spam, and trash remain host-governed. | `composer test`, `composer perf:smoke`, `composer smoke:wp` workflow assertion. | Accepted for workflow validation. |
| Workflow recipe discovery | `npcink-abilities-toolkit/list-workflow-recipes`, `npcink-abilities-toolkit/get-workflow-recipe` | Host can discover read-only workflow recipe definitions at runtime without this package owning workflow execution, approval, audit, quota, model routing, prompts, or final writes. | `composer test`, `composer smoke:wp` discovery runs. | Accepted for runtime discovery. |
| Media governance | `npcink-abilities-toolkit/get-media-cleanup-opportunities`, `npcink-abilities-toolkit/build-media-inventory-fix-plan`, `npcink-abilities-toolkit/build-media-seo-assets`, `npcink-abilities-toolkit/optimize-media-metadata`, `npcink-abilities-toolkit/resolve-media-attachment-by-url`, `npcink-abilities-toolkit/inspect-media-asset`, `npcink-abilities-toolkit/list-media-backups`, `npcink-abilities-toolkit/build-media-derivative-cloud-request`, `npcink-abilities-toolkit/build-media-derivative-batch-plan`, `npcink-abilities-toolkit/build-media-optimization-plan`, `npcink-abilities-toolkit/build-media-rename-plan`, `npcink-abilities-toolkit/update-media-details`, `npcink-abilities-toolkit/optimize-media-asset`, `npcink-abilities-toolkit/replace-media-file`, `npcink-abilities-toolkit/restore-media-backup`, `npcink-abilities-toolkit/rename-media-file`, `npcink-abilities-toolkit/adopt-cloud-media-derivative`, `npcink-abilities-toolkit/delete-media-permanently` | Agent can separate read-only cleanup/SEO/format recommendations, local uploads URL-to-attachment candidate resolution, reviewable dry-run media remediation plans, recorded backup listing, Cloud derivative request contracts with optional image or text watermark instructions, bounded batch derivative candidate plans, optimization plans that preview inline content reference repairs inside derivative evidence without a separate patch action and carry reviewed repair post/count expectations into adoption input, rename plans with hash guards and exact post-content reference patches, and host-approved metadata updates, derivative generation, recorded-derivative replacement with exact post-content reference repairs, original-URL media backup restore, main-file rename, Cloud derivative adoption with dedicated uploads backup/rollback metadata and old main/intermediate-size URL repairs, or destructive deletes. | `composer test`, `composer smoke:wp` single-ability runs for read, planning, and governed dry-run context. | Read and planning side accepted; write/destructive side remains host approval only. |
| Taxonomy consolidation | `npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions`, `npcink-abilities-toolkit/propose-post-taxonomy-terms`, `npcink-abilities-toolkit/create-term`, `npcink-abilities-toolkit/update-term`, `npcink-abilities-toolkit/set-post-terms`, `npcink-abilities-toolkit/merge-terms`, `npcink-abilities-toolkit/delete-term` | Agent can propose taxonomy cleanup from read-only signals, build a bounded post taxonomy assignment proposal, and hand final mutations to host-governed write/destructive abilities. | `composer test`, `composer smoke:wp` read/proposal-side runs; `npcink-ai-core` taxonomy terms preview consumer smoke. | Read and proposal side accepted. Core consumer proof found no additional schema, metadata, or ability contract gap. Final mutation execution remains host-governed. |
| Diagnostics and support | `npcink-abilities-toolkit/wp-diagnostics-summary`, `npcink-abilities-toolkit/wp-ops-diagnostics-detail`, `npcink-abilities-toolkit/site-info` | Agent can inspect redacted WordPress-only environment state, bounded plugin groups, caller/runtime details, log severity and safe source summaries with optional structured contents, object/page-cache state, rewrite/HTTPS/server/database/cron details, integration hints, and bounded SEO/security/performance operations summaries without leaking secrets, paths, unredacted log contents, database names, or Npcink AI internals. | `composer test:all`, `composer perf:smoke`, `composer smoke:wp`, targeted WP-CLI ability sampling. | Accepted. |

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
  belongs in Npcink AI or another host runtime, not this package.
- Media write/destructive approval UX belongs in the host runtime.
- A future fourth ability batch should be limited to gaps discovered while
  validating these workflows, ideally three to five abilities at most.
- The broad `npcink-abilities-toolkit/*` compatibility namespace remains in place for migrated
  ids. Future namespace cleanup needs explicit deprecation/successor metadata
  and host migration tests before renaming.
