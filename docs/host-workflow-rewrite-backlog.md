# Host Workflow Rewrite Backlog

Status: planning reference for hosts that consume `magick-ai-abilities`.

This backlog converts useful Magick AI Core workflow ideas into host-side
rewrite candidates. It is not a package runtime and does not require hosts to
use these recipe ids.

## How To Use This Backlog

For each candidate:

1. Discover ability availability through WordPress Abilities API.
2. Prefer bundled context abilities before calling many individual reads.
3. Stop at proposal or dry-run preview unless the host has approval authority.
4. Keep model calls, prompt selection, user interaction, audit, quota,
   idempotency, and final commit state in the host.
5. Add package code only when a missing step is a stable WordPress ability, not
   a product workflow.

## Rewrite Candidates

| Priority | Candidate | Package value | Product host |
| --- | --- | --- | --- |
| P0 | Article publish preflight | Already represented by read-only publishing context abilities and recipe docs. | Magick AI Core, Content Assistant, or another publisher host. |
| P0 | Old article refresh discovery | Already represented by refresh, SEO/GEO gap, style, and link graph abilities. | Content Assistant. |
| P0 | Comment compliance handoff | Already represented by comment queue, priority, suggestion, and handoff abilities. | Content Assistant or comment compliance host. |
| P1 | Existing article optimization | Good recipe fit: context -> suggestion -> apply plan -> dry-run write -> host approval. | Content Assistant. |
| P1 | Article draft handoff | Good recipe fit, but model generation and editor UX belong to Content Assistant. | Content Assistant. |
| P1 | Article production mainline | Good recipe fit for duplicate, style, review, media, and publish decision handoffs. | Content Assistant. |
| P2 | Article media handoff | Good ability fit for media SEO assets, inline image blocks, upload preview, metadata preview. | Content Assistant. |
| P2 | Media alt and SEO enrichment | Good proposal-first ability fit; batch scheduling belongs to the host. | Content Assistant or media tools host. |
| P2 | Content tag completion | Useful candidate, but taxonomy proposal generation needs a stable schema decision. | Content Assistant or editorial taxonomy host. |
| P3 | Cleanup old drafts | Keep as discovery until a host governance screen exists for destructive actions. | Toolbox or an operations host, not Magick AI Core by default. |
| P3 | Taxonomy quality governance | Keep as read-only consolidation suggestions first. | Toolbox or editorial taxonomy host. |
| P3 | Media format conversion | Needs host policy for storage, backups, image quality, and rollback. | Media operations host. |

## Recipe Stubs

### Existing Article Optimization

Recommended entry: `workflow/wordpress_article_optimization`.

Ability chain:

1. `magick-ai/read-post-optimization-context`
2. `magick-ai/seo-report-context`
3. `magick-ai/build-article-single-optimization-suggest`
4. `magick-ai/build-article-optimization-apply-plan`
5. `magick-ai/compose-article-optimization-apply-result`
6. Optional dry-run: `magick-ai/patch-post-content`,
   `magick-ai/set-post-seo-meta`, or `magick-ai/update-post-blocks`

Host-owned pieces:

- model rewrite;
- selected edits;
- editor preview;
- approval and commit.

### Article Media Handoff

Recommended future entry: `workflow/wordpress_article_media_handoff`.

Ability chain:

1. `magick-ai/get-post-context`
2. `magick-ai/build-media-seo-assets`
3. `magick-ai/build-inline-image-blocks`
4. `magick-ai/position-inline-image-blocks`
5. Optional dry-run: `magick-ai/upload-media-from-url`
6. Optional dry-run: `magick-ai/update-media-details`
7. Optional dry-run: `magick-ai/set-post-featured-image`

Host-owned pieces:

- source image policy;
- image generation or provider selection;
- copyright/license policy;
- editor placement approval;
- final media writes.

### Media Alt And SEO Enrichment

Recommended future entry: `workflow/wordpress_media_seo_handoff`.

Ability chain:

1. `magick-ai/list-media`
2. `magick-ai/get-media-inventory-health`
3. `magick-ai/build-media-seo-assets`
4. `magick-ai/optimize-media-metadata`
5. Optional dry-run: `magick-ai/update-media-details`

Host-owned pieces:

- batch selection;
- model-generated alt text when deterministic context is insufficient;
- per-asset review;
- scheduled retries;
- final write approval.

### Content Tag Completion

Recommended future entry: `workflow/wordpress_content_tag_proposal`.

Ability chain:

1. `magick-ai/get-post-context`
2. `magick-ai/list-tags`
3. `magick-ai/list-taxonomy-terms`
4. Future proposal-only taxonomy helper if it can be deterministic enough.
5. Optional dry-run: `magick-ai/set-post-terms`

Host-owned pieces:

- editorial taxonomy policy;
- model classification when needed;
- duplicate term handling;
- final term assignment.

### Cleanup Old Drafts

Recommended future entry: `workflow/wordpress_draft_cleanup_discovery`.

Ability chain:

1. `magick-ai/get-content-inventory-health`
2. `magick-ai/list-posts`
3. `magick-ai/get-revision-change-risk-report`
4. Optional dry-run: `magick-ai/trash-post`
5. Optional destructive dry-run: `magick-ai/delete-post-permanently`

Host-owned pieces:

- retention policy;
- exclusions;
- backup/export policy;
- destructive commit approval.

## Promotion Criteria

Move a backlog item into `docs/workflow-recipes.md` only after:

- every listed ability exists or the missing ability has a small, reusable,
  WordPress-only definition;
- the recipe can be validated without Magick AI Core internals;
- write/destructive steps are preview-first;
- host approval and commit state are explicitly outside this package;
- tests or smoke fixtures prove the chain through WordPress Abilities API
  discovery and execution.
