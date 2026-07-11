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

## Initial Overlap Map

The WordPress 7.1 merge proposal is still under review, so every row remains
non-breaking and evidence-first.

Proposal source:
https://make.wordpress.org/core/2026/07/02/merge-proposal-expanding-wordpress-core-abilities/

| Core direction | Current Toolkit surface | Initial disposition | Required evidence before change |
| --- | --- | --- | --- |
| `core/read-settings` | `site-info`, bounded diagnostics, explicit setting inspection helpers | `review_later` | Compare opt-in behavior, permissions, redaction, schemas, and host consumers. |
| `core/read-content` | `list-posts`, `get-post`, `list-pages`, `get-page`, `search-posts`, post context and inventory reports | Generic CRUD: `review_later`; advanced reports: `toolkit_extension` | Compare pagination, fields, post-type opt-in, advanced filters, and workflow output requirements. |
| `core/read-users` | `list-users` and author context projections | `review_later` | Compare privacy filtering, permission behavior, lookup fields, and returned author context. |
| Future Core content management | create/update/publish/schedule/trash and bounded patch abilities | `review_later` | Preserve host approval, dry-run, idempotency, conflict detection, and rollback evidence. |
| Future Core comments, taxonomy, media, themes, and plugins | Toolkit read, plan, write, and destructive packs | `review_later` | Review each stable Core contract before choosing Core preference or Toolkit extension. |

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
