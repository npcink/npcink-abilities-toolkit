# Provider Plugin Guide

Any WordPress plugin can use this toolkit to provide Abilities API capability packages. Npcink AI is an optional consumer; other clients can discover and run the same abilities through the standard WordPress Abilities API.

## Detect The Toolkit

Register provider abilities after plugins are loaded and check for the public
helpers before calling them:

```php
add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'npcink_abilities_toolkit_register_readonly' ) ) {
			return;
		}

		// Register categories and abilities here.
	}
);
```

Do not include files from `npcink-abilities-toolkit/includes/` and do not instantiate
classes under the `Npcink_Abilities_Toolkit` namespace. Those are implementation
details.

## Register a Provider Category

Use `npcink_abilities_toolkit_register_category()` when your plugin owns a group of abilities:

```php
npcink_abilities_toolkit_register_category(
	'acme-demo',
	array(
		'label'       => 'ACME Demo Abilities',
		'description' => 'Abilities provided by the ACME demo plugin.',
	)
);
```

## Register a Read-Only Ability

Use `npcink_abilities_toolkit_register_readonly()` for diagnostics, discovery, and context retrieval.

The toolkit sets:

- `readonly=true`
- `destructive=false`
- `idempotent=true`
- `show_in_rest=true`
- default capability `manage_options`

Example:

```php
npcink_abilities_toolkit_register_readonly(
	'acme/content-inventory-summary',
	array(
		'label'          => 'Content Inventory Summary',
		'description'    => 'Returns counts of common public content types.',
		'category'       => 'acme-demo',
		'capability'     => 'edit_posts',
		'required_scope' => 'cap.content.read',
		'input_schema'   => array(
			'type'                 => 'object',
			'additionalProperties' => false,
		),
		'output_schema'  => array(
			'type'       => 'object',
			'properties' => array(
				'posts' => array( 'type' => 'integer' ),
				'pages' => array( 'type' => 'integer' ),
			),
		),
		'execute_callback' => static function () {
			$posts = wp_count_posts( 'post' );
			$pages = wp_count_posts( 'page' );

			return array(
				'posts' => isset( $posts->publish ) ? (int) $posts->publish : 0,
				'pages' => isset( $pages->publish ) ? (int) $pages->publish : 0,
			);
		},
	)
);
```

## Opt Into Npcink AI Compatibility Projection

Abilities are not projected into Npcink AI by default. If your provider plugin intentionally wants Npcink AI compatibility, set:

```php
'project_to_npcink_catalog' => true,
```

The ability remains a normal WordPress Abilities API ability; the projection only adds a Npcink AI catalog entry when Npcink AI is installed.

Opt in only when:

- the ability is intentionally useful to Npcink AI;
- the provider is comfortable exposing its schema in the Npcink AI catalog;
- the ability can be executed through the normal WordPress Abilities API path.

Do not opt in when:

- another host already owns catalog truth for the same ability id;
- the ability requires private runtime state not represented in the schema;
- exposing the ability would imply Npcink AI approval, quota, billing, or audit
  behavior that the provider does not own.

## Register a Write Proposal Ability

Use `npcink_abilities_toolkit_register_write_proposal()` for write-like operations.

The callback should return a proposal object instead of committing. The host
product owns approval and any final mutation.

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
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
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

Third-party providers should not perform final host-governed commits through
this helper. Host-governed write/destructive commit registration is not public
third-party API in 0.1.

## Adapt Object-Storage-Backed Media

Media offload plugins are site-owned infrastructure. Npcink Abilities Toolkit
does not control their bucket settings, SDK clients, credentials, signed URLs,
upload behavior, rollback behavior, or CDN cache purge.

When a site wants media file abilities to work with OSS/S3/COS/Qiniu/CDN
offload, implement the
`npcink_abilities_toolkit_media_storage_inspection` filter as a small shim. The
shim should declare only bounded readiness metadata such as read mode, write
mode, restore mode, adapter id, and cache-purge requirement. It should not pass
credentials, bucket names, signed headers, callback URLs, SDK clients, or raw
provider responses through Toolkit.

Start with [OSS Storage Compatibility Shim](oss-storage-compatibility-shim.md)
and [examples/oss-storage-shim.php](../examples/oss-storage-shim.php).

## Local Smoke Test

When this plugin is installed in a WordPress site with WP-CLI available:

```bash
WP_PATH=/path/to/wordpress composer smoke:wp
```

The smoke test verifies Abilities API functions, REST catalog discovery, diagnostics ability execution, and blocked anonymous REST access.
