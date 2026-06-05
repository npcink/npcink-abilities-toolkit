# Agent Workflow Validation

Status: active for 0.3 stabilization.

This document defines the first three end-to-end workflows that should be proven
before another large ability batch is added. The workflows are not a runtime
owned by this package. They are consumption contracts for Npcink AI, WP Magick
Toolbox, or any host that executes WordPress abilities.

The broader public recipe catalog is documented in
[Workflow Recipes](workflow-recipes.md). This validation document names the
minimum recipe chains that must be smoke-tested for the stabilization line.

## Workflow 1: Article Publish Preflight

Goal: decide whether a draft is ready for host-approved scheduling or
publication.

Ability sequence:

1. Discover abilities through `/wp-abilities/v1/abilities` or `wp_get_ability()`.
2. Prefer `npcink-abilities-toolkit/get-article-publish-preflight-context` for the bundled
   preflight context.
3. Run `npcink-abilities-toolkit/get-post-context` for canonical post context when individual
   sections are needed.
4. Run `npcink-abilities-toolkit/get-content-publishing-checklist` for readiness checks.
5. Run `npcink-abilities-toolkit/get-post-publish-risk-report` for publish risk.
6. Run `npcink-abilities-toolkit/build-article-workflow-context` with `workflow=publish`.
7. Run `npcink-abilities-toolkit/get-publishing-calendar-context` to understand scheduling
   pressure.
8. Stop at read context unless the host explicitly approves
   `npcink-abilities-toolkit/schedule-post` or `npcink-abilities-toolkit/publish-post`.

Acceptance:

- every read step returns HTTP 200 through WordPress Abilities API;
- every read step returns a success envelope;
- workflow context includes a `publish_risk` section;
- no write or destructive action is committed by this package.

## Workflow 2: Old Article Refresh Discovery

Goal: identify articles that deserve refresh work and provide enough context for
an agent to draft a plan.

Ability sequence:

1. Prefer `npcink-abilities-toolkit/get-old-article-refresh-context` for the bundled refresh
   discovery context.
2. Run `npcink-abilities-toolkit/get-content-refresh-opportunities` for stale, thin, weak-link,
   and SEO/GEO issue signals.
3. Run `npcink-abilities-toolkit/get-seo-geo-gap-report` for cross-site topic and answer gaps.
4. Run `npcink-abilities-toolkit/get-site-style-baseline` for writing-style constraints.
5. Run `npcink-abilities-toolkit/get-internal-link-graph-health` for site-wide link structure.
6. Run `npcink-abilities-toolkit/get-internal-link-opportunity-report` for a selected post when
   the host chooses a candidate.
7. Stop at proposal/context unless the host later approves post patch abilities.

Acceptance:

- every read step returns HTTP 200 through WordPress Abilities API;
- every read step returns a success envelope;
- outputs are sufficient to prioritize candidates without direct database
  access;
- post mutation remains a separate host-governed write chain.

## Workflow 3: Comment Compliance Handoff

Goal: triage comments, prepare moderation/reply suggestions, and keep final
comment actions under host approval.

Ability sequence:

1. Prefer `npcink-abilities-toolkit/get-comment-compliance-handoff` for the bundled queue,
   priority, and optional selected-comment handoff.
2. Run `npcink-abilities-toolkit/get-comment-queue-health` for moderation queue summary.
3. Run `npcink-abilities-toolkit/get-comment-action-priority-queue` for prioritized handoff.
4. Run `npcink-abilities-toolkit/build-comment-moderation-suggest` for a selected comment.
5. Run `npcink-abilities-toolkit/build-comment-mention-reply-suggest` when mention or follow-up
   handling is needed.
6. Optionally run `npcink-abilities-toolkit/compose-comment-moderation-result` to normalize the
   handoff result.
7. Stop at suggestions unless the host explicitly approves
   `npcink-abilities-toolkit/approve-comment`, `npcink-abilities-toolkit/reply-comment`,
   `npcink-abilities-toolkit/spam-comment`, or `npcink-abilities-toolkit/trash-comment`.

Acceptance:

- every read/suggest step returns HTTP 200 through WordPress Abilities API;
- every read/suggest step returns a success envelope;
- mention-trigger comments are detected as reply candidates;
- moderation writes and destructive actions stay host-governed.

## Smoke Mapping

`tests/smoke-wp.php` now validates these workflow chains in addition to
single-ability registration and execution:

- Article Publish Preflight: preflight bundle, context, checklist, publish risk,
  workflow context, and publishing calendar.
- Old Article Refresh Discovery: refresh opportunities, SEO/GEO gap, style
  baseline, link graph, link opportunity report, and refresh bundle.
- Comment Compliance Handoff: compliance bundle, queue health, priority queue,
  moderation suggestion, and mention reply handoff.

Use the Local WordPress command documented in
[local-wpcli-smoke.md](local-wpcli-smoke.md) to rerun this validation.

## AI Consumer Replay Fixture

`tests/fixtures/agent-workflow-replay.json` records the first three natural task
replay cases for consumer-side validation. It is intentionally small and
machine-readable:

- each case follows the field rules in
  [Workflow Definition Contract](workflow-definition-contract.md);
- each case maps natural task examples to one preferred bundled ability;
- each case names the recipe id, required scope, expected output sections, and
  required input keys;
- each case lists write/destructive abilities that an agent must not pick as
  the default entry for the task;
- `composer test` verifies the fixture against registered abilities and the
  optional `wp_ability` compatibility projection consumed by host projects.

The current required consumer-side check belongs in `npcink-ai-core`. Future
Agent Gateway, MCP, or other host tests may reuse the same fixture, but the
abandoned legacy Npcink AI project is no longer a validation target.

Use this fixture to prove that task selection prefers the bundled context entry
before falling back to expanded individual calls or host-governed writes.

Runtime consumers should prefer the read-only workflow definition helper or
Abilities API discovery abilities documented in
[Workflow Definition Contract](workflow-definition-contract.md). The fixture
keeps tests aligned with the production provider; it should not be the only
production discovery path.

## Failure Handling

- Missing ability: fail the host workflow early and report the missing ability
  id.
- Permission failure: surface the WordPress capability or host scope that is
  missing.
- Missing host approval: leave write/destructive abilities in dry-run or fail
  closed.
- Duplicate ability owner: fail the duplicate-id audit in the host project; do
  not reintroduce fallback definitions here without an ADR.
