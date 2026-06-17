# Host Proof Status

Status: active tracking note.
Date: 2026-06-17.

This note records the current host-proof ledger for the freeze/observe period.
It is intentionally a status document, not an ability backlog. A new Toolkit
ability remains justified only when a real host proof fails because a small,
reusable, WordPress-only contract is missing.

## Current Decision

Keep `npcink-abilities-toolkit` in freeze/observe mode.

- Do not open a new ability branch from candidate lists alone.
- Prefer host, Core, Adapter, or Toolbox fixes when the failure belongs to
  routing, approval, execution handoff, product UX, runtime, model selection,
  cloud execution, or prompt behavior.
- Create Toolkit work only for a proved WordPress-local contract gap or a
  proposal/dry-run schema gap that existing abilities cannot satisfy by
  composition.

## Proof Ledger

| Proof target | Current state | Missing before closed-loop proof | Owner of next action |
| --- | --- | --- | --- |
| `workflow/wordpress_article_optimization` | Partially proved. Toolkit exposes the declarative workflow and replay fixture. Core smoke preserves the recipe ref through from-plan proposal intake and verifies no post excerpt mutation. Adapter pull request #2 merged signed-client preflight binding work, Adapter pull request #3 merged dependency contract semantic checks, and a prior targeted replay reached a Core proposal without commit execution. | Rerun the host proof from current main branches and record the result. | Host proof. |
| `workflow/wordpress_article_media_handoff` | Partially proved. Toolkit exposes the declarative workflow/replay direction. Toolbox has adjacent article/media batch and media conversion review-set evidence that keeps Core proposal review and no-execute boundaries visible. | Run a focused host proof for this workflow from current main branches, covering discovery, plan/proposal handoff, disallowed direct writes, and readback of proposal status. | Host/Toolbox/Adapter proof. |
| Block theme / Gutenberg intent routing | Contract-hardened, not fully host-proved. Toolkit and Core now cover block theme context, bounded layout profiles, proposal preservation, and no mutation during from-plan intake. Recent Gutenberg composer repair-loop work expanded the proof surface, so the freeze posture must be watched closely. | Run a host-side intent-routing proof that discovers abilities through WordPress Abilities API and stops at proposal/write targets. Do not add another Toolkit ability unless that proof identifies a reusable WordPress contract gap. | Host proof; Toolkit only if failed proof is Toolkit-owned. |

## Adapter Pull Request Disposition

Adapter pull request #2, "Bind Adapter preflight handoffs to signed clients",
merged on 2026-06-17:

- https://github.com/muze-page/npcink-ai-client-adapter/pull/2

The pull request merged two commits:

- `045dc74 Tighten adapter execution handoff validation`
- `9032d76 Bind Core preflight to signed local clients`

Adapter pull request #3, "Validate dependency contract semantics", also merged
on 2026-06-17:

- https://github.com/muze-page/npcink-ai-client-adapter/pull/3

That pull request merged:

- `f6099b6 Validate dependency contract semantics`

Local validation before merge:

```bash
composer test:all
git diff --check master..codex/approve-and-execute
```

The dependency contract semantics follow-up was also locally validated with:

```bash
composer test:all
git diff --check
```

Disposition: the Adapter branch is resolved. Do not treat
`workflow/wordpress_article_optimization` as closed-loop proven until the host
proof is rerun from current main branches and the result is recorded.

## Failure Classification

Use this classification before creating Toolkit work:

| Failure | Default owner | Toolkit work? |
| --- | --- | --- |
| Host cannot decide when to call a recipe | Host or Adapter | No |
| Prompt, model, or runtime selection fails | Host, runtime, or Cloud | No |
| Approval, audit, or preflight is missing | Core or governance host | No |
| Final write execution handoff is not signed or bounded | Adapter and Core | No, unless the ability schema is insufficient |
| Product UI cannot display the review set | Toolbox or product host | No |
| WordPress-local context is missing from an existing read/proposal contract | Toolkit | Maybe |
| A dry-run/proposal schema cannot express a reviewable plan | Toolkit | Maybe |

## Release Posture

No `0.5.2` release is open from this status note alone. A maintenance release is
reasonable only if the next proof work stays limited to documentation, replay
fixtures, contract snapshots, CI checks, or existing-ability hardening.

Do not treat the current state as `0.6.0` material unless a real host proof
fails and identifies one to three small, reusable, WordPress-only gaps that
cannot be fixed in host routing, Core governance, Adapter handoff, Toolbox UX,
Cloud runtime, or prompt design.
