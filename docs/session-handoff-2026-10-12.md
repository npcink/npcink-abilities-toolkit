# Session Handoff: 0.5.12 Release And Read-Only Mode Closeout

Operational update (2026-10-13): Gitee enablement and the locale-default
branch are deferred. Dependencies #245/#246 are merged; ADR and Local smoke
guidance are refreshed. The next release is 0.6.0 for read-only mode. The
dated snapshot below remains historical; use current operations docs for
live state.

Status: active handoff note for the next AI session.
Written: 2026-10-12. Covers the 2026-10-11/12 session arc from the end-user
experience audit through the 0.5.12 WordPress.org release, the read-only mode
implementation, and everything still open.

## Read This First

The next session should start from `AGENTS.md` and this file. Everything in
this handoff was merged to `master` through reviewed pull requests; the
working tree at handoff time is clean at `ad57703` with one deliberately
local-only branch (`codex/zh-cn-docs-mirror-default`, see Open Items).

Session pull requests, in order:

| PR | Content | State |
| --- | --- | --- |
| #236 | UX batch: operator-first troubleshooting docs, package on/off snippet, `Support\Docs_Links` + `npcink_abilities_toolkit_docs_base_url` filter, disabled-check reason, Copy response buttons, readme storefront rewrite, 3 strings x 7 locales | merged |
| #237 | Opt-in `gitee-mirror` workflow + ADR 0009 (read-only opt-out) | merged after 3 review rounds, all findings fixed |
| #238 | `release: prepare 0.5.12` + all four WP.org screenshots refreshed | merged |
| #239 | `docs/archive/release-0.5.12-verification.md` publication record | merged |
| #240 | Session lessons folded into operating docs (branch-check-before-commit, auto-merge re-request after thread resolution, quota-failure triage, screenshot capture procedure) | merged |
| #241 | l10n terminology alignment for the three 0.5.12 strings (6 of 7 locales had drift) | merged |
| #242 | Read-only mode implementation per ADR 0009 | merged |
| #243 | Post-merge review fixes for #242 (static class, POST nonce first, placement-aware redirect) | merged |

WordPress.org publication: **r3739502**, release tag `0.5.12` at `7be527b`.
Verified live: trunk Stable tag 0.5.12, `tags/0.5.12` present. #242/#243
landed after the release and ship with the next one.

## What The Next Session Inherits (Open Items)

Ordered by value; Gitee items 1-2 are intentionally deferred, item 3 is
complete, and item 4 is the next release.

### 1. Gitee mirror enablement (deferred)

The `gitee-mirror` workflow is merged but intentionally inert. Do not create
the repository, secret, or variable until there is a concrete mainland-China
documentation access need. This path is optional and does not block maintenance
or release. If that need returns, the maintainer must, in order:

1. Create the empty repository `gitee.com/npcink/npcink-abilities-toolkit`.
2. Add a deploy SSH key there; store the private key as the GitHub repository
   secret `GITEE_MIRROR_SSH_KEY`.
3. Set the GitHub repository variable `GITEE_MIRROR_ENABLED=true`.

A master push then mirrors automatically (tags mirror on their own pushes;
host keys are pinned in the workflow — if Gitee rotates them, refresh the
three fingerprints recorded there and dated 2026-10-11). Full setup notes:
`docs/github-publication-and-continuous-gates.md` section "Gitee Read-Only
Mirror".

### 2. Publish `codex/zh-cn-docs-mirror-default` after the mirror is live (deferred)

Keep the local-only branch at `aee42ea` unpublished while the mirror is deferred:
zh_ locale sites default their admin help links to the Gitee mirror, everyone else keeps GitHub, the
`npcink_abilities_toolkit_docs_base_url` filter overrides both. Tests cover
all three paths. **Why it is not pushed**: gitee.com/npcink does not exist
yet; merging now would point Chinese-locale sites at a 404. Before
publishing: verify the mirror serves blobs (fetch a doc URL through it),
rebase onto master — it will conflict in `tests/run/10-registry-plugin-boot.php`
against the #242/#243 read-only assertions; keep both sides' blocks (the
branch's edits are far below the read-only block, git may auto-resolve; if
not, both hunks belong in the final file).

### 3. Read-only mode: nothing further required

ADR 0009 is fully implemented and merged (#242 + #243). No open review
findings. Optional future polish, explicitly NOT committed to: a host-side
conflict notice when a host needs write abilities while read-only mode is on
(host products own that UX per the ADR).

### 4. Next release (0.5.13 or 0.6.0) when the maintainer decides

Unreleased since 0.5.12 (r3739502): #241 (l10n alignment), #242 + #243
(read-only mode; CHANGELOG Unreleased section already written). Release prep
follows `docs/wordpress-org-release-runbook.md`; the 0.5.12 run is the
freshest precedent including the screenshot procedure. Decision point for
the maintainer: read-only mode is a user-facing feature — consider whether
that makes it 0.6.0 instead of a patch.

### 5. Deferred / rejected items (do not re-litigate without new signal)

- Operator settings UI beyond the read-only checkbox — ADR 0009 chose one
  checkbox deliberately; more settings need a new ADR.
- Menu "(Npcink)" suffix — rejected: would churn every locale's msgids for
  low value; the plugin action link "View Abilities" already anchors entry.
- Per-ability schema viewer in the catalog table — rejected for now; fetch
  the detail endpoint on demand if developers actually ask.
- Translation ownership: bundled locales are machine-assisted starter
  packs aligned to each locale's terminology anchors; if translate.wordpress.org
  community translations appear, they override the bundled `.mo` files and
  the bundled set can shrink.

## Landmines Learned This Session (already in repo docs; quick reminder)

- **Verify `git branch --show-current` before every commit.** Polling CI with
  `git checkout master` in between caused two misfired commits on master this
  session; both recovered with `git reset --mixed HEAD~1` before any push.
- **Unresolved advisory-review threads silently cancel the queued
  auto-merge.** After resolving threads (REST reply `in_reply_to` + GraphQL
  `resolveReviewThread`), explicitly re-run `gh pr merge <n> --squash --auto`.
- **Advisory review may fail on Codex quota** ("usage limits" bot comment) —
  infra, not findings; record the accept reason or re-run with
  `/open-code-review`.
- **Post-merge comments still need triage**: #242's review posted after the
  auto-merge; all three findings were real and fixed forward in #243. Check
  `pulls/<n>/comments` after every merge.
- **Structure ratchet**: `includes/` line ceilings are hard; Test_Page is at
  1401 (ceiling), Read_Only_Mode at 138/139. When adding lines to a capped
  file, compress docblocks (drop the description line, keep tags) — the repo
  has precedent. The level-4 changed-file PHPStan ratchet needs the work
  COMMITTED before it sees the files, and `apply_filters()` returns are
  PHPDoc-typed string here, so cast instead of `is_string()`.
- **Locale files are hand-appended**: wrapped msgid lines must keep the
  trailing space (GNU join semantics) or translations silently mismatch; the
  round-trip check used this session is in the #241/#242 PR descriptions.
- **Local.app WP-CLI**: `WP_CLI_MYSQL_SOCKET` is NOT honored by the global
  `wp`; use `php -d mysqli.default_socket=<sock> -d pdo_mysql.default_socket=<sock>
  /opt/homebrew/bin/wp --path=...`. Socket hash rotates on site restart.

## Key Files From This Arc

- `includes/Admin/Read_Only_Mode.php` — the ADR 0009 toggle (static class).
- `includes/Support/Docs_Links.php` — filterable doc-link base.
- `docs/adr/0009-operator-read-only-package-opt-out.md` — accepted design.
- `docs/archive/release-0.5.12-verification.md` — 0.5.12 evidence + the #238
  advisory-quota accept reason.
- `.github/workflows/gitee-mirror.yml` — inert until enabled (item 1).
- `docs/troubleshooting.md` — operator-first sections (Start Here / packages
  on-off / doc mirror).
- CHANGELOG `Unreleased` section — the #241/#242/#243 pre-written release
  notes for the next release.

## Machine Memory Cross-Reference

The AI assistant's project memory also carries: OCR inline-thread merge
blocking mechanics, PHPStan ratchet gotchas (commit-first, apply_filters
cast), and the l10n hand-append constraints. That memory is agent-side; this
document is the repository-side record and the single source of truth for
the handoff.
