# Magick AI Abilities

WordPress plugin toolkit for registering agent-callable abilities through the WordPress Abilities API.

## Scope

This project owns the WordPress Abilities API registration layer:

- ability categories
- read-only ability registration
- write-proposal ability registration
- schema and metadata normalization
- optional Magick AI catalog projection

It does not own model routing, cloud execution, billing, quota, workflow runtime, MCP governance, or final WordPress writes.

## Requirements

- WordPress 6.9+ with the Abilities API available
- PHP 7.2+

## Public API

```php
magick_ai_abilities_register_category( $category_id, $args );
magick_ai_abilities_register_readonly( $ability_id, $definition );
magick_ai_abilities_register_write_proposal( $ability_id, $definition );
magick_ai_abilities_normalize_schema( $schema, $default_type );
magick_ai_abilities_get_registered();
```

## Minimal Example

```php
add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'magick_ai_abilities_register_readonly' ) ) {
			return;
		}

		magick_ai_abilities_register_readonly(
			'acme/site-summary',
			array(
				'label'            => 'Site Summary',
				'description'      => 'Returns basic site information.',
				'capability'       => 'manage_options',
				'input_schema'     => array( 'type' => 'object' ),
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
	}
);
```

When Magick AI is installed, registered abilities are also projected into `magick_ai_open_platform_ability_catalog` as `wp_ability` backend entries.
