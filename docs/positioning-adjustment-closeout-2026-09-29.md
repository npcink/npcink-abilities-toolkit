# Positioning Adjustment Closeout - 2026-09-29

Status: historical closeout record with durable rules.
Date: 2026-09-29.

This document closes out the 2026-09-29 positioning-adjustment work: two
vertical recipe batches, the host-agnostic approval contract, the value-first
documentation reorder, cross-repository contract adoption, and the phpstan
dependency bump. It records the durable decisions and lessons future
maintainers and AI agents should reuse. Live state references were verified
on the closeout date; recheck before acting on them later.

## What Landed

| Change | Pull request | Repository |
| --- | --- | --- |
| Vertical recipe batch 1 (7 -> 9 cases: article-production promoted, media-seo-handoff added) | #125 | npcink-abilities-toolkit |
| Host approval contract doc + third-party example + `check:host-approval` gate | #126 | npcink-abilities-toolkit |
| Value-first README/docs reorder with boundary phrases preserved | #127 | npcink-abilities-toolkit |
| Vertical recipe batch 2 (9 -> 12 cases: media-governance-scan added, site-operations-scan and diagnostics-triage promoted) | #129 | npcink-abilities-toolkit |
| php-analysis group bump to phpstan 2.2.16 (Dependabot, body contract repaired) | #119 | npcink-abilities-toolkit |
| Platform index registers the host approval contract | #150 | npcink-workflow-toolbox |
| Execution profiles reference the host approval contract | #42 | npcink-ai-client-adapter |
| Adapter handoff doc references the host approval contract | #82 | npcink-governance-core |

Tracking issues: #123 (closed) and #128 (closed; one time-driven item handed
to the 2026-10-21 scheduled initial check).

## Durable Recipe Rules (learned while adding six cases)

1. Entrypoint candidates must be probed before a recipe is written. The
   workflow-consumer proof requires the entrypoint to be read-risk,
   approval-free, confirmation-free, REST-visible, and not MCP-public, and the
   recipe `required_scope` must equal the entrypoint's registered scope.
   Abilities with an empty `required_scope` (for example `list-media`) cannot
   anchor a recipe today.
2. Local-only abilities (`local_only_ability_ids()`: both diagnostics
   abilities and both workflow-recipe abilities) may anchor a recipe, but they
   never project into the Npcink catalog. The replay lock supports a
   `local_only_entrypoint` flag that asserts the negative expectation: the
   entrypoint must stay OUT of the catalog projection.
3. MCP-public abilities (for example `site-info`) cannot be recipe entrypoints
   because the proof requires `meta.mcp.public` to stay false.
4. Natural-task routing uses token overlap over the case corpus (title,
   recipe id, entrypoint id, natural tasks, expected sections, expanded ids).
   Write tasks with discriminating vocabulary and let
   `composer check:workflow-consumer` verify unambiguosity; iterate on wording
   rather than weakening the scorer.
5. Fixture regeneration: rebuild `tests/fixtures/agent-workflow-replay.json`
   from the provider with
   `json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )`
   plus a trailing newline, then update the `$expected_workflow_replay_cases`
   lock in `tests/run.php` in the same change.

## Response-Shape Rules (learned from smoke assertions)

Never assume a success envelope. Probe the live response first, then assert
the real contract:

- report-style abilities (`get-media-inventory-health`,
  `get-media-cleanup-opportunities`, dashboards) return
  `{success, data, meta, message}`;
- list-style abilities (`list-media`) return a raw paginated payload
  (`total`, `page`, `per_page`, `items`) with no success envelope;
- plan-style abilities may declare `commit_execution: false` explicitly —
  assert "false when present", which is a stronger governance signal than
  asserting the key is absent;
- diagnostics abilities return raw redacted reports keyed by section.

## Host Approval Contract Decisions

- The contract is documentation-only on purpose. The 0.1 public API freeze
  forbids third-party host-governed helper functions without a prior ADR; the
  filter channel is sufficient until a real third-party host reports friction.
- `npcink-ai-client-adapter` is the first implementer of the runtime channel
  (its controller sets the global with try/finally restoration);
  `npcink-governance-core` owns approval policy and never implements the gate.
  Third-party hosts should implement the toolkit-prefixed
  `npcink_abilities_toolkit_write_commit_allowed` filter.
- Platform-level references live in the toolbox platform index (pointer only,
  no rule forking, per the authority rule) and in the Adapter/Core contract
  docs.

## Documentation Rules (learned from the reorder)

- Boundary phrases are gate-locked: 7 normalized phrases plus the
  project-split link in README.md, 5 raw `strpos` phrases that must stay
  unbroken on one line, and 3 required links. Move boundary text deeper, never
  delete or rewrap it. `composer check:boundary` and `composer test` are the
  arbiter, not memory.
- External copy must not contain machine-local absolute paths; reference
  sibling repositories by name.
- `readme.txt` is already value-first and carries 19 asserted strings; leave it
  alone unless a release requires changes.

## Process And Tooling Lessons

- The shared PR publisher retries network steps with growing pauses (45s+).
  Never pipe its output through `tail`; run it raw so progress is visible. A
  silent long run is usually a retry cycle, not a hang.
- Squash-merged topic branches never satisfy `git branch -d`. Verify content
  equivalence with `git cherry master <branch>` (0 unmatched patches) before
  `git branch -D`, and record the reasoning for anything kept.
- The old `codex/ability-contract-source` branch (commit 738c15f) was deleted
  on 2026-09-29 after verifying its `contract_source` metadata content is
  present on master via PR #122; it was an earlier text form of merged work.
- Real-site smoke runs against the Local.app site whose plugin directory is a
  symlink to this repository. WP-CLI needs the site MySQL socket passed
  through PHP ini flags (`mysqli.default_socket`,
  `pdo_mysql.default_socket`), not just an environment variable.
- Dependabot PRs fail the required PR body contract. Maintainers should edit
  the PR body into the Scope/Boundary/Verification/Risk template and run
  `gh pr update-branch` before merging; the code checks themselves are
  unaffected.

## Verification Snapshot (2026-09-29)

- `composer test:all`, `composer analyse:phpstan`, `git diff --check` green on
  every merged toolkit PR; CI five-check matrix green on each.
- Real-site smoke (`composer smoke:wp`, default and light profiles) green
  after batches 1 and 2, including all six new recipe chains.
- Cross-repo `composer test` green for the three documentation-only adoption
  PRs.

## Deferred Work

- 2026-10-21 10:00 scheduled WordPress 7.2 Beta initial check (reports to
  issue #128); full `composer e2e:official-stack` and the
  `wordpress-core-ability-convergence.md` overlap refresh wait for its
  findings and for the maintainer.
- Recipe batch 3 candidate: style baseline as its own recipe.
- Taxonomy governance recipe awaits a stable schema decision; draft cleanup
  awaits a host governance surface for destructive actions.
- CHANGELOG entries for #125, #126, #127, #129, and #119 are recorded at the
  next release; WordPress.org release steps need maintainer SVN credentials.
- A public host-governance helper function remains gated behind a future ADR
  and a real third-party demand signal.
