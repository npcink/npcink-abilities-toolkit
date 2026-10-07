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

- Tabs are cut by audience, not by surface type: Overview answers the site
  owner in one screen (status, plain-language capability summary, write
  posture, guided next actions); Developer Tools hosts every technical
  section behind anchors, and legacy tab keys alias to it (#143 moved the
  scenario catalog there; #144 landed the full two-audience restructure
  after maintainer feedback that the four-tab layout mismatched the
  capability-package positioning).
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

## Admin Iteration Record (Rounds #143-#152)

Six further maintainer-driven rounds refined the surface after the
two-audience restructure. Durable rules they established:

- **Task-based sub navigation**: the Developer Tools tab renders only the
  active sub-section (Connection / Ability Catalog / Checks / Workflow
  scenarios) behind core WP `.subsubsub` styling; legacy tab keys map to
  their sub-section (#147).
- **Hero stat hierarchy**: stat tiles show large values with explanations
  demoted to hover tooltips; next-action cards are compact
  label-plus-button pairs. Numbers first, words on demand (#149).
- **Every navigation target has exactly one entry**: the capability summary
  dropped its duplicate catalog link and intro sentence, and the duplicate
  developer-access card merged into the host-product card because both
  targeted the connection sub-section (#150). Two locked assertions were
  migrated with the rationale recorded in the assertion message itself.
- **Identity messaging lives at the top**: the boundary sentence sits in the
  intro block, and the page ends on actions instead of a disclaimer (#151).
- **Asset cache busting uses file modification time, not the plugin
  version** (#152): versioning admin.css/admin.js with the release-time
  plugin constant left every between-release styling change pinned to a
  stale browser cache — new HTML rendered with old CSS and looked like
  "nothing changed". Verify rendered output, not just committed code.
- **OpenCodeReview advisory comments block required conversation
  resolution**: every PR now collects bot review threads that must be
  resolved before auto-merge completes; treat thread resolution as a
  standard closeout step for each PR.

The recurring meta-lesson: each round was driven by an actual screenshot
from the maintainer, and each correct fix REMOVED something (a section, a
duplicate, a sentence) rather than adding. When a layout feels wrong,
suspect redundancy before absence.

## Admin Iteration Addendum (Rounds #154-#159)

Six further subtraction rounds after the first record, each applying the
established rules rather than inventing new surfaces:

- **Buttons over cards** (#154): the three next-action cards collapsed into
  one flex button row; card containers and headings whose semantics repeated
  the button labels were removed (assertions migrated with rationale in the
  assertion message).
- **The tooltip-folding pattern** (#155, #157, #158): locked explanatory
  sentences that no longer earn vertical space fold verbatim into `title`
  attributes (page boundary line, connection section description, discovery
  description). Presence-based assertions are position-independent, so this
  costs zero migrations — reuse it instead of deleting locked text.
- **On-demand output** (#158): always-rendered empty diagnostics textareas
  start `hidden` and are revealed by the writing JS entry points, mirroring
  the check-summary hidden toggling.
- **Unique-entry enforcement bites its own tail** (#159): the Host Approval
  Contract link added to the overview in #132 was removed there because the
  connection sub-section's integration guides already served the target —
  the rule caught an entry this same workstream had created.
- **Entry relocation beats deletion** (#156): the scenario summary line
  became a count-carrying button; information survived while the layout
  lost a text line.

Final overview shape: title + one intro line -> large stat tiles -> category
chips -> four task buttons. Every element has one purpose and every target
one entry.
