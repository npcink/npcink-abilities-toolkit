# Public API

The 0.1 public API is intentionally small. Provider plugins should use these
functions instead of classes under `includes/`, which remain implementation
details.

Host-governed write and destructive commit helpers are not public third-party
APIs in 0.1. Third-party providers may register read-only abilities and
write-proposal abilities only. Final commit authorization belongs to a host
runtime such as Npcink AI.

The internal registrar can still register first-party host-governed write and
destructive callbacks for this package's built-in WordPress ability packs. That
does not make commit helpers a third-party public API. External provider
plugins should expose proposals and let the consuming host decide whether and
how a final write is authorized.

## Built-In Package Controls

The default plugin boot keeps all built-in packages enabled for compatibility.
Hosts that only need the generic Abilities API surface can narrow the local
registration set with filters:

- `npcink_abilities_toolkit_enabled_packages`: package-level boot map. Supported
  slugs are `core_read`, `core_write`, `core_destructive`, `core_comment`,
  `npcink_catalog_bridge`, `admin_test_page`, and `read_cache_hooks`.
- `npcink_abilities_toolkit_enabled_read_packs`: read-only sub-packs. Returning
  only `array( 'core_wordpress_read' )` keeps generic WordPress reads and drops
  higher-level content, article, diagnostics, workflow definition discovery,
  media, taxonomy, page, SEO/GEO, and comment workflow helpers.
- `npcink_abilities_toolkit_enabled_comment_packs`: comment helper sub-packs.
- `npcink_abilities_toolkit_should_register_read_ability` and
  `npcink_abilities_toolkit_should_register_comment_ability`: final per-ability
  registration gates.
- `npcink_abilities_toolkit_projected_catalog_row`: last-mile customization for the
  optional Npcink AI compatibility row. The default row is intentionally thin;
  hosts that need product-specific runtime fields should add them here or in
  the consuming host, not by expanding this package's default projection.

These filters are host composition controls, not new public ability-definition
APIs. They prevent this package from becoming a forced Npcink AI catalog bundle
in installations that only want reusable WordPress ability definitions.

The projection filter is metadata-only. It must not be used to move final
approval, quota, audit, Open API exposure, MCP policy, workflow state, or model
routing ownership into this package.

## Runtime Contract Endpoint

Hosts may fetch this read-only discovery endpoint with a WordPress REST caller
that has `manage_options` before loading the full Abilities API catalog:

```text
GET /wp-json/npcink-abilities-toolkit/v1/contract
```

The response is metadata-only. It includes the active plugin version, Toolkit
contract version, ability registry version, workflow recipe version, active
ability count, stable sha256 hashes for ability ids, ability contracts, and
workflow recipes, plus explicit host-governed write controls.

The endpoint also reports Adapter-facing compatibility, catalog source,
schema-control posture, read/write execution surfaces, and forbidden payload
families. It intentionally omits callbacks, permission callbacks, raw ability
definitions, secrets, paths, approval records, audit state, queues, workflow
runtime state, model routing, prompts, billing, and cloud execution truth. Use
the WordPress Abilities API catalog for per-ability schemas and execution.

## `npcink_abilities_toolkit_register_category( $category_id, $args )`

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
npcink_abilities_toolkit_register_category(
	'acme-demo',
	array(
		'label'       => 'ACME Demo Abilities',
		'description' => 'Abilities provided by the ACME demo plugin.',
	)
);
```

## `npcink_abilities_toolkit_register_readonly( $ability_id, $definition )`

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
- `project_to_npcink_catalog`

`source` defaults to `third_party`, meaning the definition is supplied by a provider plugin outside the consuming host. Provider definitions are not projected into Npcink AI by default. Set `project_to_npcink_catalog => true` only when the provider intentionally wants Npcink AI compatibility projection. Official host mirrors should keep `project_to_npcink_catalog => false` when another host already owns catalog truth.

Parameters:

- `$ability_id` (`string`): Stable namespaced id in `namespace/name` form.
- `$definition` (`array`): Ability contract fields.

Returns `true` when the ability is accepted and `false` when the normalized id
is empty or does not contain `/`.

Default behavior:

- `category` defaults to `npcink-abilities-toolkit-read`.
- `capability` defaults to `manage_options`.
- `permission_callback` is generated from `capability` unless provided.
- `source` defaults to `third_party`.
- `project_to_npcink_catalog` defaults to `false`.
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
npcink_abilities_toolkit_register_readonly(
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

## `npcink_abilities_toolkit_register_write_proposal( $ability_id, $definition )`

Registers a write-like ability that returns a proposal instead of committing.

The callback must not directly perform destructive writes.

Use this for provider operations that prepare a reviewable artifact: a diff,
proposal, preview, or handoff payload. The host product owns approval and any
final commit.

Defaults are the same as read-only registration except:

- `category` defaults to `npcink-abilities-toolkit-write`.
- `risk_level` is `write`.
- `requires_confirm` is `true`.
- input schema is extended with `dry_run`, `commit`, and `idempotency_key`.
- output schema is extended with `dry_run`, `host_governed`,
  `commit_required`, and `preview`.

The injected write-control fields describe the host contract only. They do not
make the provider callback authorized to commit.

Example:

```php
npcink_abilities_toolkit_register_write_proposal(
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

## `npcink_abilities_toolkit_normalize_schema( $schema, $default_type )`

Normalizes shorthand schema fragments into arrays compatible with WordPress REST schema validation.

Parameters:

- `$schema` (`mixed`): Raw schema fragment.
- `$default_type` (`string`): Type used when the fragment is empty or not an
  array. Defaults to `object`.

Returns a normalized schema array.

Example:

```php
$schema = npcink_abilities_toolkit_normalize_schema(
	array(
		'type'       => 'object',
		'properties' => array(
			'title' => 'string',
		),
	)
);
```

## `npcink_abilities_toolkit_normalize_annotations( $annotations, $risk_level )`

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

## `npcink_abilities_toolkit_get_registered()`

Returns the normalized ability definitions currently registered through this toolkit.

This is a discovery helper for consumers and tests. It returns only abilities
registered in the current PHP process through this toolkit.

Return shape:

- keyed by normalized ability id;
- values are normalized definition arrays containing `ability_id`, `mode`,
  `source`, schemas, callbacks, permission callback, `risk_level`,
  `requires_confirm`, scopes, contract metadata, and `meta`.

## `npcink_abilities_toolkit_get_workflow_definitions()`

Returns the read-only workflow recipe definition manifest.

This is a discovery helper for hosts that need machine-readable recipe guidance
without reading files from this package. The returned definitions are
declarative; they do not execute, schedule, approve, retry, audit, route models,
select prompts, or commit final WordPress writes.

Return shape:

- `schema_version`
- `purpose`
- `cases`, keyed by workflow case id

Each case follows [Workflow Definition Contract](workflow-definition-contract.md).

## `npcink_abilities_toolkit_get_workflow_definition( $recipe_id )`

Returns one workflow recipe definition by recipe id or case id, or `null` when
the recipe is unknown.

Parameters:

- `$recipe_id` (`string`): Recipe id such as
  `workflow/wordpress_article_publish_preflight`, or a case id such as
  `article_publish_preflight`.

Hosts that need REST-based discovery can also call the read-only Abilities API
abilities:

- `npcink-abilities-toolkit/list-workflow-recipes`
- `npcink-abilities-toolkit/get-workflow-recipe`
