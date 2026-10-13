# Toolkit todo closeout — 2026-10-13

Scope: Toolkit-owned release/documentation closeout. No public contract or
runtime code changes; sibling ownership and unrelated local edits are preserved.
Files: this note, release verification, documentation index and acceptance/
host-proof status. Gates: default source gates and diff check; the completed
central matrix is cited rather than copied into Toolkit tooling. Rollback is
a documentation revert, without changing the immutable release tag.

## Completed

- Released 0.7.0 through protected PR #256, exact merged-source verification,
  new Git tag and WordPress.org SVN revision 3744229. Remote tag contents match
  all 88 verified files; the public directory API now reports 0.7.0 and its
  public download ZIP matches the same verified file set.
- Packaged the existing REST discovery and dual-branch governed-media contracts;
  fixed file-operation Plugin Check errors with refusal recovery coverage.
- Updated seven complete runtime locale sets (1,138 messages each).
- Reconciled candidate/catalog/permission/host-proof documentation. Existing
  signed HTTPS and actual operator browser evidence close the media acceptance;
  no new manual approval fixture or Toolkit authority was needed.
- Prepared zh_CN listing translations for all 145 current source strings plus
  two planned release texts; preserved 86 online waiting suggestions. Compiled
  PO, HTML and placeholder checks passed. Prepared an unsent PTE request.
  After release, GlotPress still exported the previous 145-string source set;
  the two release strings remain prepared for its next source refresh.
- Final central matrix at 2026-10-13T14:33:02+00:00: Toolkit, Core, Adapter,
  Toolbox, Eval Lab and AI Cloud source gates passed. Toolbox had 11 dirty
  files; Cloud Addon had 13 dirty files and failed its settings line ceiling
  (2282 vs 2281). With `--fail-on-dirty`, there are three failures. This is
  a Toolkit release closeout, not complete all-platform acceptance.
- Verified ten historical branch heads against merged PRs and saved a valid
  recovery bundle at `/Users/muze/gitee/.worktree-backups/` with matching JSON
  records. Safe `git branch -d` refused squash ancestry; no force deletion done.

## External or operator steps

- Translation import is blocked by the locked Mac/unavailable browser connector;
  locale editor approval is external. The PTE draft cannot be sent without
  explicit authorization to contact Polyglots.
- Historical branch force deletion would require an explicit exception to
  `AGENTS.md`; the unpublished `codex/zh-cn-docs-mirror-default` is preserved.
- Active Toolbox/Cloud Addon work belongs to its owners; their dirty state is
  not reverted or included in Toolkit changes.

## Evidence-triggered maintenance

Gitee enablement/default-link switching, structural splits (#89), fingerprint
caching and Core deprecations remain conditional work. Start them only with
configured access or a measured contract/performance/size trigger; they are not
unfinished tasks in this release.
