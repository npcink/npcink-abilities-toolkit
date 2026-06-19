# Third-Party Integration Readiness Closeout

Status: local summary, ready for PR handoff
Date: 2026-06-20

This note summarizes the third-party integration readiness work on branch:

```text
codex/third-party-integration-readiness
```

The main implementation commit is:

```text
c77b638 Improve third-party integration readiness
```

This work improves the package's release-visible onboarding path for external
provider plugins and REST clients. It does not add a new first-party ability,
workflow runtime, model routing, approval store, audit truth, final write
authorization, or second WordPress control plane.

## Decision

The package is ready for controlled third-party integration trials.

Suitable near-term integration shapes:

- provider plugins that register read-only abilities through public helpers;
- provider plugins that register write-proposal abilities returning proposals,
  previews, diffs, or handoff payloads;
- REST or host clients that discover the WordPress Abilities API catalog first
  and use the Toolkit contract endpoint for compatibility and boundary checks.

This is not positioned as a broad self-serve ecosystem launch yet. It is a
release-visible readiness step that makes the first external provider/client
trial practical without moving host-owned governance into Toolkit.

## What Changed

Release-visible onboarding now exists in `readme.txt`:

- third-party provider quickstart;
- public helper entrypoints;
- warning against including internal `includes/` files or instantiating
  internal Toolkit classes;
- clear write-proposal boundary;
- REST catalog and run endpoint list;
- Toolkit contract endpoint;
- public GitHub repository for full examples and longer docs;
- explicit `wp-abilities/v1` availability prerequisite.

The admin discovery page now supports first-run integration work:

- overview includes an "Add provider abilities" next action;
- provider plugins are directed to public helpers instead of internals;
- REST checks expose a copyable contract endpoint in addition to categories and
  abilities endpoints;
- the page remains a discovery/status surface, not a runtime or governance
  console.

The phase documents now make the next-stage posture explicit:

- Toolkit stays in freeze/observe mode;
- Block theme / Gutenberg intent routing is the next narrow host-side proof;
- that proof must discover existing abilities through the WordPress Abilities
  API and stop at proposal, dry-run, or host-governed write target selection;
- the open Gutenberg proof does not block basic third-party read-only or
  write-proposal onboarding.

Translations were updated for the new admin strings:

- `languages/npcink-abilities-toolkit.pot`;
- Chinese Simplified and Traditional;
- Japanese;
- Korean;
- French;
- German;
- Spanish;
- Brazilian Portuguese;
- matching `.mo` files regenerated with `msgfmt`.

## Feedback Closed

The previous readiness review identified six remaining issues. Their current
disposition is:

| Issue | Disposition |
| --- | --- |
| Long docs do not ship in the release zip | Kept as intended. `readme.txt` now includes the minimum onboarding path and the public GitHub repository for full docs/examples. |
| Third-party write behavior could be misunderstood | `readme.txt`, admin copy, and tests now reinforce that provider callbacks must not perform final host-governed commits. |
| Abilities API availability is a hard prerequisite | `readme.txt` now states that `wp-abilities/v1` REST routes must exist and points sites to the baseline or compatibility plugin if missing. |
| New admin strings were not in translation files | `.pot`, all starter `.po` files, and `.mo` files were updated. |
| Block theme / Gutenberg proof was still open | The proof remains open but is documented as non-blocking for basic provider and REST onboarding. |
| Current integration work was not committed | The readiness scope was committed as `c77b638` on `codex/third-party-integration-readiness`. |

## Verification

The following gates passed after the readiness changes:

```bash
composer test:all
composer analyse:phpstan
git diff --check
```

`composer test:all` reported:

```text
OK: 4947 assertions
```

The default gate also passed:

- Composer validation;
- dependency audit;
- project boundary check;
- ability contract readiness;
- Core governance consumer handoff;
- workflow consumer proof;
- official WordPress AI stack compatibility;
- MCP exposure audit;
- provider demo smoke;
- catalog audit;
- WordPress.org review guard;
- performance smoke;
- Gutenberg composer pilot;
- PHP lint.

## Remaining Boundaries

Do not use this readiness work to justify broad ability expansion.

Open only a narrow Toolkit follow-up when a real third-party provider, host,
Core, Adapter, Toolbox, or MCP proof fails because of a small reusable
WordPress-local contract gap.

Default ownership remains:

- prompt, model, and runtime selection: host/runtime/Cloud;
- final write authorization: host/Core governance;
- approval storage and audit truth: host/Core governance;
- signed execution handoff: Adapter/Core;
- product review UX: Toolbox or consuming product;
- ability schema, metadata, dry-run/proposal shape, REST discovery, and package
  docs: Toolkit.

## Working Tree Note

At the time this summary was written, two closeout-related local changes were
still outside the readiness commit:

- `docs/README.md`;
- `docs/performance-security-hardening-closeout-2026-06-19.md`.

They predate this summary and should be handled separately from the
third-party integration readiness commit unless the maintainer intentionally
folds them into the same PR.

## Recommended Next Action

Open a lightweight PR from `codex/third-party-integration-readiness` after
deciding whether to include this summary document in the same PR.

Do not start a new release from this note alone. A release should be opened
only as a separate release task that updates the plugin version, readme stable
tag, changelog, and release verification note.
