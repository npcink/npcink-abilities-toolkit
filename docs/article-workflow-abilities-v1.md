# Article Workflow Abilities v1

This document maps the recommended `article_draft_v1` composition path for
hosts such as Magick AI Toolbox, Adapter, and Core. Abilities provides reusable
WordPress callbacks and result builders. It does not provide a cloud writer,
article SaaS product, workflow runtime, approval store, batch publishing
console, or final write authorization.

## Ownership

- Toolbox owns the operator workbench and the fixed local flow artifact.
- Adapter owns OpenClaw channel adaptation and follows Core preflight guidance.
- Core owns proposal intake, approval, preflight, audit, and commit
  authorization.
- Abilities owns reusable WordPress ability callbacks and deterministic result
  builders.
- Cloud Addon may connect hosted runtime for separate enhanced tasks, but it
  does not own local article writing control.

## Recipe

Recipe id: `article_draft_v1`

Recipe ref: `workflow/wordpress_article_draft`

Recommended host artifact: `article_assistant_workbench`

Final governed write ability: `magick-ai/create-draft`

## Complexity Budget

Abilities should keep this recipe as reusable callback composition, not a
writing product. The current budget is one local article recipe, deterministic
read/review helpers, and one host-governed draft write callback.

It is not a writing product.

Do not add Abilities-owned article generation, Cloud article generation,
prompt-library, scheduler, batch writing, or workflow runtime behavior for this
recipe. If a host uses external model text, that text must return as a
reviewable artifact before Core proposal intake.

## Ability Map

| Stage | Ability | Owner | Notes |
| --- | --- | --- | --- |
| Site and post context | `magick-ai/site-info`, `magick-ai/get-post`, `magick-ai/search-posts`, `magick-ai/resolve-url-to-post` | Abilities | Read-only context inputs. |
| Internal link context | `magick-ai/resolve-internal-link-targets` | Abilities | Returns candidate internal targets; does not write links. |
| Style/context extraction | `magick-ai/extract-reference-post-style`, `magick-ai/extract-style-baseline`, `magick-ai/build-article-style-profile` | Abilities | Reusable style signals for a host workbench. |
| Media support | `magick-ai/build-inline-image-blocks`, `magick-ai/build-media-seo-assets`, `magick-ai/position-inline-image-blocks` | Abilities | Returns plans and block/metadata suggestions; no media import. |
| Production checks | `magick-ai/build-article-production-fingerprint`, `magick-ai/check-article-production-duplicate`, `magick-ai/review-article-output-light` | Abilities | Deterministic review and duplicate gates before proposal handoff. |
| Result composition | `magick-ai/compose-article-production-result`, `magick-ai/compose-article-draft-result`, `magick-ai/resolve-article-publication-decision` | Abilities | Composes host-reviewed output and decision artifacts. |
| Final write | `magick-ai/create-draft` | Abilities callback, Core governed | Must be submitted through Core proposal, approval, and preflight before commit. |

Provider or knowledge abilities such as `magick-ai-toolbox/web-research`,
`magick-ai-toolbox/search-image-source`,
`magick-ai-toolbox/search-site-knowledge`,
`magick-ai-toolbox/build-content-discoverability-brief`, and
`magick-ai-toolbox/build-article-assistant` belong to Toolbox, not this package.

## Non-Goals

Do not add first-party abilities named or shaped like:

- `draft-article-content`
- `generate-article`
- `cloud-write-article`
- `batch-publish-articles`
- `run-article-workflow`

Those names imply Abilities owns writing generation, cloud execution, batch
runtime, or workflow orchestration. Hosts can still compose read, review,
style, media, and write callbacks scientifically, then hand proposal-ready
plans to Core.

Also avoid softer aliases such as article generator, autonomous writer, hosted
article drafting, or Cloud writing assistant. Those labels create the same
product expectation even when the code path still returns dry-run artifacts.

Avoid hosted article drafting.

## Adapter and OpenClaw

OpenClaw benefits from the same map without receiving a special bypass. Adapter
can translate OpenClaw requests into the host workbench call sequence, ask Core
for preflight when a write-like action appears, and execute only the allowed
Abilities API callback after Core authorization. Natural-language article
requests should resolve to local workbench artifacts and Core proposals, not a
cloud-hosted writing endpoint.

## Legal Posture

Cloud Addon should not implement cloud article writing for this recipe. If a
host needs external model text, that is outside this package and must still
return draft text for human review before Core proposal intake. This package
keeps its role to reusable WordPress abilities, deterministic checks, and
governed callbacks.
