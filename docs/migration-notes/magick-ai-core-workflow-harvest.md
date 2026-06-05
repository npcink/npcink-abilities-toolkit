# Npcink AI Core Workflow Harvest

Status: source harvest for future host workflow rewrites.

Date: 2026-05-29.

Source snapshot: Npcink AI Core while it is being narrowed to "the WordPress AI
operation governance layer." The goal of this harvest is to keep useful workflow
knowledge without carrying Core runtime ownership into this package.

## Product Split

Use this split when rewriting any harvested workflow:

| Destination | Owns | Does not own |
| --- | --- | --- |
| `npcink-abilities-toolkit` | WordPress Abilities API definitions, schemas, callbacks, dry-run previews, and documentation-only recipes. | Workflow execution state, approval records, Agent Gateway projection, model routing, Settings UI. |
| `magick-ai-content-assistant` | Article, media, and comment product UX, defaults, editor flows, preview/apply screens, and model-assisted content experience. | Generic Abilities API package ownership or Magick AI governance internals. |
| Npcink AI Core | Approval, audit, apply guard, app auth, quota, trace, Open Platform governance, and host runtime policy. | Content product workflows, reusable WordPress ability packages, generic writing/SEO/comment automation as a product surface. |

## Harvested Workflow Groups

### 1. Content Tag Completion

Old Core surface:

- `workflow/content_tag_completion`
- skill manifest: `content_tag_completion`

Useful value:

- classify an existing post into candidate tags;
- separate tag proposal from taxonomy write;
- keep explanations for each candidate so editors can review.

Rewrite as:

1. Read post context with `npcink-abilities-toolkit/get-post-context`.
2. Read existing taxonomy terms with `npcink-abilities-toolkit/list-tags` or
   `npcink-abilities-toolkit/list-taxonomy-terms`.
3. Generate a proposal in the host product or a future proposal-only ability.
4. Preview taxonomy writes with `npcink-abilities-toolkit/set-post-terms`.
5. Commit only through host approval.

Do not keep the old Core workflow runtime or Agent Gateway skill manifest.

### 2. Content Summary And SEO Suggestion

Old Core surface:

- `workflow/content_summary_seo_completion`
- skill manifest: `content_summary_seo`

Useful value:

- summarize an existing article;
- propose excerpt and SEO metadata;
- avoid directly writing content from the summary step.

Rewrite as:

1. Read `npcink-abilities-toolkit/get-post-context`.
2. Use `npcink-abilities-toolkit/propose-post-excerpt` for bounded excerpt proposal when
   sufficient.
3. Let the host call its model layer for abstractive summary or SEO copy.
4. Use `npcink-abilities-toolkit/set-post-seo-meta` or `npcink-abilities-toolkit/update-post` in dry-run mode.
5. Commit only with host approval.

Do not move `npcink-abilities-toolkit/generate-excerpt` or `npcink-abilities-toolkit/seo-meta-generate` into
this package; they depend on model/provider routing.

### 3. Pre-Publish Report

Old Core surface:

- `workflow/content_pre_publish_report`
- skill manifest: `content_pre_publish_report`

Useful value:

- combine publish checklist, risk, SEO/GEO readiness, and calendar context;
- stop before scheduling or publishing.

Rewrite as the existing `workflow/wordpress_article_publish_preflight` recipe:

1. Prefer `npcink-abilities-toolkit/get-article-publish-preflight-context`.
2. Expand to `npcink-abilities-toolkit/get-content-publishing-checklist`,
   `npcink-abilities-toolkit/get-post-publish-risk-report`,
   `npcink-abilities-toolkit/build-article-workflow-context`, and
   `npcink-abilities-toolkit/get-publishing-calendar-context` only when needed.
3. Use `npcink-abilities-toolkit/schedule-post` or `npcink-abilities-toolkit/publish-post` as dry-run first.
4. Commit only through a host approval surface.

### 4. Existing Article Optimization

Old Core surfaces:

- `workflow/article_optimization_report`
- `workflow/article_single_optimization_suggest`
- `workflow/article_optimization_apply`
- skill manifests: `wordpress_article_optimization_report`,
  `wordpress_article_single_optimization_suggest`,
  `wordpress_article_optimization_apply`

Useful value:

- read optimization context before suggesting changes;
- separate suggestion, apply plan, dry-run preview, and approved commit;
- support partial application rather than all-or-nothing rewrites.

Rewrite as the existing `workflow/wordpress_article_optimization` recipe:

1. `npcink-abilities-toolkit/read-post-optimization-context`
2. `npcink-abilities-toolkit/seo-report-context`
3. `npcink-abilities-toolkit/build-article-single-optimization-suggest`
4. `npcink-abilities-toolkit/build-article-optimization-apply-plan`
5. `npcink-abilities-toolkit/compose-article-optimization-apply-result`
6. Optional dry-run with `npcink-abilities-toolkit/patch-post-content`,
   `npcink-abilities-toolkit/set-post-seo-meta`, or `npcink-abilities-toolkit/update-post-blocks`

Host policy decides which suggestions are allowed to commit.

### 5. Article Draft And Production

Old Core surfaces:

- `workflow/wordpress_article_draft`
- `workflow/wordpress_article_media`
- `workflow/wordpress_article_production`
- skill manifests: `wordpress_article_draft`, `wordpress_article_media`,
  `wordpress_article_production`

Useful value:

- assemble metadata, internal links, media SEO assets, duplicate checks, style
  baseline, and publication decision as separate handoffs;
- support draft-only, media-only, and production-grade flows;
- stop before create, patch, schedule, or publish unless a host authorizes the
  write.

Rewrite as Content Assistant product flows backed by documentation recipes:

- article draft: use `workflow/wordpress_article_draft`;
- article production: use `workflow/wordpress_article_production`;
- article media: keep as a future recipe that composes
  `npcink-abilities-toolkit/build-media-seo-assets`,
  `npcink-abilities-toolkit/build-inline-image-blocks`,
  `npcink-abilities-toolkit/position-inline-image-blocks`, `npcink-abilities-toolkit/upload-media-from-url`,
  `npcink-abilities-toolkit/update-media-details`, and `npcink-abilities-toolkit/set-post-featured-image`.

Do not let this package own image generation, source selection, public image
provider policy, article editor UX, or publish policy.

### 6. Media Alt And Media SEO

Old Core surfaces:

- `workflow/media-alt-single-suggest`
- `workflow/media_alt_completion`
- `workflow/media_seo_enrichment`
- `workflow/media_nightly_image_optimize`
- `workflow/media_format_convert_webp_avif`
- `workflow/batch_media_optimize`
- skill manifests: `media_alt_single_suggest`, `media_alt_completion`,
  `media_seo_enrichment`, `media_nightly_optimize`

Useful value:

- read media inventory before action;
- produce metadata suggestions with origin/license/disclosure fields;
- separate per-asset suggestions from batch scheduling and final writeback.

Rewrite as:

1. Read media context with `npcink-abilities-toolkit/list-media` and
   `npcink-abilities-toolkit/get-media-inventory-health`.
2. Build metadata proposals with `npcink-abilities-toolkit/build-media-seo-assets` and
   `npcink-abilities-toolkit/optimize-media-metadata`.
3. Preview writes with `npcink-abilities-toolkit/update-media-details`.
4. Let the host own batch selection, scheduling, retry, and commit approval.

Do not migrate nightly/batch consoles or format conversion policy into this
package. If format conversion remains valuable, document it separately as a
host-governed media operations recipe.

### 7. Comment Moderation And Reply

Old Core surfaces:

- `workflow/comment-moderation`
- `workflow/comment-moderation-batch-suggest`
- `workflow/comment-mention-reply-suggest`
- skill manifests: `comment_moderation`, `comment_moderation_batch_suggest`,
  `comment_trigger_queue_read`, `comment_mention_reply_suggest`

Useful value:

- produce safe moderation suggestions before action;
- keep batch review suggestion-only;
- compose reply text separately from final `reply-comment` commit;
- preserve a human-review path for ambiguous comments.

Rewrite as the existing `workflow/wordpress_comment_compliance_handoff` recipe:

1. Prefer `npcink-abilities-toolkit/get-comment-compliance-handoff`.
2. Expand to `npcink-abilities-toolkit/get-comment-queue-health`,
   `npcink-abilities-toolkit/get-comment-action-priority-queue`,
   `npcink-abilities-toolkit/build-comment-moderation-suggest`, and
   `npcink-abilities-toolkit/build-comment-mention-reply-suggest` when needed.
3. Compose handoff with `npcink-abilities-toolkit/compose-comment-moderation-result`.
4. Preview `npcink-abilities-toolkit/approve-comment`, `npcink-abilities-toolkit/reply-comment`,
   `npcink-abilities-toolkit/spam-comment`, or `npcink-abilities-toolkit/trash-comment`.
5. Commit only through host approval.

### 8. Operations, Cleanup, And Page Structure

Old Core surfaces:

- `workflow/wp_env_diagnostics`
- `workflow/page_structure_inspection`
- `workflow/maintenance_cleanup_old_drafts`
- `workflow/maintenance_taxonomy_quality_governance`

Useful value:

- redacted WordPress-only diagnostics;
- page structure readout;
- cleanup candidate discovery;
- taxonomy consolidation suggestions.

Rewrite as:

- diagnostics: `npcink-abilities-toolkit/wp-diagnostics-summary` and
  `npcink-abilities-toolkit/site-info`;
- page structure: `npcink-abilities-toolkit/inspect-page-structure` and
  `npcink-abilities-toolkit/get-page-structure-health`;
- cleanup discovery: `npcink-abilities-toolkit/get-media-cleanup-opportunities`,
  `npcink-abilities-toolkit/get-taxonomy-consolidation-suggestions`, and related read-only
  health abilities;
- destructive cleanup: host-owned recipe using dry-run destructive abilities.

Do not migrate operator consoles, maintenance queues, or WordPress admin
operations dashboards into this package.

## Recommended Rewrite Order

1. Keep the three already-stabilized recipes as the first validation line:
   article publish preflight, old article refresh discovery, and comment
   compliance handoff.
2. Rewrite existing article optimization next because it has the clearest
   read/proposal/apply split.
3. Rewrite article draft and article production as Content Assistant product
   flows that consume abilities from this package.
4. Rewrite media alt and media SEO as proposal-first media recipes.
5. Rewrite tag/taxonomy helpers only after deciding whether taxonomy proposal
   generation belongs to a deterministic ability or a host model call.
6. Keep cleanup and maintenance as read-only discovery until a host governance
   surface is ready for destructive commits.

## Acceptance Rule For Future Rewrites

A rewritten workflow may be documented here only when each step can be expressed
as one of:

- WordPress ability discovery;
- read-only ability execution;
- proposal helper output;
- dry-run preview from a write/destructive ability;
- host-owned approval request;
- host-owned commit result.

If a step requires persisted runtime state, queue ownership, model routing,
prompt ownership, Agent Gateway projection, quota, audit, or final commit
authorization, that step belongs in the consuming host.
