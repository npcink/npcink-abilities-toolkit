# Migration Plan

## Phase 1: Standalone Toolkit

- Keep this plugin independent from the main Magick AI runtime.
- Provide public registration functions.
- Register categories and abilities only through WordPress Abilities API hooks.
- Project registered abilities into Magick AI only through filters when the main plugin is present.

## Phase 2: Move Registration Helpers

Move stable helper behavior from the main plugin into this project:

- category registration helper
- schema normalizer
- annotation normalizer
- read-only registration helper
- write-proposal registration helper

Do not move model routing, workflow runtime, skills runtime, Cloud execution, quota, billing, or approval commit ownership.

## Phase 3: Migrate Low-Risk Abilities

Start with read-only abilities:

- site diagnostics summaries
- post context reads
- catalog/discovery helpers

Write-like abilities should remain proposal-only until the host plugin explicitly owns approval and commit.

## Phase 4: Main Plugin Integration

Update the main Magick AI plugin to consume this plugin through public functions and filters.

Avoid direct `require_once` calls into this plugin's internal `includes/` files.
