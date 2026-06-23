# Ability Namespace Contract Reset

Status: accepted direct reset for the current development phase. No
compatibility alias layer is planned for this repository.

## Purpose

The repository and brand rename is separate from ability contract ownership, but
this project has no external compatibility burden yet. New runtime calls,
fixtures, proposal handoffs, and documentation must use the canonical
`npcink-abilities-toolkit/*` ability ids directly.

Legacy `magick-ai/*` ids are invalid for new active code paths. Do not add
aliases, normalization tables, or Cloud-owned migration maps to keep them alive.

## Current Truth

| Surface | Truth owner | Direct reset responsibility |
| --- | --- | --- |
| Ability definitions and schemas | `npcink-abilities-toolkit` | Publish only canonical `npcink-abilities-toolkit/*` ids for this package. |
| Proposal, approval, preflight, and audit truth | `npcink-governance-core` | Store and preflight canonical ids supplied by current callers. |
| AI-client channel execution | `npcink-ai-client-adapter` | Execute canonical Toolkit ids after Core authorization. |
| Product UI and local operator flows | `npcink-toolbox` | Display and submit canonical ids in new handoff payloads. |
| Cloud runtime/detail | `npcink-ai-cloud` / `npcink-cloud-addon` | No ability truth. Cloud may echo ids supplied by local components but must not own ability migration state. |

## Site Baseline

The primary local deployment and release-test site is:

```text
https://magick-ai.local/
/Users/muze/Local Sites/magick-ai/app/public
```

`http://npcink.local/` and `/Users/muze/Local Sites/npcink/app/public` are
temporary isolation fixtures for rename/acceptance experiments. They must not be
used as the canonical migration acceptance target unless a test explicitly says
it is running in an isolated throwaway site.

## Direct Replacement Rules

| Retired id | Required current contract |
| --- | --- |
| `magick-ai/create-draft` is invalid for new runtime calls. | Use `npcink-abilities-toolkit/create-draft` through Core proposal, approval, and preflight. |
| `magick-ai/update-post` is invalid for new runtime calls. | Use `npcink-abilities-toolkit/update-post` through Core proposal, approval, and preflight. |
| `magick-ai/update-post-blocks` is invalid for new runtime calls. | Use `npcink-abilities-toolkit/update-post-blocks` through Core proposal, approval, and preflight. |
| `magick-ai/workflows/generate-post-draft` is invalid for new runtime calls. | Use `npcink-abilities-toolkit/build-article-block-plan`, then submit governed `create-draft` / `update-post-blocks` actions through Core. |

Same-slug retired read ids follow the same rule: replace `magick-ai/<slug>`
with `npcink-abilities-toolkit/<slug>` only when that exact Toolkit ability
exists in the current catalog. Non-matching retired ids need an explicit
contract owner decision; do not invent Cloud-side stand-ins.

Recipe refs such as `workflow/wordpress_article_draft` are not WordPress ability
ids. Keep them as recipe references until a separate recipe-id contract reset is
approved and tested across Toolkit, Core, Adapter, and Toolbox.

## Audit Gate

Active files should stay free of retired `magick-ai/*` ability ids:

```bash
php scripts/audit-legacy-ability-ids.php
```

The audit fails on current, non-archive project files that still contain retired
ability ids. It intentionally does not create alias maps.

## Acceptance Requirements

| Repository | Required gate |
| --- | --- |
| `npcink-abilities-toolkit` | Unit tests pass; `composer check:legacy-ability-ids` passes; catalog snapshots expose only canonical Toolkit ids. |
| `npcink-governance-core` | Proposal create/detail/preflight tests use canonical ids and continue to preserve recipe refs where applicable. |
| `npcink-ai-client-adapter` | Execution profile and health contract tests use canonical Toolkit ids. |
| `npcink-toolbox` | Product handoff smoke submits canonical ids for new proposal payloads. |
| Cross-repo | `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" composer acceptance:cross-repo-release` passes against the primary local site. |

## Non-Goals

- Do not add compatibility aliases for old ability ids in the current
  development phase.
- Do not let Cloud own aliases, canonicalization, workflow entry mapping, or
  migration state.
- Do not convert recipe refs such as `workflow/wordpress_article_draft` in this
  ability-id reset.
- Do not change WooCommerce addon `magick-ai/wc-*` ids here. Commerce add-on
  namespace migration needs its own owner decision.
