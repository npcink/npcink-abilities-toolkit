<?php
/**
 * Core WordPress read-only ability package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

use Magick_AI_Abilities\Registry\Ability_Registrar;
use Magick_AI_Abilities\Registry\Category_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers low-risk WordPress read abilities migrated from the Magick AI plugin.
 */
final class Core_Read_Package {
	/**
	 * Category registrar.
	 *
	 * @var Category_Registrar
	 */
	private $categories;

	/**
	 * Ability registrar.
	 *
	 * @var Ability_Registrar
	 */
	private $abilities;

	/**
	 * Constructor.
	 *
	 * @param Category_Registrar $categories Category registrar.
	 * @param Ability_Registrar  $abilities Ability registrar.
	 */
	public function __construct( Category_Registrar $categories, Ability_Registrar $abilities ) {
		$this->categories = $categories;
		$this->abilities  = $abilities;
	}

	/**
	 * Registers categories and abilities.
	 *
	 * @return void
	 */
	public function boot() {
		$this->categories->add(
			'magick-ai-data',
			array(
				'label'       => __( 'WordPress Read Abilities', 'magick-ai-abilities' ),
				'description' => __( 'Read-only WordPress site, content, and structure abilities.', 'magick-ai-abilities' ),
			)
		);
		$this->categories->add(
			'magick-ai-pages',
			array(
				'label'       => __( 'WordPress Page Abilities', 'magick-ai-abilities' ),
				'description' => __( 'Read-only WordPress page discovery and inspection abilities.', 'magick-ai-abilities' ),
			)
		);
		$this->categories->add(
			'magick-ai-abilities-diagnostics',
			array(
				'label'       => __( 'WordPress Diagnostics', 'magick-ai-abilities' ),
				'description' => __( 'Redacted WordPress environment diagnostics for Abilities API clients.', 'magick-ai-abilities' ),
			)
		);

		foreach ( $this->definitions() as $ability_id => $definition ) {
			if ( 0 === strpos( (string) $ability_id, 'magick-ai/' ) ) {
				$definition['project_to_magick_catalog'] = true;
			}
			$this->abilities->add_readonly( $ability_id, $definition );
		}
	}

	/**
	 * Returns package ability definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function definitions() {
		return array(
			'magick-ai/site-info'       => array(
				'label'            => __( 'Site Info', 'magick-ai-abilities' ),
				'description'      => __( 'Returns the site name, URLs, language, WordPress version, timezone, and active theme.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'cap.workflowwp.env.diagnostics',
				'required_scopes'  => array( 'cap.workflowwp.env.diagnostics' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'name'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'home_url'    => array( 'type' => 'string' ),
						'site_url'    => array( 'type' => 'string' ),
						'language'    => array( 'type' => 'string' ),
						'timezone'    => array( 'type' => 'string' ),
						'wp_version'  => array( 'type' => 'string' ),
						'theme'       => array( 'type' => 'string' ),
					),
					'required'   => array( 'name', 'home_url', 'site_url' ),
				),
				'execute_callback' => array( $this, 'site_info' ),
			),
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
				'execute_callback' => array( $this, 'wp_diagnostics_summary' ),
			),
			'magick-ai/list-post-types' => array(
				'label'            => __( 'List Post Types', 'magick-ai-abilities' ),
				'description'      => __( 'Returns available post types, including REST exposure and hierarchy flags.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'include_builtin' => array( 'type' => 'boolean', 'default' => false ),
						'include_private' => array( 'type' => 'boolean', 'default' => false ),
						'show_ui_only'    => array( 'type' => 'boolean', 'default' => true ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total' => array( 'type' => 'integer' ),
						'items' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'post_type'    => array( 'type' => 'string' ),
									'label'        => array( 'type' => 'string' ),
									'public'       => array( 'type' => 'boolean' ),
									'hierarchical' => array( 'type' => 'boolean' ),
									'show_in_rest' => array( 'type' => 'boolean' ),
								),
								'required'   => array( 'post_type', 'label' ),
							),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_post_types' ),
			),
			'magick-ai/list-taxonomies' => array(
				'label'            => __( 'List Taxonomies', 'magick-ai-abilities' ),
				'description'      => __( 'Returns taxonomy definitions and bound object types.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_categories',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'object_type'  => array( 'type' => 'string' ),
						'show_ui_only' => array( 'type' => 'boolean', 'default' => true ),
						'public_only'  => array( 'type' => 'boolean', 'default' => false ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total' => array( 'type' => 'integer' ),
						'items' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'taxonomy'     => array( 'type' => 'string' ),
									'label'        => array( 'type' => 'string' ),
									'public'       => array( 'type' => 'boolean' ),
									'hierarchical' => array( 'type' => 'boolean' ),
									'show_in_rest' => array( 'type' => 'boolean' ),
									'object_types' => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
								),
								'required'   => array( 'taxonomy', 'label' ),
							),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_taxonomies' ),
			),
			'magick-ai/list-media'      => array(
				'label'            => __( 'List Media', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated list of media library attachments.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'upload_files',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'mime_type'         => array( 'type' => 'string' ),
						'search'            => array( 'type' => 'string' ),
						'date_from'         => array( 'type' => 'string' ),
						'date_to'           => array( 'type' => 'string' ),
						'has_empty_alt'     => array( 'type' => 'boolean', 'default' => false ),
						'has_empty_caption' => array( 'type' => 'boolean', 'default' => false ),
						'per_page'          => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'              => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'        => array( 'type' => 'integer' ),
									'title'     => array( 'type' => 'string' ),
									'date'      => array( 'type' => 'string' ),
									'mime_type' => array( 'type' => 'string' ),
									'url'       => array( 'type' => 'string' ),
									'edit_link' => array( 'type' => 'string' ),
								),
								'required'   => array( 'id', 'url' ),
							),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_media' ),
			),
			'magick-ai/list-terms'      => array(
				'label'            => __( 'List Terms', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated list of terms for a taxonomy.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_categories',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy'   => array( 'type' => 'string', 'default' => 'category' ),
						'search'     => array( 'type' => 'string' ),
						'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'       => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array( 'type' => 'integer' ),
									'name'  => array( 'type' => 'string' ),
									'slug'  => array( 'type' => 'string' ),
									'count' => array( 'type' => 'integer' ),
								),
								'required'   => array( 'id', 'name' ),
							),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_terms' ),
			),
			'magick-ai/list-taxonomy-terms' => array(
				'label'            => __( 'List Taxonomy Terms', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated term list for one taxonomy, including parent information.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_categories',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy'   => array( 'type' => 'string', 'default' => 'category' ),
						'search'     => array( 'type' => 'string' ),
						'parent'     => array( 'type' => 'integer', 'minimum' => 0 ),
						'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20 ),
						'page'       => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy' => array( 'type' => 'string' ),
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'     => array( 'type' => 'integer' ),
									'name'   => array( 'type' => 'string' ),
									'slug'   => array( 'type' => 'string' ),
									'parent' => array( 'type' => 'integer' ),
									'count'  => array( 'type' => 'integer' ),
								),
								'required'   => array( 'id', 'name', 'slug' ),
							),
						),
					),
					'required'   => array( 'taxonomy', 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_taxonomy_terms' ),
			),
			'magick-ai/list-categories' => array(
				'label'            => __( 'List Categories', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated list of post categories.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_categories',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'search'     => array( 'type' => 'string' ),
						'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'       => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_categories' ),
			),
			'magick-ai/list-tags'       => array(
				'label'            => __( 'List Tags', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated list of post tags.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_categories',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'search'     => array( 'type' => 'string' ),
						'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'       => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_tags' ),
			),
			'magick-ai/get-term'        => array(
				'label'            => __( 'Get Term', 'magick-ai-abilities' ),
				'description'      => __( 'Returns details for one taxonomy term by id or slug.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_categories',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'taxonomy' => array( 'type' => 'string', 'default' => 'category' ),
						'id'       => array( 'type' => 'integer', 'minimum' => 1 ),
						'slug'     => array( 'type' => 'string' ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'taxonomy'    => array( 'type' => 'string' ),
						'parent'      => array( 'type' => 'integer' ),
						'description' => array( 'type' => 'string' ),
						'count'       => array( 'type' => 'integer' ),
					),
					'required'   => array( 'id', 'name', 'slug' ),
				),
				'execute_callback' => array( $this, 'get_term' ),
			),
			'magick-ai/propose-post-excerpt' => array(
				'label'            => __( 'Propose Post Excerpt', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a read-only excerpt proposal from explicit content input without writing changes.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'content'   => array( 'type' => 'string' ),
						'max_chars' => array( 'type' => 'integer', 'minimum' => 40, 'maximum' => 240, 'default' => 160 ),
						'style'     => array( 'type' => 'string', 'enum' => array( 'concise', 'neutral', 'seo' ), 'default' => 'neutral' ),
					),
					'required'   => array( 'content' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'proposal_text' => array( 'type' => 'string' ),
						'explain'       => array( 'type' => 'string' ),
						'meta'          => array(
							'type'       => 'object',
							'properties' => array(
								'source'    => array( 'type' => 'string' ),
								'style'     => array( 'type' => 'string' ),
								'max_chars' => array( 'type' => 'integer' ),
							),
						),
					),
					'required'   => array( 'proposal_text', 'meta' ),
				),
				'execute_callback' => array( $this, 'propose_post_excerpt' ),
			),
			'magick-ai/list-users'      => array(
				'label'            => __( 'List Users', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated list of users and roles.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'list_users',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'role'     => array( 'type' => 'string' ),
						'search'   => array( 'type' => 'string' ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'           => array( 'type' => 'integer' ),
									'display_name' => array( 'type' => 'string' ),
									'user_login'   => array( 'type' => 'string' ),
									'roles'        => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
								),
								'required'   => array( 'id', 'display_name' ),
							),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_users' ),
			),
			'magick-ai/list-comments'   => array(
				'label'            => __( 'List Comments', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated list of comments.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'moderate_comments',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer', 'minimum' => 1 ),
						'status'   => array( 'type' => 'string', 'default' => 'approve' ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'         => array( 'type' => 'integer' ),
									'author'     => array( 'type' => 'string' ),
									'date'       => array( 'type' => 'string' ),
									'status'     => array( 'type' => 'string' ),
									'post_id'    => array( 'type' => 'integer' ),
									'post_title' => array( 'type' => 'string' ),
									'excerpt'    => array( 'type' => 'string' ),
								),
								'required'   => array( 'id', 'post_id' ),
							),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_comments' ),
			),
			'magick-ai/list-menus'      => array(
				'label'            => __( 'List Navigation Menus', 'magick-ai-abilities' ),
				'description'      => __( 'Returns available navigation menus and their assigned theme locations.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_theme_options',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'include_locations' => array( 'type' => 'boolean', 'default' => true ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total' => array( 'type' => 'integer' ),
						'items' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_menus' ),
			),
			'magick-ai/get-menu'        => array(
				'label'            => __( 'Get Navigation Menu', 'magick-ai-abilities' ),
				'description'      => __( 'Returns one navigation menu and its menu items.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_theme_options',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'menu_id'       => array( 'type' => 'integer', 'minimum' => 1 ),
						'menu_slug'     => array( 'type' => 'string' ),
						'include_items' => array( 'type' => 'boolean', 'default' => true ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'menu'  => array( 'type' => 'object' ),
						'items' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'menu', 'items' ),
				),
				'execute_callback' => array( $this, 'get_menu' ),
			),
			'magick-ai/search-posts'    => array(
				'label'            => __( 'Search Posts', 'magick-ai-abilities' ),
				'description'      => __( 'Searches posts by keyword and returns matching post summaries.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'search'    => array( 'type' => 'string' ),
						'post_type' => array( 'type' => 'string', 'default' => 'post' ),
						'status'    => array( 'type' => 'string', 'default' => 'publish' ),
						'per_page'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'      => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'required'   => array( 'search' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'search_posts' ),
			),
			'magick-ai/get-post-stats'  => array(
				'label'            => __( 'Get Post Stats', 'magick-ai-abilities' ),
				'description'      => __( 'Returns word count, image count, reading time, comments, and dates for a post.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'              => array( 'type' => 'integer' ),
						'word_count'           => array( 'type' => 'integer' ),
						'image_count'          => array( 'type' => 'integer' ),
						'reading_time_minutes' => array( 'type' => 'integer' ),
						'comment_count'        => array( 'type' => 'integer' ),
						'published_date'       => array( 'type' => 'string' ),
						'modified_date'        => array( 'type' => 'string' ),
						'permalink'            => array( 'type' => 'string' ),
					),
					'required'   => array( 'post_id', 'word_count', 'reading_time_minutes' ),
				),
				'execute_callback' => array( $this, 'get_post_stats' ),
			),
			'magick-ai/list-revisions'  => array(
				'label'            => __( 'List Revisions', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated list of post revisions using the legacy response shape.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer', 'minimum' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_revisions' ),
			),
			'magick-ai/get-post-meta'   => array(
				'label'            => __( 'Get Post Meta', 'magick-ai-abilities' ),
				'description'      => __( 'Reads post meta for one post, optionally scoped to a single meta key.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer', 'minimum' => 1 ),
						'meta_key' => array( 'type' => 'string' ),
						'single'   => array( 'type' => 'boolean', 'default' => true ),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer' ),
						'meta_key' => array( 'type' => 'string' ),
						'value'    => array(),
						'single'   => array( 'type' => 'boolean' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'post_id' ),
				),
				'execute_callback' => array( $this, 'get_post_meta' ),
			),
			'magick-ai/list-pages'      => array(
				'label'            => __( 'List Pages', 'magick-ai-abilities' ),
				'description'      => __( 'Lists WordPress pages with status, parent, search, sorting, and pagination filters.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-pages',
				'capability'       => 'edit_pages',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'status'  => array( 'type' => 'string', 'enum' => array( 'publish', 'draft', 'pending', 'private', 'any' ), 'default' => 'publish' ),
						'parent'  => array( 'type' => 'integer', 'default' => 0 ),
						'search'  => array( 'type' => 'string' ),
						'orderby' => array( 'type' => 'string', 'enum' => array( 'menu_order', 'title', 'date', 'modified', 'id' ), 'default' => 'menu_order' ),
						'order'   => array( 'type' => 'string', 'enum' => array( 'ASC', 'DESC' ), 'default' => 'ASC' ),
						'number'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20 ),
						'offset'  => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'pages'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'total'    => array( 'type' => 'integer' ),
						'has_more' => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'pages', 'total', 'has_more' ),
				),
				'execute_callback' => array( $this, 'list_pages' ),
			),
			'magick-ai/get-page'        => array(
				'label'            => __( 'Get Page', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a single WordPress page, including content, template, hierarchy, author, and optional meta.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-pages',
				'capability'       => 'edit_pages',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'page_id'      => array( 'type' => 'integer', 'minimum' => 1 ),
						'include_meta' => array( 'type' => 'boolean', 'default' => false ),
						'meta_keys'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'   => array( 'page_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'id'                  => array( 'type' => 'integer' ),
						'title'               => array( 'type' => 'string' ),
						'slug'                => array( 'type' => 'string' ),
						'content'             => array( 'type' => 'string' ),
						'content_raw'         => array( 'type' => 'string' ),
						'excerpt'             => array( 'type' => 'string' ),
						'status'              => array( 'type' => 'string' ),
						'template'            => array( 'type' => 'string' ),
						'parent_id'           => array( 'type' => 'integer' ),
						'menu_order'          => array( 'type' => 'integer' ),
						'author'              => array( 'type' => 'object' ),
						'date'                => array( 'type' => 'string' ),
						'modified'            => array( 'type' => 'string' ),
						'meta'                => array( 'type' => 'object', 'additionalProperties' => true ),
						'url'                 => array( 'type' => 'string' ),
						'edit_url'            => array( 'type' => 'string' ),
						'available_templates' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'id', 'title', 'slug', 'content', 'status', 'template', 'url', 'edit_url', 'available_templates' ),
				),
				'execute_callback' => array( $this, 'get_page' ),
			),
			'magick-ai/inspect-page-structure' => array(
				'label'            => __( 'Inspect Page Structure', 'magick-ai-abilities' ),
				'description'      => __( 'Inspects WordPress page hierarchy, template usage, and orphaned parent relationships without modifying pages.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-pages',
				'capability'       => 'edit_pages',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'max_pages' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'pages'           => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'total_pages'     => array( 'type' => 'integer' ),
						'top_level_count' => array( 'type' => 'integer' ),
						'has_more'        => array( 'type' => 'boolean' ),
						'findings'        => array( 'type' => 'object' ),
						'max_pages'       => array( 'type' => 'integer' ),
					),
					'required'   => array( 'pages', 'total_pages', 'top_level_count', 'has_more', 'findings', 'max_pages' ),
				),
				'execute_callback' => array( $this, 'inspect_page_structure' ),
			),
			'magick-ai/list-pages-tree' => array(
				'label'            => __( 'List Pages Tree', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a hierarchical page tree for navigation and site-structure review.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_pages',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'root_id'   => array( 'type' => 'integer', 'minimum' => 0 ),
						'status'    => array( 'type' => 'string', 'default' => 'publish' ),
						'max_depth' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 6, 'default' => 3 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'root_id' => array( 'type' => 'integer' ),
						'total'   => array( 'type' => 'integer' ),
						'items'   => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'        => array( 'type' => 'integer' ),
									'parent_id' => array( 'type' => 'integer' ),
									'depth'     => array( 'type' => 'integer' ),
									'title'     => array( 'type' => 'string' ),
									'slug'      => array( 'type' => 'string' ),
									'status'    => array( 'type' => 'string' ),
									'edit_link' => array( 'type' => 'string' ),
								),
								'required'   => array( 'id', 'parent_id', 'depth', 'title' ),
							),
						),
					),
					'required'   => array( 'root_id', 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_pages_tree' ),
			),
			'magick-ai/count-posts'     => array(
				'label'            => __( 'Count Posts', 'magick-ai-abilities' ),
				'description'      => __( 'Counts posts for one post type and status.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array( 'type' => 'string', 'default' => 'post' ),
						'status'    => array( 'type' => 'string', 'default' => 'publish' ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'total' ),
				),
				'execute_callback' => array( $this, 'count_posts' ),
			),
			'magick-ai/list-posts'      => array(
				'label'            => __( 'List Posts', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated list of posts or pages.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array( 'type' => 'string', 'default' => 'post' ),
						'status'    => array( 'type' => 'string', 'default' => 'publish' ),
						'search'    => array( 'type' => 'string' ),
						'per_page'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10 ),
						'page'      => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'        => array( 'type' => 'integer' ),
									'title'     => array( 'type' => 'string' ),
									'status'    => array( 'type' => 'string' ),
									'date'      => array( 'type' => 'string' ),
									'post_type' => array( 'type' => 'string' ),
									'author'    => array( 'type' => 'string' ),
									'edit_link' => array( 'type' => 'string' ),
								),
								'required'   => array( 'id', 'title', 'status' ),
							),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_posts' ),
			),
			'magick-ai/get-post'        => array(
				'label'            => __( 'Get Post', 'magick-ai-abilities' ),
				'description'      => __( 'Returns details for a single post or page.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'         => array( 'type' => 'integer', 'minimum' => 1 ),
						'include_content' => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'id'        => array( 'type' => 'integer' ),
						'title'     => array( 'type' => 'string' ),
						'status'    => array( 'type' => 'string' ),
						'post_type' => array( 'type' => 'string' ),
						'date'      => array( 'type' => 'string' ),
						'author'    => array( 'type' => 'string' ),
						'excerpt'   => array( 'type' => 'string' ),
						'content'   => array( 'type' => 'string' ),
						'edit_link' => array( 'type' => 'string' ),
					),
					'required'   => array( 'id', 'title', 'status' ),
				),
				'execute_callback' => array( $this, 'get_post' ),
			),
			'magick-ai/resolve-url-to-post' => array(
				'label'            => __( 'Resolve URL to Post', 'magick-ai-abilities' ),
				'description'      => __( 'Resolves a URL or slug to a post ID, post type, status, edit link, and permalink.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'url'       => array( 'type' => 'string' ),
						'slug'      => array( 'type' => 'string' ),
						'post_type' => array( 'type' => 'string', 'default' => 'any' ),
					),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer' ),
						'post_type' => array( 'type' => 'string' ),
						'status'    => array( 'type' => 'string' ),
						'edit_link' => array( 'type' => 'string' ),
						'permalink' => array( 'type' => 'string' ),
						'matched_by' => array( 'type' => 'string' ),
					),
					'required'   => array( 'post_id', 'post_type', 'status', 'edit_link' ),
				),
				'execute_callback' => array( $this, 'resolve_url_to_post' ),
			),
			'magick-ai/get-post-blocks' => array(
				'label'            => __( 'Get Post Blocks', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a Gutenberg block structure snapshot for a post or revision.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'              => array( 'type' => 'integer', 'minimum' => 1 ),
						'revision_id'          => array( 'type' => 'integer', 'minimum' => 1 ),
						'include_inner_blocks' => array( 'type' => 'boolean', 'default' => true ),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'        => array( 'type' => 'integer' ),
						'revision_id'    => array( 'type' => 'integer' ),
						'post_type'      => array( 'type' => 'string' ),
						'status'         => array( 'type' => 'string' ),
						'block_count'    => array( 'type' => 'integer' ),
						'content_length' => array( 'type' => 'integer' ),
						'blocks'         => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'post_id', 'block_count', 'blocks' ),
				),
				'execute_callback' => array( $this, 'get_post_blocks' ),
			),
			'magick-ai/list-post-revisions' => array(
				'label'            => __( 'List Post Revisions', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a paginated read-only revision history for a post.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer', 'minimum' => 1 ),
						'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 10 ),
						'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer' ),
						'total'    => array( 'type' => 'integer' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
						'items'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'post_id', 'total', 'items' ),
				),
				'execute_callback' => array( $this, 'list_post_revisions' ),
			),
		);
	}

	/**
	 * Returns site information.
	 *
	 * @return array<string,mixed>
	 */
	public function site_info() {
		$theme = wp_get_theme();
		$timezone = get_option( 'timezone_string' );
		if ( '' === $timezone ) {
			$offset = get_option( 'gmt_offset' );
			$timezone = 'UTC' . ( $offset ? sprintf( '%+g', $offset ) : '' );
		}

		return array(
			'name'        => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'home_url'    => home_url(),
			'site_url'    => site_url(),
			'language'    => get_locale(),
			'timezone'    => $timezone,
			'wp_version'  => get_bloginfo( 'version' ),
			'theme'       => $theme ? $theme->get( 'Name' ) : '',
		);
	}

	/**
	 * Returns a redacted WordPress-only diagnostics summary.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function wp_diagnostics_summary( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		$include_plugins = ! array_key_exists( 'include_plugins', $input ) || ! empty( $input['include_plugins'] );
		$include_theme = ! array_key_exists( 'include_theme', $input ) || ! empty( $input['include_theme'] );
		$include_cron = ! array_key_exists( 'include_cron', $input ) || ! empty( $input['include_cron'] );
		$include_updates = ! array_key_exists( 'include_updates', $input ) || ! empty( $input['include_updates'] );

		return array(
			'summary_version' => 'v1',
			'generated_at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'redacted'        => true,
			'site'            => $this->build_site_diagnostics_summary(),
			'wordpress'       => $this->build_wordpress_diagnostics_summary(),
			'php'             => $this->build_php_diagnostics_summary(),
			'theme'           => $include_theme ? $this->build_theme_diagnostics_summary() : array( 'included' => false ),
			'plugins'         => $include_plugins ? $this->build_plugin_diagnostics_summary() : array( 'included' => false ),
			'rest_api'        => $this->build_rest_api_diagnostics_summary(),
			'abilities_api'   => $this->build_abilities_api_diagnostics_summary(),
			'cron'            => $include_cron ? $this->build_cron_diagnostics_summary() : array( 'included' => false ),
			'updates'         => $include_updates ? $this->build_updates_diagnostics_summary() : array( 'included' => false ),
			'omitted'         => array(
				'magick_ai_settings',
				'mcp_settings',
				'api_keys',
				'database_name',
				'database_table_prefix',
				'filesystem_paths',
				'error_log_contents',
				'external_http_probes',
			),
		);
	}

	/**
	 * Counts posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,int>|\WP_Error
	 */
	public function count_posts( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );

		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_type_invalid', __( 'Post type does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$query = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => $status,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return array(
			'total' => (int) $query->found_posts,
		);
	}

	/**
	 * Lists media attachments.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_media( $input ) {
		$input = is_array( $input ) ? $input : array();
		$mime_type = sanitize_text_field( (string) ( $input['mime_type'] ?? '' ) );
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);
		if ( '' !== $mime_type ) {
			$args['post_mime_type'] = $mime_type;
		}
		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$date_from = sanitize_text_field( (string) ( $input['date_from'] ?? '' ) );
		$date_to = sanitize_text_field( (string) ( $input['date_to'] ?? '' ) );
		if ( '' !== $date_from || '' !== $date_to ) {
			$args['date_query'] = array();
			if ( '' !== $date_from ) {
				$args['date_query']['after'] = $date_from;
			}
			if ( '' !== $date_to ) {
				$args['date_query']['before'] = $date_to;
			}
		}

		if ( ! empty( $input['has_empty_alt'] ) ) {
			$args['meta_query'] = is_array( $args['meta_query'] ?? null ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '=',
				),
			);
		}

		if ( ! empty( $input['has_empty_caption'] ) ) {
			$args['meta_query'] = is_array( $args['meta_query'] ?? null ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_wp_attachment_metadata',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_wp_attachment_metadata',
					'value'   => '',
					'compare' => '=',
				),
			);
		}

		$query = new \WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$items[] = array(
				'id'        => $post_id,
				'title'     => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'date'      => sanitize_text_field( (string) get_post_field( 'post_date', $post_id ) ),
				'mime_type' => sanitize_text_field( (string) get_post_mime_type( $post_id ) ),
				'url'       => esc_url_raw( (string) wp_get_attachment_url( $post_id ) ),
				'edit_link' => get_edit_post_link( $post_id, 'raw' ),
			);
		}

		return array(
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Lists taxonomy terms.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_terms( $input ) {
		return $this->list_terms_for_taxonomy( $input, false );
	}

	/**
	 * Lists taxonomy terms with taxonomy included in the response.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_taxonomy_terms( $input ) {
		return $this->list_terms_for_taxonomy( $input, true );
	}

	/**
	 * Lists post categories.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_categories( $input ) {
		$input = is_array( $input ) ? $input : array();
		$input['taxonomy'] = 'category';

		return $this->list_terms( $input );
	}

	/**
	 * Lists post tags.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_tags( $input ) {
		$input = is_array( $input ) ? $input : array();
		$input['taxonomy'] = 'post_tag';

		return $this->list_terms( $input );
	}

	/**
	 * Gets one taxonomy term.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_term( $input ) {
		$input = is_array( $input ) ? $input : array();
		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? 'category' ) );
		$term_id = absint( $input['id'] ?? 0 );
		$slug = sanitize_title( (string) ( $input['slug'] ?? '' ) );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'magick_ai_abilities_taxonomy_invalid', __( 'Taxonomy does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$term = null;
		if ( $term_id > 0 ) {
			$term = get_term( $term_id, $taxonomy );
		} elseif ( '' !== $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
		}

		if ( ! $term || is_wp_error( $term ) || ! is_object( $term ) ) {
			return new \WP_Error( 'magick_ai_abilities_term_not_found', __( 'Term was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}

		return array(
			'id'          => absint( $term->term_id ?? 0 ),
			'name'        => sanitize_text_field( (string) ( $term->name ?? '' ) ),
			'slug'        => sanitize_title( (string) ( $term->slug ?? '' ) ),
			'taxonomy'    => sanitize_key( (string) ( $term->taxonomy ?? $taxonomy ) ),
			'parent'      => absint( $term->parent ?? 0 ),
			'description' => sanitize_text_field( (string) ( $term->description ?? '' ) ),
			'count'       => absint( $term->count ?? 0 ),
		);
	}

	/**
	 * Builds a read-only post excerpt proposal from explicit content.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function propose_post_excerpt( $input ) {
		$input = is_array( $input ) ? $input : array();
		$content = wp_strip_all_tags( (string) ( $input['content'] ?? '' ) );
		$content = trim( (string) preg_replace( '/\s+/', ' ', $content ) );
		if ( '' === $content ) {
			return new \WP_Error( 'magick_ai_abilities_excerpt_content_required', __( 'Content is required to generate an excerpt proposal.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$style = sanitize_key( (string) ( $input['style'] ?? 'neutral' ) );
		if ( ! in_array( $style, array( 'concise', 'neutral', 'seo' ), true ) ) {
			$style = 'neutral';
		}

		$max_chars = max( 40, min( 240, absint( $input['max_chars'] ?? 160 ) ) );
		if ( 'concise' === $style ) {
			$max_chars = min( $max_chars, 90 );
		}

		return array(
			'proposal_text' => $this->truncate_text( $content, $max_chars ),
			'explain'       => __( 'Local read-only excerpt proposal generated from explicit content input.', 'magick-ai-abilities' ),
			'meta'          => array(
				'source'    => 'local_readonly_proposal',
				'style'     => $style,
				'max_chars' => $max_chars,
			),
		);
	}

	/**
	 * Lists users.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_users( $input ) {
		$input = is_array( $input ) ? $input : array();
		$role = sanitize_key( (string) ( $input['role'] ?? '' ) );
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );

		$args = array(
			'number' => $per_page,
			'paged'  => $page,
		);
		if ( '' !== $role ) {
			$args['role'] = $role;
		}
		if ( '' !== $search ) {
			$args['search'] = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'display_name' );
		}

		$query = new \WP_User_Query( $args );
		$items = array();
		foreach ( $query->get_results() as $user ) {
			if ( ! is_object( $user ) ) {
				continue;
			}
			$roles = is_array( $user->roles ?? null ) ? $user->roles : array();
			$items[] = array(
				'id'           => absint( $user->ID ?? 0 ),
				'display_name' => sanitize_text_field( (string) ( $user->display_name ?? '' ) ),
				'user_login'   => sanitize_user( (string) ( $user->user_login ?? '' ), true ),
				'roles'        => array_values( array_map( 'sanitize_key', $roles ) ),
			);
		}

		return array(
			'total'    => (int) $query->get_total(),
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Lists comments.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_comments( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$status = sanitize_key( (string) ( $input['status'] ?? 'approve' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$offset = ( $page - 1 ) * $per_page;

		$args = array(
			'status' => '' !== $status ? $status : 'approve',
			'number' => $per_page,
			'offset' => $offset,
		);
		if ( $post_id > 0 ) {
			$args['post_id'] = $post_id;
		}

		$comments = get_comments( $args );
		$count_args = $args;
		$count_args['count'] = true;
		$count_args['number'] = 0;
		$count_args['offset'] = 0;
		$total = (int) get_comments( $count_args );
		$items = array();
		foreach ( ( is_array( $comments ) ? $comments : array() ) as $comment ) {
			if ( ! is_object( $comment ) ) {
				continue;
			}
			$comment_post_id = absint( $comment->comment_post_ID ?? 0 );
			$items[] = array(
				'id'         => absint( $comment->comment_ID ?? 0 ),
				'author'     => sanitize_text_field( (string) ( $comment->comment_author ?? '' ) ),
				'date'       => sanitize_text_field( (string) ( $comment->comment_date ?? '' ) ),
				'status'     => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'post_id'    => $comment_post_id,
				'post_title' => $comment_post_id > 0 ? sanitize_text_field( (string) get_the_title( $comment_post_id ) ) : '',
				'excerpt'    => wp_trim_words( wp_strip_all_tags( (string) ( $comment->comment_content ?? '' ) ), 20 ),
			);
		}

		return array(
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Lists navigation menus.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_menus( $input ) {
		$input = is_array( $input ) ? $input : array();
		$include_locations = ! array_key_exists( 'include_locations', $input ) || ! empty( $input['include_locations'] );
		$menus = function_exists( 'wp_get_nav_menus' ) ? wp_get_nav_menus() : array();
		$menus = is_array( $menus ) ? $menus : array();
		$location_map = $include_locations && function_exists( 'get_nav_menu_locations' ) ? (array) get_nav_menu_locations() : array();
		$items = array();

		foreach ( $menus as $menu ) {
			if ( ! is_object( $menu ) ) {
				continue;
			}
			$menu_id = absint( $menu->term_id ?? 0 );
			if ( $menu_id <= 0 ) {
				continue;
			}

			$locations = array();
			if ( $include_locations ) {
				foreach ( $location_map as $location_id => $location_menu_id ) {
					if ( absint( $location_menu_id ) === $menu_id ) {
						$locations[] = sanitize_key( (string) $location_id );
					}
				}
			}

			$items[] = array(
				'menu_id'   => $menu_id,
				'name'      => sanitize_text_field( (string) ( $menu->name ?? '' ) ),
				'slug'      => sanitize_title( (string) ( $menu->slug ?? '' ) ),
				'count'     => absint( $menu->count ?? 0 ),
				'locations' => $locations,
			);
		}

		return array(
			'total' => count( $items ),
			'items' => $items,
		);
	}

	/**
	 * Gets one navigation menu.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_menu( $input ) {
		$input = is_array( $input ) ? $input : array();
		$menu_id = absint( $input['menu_id'] ?? 0 );
		$menu_slug = sanitize_title( (string) ( $input['menu_slug'] ?? '' ) );
		$include_items = ! array_key_exists( 'include_items', $input ) || ! empty( $input['include_items'] );

		$menu = null;
		if ( $menu_id > 0 && function_exists( 'wp_get_nav_menu_object' ) ) {
			$menu = wp_get_nav_menu_object( $menu_id );
		}
		if ( ! $menu && '' !== $menu_slug && function_exists( 'wp_get_nav_menu_object' ) ) {
			$menu = wp_get_nav_menu_object( $menu_slug );
		}
		if ( ! $menu || ! is_object( $menu ) ) {
			return new \WP_Error( 'magick_ai_abilities_menu_not_found', __( 'Menu was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}

		$items = array();
		if ( $include_items && function_exists( 'wp_get_nav_menu_items' ) ) {
			$menu_items = wp_get_nav_menu_items( absint( $menu->term_id ?? 0 ), array( 'post_status' => 'any' ) );
			foreach ( ( is_array( $menu_items ) ? $menu_items : array() ) as $item ) {
				if ( ! is_object( $item ) ) {
					continue;
				}
				$items[] = array(
					'id'         => absint( $item->ID ?? 0 ),
					'parent_id'  => absint( $item->menu_item_parent ?? 0 ),
					'title'      => sanitize_text_field( (string) ( $item->title ?? '' ) ),
					'url'        => esc_url_raw( (string) ( $item->url ?? '' ) ),
					'type'       => sanitize_key( (string) ( $item->type ?? '' ) ),
					'object'     => sanitize_key( (string) ( $item->object ?? '' ) ),
					'object_id'  => absint( $item->object_id ?? 0 ),
					'menu_order' => absint( $item->menu_order ?? 0 ),
				);
			}
		}

		return array(
			'menu'  => array(
				'menu_id'     => absint( $menu->term_id ?? 0 ),
				'name'        => sanitize_text_field( (string) ( $menu->name ?? '' ) ),
				'slug'        => sanitize_title( (string) ( $menu->slug ?? '' ) ),
				'description' => sanitize_text_field( (string) ( $menu->description ?? '' ) ),
				'count'       => absint( $menu->count ?? 0 ),
			),
			'items' => $items,
		);
	}

	/**
	 * Searches posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function search_posts( $input ) {
		$input = is_array( $input ) ? $input : array();
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );

		if ( '' === $search ) {
			return new \WP_Error( 'magick_ai_abilities_search_empty', __( 'Search keyword cannot be empty.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_type_invalid', __( 'Post type does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$query = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => '' !== $status ? $status : 'publish',
				's'              => $search,
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'relevance',
				'order'          => 'DESC',
			)
		);

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( ! is_object( $post ) ) {
				continue;
			}
			$post_id = absint( $post->ID ?? 0 );
			if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$items[] = array(
				'id'        => $post_id,
				'title'     => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'slug'      => sanitize_title( (string) ( $post->post_name ?? '' ) ),
				'status'    => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'type'      => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'author_id' => absint( $post->post_author ?? 0 ),
				'date'      => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
				'excerpt'   => wp_trim_words( wp_strip_all_tags( (string) ( $post->post_content ?? '' ) ), 30 ),
			);
		}

		return array(
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Gets post statistics.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_stats( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$content = (string) ( $post->post_content ?? '' );
		$text = wp_strip_all_tags( $content );
		$word_count = str_word_count( $text );
		$image_count = substr_count( strtolower( $content ), '<img' );

		return array(
			'post_id'              => $post_id,
			'word_count'           => $word_count,
			'image_count'          => $image_count,
			'reading_time_minutes' => max( 1, (int) ceil( $word_count / 200 ) ),
			'comment_count'        => absint( $post->comment_count ?? 0 ),
			'published_date'       => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
			'modified_date'        => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
			'permalink'            => esc_url_raw( (string) get_permalink( $post_id ) ),
		);
	}

	/**
	 * Lists revisions using the legacy list-revisions response shape.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_revisions( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post revision history.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$all_revisions = function_exists( 'wp_get_post_revisions' )
			? wp_get_post_revisions(
				$post_id,
				array(
					'check_enabled' => false,
					'order'         => 'DESC',
					'orderby'       => 'date ID',
				)
			)
			: array();
		$all_revisions = is_array( $all_revisions ) ? array_values( $all_revisions ) : array();
		$total = count( $all_revisions );
		$slice = array_slice( $all_revisions, ( $page - 1 ) * $per_page, $per_page );
		$items = array();

		foreach ( $slice as $revision ) {
			if ( ! is_object( $revision ) ) {
				continue;
			}
			$author_id = absint( $revision->post_author ?? 0 );
			$items[] = array(
				'id'     => absint( $revision->ID ?? 0 ),
				'date'   => sanitize_text_field( (string) ( $revision->post_date ?? '' ) ),
				'author' => $author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) ) : '',
				'title'  => sanitize_text_field( (string) ( $revision->post_title ?? '' ) ),
			);
		}

		return array(
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Gets post meta.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_meta( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$meta_key = sanitize_key( (string) ( $input['meta_key'] ?? '' ) );
		$single = ! array_key_exists( 'single', $input ) || ! empty( $input['single'] );

		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post meta.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		if ( '' !== $meta_key ) {
			return array(
				'post_id'  => $post_id,
				'meta_key' => $meta_key,
				'value'    => get_post_meta( $post_id, $meta_key, $single ),
				'single'   => $single,
			);
		}

		$all_meta = get_post_meta( $post_id );
		$items = array();
		foreach ( ( is_array( $all_meta ) ? $all_meta : array() ) as $key => $values ) {
			$items[] = array(
				'key'    => sanitize_key( (string) $key ),
				'values' => is_array( $values ) ? array_values( $values ) : array( $values ),
			);
		}

		return array(
			'post_id' => $post_id,
			'items'   => $items,
		);
	}

	/**
	 * Lists pages.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_pages( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read pages.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		if ( ! in_array( $status, array( 'publish', 'draft', 'pending', 'private', 'any' ), true ) ) {
			$status = 'publish';
		}
		$parent = isset( $input['parent'] ) ? (int) $input['parent'] : 0;
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$orderby = sanitize_key( (string) ( $input['orderby'] ?? 'menu_order' ) );
		if ( ! in_array( $orderby, array( 'menu_order', 'title', 'date', 'modified', 'id' ), true ) ) {
			$orderby = 'menu_order';
		}
		$order = strtoupper( sanitize_key( (string) ( $input['order'] ?? 'ASC' ) ) );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'ASC';
		}
		$number = max( 1, min( 100, absint( $input['number'] ?? 20 ) ) );
		$offset = absint( $input['offset'] ?? 0 );

		$query_args = array(
			'post_type'              => 'page',
			'post_status'            => $status,
			'posts_per_page'         => $number,
			'offset'                 => $offset,
			'orderby'                => $orderby,
			'order'                  => $order,
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		if ( 0 !== $parent ) {
			if ( -1 === $parent ) {
				$query_args['post_parent__not_in'] = array( 0 );
			} else {
				$query_args['post_parent'] = $parent;
			}
		}
		if ( '' !== $search ) {
			$query_args['s'] = $search;
		}

		$query = new \WP_Query( $query_args );
		$pages = array();
		foreach ( ( is_array( $query->posts ?? null ) ? $query->posts : array() ) as $post ) {
			if ( ! is_object( $post ) ) {
				continue;
			}
			$page_id = absint( $post->ID ?? 0 );
			if ( $page_id <= 0 || ! current_user_can( 'edit_post', $page_id ) ) {
				continue;
			}
			$template = function_exists( 'get_page_template_slug' ) ? get_page_template_slug( $page_id ) : '';
			$template = is_string( $template ) ? $template : '';
			$url = 'publish' === (string) ( $post->post_status ?? '' ) ? get_permalink( $page_id ) : '';

			$pages[] = array(
				'id'         => $page_id,
				'title'      => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'slug'       => sanitize_title( (string) ( $post->post_name ?? '' ) ),
				'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'template'   => $template,
				'parent_id'  => absint( $post->post_parent ?? 0 ),
				'menu_order' => (int) ( $post->menu_order ?? 0 ),
				'date'       => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
				'modified'   => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
				'url'        => is_string( $url ) ? esc_url_raw( $url ) : '',
			);
		}
		if ( function_exists( 'wp_reset_postdata' ) ) {
			wp_reset_postdata();
		}

		$total = (int) $query->found_posts;

		return array(
			'pages'    => $pages,
			'total'    => $total,
			'has_more' => ( $offset + $number ) < $total,
		);
	}

	/**
	 * Gets one page.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_page( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read pages.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$page_id = absint( $input['page_id'] ?? 0 );
		if ( $page_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_page_not_found', __( 'Page ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		$post = get_post( $page_id );
		if ( ! $post || 'page' !== sanitize_key( (string) ( $post->post_type ?? '' ) ) ) {
			return new \WP_Error( 'magick_ai_abilities_page_not_found', __( 'Page was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $page_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this page.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$template = function_exists( 'get_page_template_slug' ) ? get_page_template_slug( $page_id ) : '';
		$template = is_string( $template ) ? $template : '';
		$url = 'publish' === (string) ( $post->post_status ?? '' ) ? get_permalink( $page_id ) : '';
		$author = null;
		$author_id = absint( $post->post_author ?? 0 );
		$author_user = $author_id > 0 ? get_userdata( $author_id ) : false;
		if ( $author_user ) {
			$author = array(
				'id'   => absint( $author_user->ID ?? 0 ),
				'name' => sanitize_text_field( (string) ( $author_user->display_name ?? '' ) ),
			);
		}

		$available_templates = $this->get_page_templates();
		$result = array(
			'id'                  => $page_id,
			'title'               => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
			'slug'                => sanitize_title( (string) ( $post->post_name ?? '' ) ),
			'content'             => apply_filters( 'the_content', (string) ( $post->post_content ?? '' ) ),
			'content_raw'         => (string) ( $post->post_content ?? '' ),
			'excerpt'             => sanitize_textarea_field( (string) ( $post->post_excerpt ?? '' ) ),
			'status'              => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'template'            => $template,
			'parent_id'           => absint( $post->post_parent ?? 0 ),
			'menu_order'          => (int) ( $post->menu_order ?? 0 ),
			'author'              => $author,
			'date'                => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
			'modified'            => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
			'url'                 => is_string( $url ) ? esc_url_raw( $url ) : '',
			'edit_url'            => admin_url( 'post.php?post=' . $page_id . '&action=edit' ),
			'available_templates' => $available_templates,
		);

		if ( ! empty( $input['include_meta'] ) ) {
			$result['meta'] = $this->get_scoped_post_meta( $page_id, $input['meta_keys'] ?? array() );
		}

		return $result;
	}

	/**
	 * Inspects page structure.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function inspect_page_structure( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read pages.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$max_pages = max( 1, min( 100, absint( $input['max_pages'] ?? 50 ) ) );
		$query = new \WP_Query(
			array(
				'post_type'              => 'page',
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => $max_pages,
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$pages = array();
		$top_level_count = 0;
		$parent_ids = array();
		foreach ( ( is_array( $query->posts ?? null ) ? $query->posts : array() ) as $post ) {
			if ( ! is_object( $post ) ) {
				continue;
			}
			$page_id = absint( $post->ID ?? 0 );
			if ( $page_id <= 0 || ! current_user_can( 'edit_post', $page_id ) ) {
				continue;
			}
			$parent_id = absint( $post->post_parent ?? 0 );
			$template = function_exists( 'get_page_template_slug' ) ? get_page_template_slug( $page_id ) : '';
			$template = is_string( $template ) ? $template : '';
			$pages[] = array(
				'id'         => $page_id,
				'title'      => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'slug'       => sanitize_title( (string) ( $post->post_name ?? '' ) ),
				'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'parent_id'  => $parent_id,
				'menu_order' => (int) ( $post->menu_order ?? 0 ),
				'template'   => $template,
			);
			if ( 0 === $parent_id ) {
				++$top_level_count;
			} else {
				$parent_ids[] = $parent_id;
			}
		}
		if ( function_exists( 'wp_reset_postdata' ) ) {
			wp_reset_postdata();
		}

		$orphan_pages = $this->find_orphan_pages( $pages, $parent_ids );
		$total_pages = (int) $query->found_posts;

		return array(
			'pages'           => $pages,
			'total_pages'     => $total_pages,
			'top_level_count' => $top_level_count,
			'has_more'        => $total_pages > $max_pages,
			'findings'        => array(
				'orphan_pages' => $orphan_pages,
				'orphan_count' => count( $orphan_pages ),
			),
			'max_pages'       => $max_pages,
		);
	}

	/**
	 * Lists a page tree.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_pages_tree( $input ) {
		$input = is_array( $input ) ? $input : array();
		$root_id = absint( $input['root_id'] ?? 0 );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		if ( '' === $status ) {
			$status = 'publish';
		}
		$max_depth = max( 1, min( 6, absint( $input['max_depth'] ?? 3 ) ) );

		$query = new \WP_Query(
			array(
				'post_type'      => 'page',
				'post_status'    => $status,
				'posts_per_page' => 500,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$page_ids = is_array( $query->posts ?? null ) ? $query->posts : array();
		$pages = array();
		foreach ( $page_ids as $page_id ) {
			$page_id = absint( $page_id );
			if ( $page_id <= 0 || ! current_user_can( 'edit_post', $page_id ) ) {
				continue;
			}
			$page = get_post( $page_id );
			if ( $page ) {
				$pages[ $page_id ] = $page;
			}
		}

		$items = array();
		$walk = function ( $parent_id, $depth ) use ( &$walk, &$items, $pages, $max_depth ) {
			if ( $depth > $max_depth ) {
				return;
			}
			foreach ( $pages as $page ) {
				$current_parent = absint( $page->post_parent ?? 0 );
				if ( $current_parent !== $parent_id ) {
					continue;
				}
				$page_id = absint( $page->ID ?? 0 );
				if ( $page_id <= 0 ) {
					continue;
				}
				$items[] = array(
					'id'        => $page_id,
					'parent_id' => $current_parent,
					'depth'     => $depth,
					'title'     => sanitize_text_field( (string) get_the_title( $page_id ) ),
					'slug'      => sanitize_title( (string) ( $page->post_name ?? '' ) ),
					'status'    => sanitize_key( (string) ( $page->post_status ?? '' ) ),
					'edit_link' => get_edit_post_link( $page_id, 'raw' ),
				);
				$walk( $page_id, $depth + 1 );
			}
		};
		$walk( $root_id, 0 );

		return array(
			'root_id' => $root_id,
			'total'   => count( $items ),
			'items'   => $items,
		);
	}

	/**
	 * Lists posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_posts( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status = sanitize_key( (string) ( $input['status'] ?? $input['post_status'] ?? 'publish' ) );
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );

		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_type_invalid', __( 'Post type does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'        => $post_type,
			'post_status'      => '' !== $status ? $status : 'publish',
			'posts_per_page'   => $per_page,
			'paged'            => $page,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'fields'           => 'ids',
			'suppress_filters' => false,
		);
		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$query = new \WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post_id ) {
			$post_id = absint( $post_id );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$author_id = absint( $post->post_author ?? 0 );
			$status_value = sanitize_key( (string) ( $post->post_status ?? '' ) );
			$items[] = array(
				'id'          => $post_id,
				'title'       => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'status'      => $status_value,
				'post_status' => $status_value,
				'date'        => sanitize_text_field( (string) get_post_field( 'post_date', $post_id ) ),
				'post_type'   => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'author'      => $author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) ) : '',
				'edit_link'   => get_edit_post_link( $post_id, 'raw' ),
			);
		}

		return array(
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Gets one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$include_content = ! empty( $input['include_content'] );
		$author_id = absint( $post->post_author ?? 0 );
		$data = array(
			'id'        => $post_id,
			'title'     => sanitize_text_field( (string) get_the_title( $post_id ) ),
			'status'    => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'post_type' => sanitize_key( (string) ( $post->post_type ?? '' ) ),
			'date'      => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
			'author'    => $author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) ) : '',
			'excerpt'   => wp_trim_words( wp_strip_all_tags( (string) ( $post->post_content ?? '' ) ), 55 ),
			'edit_link' => get_edit_post_link( $post_id, 'raw' ),
		);
		if ( $include_content ) {
			$data['content'] = (string) ( $post->post_content ?? '' );
		}

		return $data;
	}

	/**
	 * Resolves a URL or slug to a post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function resolve_url_to_post( $input ) {
		$input = is_array( $input ) ? $input : array();
		$url = esc_url_raw( (string) ( $input['url'] ?? '' ) );
		$slug = sanitize_title( (string) ( $input['slug'] ?? '' ) );
		$post_type_hint = sanitize_key( (string) ( $input['post_type'] ?? 'any' ) );

		if ( '' === $url && '' === $slug ) {
			return new \WP_Error( 'magick_ai_abilities_resolve_input_required', __( 'A URL or slug is required.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$post_id = 0;
		$matched_by = '';
		if ( '' !== $url && function_exists( 'url_to_postid' ) ) {
			$post_id = absint( url_to_postid( $url ) );
			if ( $post_id > 0 ) {
				$matched_by = 'url';
			}
		}

		if ( $post_id <= 0 && '' === $slug && '' !== $url ) {
			$path = parse_url( $url, PHP_URL_PATH );
			$path = is_string( $path ) ? trim( $path ) : '';
			$candidate_slug = sanitize_title( basename( trim( $path, '/' ) ) );
			if ( '' !== $candidate_slug ) {
				$post_id = $this->resolve_post_id_by_slug( $candidate_slug, $post_type_hint );
				if ( $post_id > 0 ) {
					$matched_by = 'url_slug';
				}
			}
		}

		if ( $post_id <= 0 && '' !== $slug ) {
			$post_id = $this->resolve_post_id_by_slug( $slug, $post_type_hint );
			if ( $post_id > 0 ) {
				$matched_by = 'slug';
			}
		}

		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'No matching post was found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'No matching post was found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		return array(
			'post_id'    => $post_id,
			'post_type'  => sanitize_key( (string) ( $post->post_type ?? '' ) ),
			'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'edit_link'  => get_edit_post_link( $post_id, 'raw' ),
			'permalink'  => esc_url_raw( (string) get_permalink( $post_id ) ),
			'matched_by' => '' !== $matched_by ? $matched_by : 'unknown',
		);
	}

	/**
	 * Gets a post block tree.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_blocks( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$target_post = $post;
		$revision_id = absint( $input['revision_id'] ?? 0 );
		if ( $revision_id > 0 ) {
			$revision = get_post( $revision_id );
			if ( ! $revision || 'revision' !== sanitize_key( (string) ( $revision->post_type ?? '' ) ) ) {
				return new \WP_Error( 'magick_ai_abilities_revision_not_found', __( 'Revision was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
			}
			if ( absint( $revision->post_parent ?? 0 ) !== $post_id ) {
				return new \WP_Error( 'magick_ai_abilities_revision_post_mismatch', __( 'Revision does not belong to the requested post.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
			}
			$target_post = $revision;
		}

		$include_inner_blocks = ! array_key_exists( 'include_inner_blocks', $input ) || ! empty( $input['include_inner_blocks'] );
		$content = (string) ( $target_post->post_content ?? '' );
		$parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		$normalized_blocks = $this->normalize_block_tree( is_array( $parsed_blocks ) ? $parsed_blocks : array(), $include_inner_blocks );
		if ( empty( $normalized_blocks ) && '' !== trim( $content ) ) {
			$normalized_blocks[] = array(
				'blockName'   => 'core/freeform',
				'attrs'       => array(),
				'innerHTML'   => $content,
				'innerBlocks' => array(),
			);
		}

		return array(
			'post_id'        => $post_id,
			'revision_id'    => $revision_id,
			'post_type'      => sanitize_key( (string) ( $post->post_type ?? '' ) ),
			'status'         => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'block_count'    => count( $normalized_blocks ),
			'content_length' => strlen( $content ),
			'blocks'         => $normalized_blocks,
		);
	}

	/**
	 * Lists post revisions.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_post_revisions( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post revision history.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$per_page = max( 1, min( 20, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$all_revisions = function_exists( 'wp_get_post_revisions' )
			? wp_get_post_revisions(
				$post_id,
				array(
					'check_enabled' => false,
					'order'         => 'DESC',
					'orderby'       => 'date ID',
				)
			)
			: array();
		$all_revisions = is_array( $all_revisions ) ? array_values( $all_revisions ) : array();
		$total = count( $all_revisions );
		$slice = array_slice( $all_revisions, ( $page - 1 ) * $per_page, $per_page );
		$items = array();
		foreach ( $slice as $revision ) {
			if ( ! is_object( $revision ) ) {
				continue;
			}
			$revision_id = absint( $revision->ID ?? 0 );
			if ( $revision_id <= 0 ) {
				continue;
			}
			$author_id = absint( $revision->post_author ?? 0 );
			$items[] = array(
				'revision_id'    => $revision_id,
				'parent_id'      => absint( $revision->post_parent ?? 0 ),
				'modified_gmt'   => sanitize_text_field( (string) ( $revision->post_modified_gmt ?? '' ) ),
				'modified_local' => sanitize_text_field( (string) ( $revision->post_modified ?? '' ) ),
				'author_id'      => $author_id,
				'author'         => $author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) ) : '',
				'excerpt'        => $this->build_revision_excerpt( (string) ( $revision->post_content ?? '' ) ),
				'edit_link'      => get_edit_post_link( $revision_id, 'raw' ),
			);
		}

		return array(
			'post_id'  => $post_id,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Lists post types.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_post_types( $input ) {
		$input = is_array( $input ) ? $input : array();
		$include_builtin = ! empty( $input['include_builtin'] );
		$include_private = ! empty( $input['include_private'] );
		$show_ui_only = ! array_key_exists( 'show_ui_only', $input ) || ! empty( $input['show_ui_only'] );

		$post_types = get_post_types( array(), 'objects' );
		$post_types = is_array( $post_types ) ? $post_types : array();
		$items = array();
		foreach ( $post_types as $post_type => $object ) {
			$post_type = sanitize_key( (string) $post_type );
			if ( '' === $post_type || ! is_object( $object ) ) {
				continue;
			}
			if ( ! $include_builtin && ! empty( $object->_builtin ) ) {
				continue;
			}
			if ( $show_ui_only && empty( $object->show_ui ) ) {
				continue;
			}
			if ( ! $include_private && empty( $object->public ) ) {
				continue;
			}
			$items[] = array(
				'post_type'    => $post_type,
				'label'        => sanitize_text_field( (string) ( $object->label ?? $post_type ) ),
				'public'       => ! empty( $object->public ),
				'hierarchical' => ! empty( $object->hierarchical ),
				'show_in_rest' => ! empty( $object->show_in_rest ),
			);
		}

		usort( $items, array( $this, 'sort_by_post_type' ) );

		return array(
			'total' => count( $items ),
			'items' => $items,
		);
	}

	/**
	 * Lists taxonomies.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_taxonomies( $input ) {
		$input = is_array( $input ) ? $input : array();
		$object_type = sanitize_key( (string) ( $input['object_type'] ?? '' ) );
		$show_ui_only = ! array_key_exists( 'show_ui_only', $input ) || ! empty( $input['show_ui_only'] );
		$public_only = ! empty( $input['public_only'] );

		$taxonomies = get_taxonomies( array(), 'objects' );
		$taxonomies = is_array( $taxonomies ) ? $taxonomies : array();
		$items = array();
		foreach ( $taxonomies as $taxonomy => $object ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			if ( '' === $taxonomy || ! is_object( $object ) ) {
				continue;
			}
			if ( $show_ui_only && empty( $object->show_ui ) ) {
				continue;
			}
			if ( $public_only && empty( $object->public ) ) {
				continue;
			}
			$object_types = is_array( $object->object_type ?? null ) ? $object->object_type : array();
			$object_types = array_values(
				array_filter(
					array_map(
						static function ( $type ) {
							return sanitize_key( (string) $type );
						},
						$object_types
					)
				)
			);
			if ( '' !== $object_type && ! in_array( $object_type, $object_types, true ) ) {
				continue;
			}

			$items[] = array(
				'taxonomy'     => $taxonomy,
				'label'        => sanitize_text_field( (string) ( $object->label ?? $taxonomy ) ),
				'public'       => ! empty( $object->public ),
				'hierarchical' => ! empty( $object->hierarchical ),
				'show_in_rest' => ! empty( $object->show_in_rest ),
				'object_types' => $object_types,
			);
		}

		usort( $items, array( $this, 'sort_by_taxonomy' ) );

		return array(
			'total' => count( $items ),
			'items' => $items,
		);
	}

	/**
	 * Normalizes parsed block rows.
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
	 * @param bool                           $include_inner_blocks Include child blocks.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_block_tree( array $blocks, $include_inner_blocks ) {
		$normalized = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array();
			$normalized[] = array(
				'blockName'   => sanitize_text_field( (string) ( $block['blockName'] ?? '' ) ),
				'attrs'       => is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array(),
				'innerHTML'   => (string) ( $block['innerHTML'] ?? '' ),
				'innerBlocks' => $include_inner_blocks ? $this->normalize_block_tree( $inner_blocks, true ) : array(),
			);
		}

		return $normalized;
	}

	/**
	 * Builds a short revision excerpt.
	 *
	 * @param string $content Raw post content.
	 * @return string
	 */
	private function build_revision_excerpt( $content ) {
		$stripped = wp_strip_all_tags( (string) $content );
		if ( function_exists( 'wp_trim_words' ) ) {
			return wp_trim_words( $stripped, 24 );
		}
		$stripped = trim( $stripped );
		if ( strlen( $stripped ) <= 120 ) {
			return $stripped;
		}

		return substr( $stripped, 0, 117 ) . '...';
	}

	/**
	 * Truncates text by character count when multibyte helpers are available.
	 *
	 * @param string $text Text to truncate.
	 * @param int    $max_chars Maximum character count.
	 * @return string
	 */
	private function truncate_text( $text, $max_chars ) {
		$text = (string) $text;
		$max_chars = max( 1, (int) $max_chars );
		$trim_chars = " \t\n\r\0\x0B,.;:!?";

		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $text ) <= $max_chars ) {
				return $text;
			}

			return rtrim( mb_substr( $text, 0, max( 0, $max_chars - 3 ) ), $trim_chars ) . '...';
		}

		if ( strlen( $text ) <= $max_chars ) {
			return $text;
		}

		return rtrim( substr( $text, 0, max( 0, $max_chars - 3 ) ), $trim_chars ) . '...';
	}

	/**
	 * Returns active theme page templates.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function get_page_templates() {
		$available_templates = array(
			array(
				'label' => __( 'Default Template', 'magick-ai-abilities' ),
				'slug'  => 'default',
			),
		);
		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
		if ( ! $theme || ! is_object( $theme ) || ! method_exists( $theme, 'get_page_templates' ) ) {
			return $available_templates;
		}

		$templates = $theme->get_page_templates( null, 'page' );
		foreach ( ( is_array( $templates ) ? $templates : array() ) as $slug => $label ) {
			$available_templates[] = array(
				'label' => sanitize_text_field( (string) $label ),
				'slug'  => sanitize_key( (string) $slug ),
			);
		}

		return $available_templates;
	}

	/**
	 * Returns post meta scoped by optional keys.
	 *
	 * @param int   $post_id Post id.
	 * @param mixed $meta_keys Optional meta keys.
	 * @return array<string,mixed>
	 */
	private function get_scoped_post_meta( $post_id, $meta_keys ) {
		$post_id = absint( $post_id );
		$keys = is_array( $meta_keys ) ? array_filter( array_map( 'sanitize_key', $meta_keys ) ) : array();
		$meta = array();

		if ( empty( $keys ) ) {
			$all_meta = get_post_meta( $post_id );
			foreach ( ( is_array( $all_meta ) ? $all_meta : array() ) as $key => $values ) {
				$key = sanitize_key( (string) $key );
				$meta[ $key ] = is_array( $values ) && 1 === count( $values ) ? $values[0] : $values;
			}

			return $meta;
		}

		foreach ( $keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== $value || ( function_exists( 'metadata_exists' ) && metadata_exists( 'post', $post_id, $key ) ) ) {
				$meta[ $key ] = $value;
			}
		}

		return $meta;
	}

	/**
	 * Finds pages whose parent id no longer resolves to a post.
	 *
	 * @param array<int,array<string,mixed>> $pages Page rows.
	 * @param array<int,int>                 $parent_ids Parent ids.
	 * @return array<int,array<string,mixed>>
	 */
	private function find_orphan_pages( array $pages, array $parent_ids ) {
		if ( empty( $parent_ids ) ) {
			return array();
		}

		$parent_ids = array_values( array_unique( array_map( 'absint', $parent_ids ) ) );
		$missing_parent_ids = array();
		foreach ( $parent_ids as $parent_id ) {
			if ( $parent_id > 0 && ! get_post( $parent_id ) ) {
				$missing_parent_ids[] = $parent_id;
			}
		}
		if ( empty( $missing_parent_ids ) ) {
			return array();
		}

		$orphan_pages = array();
		foreach ( $pages as $page ) {
			$parent_id = absint( $page['parent_id'] ?? 0 );
			if ( in_array( $parent_id, $missing_parent_ids, true ) ) {
				$orphan_pages[] = array(
					'id'                => absint( $page['id'] ?? 0 ),
					'title'             => sanitize_text_field( (string) ( $page['title'] ?? '' ) ),
					'missing_parent_id' => $parent_id,
				);
			}
		}

		return $orphan_pages;
	}

	/**
	 * Resolves a post id by slug and optional post type hint.
	 *
	 * @param string $slug Post slug.
	 * @param string $post_type_hint Post type hint or any.
	 * @return int
	 */
	private function resolve_post_id_by_slug( $slug, $post_type_hint = 'any' ) {
		$slug = sanitize_title( (string) $slug );
		$post_type_hint = sanitize_key( (string) $post_type_hint );
		if ( '' === $slug ) {
			return 0;
		}

		$post_types = array();
		if ( '' !== $post_type_hint && 'any' !== $post_type_hint ) {
			if ( post_type_exists( $post_type_hint ) ) {
				$post_types[] = $post_type_hint;
			}
		} else {
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			$post_types = is_array( $post_types ) ? array_values( $post_types ) : array();
		}
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		$page = get_page_by_path( $slug, OBJECT, $post_types );
		if ( is_object( $page ) && ! empty( $page->ID ) ) {
			return absint( $page->ID );
		}

		$query = new \WP_Query(
			array(
				'name'           => $slug,
				'post_type'      => $post_types,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$post_id = ! empty( $query->posts[0] ) ? absint( $query->posts[0] ) : 0;

		return $post_id > 0 ? $post_id : 0;
	}

	/**
	 * Builds site diagnostics summary.
	 *
	 * @return array<string,mixed>
	 */
	private function build_site_diagnostics_summary() {
		$timezone = function_exists( 'wp_timezone_string' )
			? wp_timezone_string()
			: (string) get_option( 'timezone_string', '' );
		if ( '' === $timezone ) {
			$offset = get_option( 'gmt_offset', 0 );
			$timezone = 'UTC' . ( $offset ? sprintf( '%+g', $offset ) : '' );
		}

		return array(
			'name'                  => sanitize_text_field( (string) get_bloginfo( 'name' ) ),
			'language'              => sanitize_text_field( (string) get_locale() ),
			'timezone'              => sanitize_text_field( (string) $timezone ),
			'home_url_host'         => sanitize_text_field( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
			'site_url_host'         => sanitize_text_field( (string) wp_parse_url( site_url(), PHP_URL_HOST ) ),
			'is_multisite'          => function_exists( 'is_multisite' ) ? (bool) is_multisite() : false,
			'users_can_register'    => (bool) get_option( 'users_can_register', false ),
			'blog_public'           => (int) get_option( 'blog_public', 1 ),
			'permalink_mode'        => '' === (string) get_option( 'permalink_structure', '' ) ? 'plain' : 'custom',
		);
	}

	/**
	 * Builds WordPress diagnostics summary.
	 *
	 * @return array<string,mixed>
	 */
	private function build_wordpress_diagnostics_summary() {
		$environment_type = function_exists( 'wp_get_environment_type' )
			? sanitize_key( (string) wp_get_environment_type() )
			: 'production';

		return array(
			'version'          => sanitize_text_field( (string) get_bloginfo( 'version' ) ),
			'environment_type' => $environment_type,
			'debug'            => array(
				'wp_debug'         => defined( 'WP_DEBUG' ) ? (bool) WP_DEBUG : false,
				'wp_debug_log'     => defined( 'WP_DEBUG_LOG' ) ? (bool) WP_DEBUG_LOG : false,
				'wp_debug_display' => defined( 'WP_DEBUG_DISPLAY' ) ? (bool) WP_DEBUG_DISPLAY : false,
				'script_debug'     => defined( 'SCRIPT_DEBUG' ) ? (bool) SCRIPT_DEBUG : false,
			),
			'constants'        => array(
				'disable_wp_cron' => defined( 'DISABLE_WP_CRON' ) ? (bool) DISABLE_WP_CRON : false,
			),
		);
	}

	/**
	 * Builds PHP diagnostics summary.
	 *
	 * @return array<string,mixed>
	 */
	private function build_php_diagnostics_summary() {
		$memory_limit = (string) ini_get( 'memory_limit' );
		$upload_max_filesize = (string) ini_get( 'upload_max_filesize' );
		$post_max_size = (string) ini_get( 'post_max_size' );

		return array(
			'version'                  => sanitize_text_field( (string) phpversion() ),
			'sapi'                     => sanitize_text_field( (string) php_sapi_name() ),
			'memory_limit'             => sanitize_text_field( $memory_limit ),
			'memory_limit_bytes'       => $this->parse_ini_size_to_bytes( $memory_limit ),
			'max_execution_time'       => (int) ini_get( 'max_execution_time' ),
			'max_input_vars'           => (int) ini_get( 'max_input_vars' ),
			'post_max_size'            => sanitize_text_field( $post_max_size ),
			'post_max_size_bytes'      => $this->parse_ini_size_to_bytes( $post_max_size ),
			'upload_max_filesize'       => sanitize_text_field( $upload_max_filesize ),
			'upload_max_filesize_bytes' => $this->parse_ini_size_to_bytes( $upload_max_filesize ),
			'wp_max_upload_size_bytes'  => function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0,
		);
	}

	/**
	 * Builds active theme diagnostics summary.
	 *
	 * @return array<string,mixed>
	 */
	private function build_theme_diagnostics_summary() {
		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
		if ( ! $theme || ! is_object( $theme ) ) {
			return array(
				'included' => true,
				'available' => false,
			);
		}

		return array(
			'included'       => true,
			'available'      => true,
			'name'           => sanitize_text_field( (string) $theme->get( 'Name' ) ),
			'version'        => sanitize_text_field( (string) $theme->get( 'Version' ) ),
			'stylesheet'     => sanitize_key( (string) $theme->get_stylesheet() ),
			'template'       => sanitize_key( (string) $theme->get_template() ),
			'is_child_theme' => method_exists( $theme, 'parent' ) ? (bool) $theme->parent() : false,
			'is_block_theme' => function_exists( 'wp_is_block_theme' ) ? (bool) wp_is_block_theme() : null,
		);
	}

	/**
	 * Builds plugin count diagnostics summary.
	 *
	 * @return array<string,mixed>
	 */
	private function build_plugin_diagnostics_summary() {
		$this->load_plugin_admin_functions();

		$all_plugins = function_exists( 'get_plugins' ) ? get_plugins() : array();
		$all_plugins = is_array( $all_plugins ) ? $all_plugins : array();
		$active_plugins = get_option( 'active_plugins', array() );
		$active_plugins = is_array( $active_plugins ) ? $active_plugins : array();
		$network_active_plugins = function_exists( 'get_site_option' ) ? get_site_option( 'active_sitewide_plugins', array() ) : array();
		$network_active_plugins = is_array( $network_active_plugins ) ? $network_active_plugins : array();
		$mu_plugins = function_exists( 'get_mu_plugins' ) ? get_mu_plugins() : array();
		$mu_plugins = is_array( $mu_plugins ) ? $mu_plugins : array();

		return array(
			'included'             => true,
			'available_count'      => count( $all_plugins ),
			'active_count'         => count( $active_plugins ),
			'network_active_count' => count( $network_active_plugins ),
			'mu_count'             => count( $mu_plugins ),
		);
	}

	/**
	 * Builds REST API diagnostics summary without making HTTP requests.
	 *
	 * @return array<string,mixed>
	 */
	private function build_rest_api_diagnostics_summary() {
		$routes = array();
		if ( function_exists( 'rest_get_server' ) ) {
			$server = rest_get_server();
			if ( is_object( $server ) && method_exists( $server, 'get_routes' ) ) {
				$routes = $server->get_routes();
				$routes = is_array( $routes ) ? $routes : array();
			}
		}

		return array(
			'available'                 => function_exists( 'rest_url' ) && function_exists( 'rest_get_server' ),
			'url_host'                  => function_exists( 'rest_url' ) ? sanitize_text_field( (string) wp_parse_url( rest_url(), PHP_URL_HOST ) ) : '',
			'route_count'               => count( $routes ),
			'wp_abilities_routes_found' => $this->routes_include_prefix( $routes, '/wp-abilities/v1' ),
		);
	}

	/**
	 * Builds Abilities API diagnostics summary.
	 *
	 * @return array<string,mixed>
	 */
	private function build_abilities_api_diagnostics_summary() {
		return array(
			'register_ability_available'          => function_exists( 'wp_register_ability' ),
			'register_category_available'         => function_exists( 'wp_register_ability_category' ),
			'get_ability_available'               => function_exists( 'wp_get_ability' ),
			'has_ability_available'               => function_exists( 'wp_has_ability' ),
			'get_category_available'              => function_exists( 'wp_get_ability_category' ),
			'has_category_available'              => function_exists( 'wp_has_ability_category' ),
			'diagnostics_summary_registered'      => function_exists( 'wp_has_ability' ) ? (bool) wp_has_ability( 'magick-ai-abilities/wp-diagnostics-summary' ) : null,
			'legacy_site_info_registered'         => function_exists( 'wp_has_ability' ) ? (bool) wp_has_ability( 'magick-ai/site-info' ) : null,
		);
	}

	/**
	 * Builds cron diagnostics summary.
	 *
	 * @return array<string,mixed>
	 */
	private function build_cron_diagnostics_summary() {
		$total = 0;
		$next_timestamp = 0;
		if ( function_exists( '_get_cron_array' ) ) {
			$cron_array = _get_cron_array();
			$cron_array = is_array( $cron_array ) ? $cron_array : array();
			foreach ( $cron_array as $timestamp => $hooks ) {
				$timestamp = absint( $timestamp );
				if ( $timestamp > 0 && ( 0 === $next_timestamp || $timestamp < $next_timestamp ) ) {
					$next_timestamp = $timestamp;
				}
				if ( ! is_array( $hooks ) ) {
					continue;
				}
				foreach ( $hooks as $events ) {
					if ( is_array( $events ) ) {
						$total += count( $events );
					}
				}
			}
		}

		return array(
			'included'               => true,
			'disabled'               => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'scheduled_events_total' => $total,
			'next_event_gmt'         => $next_timestamp > 0 ? gmdate( 'Y-m-d H:i:s', $next_timestamp ) : '',
		);
	}

	/**
	 * Builds update diagnostics summary from current update data only.
	 *
	 * @return array<string,mixed>
	 */
	private function build_updates_diagnostics_summary() {
		$update_data = function_exists( 'wp_get_update_data' ) ? wp_get_update_data() : array();
		$counts = is_array( $update_data['counts'] ?? null ) ? $update_data['counts'] : array();

		return array(
			'included'     => true,
			'total'        => absint( $counts['total'] ?? 0 ),
			'wordpress'    => absint( $counts['wordpress'] ?? 0 ),
			'plugins'      => absint( $counts['plugins'] ?? 0 ),
			'themes'       => absint( $counts['themes'] ?? 0 ),
			'translations' => absint( $counts['translations'] ?? 0 ),
		);
	}

	/**
	 * Loads plugin admin helpers if available.
	 *
	 * @return void
	 */
	private function load_plugin_admin_functions() {
		if ( function_exists( 'get_plugins' ) && function_exists( 'get_mu_plugins' ) ) {
			return;
		}

		$plugin_file = defined( 'ABSPATH' ) ? ABSPATH . 'wp-admin/includes/plugin.php' : '';
		if ( is_string( $plugin_file ) && '' !== $plugin_file && file_exists( $plugin_file ) ) {
			require_once $plugin_file;
		}
	}

	/**
	 * Checks whether REST routes include a prefix.
	 *
	 * @param array<mixed> $routes REST routes.
	 * @param string       $prefix Route prefix.
	 * @return bool
	 */
	private function routes_include_prefix( array $routes, $prefix ) {
		$prefix = (string) $prefix;
		foreach ( array_keys( $routes ) as $route ) {
			if ( 0 === strpos( (string) $route, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Parses shorthand ini sizes to bytes.
	 *
	 * @param string $value Ini size value.
	 * @return int
	 */
	private function parse_ini_size_to_bytes( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return 0;
		}

		$unit = strtolower( substr( $value, -1 ) );
		$number = (float) $value;
		switch ( $unit ) {
			case 'g':
				$number *= 1024;
				// Fall through.
			case 'm':
				$number *= 1024;
				// Fall through.
			case 'k':
				$number *= 1024;
				break;
		}

		return max( 0, (int) $number );
	}

	/**
	 * Lists terms for a taxonomy.
	 *
	 * @param mixed $input Input args.
	 * @param bool  $include_taxonomy Include taxonomy in the response.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function list_terms_for_taxonomy( $input, $include_taxonomy ) {
		$input = is_array( $input ) ? $input : array();
		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? 'category' ) );
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$parent = absint( $input['parent'] ?? 0 );
		$hide_empty = ! empty( $input['hide_empty'] );
		$per_page_max = $include_taxonomy ? 100 : 50;
		$per_page_default = $include_taxonomy ? 20 : 10;
		$per_page = max( 1, min( $per_page_max, absint( $input['per_page'] ?? $per_page_default ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$offset = ( $page - 1 ) * $per_page;

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'magick_ai_abilities_taxonomy_invalid', __( 'Taxonomy does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => $hide_empty,
			'number'     => $per_page,
			'offset'     => $offset,
		);
		if ( $include_taxonomy ) {
			$args['parent'] = $parent;
		}
		if ( '' !== $search ) {
			$args['search'] = $search;
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		$terms = is_array( $terms ) ? $terms : array();

		$count_args = $args;
		$count_args['fields'] = 'count';
		$count_args['number'] = 0;
		$count_args['offset'] = 0;
		$total = get_terms( $count_args );
		$total = is_wp_error( $total ) ? 0 : (int) $total;

		$items = array();
		foreach ( $terms as $term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}
			$row = array(
				'id'    => absint( $term->term_id ?? 0 ),
				'name'  => sanitize_text_field( (string) ( $term->name ?? '' ) ),
				'slug'  => sanitize_title( (string) ( $term->slug ?? '' ) ),
				'count' => absint( $term->count ?? 0 ),
			);
			if ( $include_taxonomy ) {
				$row['parent'] = absint( $term->parent ?? 0 );
			}
			$items[] = $row;
		}

		$response = array(
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
		if ( $include_taxonomy ) {
			$response = array_merge( array( 'taxonomy' => $taxonomy ), $response );
		}

		return $response;
	}

	/**
	 * Sort callback for post type rows.
	 *
	 * @param array<string,mixed> $left Left row.
	 * @param array<string,mixed> $right Right row.
	 * @return int
	 */
	private function sort_by_post_type( array $left, array $right ) {
		return strcmp( (string) ( $left['post_type'] ?? '' ), (string) ( $right['post_type'] ?? '' ) );
	}

	/**
	 * Sort callback for taxonomy rows.
	 *
	 * @param array<string,mixed> $left Left row.
	 * @param array<string,mixed> $right Right row.
	 * @return int
	 */
	private function sort_by_taxonomy( array $left, array $right ) {
		return strcmp( (string) ( $left['taxonomy'] ?? '' ), (string) ( $right['taxonomy'] ?? '' ) );
	}
}
