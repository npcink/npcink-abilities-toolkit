# Core Governance Handoff Guide

Status: active handoff guide.

This guide is the documentation-only handoff contract between
`magick-ai-abilities` and `magick-ai-core`. It tells Core, product plugins, and
future agents which first-party abilities are ready for Core governance
proposals and which operation surfaces are intentionally deferred.

This guide does not define runtime aliases, workflow routing, a second API
surface, or a product feature roadmap inside Core.

## Ownership Boundary

`magick-ai-abilities` owns:

- stable WordPress Abilities API ids;
- ability categories, schemas, annotations, risk metadata, and callbacks;
- read-only context helpers;
- write/destructive ability definitions that remain host-governed.

`magick-ai-core` owns:

- ability intake and normalized capability rows;
- proposal records;
- approval and rejection lifecycle state;
- commit preflight authorization;
- audit logs;
- minimal governance REST and admin surfaces.

Product plugins and host runtimes own:

- domain workflows;
- user-facing product UX;
- model and provider selection;
- final execution orchestration after Core approval policy is satisfied.

## Naming Rule

Runtime records must use real ability ids, such as `magick-ai/site-info` or
`magick-ai/create-draft`.

Planning labels such as `site/read`, `content/draft-preview`,
`seo/metadata-preview`, `comment/moderation-preview`, `cdn/purge-preview`, and
`backup/restore-preflight` are documentation labels only. Do not add a runtime
short-name mapping layer for them in Core or Abilities.

## Core Handoff Matrix

| Core governance intent | Current ability ids | Status | Core handling |
| --- | --- | --- | --- |
| `site/read` | `magick-ai/site-info`, `magick-ai/get-site-operations-dashboard`, `magick-ai-abilities/wp-diagnostics-summary` | Ready. | Treat as read-only intake and discovery context. Core may list or classify these abilities, but no proposal is required unless a host chooses to record review activity. |
| `content/draft-preview` | `magick-ai/create-draft` | Ready as a host-governed write ability. | Use the real ability id in proposals. Preview/dry-run output belongs to the ability or host runtime. Final write remains gated by Core approval and commit preflight. |
| `seo/metadata-preview` | `magick-ai/resolve-post-metadata-plan`, `magick-ai/set-post-seo-meta` | Ready. | Use read helper output for planning and the write ability id for proposals. Core must not invent SEO workflow orchestration. |
| `comment/moderation-preview` | `magick-ai/get-comment-compliance-handoff`, `magick-ai/build-comment-moderation-suggest`, `magick-ai/approve-comment`, `magick-ai/reply-comment`, `magick-ai/spam-comment`, `magick-ai/trash-comment` | Ready. | Suggestions and handoff context are read-side. Final comment actions are host-governed write/destructive abilities and should flow through proposal, approval, preflight, and audit. |
| `cdn/purge-preview` | None. | Deferred operations/toolbox candidate. | Do not implement this in the current content governance phase. A future provider or toolbox plugin may add a preflight ability before any purge execution exists. |
| `backup/restore-preflight` | No site-level backup/restore ability. `magick-ai/restore-post` covers post-level restore only. | Deferred operations/toolbox candidate. | Do not treat post restore as site backup restore. Site-level backup/restore preflight needs a separate ops contract before Core should govern it. |

## Ready Surfaces

Core can safely plan against these first:

- site and diagnostics reads;
- draft creation proposals;
- SEO metadata planning and write proposals;
- comment moderation and reply handoffs.

These are enough to validate the Core governance loop without adding product
features to Core.

## Deferred Surfaces

Keep these out of the current Core governance implementation:

- CDN purge preview and execution;
- site-level backup restore preflight and execution;
- workflow builders or runtime recipe execution;
- provider credentials, model routing, prompts, presets, billing, quota, or MCP
  runtime ownership.

These surfaces can become future product or provider plugin work only after an
explicit ability acceptance row and host-governance contract exist.

## Adding Future Handoff Abilities

Before adding a new handoff ability:

1. Add or update the task surface in `docs/ability-acceptance-matrix.md`.
2. Try composing existing abilities through the WordPress Abilities API first.
3. Describe any missing capability as a workflow gap, not just as a helper name.
4. Keep write/destructive completion host-governed.
5. Use a stable namespaced ability id and bounded schemas.
6. Do not add runtime short-name aliases.
7. Add smoke coverage when the ability participates in a top-level workflow.

## Core Usage Rules

Core should:

- ingest abilities read-only;
- store the real `ability_id` in proposal and audit records;
- use ability metadata to classify risk and approval requirements;
- fail closed when an approved proposal no longer matches an available ability;
- keep final write execution outside Core until the approval-commit contract is
  complete.

Core must not:

- execute abilities during intake or commit preflight;
- create fallback ability definitions;
- route natural language tasks to ability chains;
- map planning labels to ability ids at runtime;
- own CDN, backup, workflow, provider, or product UX concerns.
