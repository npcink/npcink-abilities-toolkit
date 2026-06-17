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
| `workflow/wordpress_article_optimization` | Closed-loop host proof passed on 2026-06-17. Toolkit exposes the declarative workflow and replay fixture. Core smoke preserves the recipe ref through from-plan proposal intake and verifies no post excerpt mutation. Adapter pull requests #2 and #3 resolved signed-client preflight binding and dependency contract semantic checks. Adapter pull request #5 added and passed a real WordPress smoke that runs the Toolkit apply plan through Adapter `run-read-ability`, submits it through Adapter `/proposals/from-plan`, reads the Core proposal back through Adapter, and verifies the source recipe, no-direct-write posture, and unchanged post excerpt. | Keep observing; no Toolkit ability gap is open from this proof. | None. |
| `workflow/wordpress_article_media_handoff` | Closed-loop host proof passed on 2026-06-17. Toolkit exposes the declarative workflow/replay direction. Toolbox has adjacent article/media batch and media conversion review-set evidence that keeps Core proposal review and no-execute boundaries visible. Adapter pull request #6 added and passed a real WordPress smoke that discovers the workflow through Adapter, runs `build-media-seo-assets`, creates a governed Core `update-media-details` proposal from reviewed metadata, reads the proposal back through Adapter, and verifies the recipe ref, no-direct-write posture, and unchanged attachment metadata before approval/execution. | Keep observing; no Toolkit ability gap is open from this proof. | None. |
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

Adapter pull request #5, "Add article optimization host proof smoke", merged on
2026-06-17:

- https://github.com/muze-page/npcink-ai-client-adapter/pull/5

That pull request merged:

- `1f2cb9c Add article optimization host proof smoke`

Adapter pull request #6, "Add article media handoff host proof smoke", merged on
2026-06-17:

- https://github.com/muze-page/npcink-ai-client-adapter/pull/6

That pull request merged:

- `01db3ce Add article media handoff host proof smoke`

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

The article optimization host proof was locally validated with:

```bash
composer test:all
WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.5.3+1/bin/darwin-arm64/bin/php" \
WP_CLI_MYSQL_SOCKET="$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" \
composer smoke:wp
git diff --check
```

The article media handoff host proof was locally validated with:

```bash
php -l tests/smoke-wp.php
git diff --check
composer test:all
WP_CLI=/tmp/wp-cli.phar \
WP_CLI_PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.5.3+1/bin/darwin-arm64/bin/php" \
WP_CLI_MYSQL_SOCKET="$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock" \
composer smoke:wp
```

Disposition: the Adapter branches are resolved and
`workflow/wordpress_article_optimization` plus
`workflow/wordpress_article_media_handoff` are closed-loop proven from current
main branches. No Toolkit follow-up ability is justified by these proofs.

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
