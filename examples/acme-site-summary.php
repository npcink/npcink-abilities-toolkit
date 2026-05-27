<?php
/**
 * Example provider integration for Magick AI Abilities.
 *
 * @package MagickAIAbilitiesExample
 */

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
				'description'      => 'Returns basic site information for an authenticated operator.',
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
