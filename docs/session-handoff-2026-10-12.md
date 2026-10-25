# Session Handoff: 0.5.12 Release And Read-Only Mode Closeout

Operational update (2026-10-13): 0.6.0 is released (WordPress.org SVN
revision 3742960; release PR #253), and the governed flagged-media deletion
ability is merged in PR #251. Gitee enablement and the locale-default branch
remain deferred. The dated snapshot below is historical; use `AGENTS.md` and
the current operating docs for live state.

Status: historical handoff note; it is no longer an active work queue.
Written: 2026-10-12. Covers the 2026-10-11/12 session arc from the end-user
experience audit through the 0.5.12 WordPress.org release, the read-only mode
implementation, and everything still open.

## Read This First

The next session should start from `AGENTS.md` and the current operating
documents. This file records the state observed on 2026-10-12; all claims
about the active branch, release, or work queue must be checked against the
current repository and GitHub state.

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
later shipped in 0.6.0, recorded at SVN revision 3742960.

## What The Next Session Inherits (Open Items)

Ordered by value; Gitee items 1-2 are intentionally deferred, and item 3 is
complete. The 0.6.0 release in item 4 is complete.

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

### 2. Automatic zh_ documentation links (deferred; preservation updated 2026-10-26)

The experiment at `aee42eaeb7ee57825ad740bbc51a032cd203d5f4` makes `zh_` locale
sites default their admin help links to Gitee, keeps GitHub for other locales,
and lets `npcink_abilities_toolkit_docs_base_url` override both. Its locale tests
and structure baseline belong to that experiment. This automatic routing has
not shipped on `master`; it remains deferred, unpublished, and disabled. The
filter itself shipped separately in #236. This is not a full-repository
translation proposal, and preservation is not delivery.

The 2026-10-26 decision accepts a dated archive ref plus the existing verified
complete bundle in place of indefinite live-local-branch retention. After this
documentation change merges, a separate checked retirement will move the
preservation responsibility from `codex/zh-cn-docs-mirror-default` to:

- `refs/archive/local-branches/20261026/codex/zh-cn-docs-mirror-default`, pointing
  exactly to `aee42eaeb7ee57825ad740bbc51a032cd203d5f4`;
- the private recovery index
  `toolkit-zh-docs-mirror-aee42ea-readonly-disposition-20261026`, which binds the
  existing `related-six-repo-unique-work-preservation/npcink-abilities-toolkit/selected-tips.bundle`
  and its prior independent recovery verification. The bundle SHA-256 is
  `3d8d463d16fdedfa3f97d40696189dc87d28a3df5ae40480ed2466c81250cf02`;
  its preserved ref is `refs/heads/preserved/docs-mirror` at that exact commit.

The original local branch remains until the separate retirement confirms the
archive target and bundle binding. Do not infer that the archive ref already
exists from this decision. The private index is a recovery locator, not a
public repository link or an additional publication requirement. Historical
closeouts describing the former live-branch hold remain unchanged.

Only revisit this optional path for an actual documentation-access need. Restore
exact commit `aee42eaeb7ee57825ad740bbc51a032cd203d5f4` from the archive ref, or
import `refs/heads/preserved/docs-mirror` from the indexed bundle into an isolated
checkout. Recheck the commit and tree
`6964a6b4bfbb2feffa66ebc43211216eab884359` before editing. Mirror enablement still
requires the setup in item 1 and verification that a documentation blob URL is
reachable; then rebase onto current `master`, retain both the locale tests and
current read-only assertions, and run fresh candidate gates and acceptance.
Neither preservation nor old test evidence authorizes publishing or enabling it.

### 3. Read-only mode: nothing further required

ADR 0009 is fully implemented and merged (#242 + #243). No open review
findings. Optional future polish, explicitly NOT committed to: a host-side
conflict notice when a host needs write abilities while read-only mode is on
(host products own that UX per the ADR).

### 4. 0.6.0 release: complete

The 0.6.0 release shipped the read-only mode, localization alignment, and the
release documentation. It is recorded at WordPress.org SVN revision 3742960
and tag `0.6.0`; future releases should follow the current runbook and create
a new release verification note.

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
- `CHANGELOG.md` and `docs/archive/release-0.6.0-verification.md` — the
  release notes and evidence for 0.6.0.

## Machine Memory Cross-Reference

The AI assistant's project memory also carries: OCR inline-thread merge
blocking mechanics, PHPStan ratchet gotchas (commit-first, apply_filters
cast), and the l10n hand-append constraints. That memory is agent-side; this
document is the repository-side record and the single source of truth for
the handoff.
