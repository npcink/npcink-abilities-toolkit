# Ability Namespace Migration Map

Status: planning map only. No ability ids are changed by this document.

## Purpose

The repository and brand rename is separate from the governance ability id
migration. Ability ids are public contract keys, not labels. A mechanical rename
would break Core proposals, Adapter execution profiles, Toolbox handoff payloads,
WordPress Abilities API clients, MCP selection hints, and stored audit records.

This map defines the next migration path from legacy `magick-ai/*` identifiers to
the current `npcink-abilities-toolkit/*` catalog without creating a second
ability registry or moving governance truth out of Core.

## Current Truth

| Surface | Truth owner | Migration responsibility |
| --- | --- | --- |
| Ability definitions and schemas | `npcink-abilities-toolkit` | Publish canonical ids, alias metadata, deprecation metadata, and catalog fingerprints. |
| Proposal, approval, preflight, and audit truth | `npcink-governance-core` | Accept aliases only through an explicit compatibility layer and persist both requested and canonical ids during the window. |
| AI-client channel execution | `npcink-ai-client-adapter` | Normalize inbound aliases before profile validation, but execute only canonical approved ids after Core preflight. |
| Product UI and local operator flows | `npcink-toolbox` | Display canonical ids and use aliases only for reading older persisted records. |
| Cloud runtime/detail | `npcink-ai-cloud` / `npcink-cloud-addon` | No ability truth. Cloud may echo ids supplied by local components but must not own migration maps. |

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

## Migration Phases

| Phase | Scope | Expected behavior |
| --- | --- | --- |
| P0 map only | Generate and review the alias map. | No runtime behavior changes. Cross-repo tests assert the map exists and excludes Cloud ownership. |
| P1 read aliases | Direct-read and deterministic planning aliases. | `magick-ai/<slug>` may resolve to `npcink-abilities-toolkit/<slug>` with deprecation metadata. Canonical responses prefer the Npcink id. |
| P2 governed write aliases | Host-governed write and destructive aliases. | Core stores requested legacy id and canonical id, preflight binds the canonical id, Adapter executes canonical Toolkit id only after approval. |
| P3 workflow entry aliases | Legacy workflow entry ids. | Workflow ids are mapped to existing Toolkit recipe or plan ability ids. No second workflow registry is introduced. |
| P4 deprecation closeout | Remove old public aliases after documented consumer cutover. | Historical audit/proposal records remain readable. New writes must use canonical ids. |

## P0 Direct Same-Slug Map

Use this generated source of truth for the full direct alias candidate set:

```bash
MAGICK_AI_LEGACY_ROOT=/Users/muze/gitee/magick-ai-root/magick-ai \
php scripts/dump-ability-namespace-map.php
```

The script loads the current normalized Toolkit catalog and emits only exact
same-slug candidates, for example:

| Legacy id | Canonical id | Phase |
| --- | --- | --- |
| `magick-ai/site-info` | `npcink-abilities-toolkit/site-info` | P1 |
| `magick-ai/list-posts` | `npcink-abilities-toolkit/list-posts` | P1 |
| `magick-ai/get-post` | `npcink-abilities-toolkit/get-post` | P1 |
| `magick-ai/get-post-blocks` | `npcink-abilities-toolkit/get-post-blocks` | P1 |
| `magick-ai/build-article-optimization-apply-plan` | `npcink-abilities-toolkit/build-article-optimization-apply-plan` | P1 |
| `magick-ai/build-content-inventory-fix-plan` | `npcink-abilities-toolkit/build-content-inventory-fix-plan` | P1 |
| `magick-ai/build-media-inventory-fix-plan` | `npcink-abilities-toolkit/build-media-inventory-fix-plan` | P1 |
| `magick-ai/route-content-intent` | `npcink-abilities-toolkit/route-content-intent` | P1 |
| `magick-ai/create-draft` | `npcink-abilities-toolkit/create-draft` | P2 |
| `magick-ai/update-post` | `npcink-abilities-toolkit/update-post` | P2 |
| `magick-ai/set-post-seo-meta` | `npcink-abilities-toolkit/set-post-seo-meta` | P2 |
| `magick-ai/set-post-terms` | `npcink-abilities-toolkit/set-post-terms` | P2 |
| `magick-ai/update-post-blocks` | `npcink-abilities-toolkit/update-post-blocks` | P2 |
| `magick-ai/update-media-details` | `npcink-abilities-toolkit/update-media-details` | P2 |
| `magick-ai/upload-media-from-url` | `npcink-abilities-toolkit/upload-media-from-url` | P2 |
| `magick-ai/trash-post` | `npcink-abilities-toolkit/trash-post` | P2 |
| `magick-ai/approve-comment` | `npcink-abilities-toolkit/approve-comment` | P2 |
| `magick-ai/reply-comment` | `npcink-abilities-toolkit/reply-comment` | P2 |
| `magick-ai/trash-comment` | `npcink-abilities-toolkit/trash-comment` | P2 |
| `magick-ai/delete-media-permanently` | `npcink-abilities-toolkit/delete-media-permanently` | P2 |

Rows such as `magick-ai/includes`, `magick-ai/v1`, paths, test fixtures, demo
ids, and historical docs are extraction noise unless the generated script maps
them to a real current Toolkit catalog id.

## Workflow Entry Map

Workflow entry ids are not WordPress ability ids. They must map to recipe or
planning ability contracts, not to Cloud and not to a second workflow registry.

| Legacy workflow entry | Canonical consumer path | Phase | Notes |
| --- | --- | --- | --- |
| `magick-ai/workflows/generate-post-draft` | `npcink-abilities-toolkit/build-article-block-plan` plus governed `create-draft` / `update-post-blocks` actions | P3 | Requires Core proposal handoff tests; do not direct-write. |
| `workflow/wordpress_article_draft` | `npcink-abilities-toolkit/build-article-block-plan` or `npcink-abilities-toolkit/build-pattern-page-plan` depending on routed content intent | P3 | Existing recipe ids may stay as recipe refs while ability ids canonicalize. |
| `workflow/wordpress_article_media` | `npcink-abilities-toolkit/build-media-seo-assets` and media handoff plan abilities | P3 | Cloud image/media derivative detail remains suggestion/runtime only. |
| `workflow/wordpress_article_production` | Article production read chain plus governed post/media actions | P3 | Requires explicit stop points and proposal persistence checks. |

## Acceptance Requirements

Before enabling aliases in runtime code:

| Repository | Required gate |
| --- | --- |
| `npcink-abilities-toolkit` | Unit test for generated map, no duplicate canonical ids, deprecation/successor metadata exposed in catalog. |
| `npcink-governance-core` | Proposal create/detail/preflight tests preserve `requested_ability_id` and canonical `ability_id`; audit filters remain stable. |
| `npcink-ai-client-adapter` | Health contract hash update, execution profile hash update, alias inbound test, canonical execution test. |
| `npcink-toolbox` | Product handoff smoke uses canonical ids on new proposals and can read old legacy-id records. |
| Cross-repo | `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public" composer acceptance:cross-repo-release` passes with `WP_CLI` and `WP_CLI_BIN` pointing at a valid WP-CLI binary. |

## Non-Goals

- Do not rename ability ids during repository, plugin slug, package, Cloud, or
  brand cleanup.
- Do not let Cloud own aliases, canonicalization, workflow entry mapping, or
  migration state.
- Do not remove legacy audit/proposal readability.
- Do not change WooCommerce addon `magick-ai/wc-*` ids in this map. Commerce
  add-on namespace migration needs its own owner decision.
