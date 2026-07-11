# ADR 0005: Own Reusable Workflow Definitions And Converge With WordPress Core

## Status

Accepted.

## Date

2026-07-11.

## Context

The Npcink stack needs one canonical source for reusable workflow definitions
that compose WordPress abilities without creating competing recipe catalogs in
Core, Adapter, Toolbox, or Cloud. At the same time, WordPress Core is expanding
its own canonical Abilities API surface. Toolkit already exposes a large
first-party catalog, including generic WordPress entity reads and mutations
that may overlap with stable Core abilities over time.

References:

- WordPress Abilities API handbook: https://developer.wordpress.org/apis/abilities-api/
- WordPress Core ability expansion proposal:
  https://make.wordpress.org/core/2026/07/02/merge-proposal-expanding-wordpress-core-abilities/

The project also used a minimum catalog-size assertion as an official-stack
gate. That assertion proves breadth, but it discourages consolidation and
conflicts with the current freeze/observe rule that new abilities require a
failed consumer proof.

## Decision

1. `npcink-abilities-toolkit` is the canonical owner of reusable, static,
   versioned workflow definitions for the Npcink stack.
2. A workflow definition may describe discovery, composition, expected output,
   a suggestion or proposal handoff, failure posture, and host-governed write
   boundaries.
3. Workflow execution state, routing decisions, schedules, retries, queues,
   leases, model or prompt selection, approval, audit, quota, and final write
   authority remain host-owned and are forbidden in Toolkit definitions.
4. Toolkit will prefer stable WordPress Core abilities for generic Core entity
   operations when those contracts meet the workflow need. Existing Toolkit
   ids remain stable until a consumer-tested migration and deprecation plan is
   available.
5. Official-stack gates must verify representative required contracts and
   invariants. They must not enforce a minimum total ability count.
6. The plugin's minimum WordPress version is 6.9, the version that introduced
   the Abilities API. A higher tested-up-to version does not redefine the
   technical minimum.

## Alternatives Considered

### Let each consumer own reusable workflow definitions

Rejected because Core, Adapter, Toolbox, and Cloud would drift into parallel
recipe catalogs and make ownership ambiguous.

### Make Toolkit a workflow runtime

Rejected because runtime state and governance would turn the package into a
second control plane and couple reusable WordPress contracts to one host.

### Keep every Toolkit ability after a stable Core equivalent exists

Rejected as the default because it creates duplicate ecosystem contracts. A
Toolkit contract may remain when it provides a materially different bounded
artifact, advanced query, compatibility bridge, or governance-ready plan.

### Remove overlapping abilities immediately

Rejected because public ids and observable behavior may already have
consumers. Convergence requires evidence, deprecation metadata, successor
guidance, and a staged migration.

## Consequences

- Consumers discover reusable workflow definitions from Toolkit and add their
  own runtime state outside Toolkit.
- New definitions require definition-contract, replay, consumer, and boundary
  coverage but do not justify new abilities by themselves.
- Generic ability additions must include a WordPress Core overlap check.
- Existing generic abilities need an explicit convergence inventory before any
  deprecation work begins.
- Catalog shrinkage is allowed when compatibility rules are satisfied; quality
  gates measure required contracts rather than raw count.
- WordPress 6.9 and the current tested-up-to release both need practical smoke
  evidence before publication.

## Verification

- `composer check:boundary`
- `composer check:contracts`
- `composer check:workflow-consumer`
- `composer check:official-stack`
- real WordPress smoke on the minimum and tested-up-to versions
