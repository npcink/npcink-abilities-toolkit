<?php
/**
 * WordPress diagnostics read ability definitions.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages\Read_Definitions;

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
			'npcink-abilities-toolkit/wp-diagnostics-summary' => array(
				'label'            => __( 'WordPress Diagnostics Summary', 'npcink-abilities-toolkit' ),
				'description'      => __( 'Returns a redacted local WordPress diagnostics summary without Npcink AI, MCP, filesystem path, database name, or secret details.', 'npcink-abilities-toolkit' ),
				'category'         => 'npcink-abilities-toolkit-diagnostics',
				'capability'       => 'manage_options',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'include_plugins'        => array( 'type' => 'boolean', 'default' => true ),
						'include_theme'          => array( 'type' => 'boolean', 'default' => true ),
						'include_cron'           => array( 'type' => 'boolean', 'default' => true ),
						'include_updates'        => array( 'type' => 'boolean', 'default' => true ),
						'include_current_user'   => array( 'type' => 'boolean', 'default' => false ),
						'include_object_cache'   => array( 'type' => 'boolean', 'default' => true ),
						'include_rewrite'        => array( 'type' => 'boolean', 'default' => true ),
						'include_https'          => array( 'type' => 'boolean', 'default' => true ),
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
						'current_user'    => array( 'type' => 'object' ),
						'object_cache'    => array( 'type' => 'object' ),
						'rewrite'         => array( 'type' => 'object' ),
						'https'           => array( 'type' => 'object' ),
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
			'npcink-abilities-toolkit/wp-ops-diagnostics-detail' => array(
				'label'            => __( 'WordPress Operations Diagnostics Detail', 'npcink-abilities-toolkit' ),
				'description'      => __( 'Returns bounded local WordPress operations diagnostics with plugin, caller capability, PHP, cache, rewrite, HTTPS, server, database, cron, structured log, content type, role, widget, block-theme, search, integration, SEO, security, and performance details.', 'npcink-abilities-toolkit' ),
				'category'         => 'npcink-abilities-toolkit-diagnostics',
				'capability'       => 'manage_options',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'profile'                => array( 'type' => 'string', 'enum' => array( 'summary', 'detail', 'forensics' ), 'default' => 'summary' ),
						'include_plugins'        => array( 'type' => 'boolean', 'default' => false ),
						'include_active_plugins' => array( 'type' => 'boolean', 'default' => false ),
						'include_inactive_plugins' => array( 'type' => 'boolean', 'default' => false ),
						'include_plugin_updates' => array( 'type' => 'boolean', 'default' => false ),
						'include_must_use_plugins' => array( 'type' => 'boolean', 'default' => false ),
						'include_dropins'        => array( 'type' => 'boolean', 'default' => false ),
						'max_plugins_per_group'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 500, 'default' => 100 ),
						'include_current_user'   => array( 'type' => 'boolean', 'default' => false ),
						'include_php'            => array( 'type' => 'boolean', 'default' => true ),
						'include_object_cache'   => array( 'type' => 'boolean', 'default' => true ),
						'include_rewrite'        => array( 'type' => 'boolean', 'default' => true ),
						'include_https'          => array( 'type' => 'boolean', 'default' => true ),
						'include_server'         => array( 'type' => 'boolean', 'default' => true ),
						'include_database'       => array( 'type' => 'boolean', 'default' => false ),
						'include_cron_events'    => array( 'type' => 'boolean', 'default' => false ),
						'max_cron_events'        => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20 ),
						'include_error_log'      => array( 'type' => 'boolean', 'default' => false ),
						'include_log_contents'   => array( 'type' => 'boolean', 'default' => false ),
						'tail_lines'             => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'default' => 50 ),
						'since_minutes'          => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 10080, 'default' => 0 ),
						'severity'               => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
								'enum' => array( 'fatal', 'error', 'warning', 'deprecated', 'notice', 'info', 'unknown' ),
							),
						),
						'include_content_types'  => array( 'type' => 'boolean', 'default' => false ),
						'include_roles'          => array( 'type' => 'boolean', 'default' => false ),
						'include_widgets'        => array( 'type' => 'boolean', 'default' => false ),
						'include_block_theme'    => array( 'type' => 'boolean', 'default' => false ),
						'include_search'         => array( 'type' => 'boolean', 'default' => false ),
						'include_integrations'   => array( 'type' => 'boolean', 'default' => false ),
						'include_summaries'      => array( 'type' => 'boolean', 'default' => true ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'detail_version' => array( 'type' => 'string' ),
						'profile'        => array( 'type' => 'string' ),
						'generated_at'   => array( 'type' => 'string' ),
						'redacted'       => array( 'type' => 'boolean' ),
						'plugins'        => array( 'type' => 'object' ),
						'current_user'   => array( 'type' => 'object' ),
						'php'            => array( 'type' => 'object' ),
						'object_cache'   => array( 'type' => 'object' ),
						'rewrite'        => array( 'type' => 'object' ),
						'https'          => array( 'type' => 'object' ),
						'server'         => array( 'type' => 'object' ),
						'database'       => array( 'type' => 'object' ),
						'cron_events'    => array( 'type' => 'object' ),
						'error_log'      => array( 'type' => 'object' ),
						'content_types'  => array( 'type' => 'object' ),
						'roles'          => array( 'type' => 'object' ),
						'widgets'        => array( 'type' => 'object' ),
						'block_theme'    => array( 'type' => 'object' ),
						'search'         => array( 'type' => 'object' ),
						'integrations'   => array( 'type' => 'object' ),
						'seo_summary'    => array( 'type' => 'object' ),
						'security_summary' => array( 'type' => 'object' ),
						'performance_summary' => array( 'type' => 'object' ),
						'omitted'        => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'   => array( 'detail_version', 'profile', 'generated_at', 'redacted', 'plugins', 'current_user', 'php', 'object_cache', 'rewrite', 'https', 'server', 'database', 'cron_events', 'error_log', 'content_types', 'roles', 'widgets', 'block_theme', 'search', 'integrations', 'seo_summary', 'security_summary', 'performance_summary', 'omitted' ),
				),
				'execute_callback' => array( $callbacks, 'wp_ops_diagnostics_detail' ),
			),
		);
	}
}
