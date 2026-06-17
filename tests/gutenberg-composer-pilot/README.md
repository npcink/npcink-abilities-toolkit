# Gutenberg Composer Pilot

This folder contains the deterministic local pilot gate for
`npcink-abilities-toolkit/compose-gutenberg-block-plan`.

The command is intentionally offline:

- it routes natural-language prompts;
- it reads the Gutenberg block capability catalog through the composer output;
- it checks composer profile selection;
- it builds and reviews one read-only plan;
- it verifies the final gate is proposal-ready;
- it never creates Core proposals, executes WordPress writes, calls Adapter, or
  calls model providers.

## Run

```bash
composer pilot:gutenberg-composer
```

Default JSON output is written to:

```text
tests/gutenberg-composer-pilot/generated/gutenberg-composer-pilot.json
```

Use a custom output path:

```bash
composer pilot:gutenberg-composer -- output=/tmp/gutenberg-composer-pilot.json
```

Limit cases while iterating:

```bash
composer pilot:gutenberg-composer -- limit=3
```

## Role In The Workflow

This pilot is a Toolkit-level acceptance gate. It is useful before opening or
updating a pull request because it catches route/profile/repair regressions
without needing a real WordPress site.

It does not replace the final OpenClaw workflow check:

```text
natural language
-> route-content-intent
-> compose-gutenberg-block-plan
-> Core proposal
-> user approval / preflight
-> Adapter execute
-> get-post-blocks / get-template-blocks validation
```
