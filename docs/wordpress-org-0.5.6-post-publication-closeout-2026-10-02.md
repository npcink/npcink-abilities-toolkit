# WordPress.org 0.5.6 Post-Publication Closeout

Status: completed.
Date: 2026-10-02.

This note records the WordPress.org SVN publication of `0.5.6`, the stale
working-copy incident found and resolved immediately before the credential
commit, and the follow-ups that remain open after publication.

## WordPress.org Publication

The `0.5.6` release was published to WordPress.org SVN after the full local
release lane and packaged Plugin Check passed (see
[0.5.6 Release Verification](release-0.5.6-verification.md)).

Evidence:

- `composer release:verify` passed on the release candidate, including the
  packaged Plugin Check with zero errors after the two blocking fixes.
- Release source: Git tag `0.5.6` at commit `70c10d9`, enforced by
  `scripts/check-release-source.sh` during `composer release:prepare-wporg`.
- WordPress.org SVN commit revision: `3723664`.
- Remote `tags/0.5.6/` exists.
- Remote `trunk/readme.txt` reports Stable tag `0.5.6`.
- The local SVN working copy is clean after the commit.

Directory jump: before this commit the WordPress.org trunk was still `0.5.3`;
`0.5.4` and `0.5.5` were GitHub-only releases. WordPress.org consumers jump
from `0.5.3` to `0.5.6`, and the `readme.txt` changelog covers the `0.5.6`
entries.

## Stale SVN Working Copy Incident

The prepared working copy at
`build/wporg-svn-wc/npcink-abilities-toolkit` was found to be based on
revision `3581121` (checked out 2026-06-06, the 0.5.0 era) while the remote
repository had moved to `3723624`. The stale copy still carried a
never-committed `0.5.3` preparation: phantom `svn add` entries for
`tags/0.5.3`, which already existed remotely, plus `0.5.6` staged on the
stale base. The documented commit command would have failed on out-of-date
paths and duplicate adds.

Root cause: `scripts/prepare-wporg-svn-release.sh` requires an existing
`.svn` directory but never runs `svn update`, so it stages a release onto
whatever revision the local working copy happens to be. CI cannot catch this
because `build/` is ignored local state.

Resolution used:

1. Archived the stale copy to
   `build/wporg-svn-wc/npcink-abilities-toolkit.stale-r3581121-20261002`
   (ignored local state; safe to delete).
2. Fresh `svn checkout` at remote HEAD `3723624`.
3. Re-ran `VERSION=0.5.6 composer release:prepare-wporg` from the exact tag
   commit in a clean worktree, then returned the worktree to `master`.
4. Verified the prepared status: one new tag directory, trunk updates and
   additions only, and `svn status -u` reporting zero out-of-date entries.

The script guard is tracked in
[issue #173](https://github.com/npcink/npcink-abilities-toolkit/issues/173):
fail closed when the working-copy revision does not match remote HEAD.

Durable lesson: the prepared SVN working copy is untested release surface.
Before handing off or running the credential-bound commit, run
`svn status -u` and require zero out-of-date markers, and confirm the status
contains only the intended trunk update plus the single new tag directory.

## Credential Delegation

The commit used the maintainer's locally cached Keychain credential for
`muze233` after the maintainer explicitly delegated the commit for this
release. No WordPress.org SVN password was typed into chat or exposed to the
agent session; the Keychain supplied it transparently. Delegation remains the
maintainer's explicit per-release choice, and the standing rule is unchanged:
WordPress.org SVN credentials are typed only by the maintainer.

## Listing Assets

Unchanged this release: `sj/exports/wordpress-org/` already matches the
remote `assets/` directory (the prepared status showed no asset changes).
Follow-up: the listing screenshots still show the pre-`0.5.6` admin page.
Refresh the screenshot exports and commit `assets/` separately when
convenient.

## Follow-Up

- Issue #173: add the stale working-copy guard to
  `prepare-wporg-svn-release.sh` and a runbook pre-check step.
- Issue #170: revise the delete-attachment candidate from the verified delta
  and decide new id versus in-place extension with #116.
- Refresh WordPress.org listing screenshots for the two-audience admin page.
- The WordPress 7.2 Beta automated initial check is scheduled for
  2026-10-21 10:00 local time and will report on issue #128; the full
  `composer e2e:official-stack` run and convergence refresh stay
  maintainer-triggered after that.
