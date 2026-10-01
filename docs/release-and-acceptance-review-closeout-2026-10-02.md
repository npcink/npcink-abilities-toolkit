# Release 0.5.6 And Acceptance Review Closeout

Date: 2026-10-02.

## Change Envelope

- Target repository: `npcink-abilities-toolkit`.
- Focused module: the 0.5.6 release lane and the delete-attachment ability
  candidate governance.
- Intended change: ship the verified 0.5.6 release candidate, and run the
  candidate acceptance review per the ability-candidate rules.
- Explicit non-goals: no ability implementation, no new public contract, no
  structural slice (the admin test page slice stays evidence-gated).
- Public contracts touched: none.
- Landed pull requests: #168 (evidence inventory in the split plan), #169
  (0.5.6 release preparation, two release-blocking fixes, verification note),
  #171 (acceptance review record). Tracking issue: #170.
- Required gates run per pull request: `composer test:all`,
  `composer analyse:phpstan`, `composer check:boundary`, `git diff --check`,
  `composer release:verify` (full local lane), advisory `ocr review`, and the
  required GitHub checks.
- Cross-repository matrix: status inspected from the toolbox; this repository
  clean and synced; other repositories carry unrelated parallel states and
  were not touched.
- Rollback: each pull request is one squash commit; tag `0.5.6` would need
  deleting only before SVN publication (WordPress.org rules forbid retagging
  after publication).

## Durable Lessons

1. **Green CI is not release-ready.** Both release-blocking findings (an
   unescaped admin printf argument and agent-tool dotfiles inside the package)
   lived on `master` with every required CI check green. Only the local
   `composer release:verify` lane runs the real packaged Plugin Check against
   a WordPress environment. Rule: run the full local release lane before any
   release and treat its findings as blockers, never warnings to defer.
2. **`release:verify` is also a lint dragnet.** It caught a template-escaping
   defect introduced by an ordinary feature PR (#146) weeks earlier. When a
   release verification fails on source-level findings, fix them on the
   release branch as separate commits and record them in the verification
   note; do not postpone the release without them.
3. **Packaging hygiene guards local artifacts.** rsync-based packaging copies
   whatever sits in the working tree, so local tool dotfiles (`.zcode`,
   `.zcodeignore`) leak into the plugin until `.distignore` names them. CI
   cannot catch this — clean CI checkouts have no dotfiles. When a new tool
   starts writing into the repository root, extend `.distignore` in the same
   change.
4. **Stale git daemon sockets break Docker mounts.** A dead
   `fsmonitor--daemon` left `.git/fsmonitor--daemon.ipc` behind; the
   in-container `find` over the mounted `.git` failed with ENOENT under
   `set -e` and killed the minimum-WordPress smoke. `git fsmonitor--daemon
   status` says "not watching" — remove the stale socket and re-run.
   Packagist advisory-API timeouts (curl error 28) were transient; retry.
5. **Acceptance reviews must fact-check the premise.** The candidate claimed
   no attachment-deletion ability existed; `delete-media-permanently` has
   shipped for months. Before accepting any candidate, grep the live catalog
   for the claimed-absent capability and re-argue from the verified delta.
   A wrong premise with a real delta earns "revise before acceptance", and
   the new-id-versus-extend decision belongs with the duplicate-registration
   policy issue (#116), not inside the candidate document.
6. **The release sequence held end to end:** full local verification, blocker
   fixes, version PR, tag created on the exact merge commit, SVN working copy
   prepared from the tag, and the credentials-bound `svn` commit left in the
   maintainer's terminal by design.

## Outstanding

- WordPress.org publication of 0.5.6: run
  `SVN_USERNAME=muze233 COMMIT_MESSAGE="Release 0.5.6" build/commit-wporg-release.sh`
  from the maintainer's terminal.
- Issue #170: revise the delete-attachment candidate from the verified delta
  and decide new id versus in-place extension (with #116).
- The admin test page slice remains evidence-gated per the split plan
  inventory.
