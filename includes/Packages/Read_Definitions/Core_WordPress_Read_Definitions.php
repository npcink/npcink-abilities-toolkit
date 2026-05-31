<?php
/**
 * Core WordPress read ability definitions.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages\Read_Definitions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides generic WordPress read ability definitions.
 */
final class Core_WordPress_Read_Definitions {
	/**
	 * Returns core WordPress read ability definitions.
	 *
	 * @param object $callbacks Callback owner.
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions( $callbacks ) {
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
				'execute_callback' => array( $callbacks, 'site_info' ),
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
				'execute_callback' => array( $callbacks, 'list_post_types' ),
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
				'execute_callback' => array( $callbacks, 'list_taxonomies' ),
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
				'execute_callback' => array( $callbacks, 'propose_post_excerpt' ),
			),
			'magick-ai/resolve-post-metadata-plan' => array(
				'label'            => __( 'Resolve Post Metadata Plan', 'magick-ai-abilities' ),
				'description'      => __( 'Normalizes a post metadata plan into the canonical excerpt, taxonomy, slug, author, template, format, and publication handoff payload.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_metadata_plan'      => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'taxonomy_plan'           => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'generated_excerpt'       => array( 'type' => array( 'string', 'null' ) ),
						'generated_excerpt_text'  => array( 'type' => array( 'string', 'null' ) ),
						'generated_slug'          => array( 'type' => array( 'string', 'null' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'       => 'object',
							'properties' => array(
								'excerpt'    => array( 'type' => 'string' ),
								'slug'       => array( 'type' => 'string' ),
								'categories' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
								'tags'       => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
								'publish_at' => array( 'type' => 'string' ),
								'author_id'  => array( 'type' => 'integer' ),
								'template'   => array( 'type' => 'string' ),
								'format'     => array( 'type' => 'string' ),
							),
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $callbacks, 'resolve_post_metadata_plan' ),
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
									'author_profile' => array( 'type' => 'object', 'additionalProperties' => true ),
								),
								'required'   => array( 'id', 'display_name' ),
							),
						),
					),
					'required'   => array( 'total', 'items' ),
				),
				'execute_callback' => array( $callbacks, 'list_users' ),
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
				'execute_callback' => array( $callbacks, 'list_menus' ),
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
						'tree'  => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
					'required'   => array( 'menu', 'items', 'tree' ),
				),
				'execute_callback' => array( $callbacks, 'get_menu' ),
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
				'execute_callback' => array( $callbacks, 'search_posts' ),
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
				'execute_callback' => array( $callbacks, 'get_post_stats' ),
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
				'execute_callback' => array( $callbacks, 'list_revisions' ),
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
				'execute_callback' => array( $callbacks, 'get_post_meta' ),
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
				'execute_callback' => array( $callbacks, 'count_posts' ),
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
						'author_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'taxonomy'  => array( 'type' => 'string' ),
						'term_id'   => array( 'type' => 'integer', 'minimum' => 1 ),
						'term_slug' => array( 'type' => 'string' ),
						'date_after' => array( 'type' => 'string' ),
						'date_before' => array( 'type' => 'string' ),
						'modified_after' => array( 'type' => 'string' ),
						'modified_before' => array( 'type' => 'string' ),
						'orderby'   => array( 'type' => 'string', 'enum' => array( 'date', 'modified', 'title', 'id', 'menu_order', 'comment_count' ), 'default' => 'date' ),
						'order'     => array( 'type' => 'string', 'enum' => array( 'ASC', 'DESC' ), 'default' => 'DESC' ),
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
						'filters'  => array( 'type' => 'object', 'additionalProperties' => true ),
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
				'execute_callback' => array( $callbacks, 'list_posts' ),
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
				'execute_callback' => array( $callbacks, 'get_post' ),
			),
			'magick-ai/get-post-context' => array(
				'label'            => __( 'Get Post Context', 'magick-ai-abilities' ),
				'description'      => __( 'Returns one agent-ready post context bundle with content, stats, terms, media, blocks, revisions, and optional scoped metadata.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'           => array( 'type' => 'integer', 'minimum' => 1 ),
						'include_content'   => array( 'type' => 'boolean', 'default' => true ),
						'include_blocks'    => array( 'type' => 'boolean', 'default' => true ),
						'include_terms'     => array( 'type' => 'boolean', 'default' => true ),
						'include_media'     => array( 'type' => 'boolean', 'default' => true ),
						'include_revisions' => array( 'type' => 'boolean', 'default' => false ),
						'include_meta'      => array( 'type' => 'boolean', 'default' => false ),
						'meta_keys'         => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'post'      => array( 'type' => 'object', 'additionalProperties' => true ),
								'stats'     => array( 'type' => 'object', 'additionalProperties' => true ),
								'terms'     => array( 'type' => 'object', 'additionalProperties' => true ),
								'media'     => array( 'type' => 'object', 'additionalProperties' => true ),
								'blocks'    => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'revisions' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'meta'      => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'post', 'stats' ),
							'additionalProperties' => true,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $callbacks, 'get_post_context' ),
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
				'execute_callback' => array( $callbacks, 'resolve_url_to_post' ),
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
				'execute_callback' => array( $callbacks, 'get_post_blocks' ),
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
				'execute_callback' => array( $callbacks, 'list_post_revisions' ),
			),
		);
	}
}
