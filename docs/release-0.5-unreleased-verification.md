# 0.5 Unreleased Verification

Status: observation checklist.
Date: 2026-05-30.

This note records the current `0.5.0` unreleased line after adding the taxonomy
terms proposal helper and starting the ability contract readiness gate. It is
intentionally not a release checklist yet. The goal is to preserve the consumer
proof, make clear that no additional ability batch should start without a
concrete workflow gap, and verify the registered first-party ability surface
directly.

## Scope

- Added `magick-ai/propose-post-taxonomy-terms` as a deterministic
  proposal-only helper for existing taxonomy terms.
- The helper targets `magick-ai/set-post-terms` with dry-run input and
  `commit_execution=false`.
- The helper does not create terms, assign terms, mutate posts, run model
  calls, own workflow runtime, or perform approval/audit governance.
- `magick-ai-core` has consumed the real helper contract in its taxonomy terms
  preview smoke path.
- The consumer handoff check now locks the five harvested surfaces from the
  main repo: taxonomy/content cleanup, publish preflight, media inventory, page
  structure inspection, and comment handoff context. For each surface it
  verifies read/proposal ability discovery plus corresponding host-governed
  write/destructive targets with schema, REST metadata, dry-run controls, and
  approval metadata.
- `docs/ability-contract-readiness-0.5.md` defines the 0.5 contract readiness
  line and keeps the package focused on ability truth, not runtime ownership.
- `composer check:contracts` now audits the registered ability catalog after
  default plugin boot, including risk metadata, schemas, write controls, REST
  visibility, Magick/MCP metadata consistency, and workflow recipe references.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer check:contracts` | Pass | Registered first-party ability contract audit passed for 115 abilities. |
| `composer test:all` | Pass | Composer validation, project boundary, consumer handoff, catalog audit, lightweight tests, and PHP lint passed; `OK: 2582 assertions`. |
| `composer smoke:wp` | Pass | Full profile covered the taxonomy proposal helper; default profile reported `Smoke OK: 198 assertions`, light profile reported `Smoke OK: 14 assertions`. |
| `magick-ai-core composer test:all` | Pass | Core PHP lint and static contracts passed. |
| `magick-ai-core composer smoke:wp` | Pass | Core discovered `magick-ai/propose-post-taxonomy-terms`, ran it through WordPress Abilities API, created and approved a `magick-ai/set-post-terms` proposal, returned commit preflight with `commit_execution=false`, and did not mutate post terms. |

## Current Decision

Keep `npcink-abilities-toolkit` in freeze/observe mode:

- do not add another fourth-batch ability from candidate lists alone;
- treat the main Magick AI repo cleanup list as harvest input, not as an
  implementation queue;
- only add a new ability when Core or a host workflow reports a concrete
  schema, metadata, or ability contract gap;
- keep final taxonomy/media/write/destructive execution in host runtimes;
- keep model routing, MCP governance, approval storage, audit, quota, and
  workflow runtime outside this package.

The current harvest checkpoint is
`docs/migration-notes/magick-ai-main-repo-harvest-2026-05-30.md`. It maps the
main repo content/media/comment/batch cleanup signals to existing ability
coverage and records that no new ability batch is open from those signals alone.

## Open Gaps

No active `npcink-abilities-toolkit` schema, metadata, or ability contract gap is
open for the taxonomy terms preview handoff.

Future work should start from a failed or incomplete host workflow proof, not
from expanding the ability catalog opportunistically.
