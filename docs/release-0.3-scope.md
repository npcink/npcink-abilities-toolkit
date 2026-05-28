# Release 0.3 Scope

Status: proposed stabilization scope.

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
- Keep Magick AI projection metadata-only with `executor_type=wp_ability`.
- Record smoke results whenever the Local WordPress site is available.

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
- `composer test:all` passes.
- `git diff --check` passes.
- `composer smoke:wp` status is recorded, including the assertion count or why
  it was not run.
- Any new ability id is added to `docs/magick-ai-migration-inventory.md`.

## Fourth Batch Rule

Only add a fourth batch when one of the validated workflows cannot be completed
cleanly with existing abilities. The batch should be small:

- three to five abilities maximum;
- read-only first unless the output is explicitly a write proposal;
- one workflow gap per ability;
- no new runtime boundary.
