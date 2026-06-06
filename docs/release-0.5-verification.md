# 0.5 Release Verification

Status: release verification.
Date: 2026-06-06.
Updated: 2026-06-07.

This note records the `0.5.0` release line after adding the taxonomy terms
proposal helper, admin surface cleanup, public nonproduction naming cleanup, and
the ability contract readiness gate. The goal is to preserve the consumer proof,
make clear that no additional ability batch should start without a concrete
workflow gap, and verify the registered first-party ability surface directly.

## Scope

- Added `npcink-abilities-toolkit/propose-post-taxonomy-terms` as a deterministic
  proposal-only helper for existing taxonomy terms.
- The helper targets `npcink-abilities-toolkit/set-post-terms` with dry-run input and
  `commit_execution=false`.
- The helper does not create terms, assign terms, mutate posts, run model
  calls, own workflow runtime, or perform approval/audit governance.
- `npcink-ai-core` has consumed the real helper contract in its taxonomy terms
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
- `docs/next-stage-operating-standard.md` records the freeze/observe operating
  standard: workflow proof before ability expansion, declarative recipes,
  performance gates, security contract discipline, and current release evidence.
- `workflow/wordpress_article_optimization` and
  `workflow/wordpress_article_media_handoff` were promoted to declarative
  workflow definitions and replay-fixture cases without adding new abilities or
  runtime ownership.
- `docs/security-and-governance-gates.md` records the security boundary for
  permission, metadata, dry-run, host-approval, schema, and diagnostics
  redaction checks.
- `docs/official-wordpress-ai-stack-compatibility.md` records how this package
  should line up with official Abilities API, MCP Adapter, AI plugin, and AI
  Client layers without duplicating their runtime ownership.
- `composer test:all` now includes official-stack compatibility, workflow
  consumer proof, MCP exposure, provider demo smoke, and performance smoke
  gates.
- `docs/testing-strategy.md` documents the foundation-layer testing policy:
  contract and invariant gates are release-critical, while host runtime,
  approval storage, audit, quota, model routing, and product UX remain outside
  this package's default local test loop.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| `composer check:contracts` | Pass | Registered first-party ability contract audit passed for 136 abilities. |
| `composer check:workflow-consumer` | Pass | Workflow replay fixture matched the production provider and proved 6 host-discoverable recipes with read entrypoints and disallowed write defaults. |
| `composer check:official-stack` | Pass | Official WordPress AI stack compatibility contract shape passed for 136 abilities. |
| `composer check:mcp-exposure` | Pass | MCP risk, annotations, server routing, REST discoverability, and host-governed write defaults passed for 136 abilities. |
| `composer check:provider-demo` | Pass | Demo provider plugin registered read, projected read, and write-proposal abilities through public helpers. |
| `composer test` | Pass | Lightweight tests passed; `OK: 3757 assertions`. |
| `composer perf:smoke` | Pass | Content inventory, cached SEO/GEO gap, article publish preflight, old article refresh, and comment compliance handoff stayed within local budgets. |
| `composer test:all` | Pass | Composer validation, project boundary, contract, consumer, workflow, official-stack, MCP, provider demo, catalog, WordPress.org guard, performance, lightweight tests, and PHP lint passed; `OK: 3757 assertions`. |
| `composer smoke:wp` | Pass | Shared Local site smoke covered taxonomy proposal, existing article optimization, and article media handoff; default profile reported `Smoke OK: 266 assertions`, light profile reported `Smoke OK: 21 assertions`. |
| `npcink-ai-core composer test:all` | Pass | Core PHP lint and static contracts passed. |
| `npcink-ai-core composer smoke:wp` | Pass | Core discovered `npcink-abilities-toolkit/propose-post-taxonomy-terms`, ran it through WordPress Abilities API, created and approved a `npcink-abilities-toolkit/set-post-terms` proposal, returned commit preflight with `commit_execution=false`, and did not mutate post terms. |

## Current Decision

Keep `npcink-abilities-toolkit` in freeze/observe mode:

- do not add another fourth-batch ability from candidate lists alone;
- treat the main Npcink AI repo cleanup list as harvest input, not as an
  implementation queue;
- only add a new ability when Core or a host workflow reports a concrete
  schema, metadata, or ability contract gap;
- keep final taxonomy/media/write/destructive execution in host runtimes;
- keep model routing, MCP governance, approval storage, audit, quota, and
  workflow runtime outside this package.
- promote additional workflow recipes only when they can stay declarative and
  reuse existing read/proposal ability contracts.

The current harvest checkpoint is
`docs/migration-notes/magick-ai-main-repo-harvest-2026-05-30.md`. It maps the
main repo content/media/comment/batch cleanup signals to existing ability
coverage and records that no new ability batch is open from those signals alone.

## Open Gaps

No active `npcink-abilities-toolkit` schema, metadata, or ability contract gap is
open for the taxonomy terms preview handoff, existing article optimization
handoff, or article media handoff.

Future work should start from a failed or incomplete host workflow proof, not
from expanding the ability catalog opportunistically.
