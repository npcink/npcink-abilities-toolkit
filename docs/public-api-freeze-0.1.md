# Public API Freeze 0.1

Status: active for the 0.1 development line.

The following functions are the only public PHP API for provider plugins and consumers in 0.1:

- `magick_ai_abilities_register_category( $category_id, $args )`
- `magick_ai_abilities_register_readonly( $ability_id, $definition )`
- `magick_ai_abilities_register_write_proposal( $ability_id, $definition )`
- `magick_ai_abilities_normalize_schema( $schema, $default_type )`
- `magick_ai_abilities_normalize_annotations( $annotations, $risk_level )`
- `magick_ai_abilities_get_registered()`

Consumers should not instantiate classes under `includes/` directly. Those classes are implementation details until a later release explicitly promotes them.

## Compatibility Rules

- Add optional fields instead of changing existing field meanings.
- Keep `ability_id` stable once published.
- Keep `meta.show_in_rest`, `meta.mcp.public`, and `meta.magick.channels` separate.
- Keep Magick AI catalog projection explicit: `project_to_magick_catalog` defaults to false.
- Write-like helpers must produce proposal-oriented abilities; final commit ownership stays outside this toolkit.
- Do not add public third-party host-governed write/destructive helper functions in the 0.1 line. If that changes later, write a new ADR first.
- Built-in ability ids follow SemVer-style compatibility: changing the meaning of an existing id is breaking; adding optional schema properties is non-breaking; removing an id requires `deprecated` and `successor` metadata first.
- Schema additions must be optional unless the release is explicitly breaking.
- `deprecated=true` means consumers may continue to call the ability in the current line but should migrate to `successor` when present.

## Definition Fields

Supported definition fields:

- `label`
- `description`
- `category`
- `capability`
- `permission_callback`
- `input_schema`
- `output_schema`
- `execute_callback`
- `annotations`
- `meta`
- `channels`
- `required_scope`
- `required_scopes`
- `contract_version`
- `source`
- `project_to_magick_catalog`
- `deprecated`
- `successor`

## 0.2 Release Checklist

- Documentation updated: public API, ability contract, provider guide, integration contract, and first-party pack grouping.
- Migration inventory updated for any added, moved, removed, deprecated, or successor ability ids.
- Public API reviewed to confirm no accidental promise of host-governed third-party commit helpers.
- `composer validate:composer` passes.
- `composer check:boundary` passes.
- `composer test` passes.
- `composer lint:php` passes.
- `composer test:all` passes.
- `git diff --check` passes.
- WordPress smoke status recorded: either `WP_PATH=/path/to/wordpress composer smoke:wp` passed, or the release notes state why it was not run.
- Cross-repository Magick AI consumer verification recorded in [0.2 Candidate Verification](release-0.2-candidate-verification.md).
