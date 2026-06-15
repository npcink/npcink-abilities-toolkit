# Block Theme Context Snapshot Proof

Status: 0.5.x proof-hardening candidate.
Date: 2026-06-15.

This note records the scope for the block theme site context snapshot work. It
is intended to support host workflow proof for bounded block theme layout
planning without changing this package into a layout runtime, template writer,
or approval system.

## Decision

Continue freeze/observe mode. The block theme snapshot work is allowed only as
contract hardening for existing abilities:

- `npcink-abilities-toolkit/get-block-theme-context`;
- `npcink-abilities-toolkit/build-block-theme-site-plan`.

It must not add a new first-party ability id, a workflow runtime, a template
write executor, approval storage, audit state, model routing, prompt ownership,
or final WordPress mutation policy.

## Proof Target

The host proof target is a lightweight block theme intent routing replay:

1. A host receives a natural-language request for homepage, blog, article, or
   page template layout changes.
2. The host resolves the intended route and discovers the existing abilities
   through the WordPress Abilities API.
3. `get-block-theme-context` returns factual site context: front-page reading
   settings, template resolution, bounded content inventory, CTA candidates, and
   existing override hashes.
4. `build-block-theme-site-plan` returns a reviewable preview or a fail-closed
   result.
5. Write actions remain proposal targets for the host. Final template updates
   require host approval and write execution outside this package.

## Contract Hardening

The snapshot may expose factual, bounded WordPress context:

- `reading_settings` for `show_on_front`, `page_on_front`, and
  `page_for_posts`;
- `template_resolution` for `front-page`, `home`, and `index`;
- `content_inventory` with bounded counts and candidate CTA pages;
- `existing_overrides` with template ids and content hashes;
- preview metadata such as `template_resolver`, `cta_resolution`, and
  `page_content_enabled`.

Homepage layout planning must fail closed when a CTA cannot resolve to a
caller-supplied URL or a trusted existing page. In that case the plan should
return `cta_link_unresolved`, mark the preview as not proposal-ready, and emit
no write action.

## Boundary

Allowed in this package:

- read block theme, template, and content context;
- explain deterministic template resolution;
- build bounded proposal previews;
- validate block editor quality gates;
- return risk, warning, and fail-closed metadata.

Not allowed in this package:

- choose final site strategy for a product host;
- run template writes directly from natural-language intent;
- publish, approve, retry, schedule, or audit template changes;
- maintain layout workflow state;
- patch `theme.json`, global styles, navigation, raw template HTML, custom CSS,
  non-core blocks, or arbitrary unprofiled template composition.

## Failure Classification

Only the last two failure classes can justify future toolkit code:

| Failure class | Owner | Toolkit ability change? |
| --- | --- | --- |
| Host cannot choose when to call a workflow | Host or adapter | No |
| Prompt, router, or model selection fails | Host or AI runtime | No |
| Approval, audit, or preflight is missing | Core or governance host | No |
| Provider, quota, billing, or cloud execution fails | Cloud or runtime | No |
| WordPress-local block theme context is missing | Toolkit | Maybe |
| Proposal or fail-closed schema cannot be verified | Toolkit | Maybe |

## Version Guidance

This work fits a possible `0.5.2` maintenance release if it remains limited to
documentation, replay fixtures, contract snapshots, and existing-ability
hardening.

Do not treat it as `0.6.0` unless a real host proof fails and identifies one to
three small, reusable, WordPress-only contract gaps that cannot be solved by
host routing, prompt design, approval flow, or adapter behavior.

## Verification

Before merging this proof-hardening branch, run:

```bash
composer test:all
composer analyse:phpstan
git diff --check
```

When a real WordPress site is available, add:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

Record any failure as host-owned, toolkit-owned, or deferred before creating a
new ability proposal.
