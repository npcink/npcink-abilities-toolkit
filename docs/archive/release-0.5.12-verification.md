# 0.5.12 Release Verification

Status: verified on master `7be527b` (release tag `0.5.12`); all gates below
passed before the WordPress.org commit r3739502 on 2026-10-11.

## Scope

0.5.12 is the user-experience and operator-guidance release from the 2026-10
end-user audit. Two pull requests since 0.5.11: #236 (operator-first
troubleshooting docs with a no-command-line section and a package on/off
snippet, the `Support\Docs_Links` builder with the
`npcink_abilities_toolkit_docs_base_url` filter, disabled-check button
explanations, Copy response buttons beside the raw check response and raw
discovery outputs, the operator-value-first readme.txt Description, the SVN
screenshot reminder moved into the release runbook, and three new admin
strings across the template and all seven bundled locales) and #237 (ADR 0009
accepting the narrowing-only operator read-only opt-out with implementation
deferred to the 0.6 line, plus the opt-in `gitee-mirror` workflow; neither
ships in the WordPress.org package). No ability contract, schema, annotation,
response shape, workflow definition, or ability behavior changed anywhere in
the release; ability ids and payloads are identical to 0.5.11.

All four WordPress.org screenshots were refreshed: the previous set still
showed the pre-0.5.9 four-tab layout in Chinese. The new set was captured at
1366x920 from the local verification site (WordPress 7.1.1, PHP 8.2, the
0.5.12 source deployed to `magick-ai`, standalone Tools placement, en_US
admin user) with DOM-verified framing: screenshot 1 shows the Overview tiles,
screenshot 2 the Ability Catalog, screenshot 3 the Checks view with a
populated result table and the expanded raw-response disclosure including the
Copy response button, and screenshot 4 the Connection values with a fetched
abilities response and its Copy response button.

## Verification Evidence

Run against the release prep commit before the pull request merge, on this
device:

- `composer release:verify` (green): `test:all`, `analyse:phpstan`,
  `check:phpstan-total`, `smoke:wp-minimum` and `smoke:wp` (Docker WordPress
  matrices), and `check:plugin-package` against the local
  `WP_PATH=/Users/muze/Local Sites/magick-ai/app/public` site (packaged
  Plugin Check passed with no errors; the warnings are the known accepted set
  also present in 0.5.11).
- Required CI on pull request #238 all green: `php (8.0)`, `php (8.3)`,
  `wordpress-smoke (minimum)`, `wordpress-smoke (current)`, and the PR body
  contract.
- Boundary-language drift check: `AGENTS.md` and
  `docs/workflow-definition-contract.md` were untouched in this release arc,
  so there was no drift to reconcile; `composer check:boundary` and
  `composer check:wporg` passed inside `release:verify`.
- Advisory review note for #238: the OpenCodeReview job failed with no AI
  review delivered because the Codex code-review quota was exhausted for the
  day ("usage limits" bot comment). The check is advisory and never required;
  the textual diff was 18 lines of version/changelog text plus four binary
  screenshots, and the same code was reviewed in #236/#237 where the advisory
  review completed with all findings resolved, so the release merged without
  a re-run.

## Publication Record

- Git release tag: `0.5.12` at `7be527b3f36a5748daf810c2d2e09bb85076314f`.
- `VERSION=0.5.12 composer release:prepare-wporg` staged trunk plus the new
  `tags/0.5.12` directory and the four refreshed `assets/screenshot-*.png`
  files from `marketing/exports/wordpress-org/`.
- `svn status -u` before commit: zero `*` markers against revision 3739498.
- WordPress.org commit: **r3739502**, `svn commit --username muze233
  --non-interactive -m "Release 0.5.12"` (keychain-cached credentials).
