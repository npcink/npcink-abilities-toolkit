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
