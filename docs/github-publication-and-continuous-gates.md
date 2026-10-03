# GitHub Publication And Continuous Gates

Status: active operations note.
Last verified: 2026-10-02.

This note records the public GitHub handoff and the current continuous
performance and security gates for `npcink-abilities-toolkit`.

## Repository State

The canonical public source repository is:

```text
https://github.com/npcink/npcink-abilities-toolkit
```

GitHub repository state verified on 2026-10-02:

- owner/name: `npcink/npcink-abilities-toolkit`;
- visibility: public;
- default branch: `master`;
- published branch: `master`;
- published release tags: `0.2.0`, `0.4.0`, `0.5.0`, `0.5.1`, `0.5.2`,
  `0.5.3`, `0.5.4`, `0.5.5`, and `0.5.6` (`0.5.4` and `0.5.5` were
  Git-only releases; `0.5.6` is the current WordPress.org stable tag);
- maintenance marker: `pre-refactor-2026-07-14`.

## Master Branch Protection

`master` is the published branch and should be updated through pull requests,
not direct pushes.

Required checks:

- `php (8.0)`;
- `php (8.3)`;
- `PR body contract`;
- `wordpress-smoke (minimum)`;
- `wordpress-smoke (current)`.

The checks are strict, so pull requests should be current with `master` before
merge. Administrator bypass should stay disabled in normal operations. If a
break-glass direct push is ever used, verify the resulting commit's GitHub
Actions run, record the reason in the release or operations note, and restore
the pull-request path immediately.

The local checkout should use the canonical GitHub repository as `origin`:

```text
origin  git@github.com:npcink/npcink-abilities-toolkit.git
```

GitHub redirects the earlier `muze-page/npcink-abilities-toolkit` URL, but local
checkouts should be normalized to the canonical owner before publication. The
previous Gitee remote, `git@gitee.com:gitgreat/npcink-abilities-toolkit.git`,
should not be used for this repository.

## Continuous Gate Baseline

The default source gate is:

```bash
composer test:all
```

It now includes:

- Composer metadata validation;
- Composer dependency advisory audit from `composer audit --locked`;
- project boundary checks;
- ability contract readiness;
- consumer and workflow handoff checks;
- official WordPress AI stack compatibility checks;
- MCP exposure audit;
- provider demo smoke;
- ability catalog audit;
- WordPress.org review guard;
- release-source immutability regression;
- WordPress.org SVN working-copy freshness regression;
- WordPress smoke lifecycle restoration regression;
- single-site and multisite uninstall cleanup regression;
- bounded performance smoke;
- lightweight regression tests;
- PHP syntax linting.

The PHP CI matrix matches the package runtime floor:

- PHP `8.0`;
- PHP `8.3`.

The WordPress runtime matrix covers:

- WordPress `6.9.4` with PHP `8.0`;
- WordPress `7.0` with PHP `8.5`.

PHPStan also analyzes against PHP `8.0`, matching `composer.json`'s
`php >=8.0` requirement.

Since 2026-10-03 the PHP CI job also runs `composer check:phpstan-ratchet`:
for every analyzed PHP file a pull request touches, the PHPStan level-4
error count must not exceed the count at the base revision. The gate skips
when no analyzed file changed, when the base ref is unavailable, or when
composer dependencies cannot be installed in the temporary base worktree.
Existing level-4 debt never blocks a change; new level-4 debt does. The
first application of a level-4 pass over changed code caught the
`wp_get_ability()` array/object mix-up before any release shipped it (see
ADR 0008).

## Verified Commands

The following local checks passed after adding the dependency audit gate and
aligning PHP version targets:

```bash
composer validate --no-check-publish
composer audit:composer
composer test:all
composer analyse:phpstan
```

The 2026-07-30 publication baseline is pull request
[#104](https://github.com/npcink/npcink-abilities-toolkit/pull/104), merged as
`625a107cadabe2712c95b01eeb415e562360b94a`. The source, runtime, package, and
closeout evidence is summarized in
[System Audit And Closeout Standard](system-audit-and-closeout-standard.md).

## Known Historical CI Signal

The pushed historical tag `0.5.0` has a failed GitHub Actions run because that
tag's workflow still runs PHP `7.2` while the package already requires
`php >=8.0`.

Do not move the published `0.5.0` tag only to make that historical CI green.
Use the current `master` baseline for the next patch release instead.

## Auto-Merge Operational Lessons (2026-10-02)

Recorded after the `0.5.6` closeout pull requests (#174–#176) went through
protected squash auto-merge.

**Advisory review threads gate auto-merge.** The OpenCodeReview workflow
posts fresh inline comments on every push, and required conversation
resolution blocks auto-merge while any thread is unresolved — even with
every required check green. Diagnose with the PR's `reviewThreads` (GraphQL)
before assuming CI is stuck.

**End the advisory-review loop deliberately.** Every push can trigger one
more round of low-severity findings. Fix real findings with code, but once a
round only produces nits that keep regenerating, record why each finding is
acceptable in a thread reply, resolve the thread, and stop pushing; the
merge then proceeds on the existing green head. Pushing again to chase the
latest nit restarts the cycle.

**A sibling merge stalls older pull requests.** With strict up-to-date
checks, merging one PR first puts still-open PRs into the `BEHIND` state and
auto-merge waits indefinitely. Merge `origin/master` into the topic branch
and push; checks rerun and auto-merge resumes.

**Squash merges need verified branch deletion.** After a squash merge,
`git branch -d` refuses ("not fully merged") because the original commits
are not ancestors of the squash commit. Verify the content landed with an
empty `git diff <branch> master` for the PR's files, then delete with
`git branch -D`.

**Local tool hangs are transient.** During this session `gh`, `curl`, and
one `composer` script run each stalled once (no code defect; retries and
direct REST calls recovered). When the `gh` CLI hangs, `curl` against
`api.github.com` with the token from `gh auth token` usually still works;
parse API JSON with `strict=False` because review bodies can contain raw
control characters.

## Future Release Follow-Up

Before the next public patch release after `0.5.6`:

1. Run the release gate from a clean, verified source checkout:

```bash
WP_PATH=/path/to/wordpress composer release:verify
```

For Local.app sites that require a MySQL socket, include
`WP_CLI_MYSQL_SOCKET=/path/to/mysqld.sock`.

2. Record the smoke and Plugin Check result in the next release verification
   note.
3. Tag a new patch release from the verified `master` commit instead of
   retagging a historical release.
