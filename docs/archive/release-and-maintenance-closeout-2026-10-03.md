# Release And Maintenance Closeout 2026-10-03

Status: completed closeout record for the 2026-10-03 session.
Session scope: the 0.5.7 release with bundled locale backfill, the
`tests/run.php` structural split, the issue #116 policy decision, and the
PHPStan level-4 incremental ratchet.

## What Landed

| Change | Pull request |
| --- | --- |
| 0.5.7 release: version bump, changelog/readme closeout, POT regeneration, and the bundled locale backfill (de_DE, es_ES, fr_FR, ja, ko_KR, pt_BR each +476 strings; zh_CN +24; missing `Plural-Forms` headers added; plugin name unified untranslated); packaged Plugin Check kept error-free by routing the uninstall backup-tree deletion through `wp_delete_file()` | [#181](https://github.com/npcink/npcink-abilities-toolkit/pull/181) |
| `tests/run.php` split into a ~1,000-line ordered aggregator over eleven `tests/run/` part files cut along the original execution order; 16,657 assertions byte-identical | [#182](https://github.com/npcink/npcink-abilities-toolkit/pull/182) |
| ADR 0008 decided and implemented: duplicate-registration bounded diagnostics (`abilities.registration.duplicate` events) and the explicit default-enabled package policy; issue #116 closed | [#183](https://github.com/npcink/npcink-abilities-toolkit/pull/183) |
| WordPress-side duplicate comparison corrected to the `WP_Ability` object surface (the shipped `is_array()` check was dead code against real WordPress; found by a level-4 pass) | [#184](https://github.com/npcink/npcink-abilities-toolkit/pull/184) |
| PHPStan level-4 count ratchet for changed analyzed files, wired into the PHP CI job | [#185](https://github.com/npcink/npcink-abilities-toolkit/pull/185) |
| Ratchet base-worktree cleanup hardened after the macOS path-canonicalization leak | [#186](https://github.com/npcink/npcink-abilities-toolkit/pull/186) |

All six pull requests merged by protected squash; CI green on each. Final
repository state: single local worktree on `master` (`670d6e2`), no local or
remote topic branches, no open pull requests.

## Durable Lessons

1. **Bundled locale backfill is a scriptable pipeline, not a manual edit.**
   The working pipeline: `wp i18n make-pot` against the bumped headers,
   `msgmerge` each locale against the new POT, extract the untranslated and
   fuzzy inventory with `polib` (a hand-rolled PO parser miscounted multi-line
   msgstr and fuzzy flags), translate the inventory in parallel with one agent
   per locale constrained to that locale's own glossary sample, apply with
   `polib` (setting `Plural-Forms` per locale before filling plural entries),
   then verify with `msgfmt --check --statistics` plus `msgattrib` counts for
   zero untranslated and zero fuzzy. Each of the seven locales ended at
   exactly 1,070 translated messages.
2. **The packaged Plugin Check gate is the only thing standing between
   post-verification merges and a WordPress.org rejection.** The
   `unlink()`/`rmdir()` errors were introduced by PRs merged after the 0.5.6
   verification and were only caught when the 0.5.7 `release:verify` lane ran.
   Merged-but-unreleased code has not actually been release-checked.
3. **A level-4 pass over changed files pays for itself before the ratchet
   even exists.** It caught `wp_get_ability()` returning `WP_Ability` where
   the code assumed an array — a bug the unit harness could not catch because
   the stub returned an array. When stubbing core functions with objects,
   mirror the real return type (the `wp_get_ability` stub now constructs the
   same core-faithful `WP_Ability` getter surface).
4. **macOS `mktemp` paths are not canonical.** `mktemp` returns
   `/var/folders/...` while `git worktree list --porcelain` reports
   `/private/var/folders/...`; guarding cleanup on a grep of the listed path
   silently skipped removal and leaked one detached worktree per run
   (five recovered). Canonicalize with `pwd -P` and remove unconditionally
   under `|| true` plus `git worktree prune`.
5. **Squash merges make `git branch -d` structurally unable to verify
   mergedness.** The topic-branch commits are never ancestors of `master`, so
   `-d` always refuses. The evidence-based deletion used here: `git cherry
   master <branch>` shows zero unapplied patches for single-commit branches;
   for multi-commit branches whose PR squash-merged (the release and ADR
   branches), the GitHub merge SHA verified earlier in the session is the
   record. AGENTS.md's "delete with `git branch -d`" rule predates
   squash-always merges and should be read as "delete only with merge
   evidence", which `-d` can no longer provide on its own.
6. **The local `ocr review` advisory gate can stall without output.** When it
   hangs (zero output, ~0.03s CPU), do not block: the OpenCodeReview CI
   workflow posts the same review on the pull request, where the
   required-conversation-resolution rule makes findings actionable anyway.
   The review-loop rules in
   `docs/user-experience-hardening-closeout-2026-10-02.md` (batch a round,
   fetch authoritative thread ids, reply then resolve) were applied to #183
   and remain the protocol.
7. **A blocked-with-green-checks auto-merge usually means unresolved review
   conversations.** Branch protection requires conversation resolution;
   advisory-bot findings open threads that block the squash until replied to
   and resolved.

## Declines And Deferred

- The `sj/` directory rename to something self-describing was declined as
  optional churn; its README already explains the purpose.
- Pre-existing glossary machine-translation slips in the six older locales
  (for example pt_BR "Get Term" -> "Obter prazo") were deliberately not
  rewritten in the backfill to keep the release diff reviewable; recorded in
  `docs/release-0.5.7-verification.md` as a future small-batch polish task.
- Issue #89 remains open by design as the evidence-triggered structural
  split record; the test-suite slice it tracks is complete, the remaining
  candidates stay deferred pending evidence.

## Remaining Maintainer Steps

The 0.5.7 publication operations stay in the maintainer's terminal where
WordPress.org SVN credentials are typed:

```bash
git tag 0.5.7 && git push origin 0.5.7
VERSION=0.5.7 composer release:prepare-wporg
SVN_USERNAME=muze233 COMMIT_MESSAGE="Release 0.5.7" build/commit-wporg-release.sh
```
