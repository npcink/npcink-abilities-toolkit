# 0.5.6 Release Verification

Status: release candidate fully verified; publication operations remain
pending.

This release ships the 2026-09/10 batch: the `rename-media-file` production
defect fix (void-returning `wp_delete_file()` misuse), the admin two-audience
restructure, five workflow recipes, the host approval contract documentation,
ability response-shape and scope-semantics gates, the zh_CN locale backfill,
and two behavior-preserving write-package structural slices. No ability id,
schema, annotation, callback, dry-run default, permission, or final
authorization contract changed.

Two release-blocking findings were fixed during verification: the admin
scenario-count printf argument was not passed through an escaping function
(`WordPress.Security.EscapeOutput`), and local agent-tool dotfiles
(`.zcode`, `.zcodeignore`) leaked into the packaged plugin (`hidden_files`).

## Boundary

- No ability id, schema, annotation, callback, package profile, or workflow
  definition contract changed.
- Approval truth, audit truth, workflow runtime, cloud execution, and final
  write authorization remain outside Toolkit.
- Boundary language across `AGENTS.md` and
  `docs/workflow-definition-contract.md` was not modified in this release
  cycle; `composer check:boundary` passed.

## Verification Matrix

| Check | Status | Evidence |
| --- | --- | --- |
| `composer test:all` | Pass | All source, contract, package, lifecycle, cron, uninstall, performance, and syntax checks passed; 16,617 assertions. |
| `composer analyse:phpstan` | Pass | PHPStan level 3 (raised from 2 in this release) reported no errors. |
| `composer check:boundary` | Pass | Project boundary guard passed. |
| `composer check:wporg` | Pass | WordPress.org review rules guard passed. |
| `composer release:verify` (LocalWP) | Pass | Full lane passed with `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public"` and the site MySQL socket: source gate, PHPStan, LocalWP smoke (58 assertions), packaged Plugin Check, minimum WordPress 6.9.4 smoke (478 assertions), current WordPress smoke (479 assertions). |
| Packaged Plugin Check | Pass | Zero errors after the two blocking fixes; the pre-existing translation-loading and bounded direct-database-query warnings remain unchanged. |
| `git diff --check` | Pass | No whitespace errors. |

## Environment Notes

- The Docker legs initially failed on a stale `.git/fsmonitor--daemon.ipc`
  socket (dead daemon, present since May 30): the in-container `find` over the
  mounted `.git` failed with ENOENT under `set -e`. Removing the stale socket
  resolved it. If this recurs for any maintainer, `git fsmonitor--daemon
  status` confirms the daemon is not watching and the socket can be removed.
- Packagist's security-advisories API timed out once (curl error 28); the
  audit passed on retry with no advisories.
- WordPress.org SVN publication (`composer release:prepare-wporg` plus the
  `svn` commit) is intentionally left to the maintainer's terminal, where the
  credentials are typed.
