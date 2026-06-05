<?php
/**
 * Example provider integration for Npcink Abilities Toolkit.
 *
 * @package NpcinkAbilitiesToolkitExample
 */

add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'npcink_abilities_toolkit_register_readonly' ) ) {
			return;
		}

		npcink_abilities_toolkit_register_readonly(
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
