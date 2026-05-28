# Agent Workflow Validation

Status: active for 0.3 stabilization.

This document defines the first three end-to-end workflows that should be proven
before another large ability batch is added. The workflows are not a runtime
owned by this package. They are consumption contracts for Magick AI, WP Magick
Toolbox, or any host that executes WordPress abilities.

## Workflow 1: Article Publish Preflight

Goal: decide whether a draft is ready for host-approved scheduling or
publication.

Ability sequence:

1. Discover abilities through `/wp-abilities/v1/abilities` or `wp_get_ability()`.
2. Run `magick-ai/get-post-context` for canonical post context.
3. Run `magick-ai/get-content-publishing-checklist` for readiness checks.
4. Run `magick-ai/get-post-publish-risk-report` for publish risk.
5. Run `magick-ai/build-article-workflow-context` with `workflow=publish`.
6. Run `magick-ai/get-publishing-calendar-context` to understand scheduling
   pressure.
7. Stop at read context unless the host explicitly approves
   `magick-ai/schedule-post` or `magick-ai/publish-post`.

Acceptance:

- every read step returns HTTP 200 through WordPress Abilities API;
- every read step returns a success envelope;
- workflow context includes a `publish_risk` section;
- no write or destructive action is committed by this package.

## Workflow 2: Old Article Refresh Discovery

Goal: identify articles that deserve refresh work and provide enough context for
an agent to draft a plan.

Ability sequence:

1. Run `magick-ai/get-content-refresh-opportunities` for stale, thin, weak-link,
   and SEO/GEO issue signals.
2. Run `magick-ai/get-seo-geo-gap-report` for cross-site topic and answer gaps.
3. Run `magick-ai/get-site-style-baseline` for writing-style constraints.
4. Run `magick-ai/get-internal-link-graph-health` for site-wide link structure.
5. Run `magick-ai/get-internal-link-opportunity-report` for a selected post when
   the host chooses a candidate.
6. Stop at proposal/context unless the host later approves post patch abilities.

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

1. Run `magick-ai/get-comment-queue-health` for moderation queue summary.
2. Run `magick-ai/get-comment-action-priority-queue` for prioritized handoff.
3. Run `magick-ai/build-comment-moderation-suggest` for a selected comment.
4. Run `magick-ai/build-comment-mention-reply-suggest` when mention or follow-up
   handling is needed.
5. Optionally run `magick-ai/compose-comment-moderation-result` to normalize the
   handoff result.
6. Stop at suggestions unless the host explicitly approves
   `magick-ai/approve-comment`, `magick-ai/reply-comment`,
   `magick-ai/spam-comment`, or `magick-ai/trash-comment`.

Acceptance:

- every read/suggest step returns HTTP 200 through WordPress Abilities API;
- every read/suggest step returns a success envelope;
- mention-trigger comments are detected as reply candidates;
- moderation writes and destructive actions stay host-governed.

## Smoke Mapping

`tests/smoke-wp.php` now validates these workflow chains in addition to
single-ability registration and execution:

- Article Publish Preflight: context, checklist, publish risk, workflow context,
  and publishing calendar.
- Old Article Refresh Discovery: refresh opportunities, SEO/GEO gap, style
  baseline, link graph, and link opportunity report.
- Comment Compliance Handoff: queue health, priority queue, moderation
  suggestion, and mention reply handoff.

Use the Local WordPress command documented in
[local-wpcli-smoke.md](local-wpcli-smoke.md) to rerun this validation.

## Failure Handling

- Missing ability: fail the host workflow early and report the missing ability
  id.
- Permission failure: surface the WordPress capability or host scope that is
  missing.
- Missing host approval: leave write/destructive abilities in dry-run or fail
  closed.
- Duplicate ability owner: fail the duplicate-id audit in the host project; do
  not reintroduce fallback definitions here without an ADR.
