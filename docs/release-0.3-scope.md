# Release 0.3 Scope

Status: accepted for `0.3.0`.

The 0.3 line should make the current ability surface dependable for consumers
instead of expanding aggressively. The priority is proving that Magick AI,
Toolbox, and direct Abilities API clients can discover and execute useful
workflow chains through standard WordPress ability contracts.

## Goals

- Treat the current first-party ability packs as the baseline ability surface.
- Keep public third-party helpers limited to category, read-only,
  write-proposal, normalization, and registry inspection helpers.
- Validate the three primary workflow chains documented in
  [Agent Workflow Validation](agent-workflow-validation.md).
- Validate bounded-chain performance with `composer perf:smoke`.
- Keep Magick AI projection metadata-only with `executor_type=wp_ability`.
- Keep default/full and light `core_wordpress_read` smoke profiles passing.
- Record smoke results whenever the Local WordPress site is available.
- Publish release metadata as plugin/readme version `0.3.0` after final local
  verification passes.

## Non-Goals

- No workflow builder UI.
- No model connector UI.
- No billing, quota, audit, MCP, Open API Gateway, or cloud execution ownership.
- No broad fourth batch of abilities until workflow validation exposes concrete
  gaps.
- No public third-party host-governed commit helper without a new ADR.

## Exit Criteria

- [Ability Acceptance Matrix](ability-acceptance-matrix.md) is up to date.
- [Agent Workflow Validation](agent-workflow-validation.md) is up to date.
- `composer validate:composer` passes.
- `composer check:boundary` passes.
- `composer test` passes.
- `composer lint:php` passes.
- `composer perf:smoke` passes.
- `composer test:all` passes.
- `git diff --check` passes.
- `composer smoke:wp` status is recorded, including the assertion count or why
  it was not run.
- Any new ability id is added to `docs/magick-ai-migration-inventory.md`.

## Compatibility Checklist

- Default boot still enables `core_read`, `core_write`, `core_destructive`,
  `core_comment`, `npcink_catalog_bridge`, `admin_test_page`, and
  `read_cache_hooks`.
- Light host profile can keep only `core_read` plus the `core_wordpress_read`
  sub-pack and must not register workflow definition discovery, workflow,
  diagnostics, write, destructive, or comment helper abilities.
- Projected Npcink AI catalog rows remain thin by default and do not include
  host-owned policy fields such as `open_api_enabled`, `backend_priority`,
  `write_mode`, `tool_policy`, or catalog fallback controls.
- Magick AI consumer tests derive backend priority and tool policy in Magick AI
  from `executor_type`, `risk_level`, and `requires_confirm`.
- Public third-party helpers remain limited to category, read-only,
  write-proposal, normalization, registry inspection, and composition filters.
  No third-party final commit helper is introduced without a new ADR.
- Existing `npcink-abilities-toolkit/*` ids remain compatibility ids. Namespace cleanup is
  deferred until each renamed id has `deprecated` / `successor` metadata and
  host migration tests.
- Built-in read/comment definitions must have explicit sub-pack map entries;
  heuristics are only a fallback for unknown ids during local development.
- Any source-file split must be zero behavior change: no ability id, schema,
  annotation, risk, confirmation, callback, or default registration change may
  be bundled with the split.

## Fourth Batch Rule

Only add a fourth batch when one of the validated workflows cannot be completed
cleanly with existing abilities. The batch should be small:

- three to five abilities maximum;
- read-only first unless the output is explicitly a write proposal;
- one workflow gap per ability;
- no new runtime boundary.
