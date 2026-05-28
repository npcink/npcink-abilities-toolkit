# Public API

The 0.1 public API is intentionally small. Provider plugins should use these
functions instead of classes under `includes/`, which remain implementation
details.

Host-governed write and destructive commit helpers are not public third-party
APIs in 0.1. Third-party providers may register read-only abilities and
write-proposal abilities only. Final commit authorization belongs to a host
runtime such as Magick AI.

## `magick_ai_abilities_register_category( $category_id, $args )`

Registers an Abilities API category.

Parameters:

- `$category_id` (`string`): Stable machine id. Use lowercase words separated
  by hyphens, for example `acme-demo`.
- `$args` (`array`): Optional category fields. Supported keys are `label` and
  `description`.

Returns `true` when the category is accepted and `false` when the id is empty.

Default behavior:

- `label` defaults to the category id.
- `description` defaults to an empty string.

Failure conditions:

- Empty or fully sanitized-away category ids are rejected.

Example:

```php
magick_ai_abilities_register_category(
	'acme-demo',
	array(
		'label'       => 'ACME Demo Abilities',
		'description' => 'Abilities provided by the ACME demo plugin.',
	)
);
```

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

Parameters:

- `$ability_id` (`string`): Stable namespaced id in `namespace/name` form.
- `$definition` (`array`): Ability contract fields.

Returns `true` when the ability is accepted and `false` when the normalized id
is empty or does not contain `/`.

Default behavior:

- `category` defaults to `magick-ai-abilities-read`.
- `capability` defaults to `manage_options`.
- `permission_callback` is generated from `capability` unless provided.
- `source` defaults to `third_party`.
- `project_to_magick_catalog` defaults to `false`.
- `contract_version` defaults to `v1`.
- `meta.show_in_rest` defaults to `true`.
- `channels` defaults to `abilities_rest`.
- `risk_level` is `read`.
- `requires_confirm` is `false`.

Failure conditions:

- Ability ids without a namespace separator (`/`) are rejected.
- Missing or non-callable `execute_callback` is accepted but normalized to a
  callback that returns `WP_Error`; provider plugins should treat that as an
  invalid definition during development.

Example:

```php
magick_ai_abilities_register_readonly(
	'acme/site-summary',
	array(
		'label'            => 'Site Summary',
		'description'      => 'Returns basic public site information.',
		'category'         => 'acme-demo',
		'capability'       => 'manage_options',
		'required_scope'   => 'cap.site.read',
		'input_schema'     => array(
			'type'                 => 'object',
			'additionalProperties' => false,
		),
		'output_schema'    => array(
			'type'       => 'object',
			'properties' => array(
				'name' => array( 'type' => 'string' ),
				'url'  => array( 'type' => 'string' ),
			),
		),
		'execute_callback' => static function () {
			return array(
				'name' => get_bloginfo( 'name' ),
				'url'  => home_url(),
			);
		},
	)
);
```

## `magick_ai_abilities_register_write_proposal( $ability_id, $definition )`

Registers a write-like ability that returns a proposal instead of committing.

The callback must not directly perform destructive writes.

Use this for provider operations that prepare a reviewable artifact: a diff,
proposal, preview, or handoff payload. The host product owns approval and any
final commit.

Defaults are the same as read-only registration except:

- `category` defaults to `magick-ai-abilities-write`.
- `risk_level` is `write`.
- `requires_confirm` is `true`.
- input schema is extended with `dry_run`, `commit`, and `idempotency_key`.
- output schema is extended with `dry_run`, `host_governed`,
  `commit_required`, and `preview`.

The injected write-control fields describe the host contract only. They do not
make the provider callback authorized to commit.

Example:

```php
magick_ai_abilities_register_write_proposal(
	'acme/create-draft-proposal',
	array(
		'label'            => 'Create Draft Proposal',
		'description'      => 'Builds a draft proposal without creating a post.',
		'category'         => 'acme-demo',
		'capability'       => 'edit_posts',
		'required_scope'   => 'cap.content.write',
		'input_schema'     => array(
			'type'       => 'object',
			'properties' => array(
				'title'   => array( 'type' => 'string' ),
				'content' => array( 'type' => 'string' ),
			),
			'required'   => array( 'title' ),
		),
		'output_schema'    => array(
			'type'       => 'object',
			'properties' => array(
				'proposal_id'  => array( 'type' => 'string' ),
				'diff'         => array( 'type' => 'object' ),
				'next_actions' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		),
		'execute_callback' => static function ( $input = array() ) {
			$input = is_array( $input ) ? $input : array();

			return array(
				'proposal_id'  => 'proposal-' . wp_generate_uuid4(),
				'diff'         => array(
					'post_title' => array(
						'from' => null,
						'to'   => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
					),
				),
				'next_actions' => array( 'approve_in_host', 'reject' ),
			);
		},
	)
);
```

## `magick_ai_abilities_normalize_schema( $schema, $default_type )`

Normalizes shorthand schema fragments into arrays compatible with WordPress REST schema validation.

Parameters:

- `$schema` (`mixed`): Raw schema fragment.
- `$default_type` (`string`): Type used when the fragment is empty or not an
  array. Defaults to `object`.

Returns a normalized schema array.

Example:

```php
$schema = magick_ai_abilities_normalize_schema(
	array(
		'type'       => 'object',
		'properties' => array(
			'title' => 'string',
		),
	)
);
```

## `magick_ai_abilities_normalize_annotations( $annotations, $risk_level )`

Normalizes `readonly`, `destructive`, and `idempotent` annotations from one risk level.

Parameters:

- `$annotations` (`mixed`): Raw annotations array.
- `$risk_level` (`string`): `read`, `write`, or `destructive`. Defaults to
  `read`.

Returns a normalized annotations array.

Default behavior:

- `read` sets `readonly=true`, `destructive=false`, and `idempotent=true`.
- `write` sets `readonly=false`, `destructive=false`, and `idempotent=false`.
- `destructive` sets `readonly=false`, `destructive=true`, and
  `idempotent=false`.

## `magick_ai_abilities_get_registered()`

Returns the normalized ability definitions currently registered through this toolkit.

This is a discovery helper for consumers and tests. It returns only abilities
registered in the current PHP process through this toolkit.

Return shape:

- keyed by normalized ability id;
- values are normalized definition arrays containing `ability_id`, `mode`,
  `source`, schemas, callbacks, permission callback, `risk_level`,
  `requires_confirm`, scopes, contract metadata, and `meta`.
