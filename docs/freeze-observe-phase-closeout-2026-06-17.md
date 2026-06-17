# Freeze/Observe Phase Closeout

Status: phase closed
Date: 2026-06-17

This note closes the current host-proof push for
`npcink-abilities-toolkit`. It does not start a release, a new ability branch,
or a new workflow implementation phase.

## Decision

Keep `npcink-abilities-toolkit` in freeze/observe mode.

The immediate objective was to test whether the existing ability surface can be
discovered, composed, handed to host governance, and stopped before direct
WordPress writes. The two highest-value workflow proofs now pass from current
main branches, so the next correct action is to stop expanding and observe real
host usage.

## Completed

- `workflow/wordpress_article_optimization` has a closed-loop host proof.
- `workflow/wordpress_article_media_handoff` has a closed-loop host proof.
- Adapter handoff issues found during proof work were fixed in Adapter, not by
  moving runtime or governance into Toolkit.
- Toolkit's host proof ledger records both proofs and their validation commands
  in [Host Proof Status](host-proof-status.md).

The completed proofs cover the main boundary this phase needed to validate:

```text
Toolkit deterministic ability output
-> Adapter host/channel handoff
-> Core proposal storage and readback
-> no direct WordPress mutation before approval/execution
```

## Intentionally Not Done

- No new first-party Toolkit ability was added.
- No workflow runtime, scheduler, queue, prompt registry, model routing, or
  final write authority was added to Toolkit.
- No second ability registry, workflow registry, or WordPress control plane was
  introduced.
- `Block theme / Gutenberg intent routing` was not expanded into a new active
  proof phase.

The remaining block-theme/Gutenberg proof target still has value, but its
complexity risk is higher than the current marginal benefit. It should not be
used as a reason to reopen broad ability work.

## Deferred Candidate Abilities

The following names are deferred investigation labels, not backlog items:

- `read-block-template-context`
- `validate-gutenberg-block-tree`
- `preview-template-part-change`
- `read-template-part-usage`
- `resolve-template-target-candidates`

Do not add any of them from the name alone. Current Toolkit coverage already
includes block and Site Editor context, Gutenberg capability catalogs,
composition inspection, bounded block-theme planning, post block readback, and
host-governed template/post block write abilities. A new ability is justified
only if a later host proof shows that those existing contracts cannot be
composed into the needed proof path.

If this area is revisited, evaluate candidates in this order:

1. `validate-gutenberg-block-tree`, only if
   `inspect-gutenberg-composition-contract` cannot express the failing
   validation result.
2. `read-template-part-usage`, only if a host proof needs stable
   WordPress-local usage context and existing Site Editor context is
   insufficient.
3. `resolve-template-target-candidates`, only if the failure is not host
   routing or prompt selection and requires a reusable WordPress-local target
   resolution contract.
4. `read-block-template-context`, only if `get-block-theme-context` and
   `inspect-block-theme-surface` cannot provide the required template context.
5. `preview-template-part-change`, only after a separate proof shows a safe
   proposal-only preview contract is needed. This is the highest-risk candidate
   because it can drift into template-part planning or write ownership.

Until a proof fails, these candidates remain deferred.

## Reopen Triggers

Reopen Toolkit work only when a real host, Core, Adapter, Toolbox, or MCP proof
fails because of one of these Toolkit-owned gaps:

- an existing WordPress-local read/proposal contract cannot express required
  context;
- a dry-run or proposal schema cannot represent a reviewable plan;
- a registered ability exposes incorrect risk metadata, permission behavior, or
  discovery metadata;
- a bounded performance or diagnostics contract fails under a real host proof.

Do not reopen Toolkit work for:

- prompt or model selection;
- workflow runtime execution;
- approval storage, audit truth, preflight state, or final write authorization;
- Adapter routing and signed handoff policy;
- Toolbox UX or product review screens;
- Cloud execution, quotas, billing, provider credentials, or MCP gateway policy.

## Next Default Action

Do nothing new in Toolkit by default.

If the block-theme/Gutenberg path is revisited, keep it to a narrow proof only:

1. discover existing abilities through the WordPress Abilities API;
2. map intent to existing proposal/write targets;
3. stop before write execution;
4. record the result in the proof ledger;
5. open Toolkit work only if that proof identifies a small, reusable,
   WordPress-only contract gap.

Until then, this phase can be considered complete.
