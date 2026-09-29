# Admin Experience And Caller Contract Closeout - 2026-09-29

Status: historical closeout record with durable rules.
Date: 2026-09-29.

This document closes out the admin-experience and caller-contract batch
(tracking issue #131): documentation links in the admin surface, the workflow
scenario overview, the response shape registration gate, scope semantics, and
the standalone smoke. It also records why these gaps survived earlier gates,
so the same class of miss is less likely to recur.

## What Landed

| Change | Pull request |
| --- | --- |
| Admin workflow scenario overview, documentation links, i18n (en + zh_CN) | #132 |
| Response shape taxonomy, 126-ability registration fixture, `check:shapes` gate, `get-block-theme-context` schema completion | #133 |
| Scope semantics lint (capability-gated list), standalone admin smoke assertions | #134 |
| Three-persona walkthrough standard (this document's sibling change) | #134-follow-up |

## Why Earlier Gates Missed These (Root Causes)

1. **Gates verify implementations against contracts, not contracts against
   users.** The envelope/paginated/raw split was encoded per-ability in tests
   that all passed; no check compared shapes across abilities until
   `check:shapes`.
2. **The development environment is the integrated environment.** Local
   testing always had the full Npcink AI stack installed, so the standalone
   experience — the third-party user's reality — was never exercised before
   the dedicated smoke assertions.
3. **Prohibitions were enforced; positive journeys were not.** The admin
   surface had dozens of "must not" rules and zero acceptance criteria for
   "a standalone site owner knows the next step".
4. **Surfaces age separately from value.** Recipes grew from 7 to 12 while
   the admin page still presented a developer-facing id catalog; nothing
   triggered a surface review when recipe value landed.
5. **Knowledge stayed local.** Each response shape and each untranslated
   string was known to whoever wrote its test; nobody aggregated them into a
   contract-level statement until an external-consumer question was asked.

## Durable Rules

- Run the three-persona walkthrough (see Solo AI Development Workflow) for
  every user-facing surface change; gates do not replace it.
- Every read ability's response shape lives in
  `tests/fixtures/ability-response-shapes.json`; update the fixture in the
  same change as any output_schema or callback shape change
  (`composer check:shapes`).
- An ability without a scope must join the explicit capability-gated list in
  `scripts/check-ability-contracts.php`; an empty scope is a decision, not a
  default (see Permission Matrix "Scope Semantics").
- Admin external links follow `docs/admin-surface-standard.md`: stable
  documentation URLs as class constants, `rel="noopener noreferrer"`,
  integration guidance only.
- A new admin section needs its markup AND its stylesheet entries in
  `assets/admin.css` in the same change. The scenario overview in #132
  shipped without its grid styles and rendered as an unstyled stack until
  #139; no gate pairs custom classes with CSS, so check the rendered page,
  not just the PHP output.
- i18n strategy: new admin strings ship English-first with zh_CN translated
  in the same change; other bundled locales fall back to English. Note the
  pre-existing backlog: regenerating the POT on 2026-09-29 exposed 132
  post-2026-06 code strings no locale translates — decide that backlog
  separately.

## Known Follow-Ups

- The 132-string locale backlog exposed by the POT regeneration was backfilled
  for zh_CN on 2026-09-29 (1,052/1,052 translated, complete starter set
  restored); the other six bundled locales keep falling back to English per
  the agreed strategy.
- 80 of 126 shape entries are fixture-only until verification inputs or
  richer WordPress stubs exist (`runtime_verified: false`).
- Recipe batch 3 (style baseline) and the deferred contract decisions remain
  tracked in the positioning closeout document.
