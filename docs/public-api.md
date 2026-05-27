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
- `source`
- `project_to_magick_catalog`

`source` defaults to `third_party`, meaning the definition is supplied by a provider plugin outside the consuming host. Provider definitions are not projected into Magick AI by default. Set `project_to_magick_catalog => true` only when the provider intentionally wants Magick AI compatibility projection. Official host mirrors should keep `project_to_magick_catalog => false` when another host already owns catalog truth.

## `magick_ai_abilities_register_write_proposal( $ability_id, $definition )`

Registers a write-like ability that returns a proposal instead of committing.

The callback must not directly perform destructive writes.

## `magick_ai_abilities_normalize_schema( $schema, $default_type )`

Normalizes shorthand schema fragments into arrays compatible with WordPress REST schema validation.

## `magick_ai_abilities_normalize_annotations( $annotations, $risk_level )`

Normalizes `readonly`, `destructive`, and `idempotent` annotations from one risk level.

## `magick_ai_abilities_get_registered()`

Returns the normalized ability definitions currently registered through this toolkit.
