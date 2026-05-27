# Public API

## `magick_ai_abilities_register_category( $category_id, $args )`

Registers an Abilities API category.

## `magick_ai_abilities_register_readonly( $ability_id, $definition )`

Registers a read-only ability.

Required definition fields:

- `label`
- `description`
- `input_schema`
- `output_schema`
- `execute_callback`

Optional fields:

- `category`
- `capability`
- `permission_callback`
- `required_scope`
- `required_scopes`
- `channels`
- `contract_version`

## `magick_ai_abilities_register_write_proposal( $ability_id, $definition )`

Registers a write-like ability that returns a proposal instead of committing.

The callback must not directly perform destructive writes.

## `magick_ai_abilities_normalize_schema( $schema, $default_type )`

Normalizes shorthand schema fragments into arrays compatible with WordPress REST schema validation.

## `magick_ai_abilities_get_registered()`

Returns the normalized ability definitions currently registered through this toolkit.
