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
