# Ability Contract Reuse Readiness - 2026-07-08

Status: active observation record

This record closes the Abilities Toolkit observation pass after Governance Core
confirmed that its current `proposal_handoff` contract is sufficient for the
merged Cloud/Add-on/Toolbox reuse stack. The purpose is to decide whether
Toolkit needs new implementation work before the next project optimization
pass.

## Scope

Toolkit's role in the current reuse stack is `ability_contracts`:

- expose stable WordPress Abilities API ids;
- normalize input and output schemas;
- publish REST-visible risk, approval, annotation, MCP, and Npcink metadata;
- default write-like abilities to dry-run previews;
- expose `implementation_posture` for write and destructive abilities;
- keep callbacks generic WordPress operations;
- provide consumer proof that Core proposal payloads can be prepared from real
  ability contracts.

The adjacent roles stay outside Toolkit:

| Role | Owner |
| --- | --- |
| `proposal_handoff` | `npcink-governance-core` |
| `execution_profiles` | `npcink-ai-client-adapter` or another approved channel adapter |
| `product_surface` | `npcink-workflow-toolbox` or another product plugin |
| `signed_transport` | `npcink-cloud-addon` |
| `runtime_detail` | `npcink-ai-cloud` |

## Current Evidence

The current Toolkit already has the contract hooks needed for Core reuse:

- the public ability contract requires stable `namespace/name` ids,
  object-shaped input/output schemas, REST exposure, risk metadata, and
  WordPress Abilities API alignment;
- write-like normalization adds `dry_run`, `commit`, and `idempotency_key`
  controls, with `dry_run=true` and `commit=false` defaults;
- write and destructive abilities expose `implementation_posture` at the top
  level, in `meta.implementation_posture`, and in
  `meta.npcink.implementation_posture`;
- `implementation_posture` declares Toolkit as implementation owner while
  leaving final authorization, approval truth, and audit truth with the host
  governance layer;
- `composer check:contracts` audits registered abilities for schemas, risk,
  REST/MCP/Npcink metadata, dry-run controls, implementation posture, forbidden
  runtime ownership flags, and workflow recipe references;
- `composer check:consumer` verifies that a host can discover representative
  read, proposal, write, and destructive contracts and prepare Core governance
  proposal payloads from the real catalog;
- the Core Governance Handoff Guide names ready surfaces for site reads, draft
  creation, SEO metadata, content metadata apply plans, taxonomy terms, and
  comment moderation without creating runtime aliases.

## Active Observation Result

No new Toolkit ability or runtime code is needed for this pass.

The current contract surface is sufficient for Core, Adapter, and product
plugins to reuse Toolkit as the ability-contract source. The important
follow-up is not to broaden the ability catalog, but to keep future host work
inside the existing composition discipline:

```text
product or adapter intent
-> real Toolkit ability id
-> schema/risk/implementation_posture discovery
-> Core proposal or local-consent audit classification when required
-> host-governed dry-run or Adapter execution after approval/preflight
-> Core record-execution or host audit evidence
```

## Representative Ready Contracts

These existing contracts are enough to continue the reuse pass:

- `npcink-abilities-toolkit/create-draft`
- `npcink-abilities-toolkit/update-post`
- `npcink-abilities-toolkit/update-post-blocks`
- `npcink-abilities-toolkit/set-post-terms`
- `npcink-abilities-toolkit/update-media-details`
- `npcink-abilities-toolkit/propose-post-taxonomy-terms`
- `npcink-abilities-toolkit/build-content-metadata-apply-plan`
- `npcink-abilities-toolkit/build-pattern-page-plan`
- `npcink-abilities-toolkit/get-comment-compliance-handoff`

Treat these as reference contracts for host reuse. Add a new ability only when
a real Core, Adapter, Toolbox, MCP host, or provider proof fails because an
existing ability cannot express the reusable WordPress-only contract.

## Stop Rule

Stop and write a boundary note or ADR before implementing if a follow-up
requires Toolkit to own any of these:

- proposal records, approval lifecycle, commit preflight, or audit truth;
- final WordPress mutation policy or final write authorization;
- workflow runtime, task queues, retry workers, leases, schedulers, or batch
  execution consoles;
- product workflow UX, market onboarding, commercial packaging, or
  site-owner-specific flows;
- model routing, prompt/preset truth, provider credentials, quota, billing, or
  cloud execution;
- MCP gateway policy, Agent Gateway catalogs, or OpenClaw projection truth;
- signed transport, Cloud runtime detail, or Site Knowledge lifecycle.

## Next Development Recommendation

End this Toolkit observation pass here.

The next useful development slice should move to the next repository in the
reuse chain, not add new Toolkit functionality. A good next slice is
`npcink-ai-client-adapter`: verify that Adapter reads Core and Toolkit contract
metadata, presents execution posture clearly, and only executes after the host
governance path supplies the required approval or local-consent evidence.

## Verification

Required Toolkit gates for this record:

```bash
composer test:all
composer analyse:phpstan
composer check:boundary
```

Run `WP_PATH=/path/to/wordpress composer smoke:wp` only if a future change
touches real WordPress activation, REST routing, registered ability behavior,
callback execution, proposal handoff fixtures, or Local.app smoke assumptions.
