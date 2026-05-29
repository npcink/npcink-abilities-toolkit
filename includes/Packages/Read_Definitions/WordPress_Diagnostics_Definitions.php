<?php
/**
 * WordPress diagnostics read ability definitions.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages\Read_Definitions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides redacted WordPress diagnostics read ability definitions.
 */
final class WordPress_Diagnostics_Definitions {
	/**
	 * Returns diagnostics ability definitions.
	 *
	 * @param object $callbacks Callback owner.
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions( $callbacks ) {
		return array(
			'magick-ai-abilities/wp-diagnostics-summary' => array(
				'label'            => __( 'WordPress Diagnostics Summary', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a redacted local WordPress diagnostics summary without Magick AI, MCP, filesystem path, database name, or secret details.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-abilities-diagnostics',
				'capability'       => 'manage_options',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'include_plugins' => array( 'type' => 'boolean', 'default' => true ),
						'include_theme'   => array( 'type' => 'boolean', 'default' => true ),
						'include_cron'    => array( 'type' => 'boolean', 'default' => true ),
						'include_updates' => array( 'type' => 'boolean', 'default' => true ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'summary_version' => array( 'type' => 'string' ),
						'generated_at'    => array( 'type' => 'string' ),
						'redacted'        => array( 'type' => 'boolean' ),
						'site'            => array( 'type' => 'object' ),
						'wordpress'       => array( 'type' => 'object' ),
						'php'             => array( 'type' => 'object' ),
						'theme'           => array( 'type' => 'object' ),
						'plugins'         => array( 'type' => 'object' ),
						'rest_api'        => array( 'type' => 'object' ),
						'abilities_api'   => array( 'type' => 'object' ),
						'cron'            => array( 'type' => 'object' ),
						'updates'         => array( 'type' => 'object' ),
						'omitted'         => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'   => array( 'summary_version', 'generated_at', 'redacted', 'site', 'wordpress', 'php', 'rest_api', 'abilities_api', 'omitted' ),
				),
				'execute_callback' => array( $callbacks, 'wp_diagnostics_summary' ),
			),
		);
	}
}
