# Pattern Page Reference Spike

This note records the reference decision behind
`npcink-abilities-toolkit/build-pattern-page-plan`. The goal is to learn from
existing Gutenberg page-building products without turning this package into a
page builder, block library, or direct WordPress writer.

## Decision

Use Pattern generation as a governed planning layer:

- AI fills page copy, section order, and variable content.
- The package renders a fixed, whitelisted Gutenberg block plan.
- Core receives the resulting plan through the proposal flow.
- Approved writes still execute through the host-governed `create-draft` and
  `update-post-blocks` abilities.

Do not add a third-party block dependency or an Elementor-style layout runtime.
This package should stay a small ability package, not a visual builder product.

## References To Learn From

These references are design inputs only. They are not dependencies.

### WordPress Core Block Patterns

WordPress Core already provides the main primitive this feature needs: reusable
patterns composed from core blocks. The applicable lesson is to keep generated
pages close to standard `core/group`, `core/columns`, `core/heading`,
`core/paragraph`, `core/buttons`, and `core/button` structures.

Adopt:

- section composition with core blocks;
- stable block attributes instead of hand-authored wrapper HTML;
- predictable pattern ids and variable slots;
- editor-friendly markup that remains recoverable after a serialize/parse
  round trip.

Avoid:

- custom block types for the first landing-page pattern;
- arbitrary HTML blocks as the primary layout mechanism;
- plugin-owned editor UI for inserting or modifying patterns.

### Pattern Libraries And Block Suites

Products in the Spectra, CoBlocks, Getwid, Gutenverse, and Extendify family show
that good landing pages usually come from reusable section patterns, not from
free-form per-request styling. Their useful pattern is a catalog of hero,
feature, proof, workflow, FAQ, and CTA sections with consistent spacing,
typography, color, and card treatment.

Adopt:

- a small internal pattern registry;
- named `pattern_id` and `style_preset` values;
- section-level variables rather than full layout DSL input;
- repeatable spacing, color, border, and typography tokens.

Avoid:

- depending on their custom blocks;
- copying their CSS or templates;
- adding a marketplace, pattern browser, or page-builder admin surface here.

### AI Page Builders

AI page-building products set user expectations for prompt-to-page workflows:
they ask for business context, generate a complete page, and keep the result
editable. The useful lesson is the product shape, not the execution authority.

Adopt:

- prompt variables that map to a complete page skeleton;
- visibly complete first output: split hero, proof strip, optional media-text,
  features, workflow, FAQ, and final CTA;
- metadata describing output quality, responsive behavior, and style strategy.

Avoid:

- direct publish behavior;
- model routing or prompt management in this package;
- bypassing Core proposal review.

## Applied To `openai-style-landing`

The current `openai-style-landing` pattern follows these rules:

- it is core-block-only;
- it requires no custom CSS;
- it uses whitelisted classes only as stable handles;
- it uses Gutenberg-native `align`, `layout`, `style.spacing`, `style.color`,
  `style.typography`, and `style.border` attributes for visual structure;
- it emits `design_quality` and `responsive_quality` summaries so hosts can
  verify the pattern strategy before proposal execution.
- `design_quality` includes OpenClaw-facing design-system signals:
  `design_system=gutenberg_native_v1`, `recipe_variant`, `variant_reason`,
  `section_shape_variety`, `media_coverage_score`,
  `template_similarity_score`, `uses_core_html`, and
  `uses_non_core_blocks`. These signals let hosts reject visually repetitive or
  unsafe plans before creating a Core proposal.
- it exposes bounded `color_story` choices. `minimal-dark-light` remains the
  conservative monochrome story, while `editorial-accent` uses only
  Gutenberg-native color, border, and spacing attributes to add a controlled
  accent rhythm without custom CSS.
- it emits `media_slots` so OpenClaw can determine target media ratios before
  generation or adoption. The default hero slot maps to `hero_media_url`,
  requires `16:9`, carries an aspect-ratio crop request, and recommends
  `openclaw_recipes.ai_image_ratio_crop_media_adoption`.
- `review-pattern-page` can review either a saved `post_id` or proposed block
  tree and report score, section variety, media completeness, responsive risk,
  native style density, and server-observable invalid-block risk without
  writing WordPress content.

Version 3.0 uses seven to eight sections:

1. split hero with CTA buttons and either reviewed media or a dashboard-style
   proof panel;
2. proof strip;
3. optional media-text section when OpenClaw or another caller supplies an
   existing media URL;
4. Gutenberg-native Bento feature grid;
5. workflow steps;
6. proposal-first comparison section;
7. FAQ with core details blocks;
8. final CTA.

The default responsive profile is `landing_standard`. It keeps `core/columns`
configured with `isStackedOnMobile=true`, uses `core/media-text` only for an
existing media URL, and reports max column count, FAQ presence, media presence,
button flex layout, and custom CSS requirements in `responsive_quality`.

`review-pattern-page` reports `color_story_monochrome` as a non-blocking visual
finding when a complete page still reads too black-and-white. If that review is
fed back into `build-pattern-page-plan` and the caller has not explicitly chosen
a `color_story`, the next plan switches to `editorial-accent` so the review loop
can improve visual rhythm while staying inside core blocks and native attrs.

This is intentionally closer to a reusable WordPress pattern library than to a
free-form page generator.

## Boundary Tests

Future changes should preserve these guardrails:

- `build-pattern-page-plan` remains a read-only planning ability.
- `review-pattern-page` remains a read-only quality review ability and must not
  emit write actions.
- Generated write actions remain proposal-bound and draft-safe.
- The pattern uses core Gutenberg blocks before any custom CSS or custom HTML.
- `responsive_profile` stays bounded to known profiles.
- Column-based sections keep mobile stacking enabled.
- Media sections use existing media URLs supplied by the caller; this package
  must not fetch, upload, or invent remote assets.
- Media slot requirements stay metadata-only. They can guide OpenClaw image
  generation and Cloud crop requests, but this package must not call image
  providers, import media, or crop assets.
- `custom_css_required` stays `false` for the default `minimal-dark-light`
  preset.
- External references are used for product and composition lessons only; they
  must not become runtime dependencies.

## Next Safe Extensions

The next additions should be small and explicit:

- add optional sections such as use cases, comparison, or governance proof;
- add more `style_preset` or `color_story` values using Gutenberg-native
  attributes;
- expose section toggles as validated variables;
- add editor smoke coverage after a governed proposal creates a draft page.
- feed `review-pattern-page` findings back into future Pattern variant
  selection, while keeping it advisory and read-only.

Do not start by adding a generic visual DSL, arbitrary CSS input, custom block
dependencies, or a new editor surface.
