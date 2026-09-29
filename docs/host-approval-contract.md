# Host Approval Contract

Status: active guidance
Date: 2026-09-29

This document defines how any host runtime — not only Npcink AI — can govern the
write and destructive abilities registered by `npcink-abilities-toolkit`. It is
the documented contract behind the `host_governed` write posture advertised by
the runtime contract endpoint and by `implementation_posture.v1` metadata.

## Contract Status

- This is a documentation contract for host integrations. It is not part of the
  frozen 0.1 public PHP API ([Public API](public-api.md)). Adding public
  host-governed helper functions would require a new ADR first
  ([0.1 freeze rules](public-api-freeze-0.1.md)).
- The gate applies to all built-in write and destructive ability families
  (core write, core destructive, post attribute writes, and site editor
  writes). Third-party provider abilities follow the metadata their providers
  declare.

## 1. Write Control Inputs

Every built-in write and destructive ability input schema automatically
declares:

| Field | Type | Default | Meaning |
|---|---|---|---|
| `dry_run` | boolean | `true` | Return a host-governed preview without mutating WordPress. |
| `commit` | boolean | `false` | Attempt the final commit. Host approval is still required. |
| `idempotency_key` | string, 1-190 chars | unset | Host-provided key for audit and replay correlation. |

Dry-run wins: `dry_run=true` combined with `commit=true` still returns a
preview. Only `commit=true` with `dry_run` unset or explicitly `false` can
reach the commit gate.

## 2. Dry-Run And Commit Outputs

Dry-run responses set `dry_run: true`, `host_governed: true`, and
`commit_required: true`, plus a `preview` object describing the would-be
change. A successful commit returns the committed payload with
`dry_run: false`.

## 3. The Commit Gate

The gate runs after input validation and before any WordPress mutation:

1. The Toolkit reads `$GLOBALS['npcink_ai_runtime_wp_ability_context']['context']`.
   This is the runtime channel of the first host implementation, Npcink AI.
   The only consumed value is `approval_commit_authorized` (boolean).
2. The Toolkit applies the filter
   `npcink_abilities_toolkit_write_commit_allowed` with
   `($allowed, $ability_id, $input, $runtime_context)`. The filter receives the
   current decision — `true` when the global channel already authorized the
   call — and returns the final decision for that call.
3. If the final decision is not authorized, the Toolkit fails closed with a
   `WP_Error` `npcink_abilities_toolkit_host_approval_required` (HTTP 403,
   `host_governed: true`). No mutation happens.

Third-party hosts should implement the filter. It is toolkit-prefixed, receives
the full input including `idempotency_key`, and does not depend on any Npcink
AI state. Hosts that already set the Npcink AI global keep working unchanged:
both channels feed the same single decision, and a filter callback should keep
returning `true` when it receives `$allowed === true` instead of vetoing
another runtime's authorization.

## 4. Required Host Evidence

The `implementation_posture.v1` metadata (`required_host_evidence`) documents
what a host must be able to show before requesting a commit:

- an approved proposal id or equivalent host approval token;
- caller identity and capability context;
- an `idempotency_key` for replay protection.

The gate itself only consumes an authorization decision. Recording approvals,
verifying caller identity, and keeping the audit trail remain host
responsibilities.

## 5. Ownership Boundary

The host owns caller identity and capability checks, approval records and
lifecycle, audit truth, quota, model routing, and final write orchestration.
The Toolkit owns ability ids, schemas, callbacks, dry-run preview shape, and
this gate. The Toolkit never stores approvals, audit records, or provider
credentials, and it is not the final write authority.

## 6. Worked Example

- [examples/third-party-host-approval.php](../examples/third-party-host-approval.php)
  shows a fail-closed third-party host: dry-run preview first, its own recorded
  approval keyed by ability id and idempotency key, then the filter decision.
  It never performs a final write itself.
- Verify the example against this contract with:

```bash
composer check:host-approval
```

- For the Npcink AI Core integration shape, see
  [Core Governance Handoff Guide](core-governance-handoff-guide.md).
- For deployment shapes and package gating, see
  [Host Profiles](host-profiles.md).
