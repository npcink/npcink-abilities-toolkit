# WordPress Core Ability Convergence

Status: active review for the 0.5.x freeze/observe line.
Date: 2026-07-11.

## Purpose

This document prevents Toolkit from becoming a permanent parallel catalog for
generic WordPress Core entities. It is an adoption and compatibility review,
not an instruction to remove public Toolkit abilities immediately.

Reusable workflow definitions remain owned by Toolkit. A definition may use a
stable WordPress Core ability, a Toolkit-specific ability, or a bounded chain
of both. Consumers still own execution and governance state.

## Classification

Use these dispositions when a stable Core ability is available:

| Disposition | Meaning |
| --- | --- |
| `core_preferred` | The Core contract covers the generic need; new definitions should prefer it. |
| `toolkit_extension` | Toolkit adds a materially different bounded query, diagnostic, plan, or review artifact. |
| `compatibility_bridge` | Toolkit keeps a stable public id while consumers migrate to Core. |
| `toolkit_unique` | The contract is outside the generic Core entity baseline and remains Toolkit-owned. |
| `review_later` | The Core proposal is not stable enough for a compatibility decision. |

## Observed Overlap Map

The machine-readable companion is
[`wordpress-core-ability-convergence.json`](wordpress-core-ability-convergence.json).
The current rows were observed through the authenticated WordPress Abilities
REST catalog on WordPress `7.1-alpha-62692`. Because that is a pre-release
build, every decision remains non-breaking and evidence-first.

Proposal source:
https://make.wordpress.org/core/2026/07/02/merge-proposal-expanding-wordpress-core-abilities/

| Observed Core ability | Current Toolkit surface | Classification | Current decision and evidence gap |
| --- | --- | --- | --- |
| `core/get-site-info` | `npcink-abilities-toolkit/site-info` | `review_later` | Keep Toolkit while WordPress 6.9 remains supported. Input/output schemas only partially overlap; permissions and error envelopes still need comparison, followed by Adapter and Toolbox consumer proofs before any preference or deprecation. |
| `core/get-user-info` | `npcink-abilities-toolkit/list-users` | `toolkit_extension` | Keep Toolkit. Core returns the current authenticated user, while Toolkit provides a bounded user-list and author-context contract; the schemas and cardinality are intentionally different. |
| `core/get-environment-info` | `npcink-abilities-toolkit/wp-diagnostics-summary`, `npcink-abilities-toolkit/wp-ops-diagnostics-detail` | `toolkit_extension` | Keep Toolkit. The diagnostic contracts add bounded profiles, redaction, severity summaries, and support evidence not represented by the generic Core environment response. |

Future Core content, comment, taxonomy, media, theme, plugin, and management
abilities must be added as new observed rows only after they appear in a
testable WordPress release. Do not convert proposal directions into successor
ids.

## Automated Gate

Run:

```bash
composer check:core-convergence
```

The checker validates that every Toolkit id exists, the Markdown and JSON rows
stay synchronized, and each row compares input schema, output schema,
permissions, error envelope, and write posture. A `deprecate` decision fails
unless all of these are true:

- the Core contract status is `stable`;
- every comparison dimension is `equivalent`;
- Adapter and Toolbox consumer proofs are `passed`;
- the classification is `core_preferred` or `compatibility_bridge`;
- at least one overlap release, migration guidance, and rollback guidance are
  recorded.

Raw ability or catalog counts are forbidden as convergence evidence.
Maintainers may set `NPCINK_CORE_CONVERGENCE_MANIFEST` to an alternate JSON
file when exercising negative fixtures; the default gate always reads the
versioned companion beside this document.

## Decision Rules

1. Do not rename or remove a Toolkit ability from a Core proposal alone.
2. Run a real consumer proof using the Core contract and the existing Toolkit
   contract against the same task.
3. Prefer Core when it covers the generic entity operation without weakening
   permissions, redaction, boundedness, or host governance.
4. Keep Toolkit when its value is the higher-level deterministic artifact,
   advanced bounded query, diagnostics redaction, dry-run plan, conflict guard,
   or rollback evidence rather than generic CRUD.
5. Before deprecation, add `deprecated` and `successor` metadata, consumer
   guidance, contract fixtures, and at least one release of overlap.
6. Workflow definitions should change additively and retain a compatible
   fallback while supported WordPress versions differ.

## Current Actions

- Keep the first-party catalog frozen except for contract bugs or failed host
  proofs.
- Track the WordPress Core proposal through its stabilization cycle.
- Replace raw ability-count targets with representative contract assertions.
- Add a focused overlap row whenever a new Core ability reaches a testable
  release.
- Do not begin deprecation work until a Core contract and a consumer proof are
  both available.
