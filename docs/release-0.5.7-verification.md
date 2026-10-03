# 0.5.7 Release Verification

Status: release candidate verified; publication operations remain with the
maintainer's terminal (SVN credentials are typed there, never into agents).

This release ships the user-experience hardening batch already recorded in the
0.5.7 changelog (read-cache invalidation for direct post-meta writes, fail-loud
admin health notices, uninstall cleanup extensions, the honest
`ability_catalog_available` contract value with ETag/304 revalidation, and the
admin surface usability fixes), plus the bundled locale backfill: de_DE, es_ES,
fr_FR, ja, ko_KR, and pt_BR were restored to full template parity, zh_CN gained
the newest strings, and the POT template was regenerated against the 0.5.7
headers. No ability id, schema, annotation, callback, dry-run default,
permission, or final authorization contract changed.

## Locale Backfill Notes

- Every bundled locale now compiles to 1,070 translated messages with zero
  untranslated and zero fuzzy entries (`msgfmt --check` clean).
- The six older locale files carried no `Plural-Forms` header at all; each now
  declares the correct rule for its language (French and pt_BR use
  `plural=(n > 1)`, Japanese and Korean use `nplurals=1; plural=0`).
- The plugin-name msgid is now consistently kept untranslated ("Npcink
  Abilities Toolkit") across all bundled locales, matching the zh_CN convention
  from the 0.5.6 backfill; the legacy translated name entries were unified.
- Known limitation inherited from the starter sets: the pre-existing glossary
  translations contain a few machine-translation slips that predate this
  release (for example pt_BR "Get Term" -> "Obter prazo"). The 476 new strings
  per locale deliberately avoid reusing those renderings, but the old entries
  were not rewritten in this release to keep the diff reviewable.

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
| `composer test:all` | Pass | All source, contract, package, lifecycle, cron, uninstall, performance, and syntax checks passed. |
| `composer analyse:phpstan` | Pass | PHPStan level 3 reported no errors. |
| `composer check:boundary` | Pass | Project boundary guard passed. |
| `composer check:wporg` | Pass | WordPress.org review rules guard passed, including the bundled locale po/mo pairing checks. |
| `composer release:verify` (LocalWP) | Pending | Full lane with `WP_PATH="/Users/muze/Local Sites/magick-ai/app/public"` and the live site MySQL socket. |
| `git diff --check` | Pass | No whitespace errors. |

## Environment Notes

- The bundled locale `.mo` files were recompiled from the backfilled `.po`
  files; `msgfmt --check --statistics` reports 1,070 translated messages for
  every locale.
- WordPress.org SVN publication (`composer release:prepare-wporg` plus the
  `svn` commit) is intentionally left to the maintainer's terminal, where the
  credentials are typed.
