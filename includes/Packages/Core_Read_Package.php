<?php
/**
 * Core WordPress read-only ability package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

use Magick_AI_Abilities\Packages\Read_Definitions\WordPress_Diagnostics_Definitions;
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
			$pack = $this->read_pack_for( $ability_id );
			if ( ! $this->should_register_read_ability( $pack, $ability_id, $definition ) ) {
				continue;
			}

			$definition['meta'] = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
			$definition['meta']['magick_ai_abilities'] = is_array( $definition['meta']['magick_ai_abilities'] ?? null )
				? $definition['meta']['magick_ai_abilities']
				: array();
			$definition['meta']['magick_ai_abilities']['pack'] = $pack;
			if ( 0 === strpos( (string) $ability_id, 'magick-ai/' ) ) {
				$definition['project_to_magick_catalog'] = true;
			}
			$this->abilities->add_readonly( $ability_id, $definition );
		}
	}

	/**
	 * Returns whether a read ability from a sub-pack should register.
	 *
	 * @param string              $pack Ability sub-pack.
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return bool
	 */
	private function should_register_read_ability( $pack, $ability_id, array $definition ) {
		$defaults = Core_Read_Pack_Classifier::default_packs();

		/**
		 * Filters enabled read-only sub-packs.
		 *
		 * The default preserves the full built-in catalog. Hosts that only need
		 * generic WordPress reads can return array( 'core_wordpress_read' ).
		 *
		 * @param string[] $defaults Enabled read sub-pack slugs.
		 */
		$enabled = apply_filters( 'magick_ai_abilities_enabled_read_packs', $defaults );
		$enabled = is_array( $enabled ) ? array_map( 'sanitize_key', $enabled ) : $defaults;
		$pack    = sanitize_key( $pack );

		/**
		 * Filters registration for a single built-in read ability.
		 *
		 * @param bool                $register Whether to register the ability.
		 * @param string              $ability_id Ability id.
		 * @param string              $pack Ability sub-pack.
		 * @param array<string,mixed> $definition Ability definition.
		 */
		return (bool) apply_filters(
			'magick_ai_abilities_should_register_read_ability',
			in_array( $pack, $enabled, true ),
			$ability_id,
			$pack,
			$definition
		);
	}

	/**
	 * Classifies a built-in read ability into a coarse sub-pack.
	 *
	 * @param string $ability_id Ability id.
	 * @return string
	 */
	private function read_pack_for( $ability_id ) {
		return Core_Read_Pack_Classifier::classify( $ability_id );
	}

	/**
	 * Returns package ability definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function definitions() {
		$definitions = array(
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
				'execute_callback' => array( $this, 'resolve_post_metadata_plan' ),
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
			'magick-ai/resolve-internal-link-targets' => array(
				'label'            => __( 'Resolve Internal Link Targets', 'magick-ai-abilities' ),
				'description'      => __( 'Finds local published content that can serve as internal-link targets for a post draft or optimization workflow.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'current_post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'title'           => array( 'type' => 'string' ),
						'content'         => array( 'type' => 'string' ),
						'focus_keyword'   => array( 'type' => 'string' ),
						'keywords'        => array(
							'type'  => array( 'array', 'null' ),
							'items' => array( 'type' => 'string' ),
						),
						'max_targets'     => array( 'type' => array( 'integer', 'null' ), 'minimum' => 1, 'maximum' => 6, 'default' => 3 ),
					),
					'required'             => array( 'title', 'content' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'targets'        => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'placement_plan' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'no_link_zones'  => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
								'summary'        => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'resolve_internal_link_targets' ),
			),
			'magick-ai/build-inline-image-blocks' => array(
				'label'            => __( 'Build Inline Image Blocks', 'magick-ai-abilities' ),
				'description'      => __( 'Converts uploaded inline image rows into append-ready Gutenberg image blocks without writing post content.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'cap.text.generate',
				'required_scopes'  => array( 'cap.text.generate' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'uploaded_inline_media'   => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'generated_inline_media'  => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'inline_plan'             => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
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
								'blocks'  => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary' => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'   => array( 'blocks', 'summary' ),
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_inline_image_blocks' ),
			),
			'magick-ai/build-media-seo-assets' => array(
				'label'            => __( 'Build Media SEO Assets', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a canonical media SEO enrichment asset list from article media workflow upload outputs.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'upload_files',
				'required_scope'   => 'media.read',
				'required_scopes'  => array( 'media.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'article'                   => array( 'type' => 'object', 'additionalProperties' => true ),
						'resolved_image_source'     => array( 'type' => 'object', 'additionalProperties' => true ),
						'featured_upload'           => array( 'type' => 'object', 'additionalProperties' => true ),
						'generated_featured_upload' => array( 'type' => 'object', 'additionalProperties' => true ),
						'inline_uploads'            => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'generated_inline_uploads'  => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'vision_fallback_mode'      => array( 'type' => 'string', 'enum' => array( 'off', 'auto' ), 'default' => 'auto' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_media_seo_assets' ),
			),
			'magick-ai/geo-analyze' => array(
				'label'            => __( 'GEO Analysis', 'magick-ai-abilities' ),
				'description'      => __( 'Builds deterministic GEO and AI visibility issues, recommendations, and evidence from article text.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'title'         => array( 'type' => 'string' ),
						'content'       => array( 'type' => 'string' ),
						'excerpt'       => array( 'type' => array( 'string', 'null' ) ),
						'focus_keyword' => array( 'type' => array( 'string', 'null' ) ),
						'entities'      => array( 'type' => array( 'array', 'null' ), 'items' => array( 'type' => 'string' ) ),
					),
					'required'             => array( 'title', 'content' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'geo_analyze' ),
			),
			'magick-ai/optimize-media-metadata' => array(
				'label'            => __( 'Optimize Media Metadata', 'magick-ai-abilities' ),
				'description'      => __( 'Builds deterministic media title, alt, caption, description, source, and disclosure suggestions from article and media context.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'upload_files',
				'required_scope'   => 'media.read',
				'required_scopes'  => array( 'media.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'article_title'        => array( 'type' => 'string' ),
						'article_excerpt'      => array( 'type' => 'string' ),
						'article_content'      => array( 'type' => 'string' ),
						'focus_keyword'        => array( 'type' => 'string' ),
						'vision_fallback_mode' => array( 'type' => 'string', 'enum' => array( 'off', 'auto' ) ),
						'media_assets'         => array( 'type' => array( 'array', 'null' ), 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
					),
					'required'             => array( 'media_assets' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'optimize_media_metadata' ),
			),
			'magick-ai/position-inline-image-blocks' => array(
				'label'            => __( 'Position Inline Image Blocks', 'magick-ai-abilities' ),
				'description'      => __( 'Places generated inline image blocks after matching paragraph, heading, or anchor targets and falls back to append.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'existing_blocks' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'inline_blocks'   => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'inline_plan'     => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
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
								'blocks'  => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary' => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'   => array( 'blocks', 'summary' ),
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'position_inline_image_blocks' ),
			),
			'magick-ai/build-article-optimization-report' => array(
				'label'            => __( 'Build Article Optimization Report', 'magick-ai-abilities' ),
				'description'      => __( 'Composes SEO, GEO, internal-link, and media optimization sections into one deterministic report.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal report composer for article optimization workflows only.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post'           => array( 'type' => 'object', 'additionalProperties' => true ),
						'seo'            => array( 'type' => 'object', 'additionalProperties' => true ),
						'geo'            => array( 'type' => 'object', 'additionalProperties' => true ),
						'internal_links' => array( 'type' => 'object', 'additionalProperties' => true ),
						'media'          => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'             => array( 'post', 'seo', 'geo', 'internal_links', 'media' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_article_optimization_report' ),
			),
			'magick-ai/seo-report-context' => array(
				'label'            => __( 'Build SEO Report Context', 'magick-ai-abilities' ),
				'description'      => __( 'Builds lightweight deterministic SEO issues and recommendations for article optimization workflows.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'input'         => array( 'type' => 'string' ),
						'focus_keyword' => array( 'type' => array( 'string', 'null' ) ),
					),
					'required'             => array( 'input' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_seo_report_context' ),
			),
			'magick-ai/read-post-optimization-context' => array(
				'label'            => __( 'Read Post Optimization Context', 'magick-ai-abilities' ),
				'description'      => __( 'Reads a post title, excerpt, content, slug, categories, tags, SEO fields, template, and format snapshot for article optimization workflows.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only. Reads canonical post optimization context without applying changes.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'read_post_optimization_context' ),
			),
			'magick-ai/build-article-single-optimization-suggest' => array(
				'label'            => __( 'Build Article Single Optimization Suggest', 'magick-ai-abilities' ),
				'description'      => __( 'Builds one canonical single-article optimization suggestion envelope for title, taxonomy, excerpt, slug, SEO, content, and GEO improvements.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal workflow helper only. Builds suggestions and never applies writes.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post'              => array( 'type' => 'object', 'additionalProperties' => true ),
						'generated_excerpt' => array( 'type' => 'object', 'additionalProperties' => true ),
						'generated_seo'     => array( 'type' => 'object', 'additionalProperties' => true ),
						'seo_analysis'      => array( 'type' => 'object', 'additionalProperties' => true ),
						'geo_analysis'      => array( 'type' => 'object', 'additionalProperties' => true ),
						'focus_keyword'     => array( 'type' => array( 'string', 'null' ) ),
						'keywords'          => array(
							'type'  => array( 'array', 'null' ),
							'items' => array( 'type' => 'string' ),
						),
						'taxonomy_context'  => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
					),
					'required'             => array( 'post' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_article_single_optimization_suggest' ),
			),
			'magick-ai/build-article-optimization-apply-plan' => array(
				'label'            => __( 'Build Article Optimization Apply Plan', 'magick-ai-abilities' ),
				'description'      => __( 'Builds one conservative apply plan from the read-only article optimization report without applying writes.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal planner only. Build one conservative apply plan and only mark safe write actions as directly applicable.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post'              => array( 'type' => 'object', 'additionalProperties' => true ),
						'report'            => array( 'type' => 'object', 'additionalProperties' => true ),
						'optimization_plan' => array( 'type' => 'object', 'additionalProperties' => true ),
						'generated_excerpt' => array( 'type' => 'object', 'additionalProperties' => true ),
						'seo_meta'          => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'             => array( 'post', 'report' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_article_optimization_apply_plan' ),
			),
			'magick-ai/compose-article-optimization-apply-result' => array(
				'label'            => __( 'Compose Article Optimization Apply Result', 'magick-ai-abilities' ),
				'description'      => __( 'Composes report, apply plan, safe apply result, and remaining advisory changes into one deterministic envelope.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_options',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Internal composer only. Keep one canonical apply result envelope instead of inventing channel-private summaries.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'report'        => array( 'type' => 'object', 'additionalProperties' => true ),
						'apply_plan'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'apply_excerpt' => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'             => array( 'report', 'apply_plan' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'compose_article_optimization_apply_result' ),
			),
			'magick-ai/extract-reference-post-style' => array(
				'label'            => __( 'Extract Reference Post Style', 'magick-ai-abilities' ),
				'description'      => __( 'Reads reference posts and returns a compact writing-style profile for article workflows.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.write',
				'required_scopes'  => array( 'post.write' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Read reference posts and return a compact style profile, not verbatim excerpts.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'reference_post_ids' => array(
							'type'     => 'array',
							'items'    => array( 'type' => 'integer', 'minimum' => 1 ),
							'minItems' => 1,
							'maxItems' => 5,
						),
					),
					'required'             => array( 'reference_post_ids' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'                 => 'object',
					'properties'           => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'profile' => array( 'type' => 'object', 'additionalProperties' => true ),
								'samples' => array(
									'type'  => 'array',
									'items' => array( 'type' => 'object', 'additionalProperties' => true ),
								),
							),
							'required'             => array( 'profile', 'samples' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'             => array( 'success', 'data' ),
					'additionalProperties' => false,
				),
				'execute_callback' => array( $this, 'extract_reference_post_style' ),
			),
			'magick-ai/extract-style-baseline' => array(
				'label'            => __( 'Extract Site Style Baseline', 'magick-ai-abilities' ),
				'description'      => __( 'Extracts a lightweight site-wide or author-specific writing-style baseline from recent posts.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.write',
				'required_scopes'  => array( 'post.write' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'annotations'      => array(
					'instructions' => 'Read a small recent post sample and return lightweight site/author style baseline only.',
				),
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'      => array(
							'type' => 'string',
							'enum' => array( 'off', 'site_recent', 'author_recent' ),
						),
						'author_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'limit'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 5 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'                 => 'object',
					'properties'           => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'profile' => array( 'type' => 'object', 'additionalProperties' => true ),
								'samples' => array(
									'type'  => 'array',
									'items' => array( 'type' => 'object', 'additionalProperties' => true ),
								),
								'source'  => array( 'type' => 'string' ),
							),
							'required'             => array( 'profile', 'samples', 'source' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'             => array( 'success', 'data' ),
					'additionalProperties' => false,
				),
				'execute_callback' => array( $this, 'extract_style_baseline' ),
			),
			'magick-ai/build-article-production-fingerprint' => array(
				'label'            => __( 'Build Article Production Fingerprint', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a stable lightweight production fingerprint for article dedupe and result summaries.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'cap.text.generate',
				'required_scopes'  => array( 'cap.text.generate' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'topic'              => array( 'type' => 'string' ),
						'prompt'             => array( 'type' => 'string' ),
						'image_mode'         => array( 'type' => array( 'string', 'null' ) ),
						'publish_mode'       => array( 'type' => array( 'string', 'null' ) ),
						'content_format'     => array( 'type' => array( 'string', 'null' ) ),
						'voice_profile'      => array( 'type' => array( 'string', 'null' ) ),
						'opening_style'      => array( 'type' => array( 'string', 'null' ) ),
						'structure_style'    => array( 'type' => array( 'string', 'null' ) ),
						'reference_post_ids' => array(
							'type'  => array( 'array', 'null' ),
							'items' => array( 'type' => 'integer' ),
						),
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
								'production_fingerprint' => array( 'type' => 'string' ),
							),
							'required'   => array( 'production_fingerprint' ),
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_article_production_fingerprint' ),
			),
			'magick-ai/check-article-production-duplicate' => array(
				'label'            => __( 'Check Article Production Duplicate', 'magick-ai-abilities' ),
				'description'      => __( 'Checks whether a lightweight article production fingerprint already exists on a post.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.write',
				'required_scopes'  => array( 'post.write' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'production_fingerprint' => array( 'type' => 'string' ),
						'write_guard_mode'       => array( 'type' => array( 'string', 'null' ) ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'check_article_production_duplicate' ),
			),
			'magick-ai/review-article-output-light' => array(
				'label'            => __( 'Review Article Output Light', 'magick-ai-abilities' ),
				'description'      => __( 'Builds lightweight local article quality, style, image, and AI-risk review signals without model calls.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'cap.text.generate',
				'required_scopes'  => array( 'cap.text.generate' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'article'          => array( 'type' => 'object', 'additionalProperties' => true ),
						'style_profile'    => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'media'            => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'image_mode'       => array( 'type' => array( 'string', 'null' ) ),
						'platform_profile' => array(
							'type' => array( 'string', 'null' ),
							'enum' => array( 'generic', 'wechat', 'xiaohongshu', null ),
						),
						'human_signals'    => array(
							'type'  => array( 'array', 'null' ),
							'items' => array( 'type' => 'string' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'review_article_output_light' ),
			),
			'magick-ai/compose-article-production-result' => array(
				'label'            => __( 'Compose Article Production Result', 'magick-ai-abilities' ),
				'description'      => __( 'Summarizes the article production mainline with result mode, degraded reasons, fingerprint, handoff, and next action.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'input'                    => array( 'type' => 'object', 'additionalProperties' => true ),
						'article'                  => array( 'type' => 'object', 'additionalProperties' => true ),
						'draft'                    => array( 'type' => 'object', 'additionalProperties' => true ),
						'media'                    => array( 'type' => 'object', 'additionalProperties' => true ),
						'metadata_plan_resolution' => array( 'type' => 'object', 'additionalProperties' => true ),
						'seo_analysis'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'geo_analysis'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'review'                   => array( 'type' => 'object', 'additionalProperties' => true ),
						'publication_decision'     => array( 'type' => 'object', 'additionalProperties' => true ),
						'duplicate_guard'          => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'compose_article_production_result' ),
			),
			'magick-ai/compose-article-draft-result' => array(
				'label'            => __( 'Compose Article Draft Result', 'magick-ai-abilities' ),
				'description'      => __( 'Composes one canonical draft workflow result with draft content, metadata resolution, shared SEO/GEO review, and handoff hints.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'input'                    => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'article'                  => array( 'type' => 'object', 'additionalProperties' => true ),
						'draft'                    => array( 'type' => 'object', 'additionalProperties' => true ),
						'generated_seo'            => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'metadata_plan_resolution' => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'seo_analysis'             => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'geo_analysis'             => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'quality_scoring'          => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'ai_slop_detection'        => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'review'                   => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'duplicate_guard'          => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'compose_article_draft_result' ),
			),
			'magick-ai/resolve-article-publication-decision' => array(
				'label'            => __( 'Resolve Article Publication Decision', 'magick-ai-abilities' ),
				'description'      => __( 'Resolves a deterministic article publish, schedule, review, or draft handoff after quality and duplicate checks.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'cap.text.extract',
				'required_scopes'  => array( 'cap.text.extract' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'publish_mode'    => array( 'type' => 'string', 'enum' => array( 'draft', 'review', 'schedule', 'publish' ) ),
						'review'          => array( 'type' => 'object', 'additionalProperties' => true ),
						'duplicate_guard' => array( 'type' => 'object', 'additionalProperties' => true ),
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
								'requested_publish_mode' => array( 'type' => 'string' ),
								'effective_publish_mode' => array( 'type' => 'string' ),
								'publish_blocked'        => array( 'type' => 'boolean' ),
								'gate_reason'            => array( 'type' => 'string' ),
								'user_message'           => array( 'type' => 'string' ),
							),
							'required'   => array( 'requested_publish_mode', 'effective_publish_mode', 'publish_blocked', 'gate_reason', 'user_message' ),
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'resolve_article_publication_decision' ),
			),
			'magick-ai/build-article-style-profile' => array(
				'label'            => __( 'Build Article Style Profile', 'magick-ai-abilities' ),
				'description'      => __( 'Merges baseline, reference, and explicit writing-style hints into one lightweight article style profile.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'cap.text.generate',
				'required_scopes'  => array( 'cap.text.generate' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'reference_profile' => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'baseline_profile'  => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => true ),
						'voice_profile'     => array( 'type' => 'string' ),
						'opening_style'     => array( 'type' => 'string' ),
						'structure_style'   => array( 'type' => 'string' ),
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
								'profile' => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'   => array( 'profile' ),
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_article_style_profile' ),
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
				'execute_callback' => array( $this, 'get_post_context' ),
			),
			'magick-ai/get-content-publishing-checklist' => array(
				'label'            => __( 'Get Content Publishing Checklist', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a deterministic pre-publish readiness checklist for one post or page without applying changes.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'       => array( 'type' => 'integer', 'minimum' => 1 ),
						'target_status' => array( 'type' => 'string', 'enum' => array( 'publish', 'future', 'draft' ), 'default' => 'publish' ),
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
								'ready'        => array( 'type' => 'boolean' ),
								'post_id'      => array( 'type' => 'integer' ),
								'post_type'    => array( 'type' => 'string' ),
								'current_status' => array( 'type' => 'string' ),
								'target_status' => array( 'type' => 'string' ),
								'checks'       => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'missing'      => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
								'warnings'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
								'summary'      => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'ready', 'post_id', 'target_status', 'checks', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_content_publishing_checklist' ),
			),
			'magick-ai/get-content-inventory-health' => array(
				'label'            => __( 'Get Content Inventory Health', 'magick-ai-abilities' ),
				'description'      => __( 'Scans a bounded content inventory and summarizes missing titles, thin content, excerpt gaps, media gaps, SEO metadata gaps, and stale content.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'      => array( 'type' => 'string', 'default' => 'post' ),
						'status'         => array( 'type' => 'string', 'default' => 'any' ),
						'per_page'       => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'           => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'stale_days'     => array( 'type' => 'integer', 'minimum' => 30, 'maximum' => 3650, 'default' => 365 ),
						'target_status'  => array( 'type' => 'string', 'enum' => array( 'publish', 'future', 'draft' ), 'default' => 'publish' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'total'        => array( 'type' => 'integer' ),
								'page'         => array( 'type' => 'integer' ),
								'per_page'     => array( 'type' => 'integer' ),
								'health_score' => array( 'type' => 'integer' ),
								'issue_counts' => array( 'type' => 'object', 'additionalProperties' => true ),
								'items'        => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary'      => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'total', 'items', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_content_inventory_health' ),
			),
			'magick-ai/get-bulk-publishing-checklist' => array(
				'label'            => __( 'Get Bulk Publishing Checklist', 'magick-ai-abilities' ),
				'description'      => __( 'Runs the read-only publishing checklist for a bounded list of posts and returns batch readiness totals.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_ids'      => array(
							'type'     => 'array',
							'items'    => array( 'type' => 'integer', 'minimum' => 1 ),
							'minItems' => 1,
							'maxItems' => 50,
						),
						'target_status' => array( 'type' => 'string', 'enum' => array( 'publish', 'future', 'draft' ), 'default' => 'publish' ),
					),
					'required'             => array( 'post_ids' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'total'         => array( 'type' => 'integer' ),
								'ready_count'   => array( 'type' => 'integer' ),
								'blocked_count' => array( 'type' => 'integer' ),
								'warning_count' => array( 'type' => 'integer' ),
								'items'         => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary'       => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'total', 'items', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_bulk_publishing_checklist' ),
			),
			'magick-ai/get-internal-link-opportunity-report' => array(
				'label'            => __( 'Get Internal Link Opportunity Report', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a post-id based internal-link opportunity report with candidate targets, anchor suggestions, and placement hints.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'       => array( 'type' => 'integer', 'minimum' => 1 ),
						'focus_keyword' => array( 'type' => 'string' ),
						'max_targets'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 10, 'default' => 5 ),
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
								'source_post'    => array( 'type' => 'object', 'additionalProperties' => true ),
								'targets'        => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'placement_plan' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary'        => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'source_post', 'targets', 'placement_plan', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_internal_link_opportunity_report' ),
			),
			'magick-ai/get-site-operations-dashboard' => array(
				'label'            => __( 'Get Site Operations Dashboard', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a bounded read-only site operations dashboard for content status, inventory issues, taxonomy health, comments, and media signals.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'  => array( 'type' => 'string', 'default' => 'post' ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'stale_days' => array( 'type' => 'integer', 'minimum' => 30, 'maximum' => 3650, 'default' => 365 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_site_operations_dashboard' ),
			),
			'magick-ai/get-post-publish-risk-report' => array(
				'label'            => __( 'Get Post Publish Risk Report', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a read-only publish risk report for one post from checklist, SEO/GEO, revision, and internal-link signals.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'       => array( 'type' => 'integer', 'minimum' => 1 ),
						'target_status' => array( 'type' => 'string', 'enum' => array( 'publish', 'future', 'draft' ), 'default' => 'publish' ),
						'focus_keyword' => array( 'type' => 'string' ),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_post_publish_risk_report' ),
			),
			'magick-ai/get-article-publish-preflight-context' => array(
				'label'            => __( 'Get Article Publish Preflight Context', 'magick-ai-abilities' ),
				'description'      => __( 'Aggregates post context, publishing checklist, publish risk, workflow context, and calendar context for host-side publish preflight workflows without writing.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'         => array( 'type' => 'integer', 'minimum' => 1 ),
						'target_status'   => array( 'type' => 'string', 'enum' => array( 'publish', 'future', 'draft' ), 'default' => 'publish' ),
						'focus_keyword'   => array( 'type' => 'string' ),
						'window_days'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 365, 'default' => 30 ),
						'include_content' => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_article_publish_preflight_context' ),
			),
			'magick-ai/get-content-refresh-opportunities' => array(
				'label'            => __( 'Get Content Refresh Opportunities', 'magick-ai-abilities' ),
				'description'      => __( 'Scans a bounded content set and returns stale, thin, SEO-light, answer-light, and low-link refresh opportunities.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'      => array( 'type' => 'string', 'default' => 'post' ),
						'status'         => array( 'type' => 'string', 'default' => 'publish' ),
						'per_page'       => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'           => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'stale_days'     => array( 'type' => 'integer', 'minimum' => 30, 'maximum' => 3650, 'default' => 365 ),
						'min_word_count' => array( 'type' => 'integer', 'minimum' => 50, 'maximum' => 5000, 'default' => 500 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_content_refresh_opportunities' ),
			),
			'magick-ai/get-old-article-refresh-context' => array(
				'label'            => __( 'Get Old Article Refresh Context', 'magick-ai-abilities' ),
				'description'      => __( 'Aggregates refresh opportunities, SEO/GEO gaps, site style baseline, internal-link graph health, and optional selected-post link opportunities for host-side old article refresh workflows.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'      => array( 'type' => 'string', 'default' => 'post' ),
						'status'         => array( 'type' => 'string', 'default' => 'publish' ),
						'per_page'       => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'           => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'stale_days'     => array( 'type' => 'integer', 'minimum' => 30, 'maximum' => 3650, 'default' => 365 ),
						'topic_seed'     => array( 'type' => 'string' ),
						'post_id'        => array( 'type' => 'integer', 'minimum' => 1 ),
						'focus_keyword'  => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_old_article_refresh_context' ),
			),
			'magick-ai/get-internal-link-graph-health' => array(
				'label'            => __( 'Get Internal Link Graph Health', 'magick-ai-abilities' ),
				'description'      => __( 'Scans a bounded content set and summarizes outgoing links, incoming links, orphan posts, and candidate internal-link pairs.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'          => array( 'type' => 'string', 'default' => 'post' ),
						'status'             => array( 'type' => 'string', 'default' => 'publish' ),
						'per_page'           => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'               => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'min_outbound_links' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 20, 'default' => 1 ),
						'max_outbound_links' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_internal_link_graph_health' ),
			),
			'magick-ai/get-media-cleanup-opportunities' => array(
				'label'            => __( 'Get Media Cleanup Opportunities', 'magick-ai-abilities' ),
				'description'      => __( 'Scans a bounded media inventory and returns cleanup opportunities for metadata gaps, source gaps, and likely unused assets.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'upload_files',
				'required_scope'   => 'media.read',
				'required_scopes'  => array( 'media.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'mime_type' => array( 'type' => 'string' ),
						'per_page'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'      => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_media_cleanup_opportunities' ),
			),
			'magick-ai/get-taxonomy-consolidation-suggestions' => array(
				'label'            => __( 'Get Taxonomy Consolidation Suggestions', 'magick-ai-abilities' ),
				'description'      => __( 'Scans taxonomy terms and suggests read-only consolidation candidates for empty, duplicate, similar, and unused terms.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_categories',
				'required_scope'   => 'taxonomy.read',
				'required_scopes'  => array( 'taxonomy.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'taxonomy'   => array( 'type' => 'string', 'default' => 'post_tag' ),
						'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 100 ),
						'page'       => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_taxonomy_consolidation_suggestions' ),
			),
			'magick-ai/get-page-structure-health' => array(
				'label'            => __( 'Get Page Structure Health', 'magick-ai-abilities' ),
				'description'      => __( 'Scans one page or a bounded page set and reports heading, CTA, content depth, and block structure health.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-pages',
				'capability'       => 'edit_pages',
				'required_scope'   => 'page.read',
				'required_scopes'  => array( 'page.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'page_id'   => array( 'type' => 'integer', 'minimum' => 1 ),
						'status'    => array( 'type' => 'string', 'default' => 'publish' ),
						'max_pages' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_page_structure_health' ),
			),
			'magick-ai/get-seo-geo-gap-report' => array(
				'label'            => __( 'Get SEO GEO Gap Report', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a bounded SEO/GEO gap report from topic coverage, refresh opportunities, answer structure, and SEO metadata signals.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'  => array( 'type' => 'string', 'default' => 'post' ),
						'status'     => array( 'type' => 'string', 'default' => 'publish' ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'       => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'topic_seed' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_seo_geo_gap_report' ),
			),
			'magick-ai/get-site-style-baseline' => array(
				'label'            => __( 'Get Site Style Baseline', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a compact read-only site-wide or author-specific writing style baseline from recent posts.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'mode'      => array( 'type' => 'string', 'enum' => array( 'site_recent', 'author_recent' ), 'default' => 'site_recent' ),
						'author_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'limit'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 5, 'default' => 5 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_site_style_baseline' ),
			),
			'magick-ai/build-article-workflow-context' => array(
				'label'            => __( 'Build Article Workflow Context', 'magick-ai-abilities' ),
				'description'      => __( 'Builds one read-only context bundle for article refresh, publish, or new-article workflows without model routing.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'workflow'   => array( 'type' => 'string', 'enum' => array( 'new_article', 'refresh', 'publish' ), 'default' => 'new_article' ),
						'post_id'    => array( 'type' => 'integer', 'minimum' => 1 ),
						'topic_seed' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'build_article_workflow_context' ),
			),
			'magick-ai/get-publishing-calendar-context' => array(
				'label'            => __( 'Get Publishing Calendar Context', 'magick-ai-abilities' ),
				'description'      => __( 'Returns scheduled posts, draft backlog, pending review items, and near-term publishing cadence context.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'   => array( 'type' => 'string', 'default' => 'post' ),
						'window_days' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 365, 'default' => 30 ),
						'per_page'    => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_publishing_calendar_context' ),
			),
			'magick-ai/get-media-inventory-health' => array(
				'label'            => __( 'Get Media Inventory Health', 'magick-ai-abilities' ),
				'description'      => __( 'Scans a bounded media inventory and summarizes missing alt text, captions, descriptions, source metadata, and format attention.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'upload_files',
				'required_scope'   => 'media.read',
				'required_scopes'  => array( 'media.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'mime_type' => array( 'type' => 'string' ),
						'search'    => array( 'type' => 'string' ),
						'per_page'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'      => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'total'        => array( 'type' => 'integer' ),
								'page'         => array( 'type' => 'integer' ),
								'per_page'     => array( 'type' => 'integer' ),
								'health_score' => array( 'type' => 'integer' ),
								'issue_counts' => array( 'type' => 'object', 'additionalProperties' => true ),
								'items'        => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary'      => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'total', 'items', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_media_inventory_health' ),
			),
			'magick-ai/get-post-seo-geo-readiness' => array(
				'label'            => __( 'Get Post SEO GEO Readiness', 'magick-ai-abilities' ),
				'description'      => __( 'Returns one deterministic SEO/GEO readiness snapshot for a post, including metadata, content-depth, question, and media signals.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'       => array( 'type' => 'integer', 'minimum' => 1 ),
						'focus_keyword' => array( 'type' => 'string' ),
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
								'post'            => array( 'type' => 'object', 'additionalProperties' => true ),
								'readiness_score' => array( 'type' => 'integer' ),
								'status'          => array( 'type' => 'string' ),
								'checks'          => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'recommendations' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary'         => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'post', 'readiness_score', 'status', 'checks', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_post_seo_geo_readiness' ),
			),
			'magick-ai/get-site-topic-coverage-report' => array(
				'label'            => __( 'Get Site Topic Coverage Report', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a bounded site topic coverage summary from post titles, terms, and content snippets without model routing.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_type'  => array( 'type' => 'string', 'default' => 'post' ),
						'status'     => array( 'type' => 'string', 'default' => 'publish' ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'       => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'topic_seed' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'total'          => array( 'type' => 'integer' ),
								'topics'         => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'coverage_gaps'  => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'representative_posts' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary'        => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'total', 'topics', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_site_topic_coverage_report' ),
			),
			'magick-ai/get-taxonomy-inventory-health' => array(
				'label'            => __( 'Get Taxonomy Inventory Health', 'magick-ai-abilities' ),
				'description'      => __( 'Scans a bounded taxonomy term inventory and summarizes empty, unused, duplicate, and hierarchy attention signals.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'manage_categories',
				'required_scope'   => 'taxonomy.read',
				'required_scopes'  => array( 'taxonomy.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'taxonomy'   => array( 'type' => 'string', 'default' => 'category' ),
						'hide_empty' => array( 'type' => 'boolean', 'default' => false ),
						'per_page'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'page'       => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
					),
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'data'    => array(
							'type'                 => 'object',
							'properties'           => array(
								'taxonomy'     => array( 'type' => 'string' ),
								'total'        => array( 'type' => 'integer' ),
								'page'         => array( 'type' => 'integer' ),
								'per_page'     => array( 'type' => 'integer' ),
								'health_score' => array( 'type' => 'integer' ),
								'issue_counts' => array( 'type' => 'object', 'additionalProperties' => true ),
								'items'        => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary'      => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'taxonomy', 'total', 'items', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_taxonomy_inventory_health' ),
			),
			'magick-ai/get-revision-change-risk-report' => array(
				'label'            => __( 'Get Revision Change Risk Report', 'magick-ai-abilities' ),
				'description'      => __( 'Reads recent revisions for one post and summarizes title, content length, block count, and recency change-risk signals.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'       => array( 'type' => 'integer', 'minimum' => 1 ),
						'max_revisions' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 10 ),
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
								'post'             => array( 'type' => 'object', 'additionalProperties' => true ),
								'revision_count'   => array( 'type' => 'integer' ),
								'risk_level'       => array( 'type' => 'string' ),
								'risk_flags'       => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
								'latest_revision'  => array( 'type' => 'object', 'additionalProperties' => true ),
								'change_summary'   => array( 'type' => 'object', 'additionalProperties' => true ),
								'recent_revisions' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
								'summary'          => array( 'type' => 'object', 'additionalProperties' => true ),
							),
							'required'             => array( 'post', 'revision_count', 'risk_level', 'risk_flags', 'summary' ),
							'additionalProperties' => false,
						),
						'meta'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'data' ),
				),
				'execute_callback' => array( $this, 'get_revision_change_risk_report' ),
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

		return array_merge(
			array_slice( $definitions, 0, 1, true ),
			WordPress_Diagnostics_Definitions::definitions( $this ),
			array_slice( $definitions, 1, null, true )
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
	 * Normalizes post metadata plan output for workflow handoffs.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function resolve_post_metadata_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_metadata_plan = is_array( $input['post_metadata_plan'] ?? null ) ? $input['post_metadata_plan'] : array();
		$taxonomy_plan = is_array( $input['taxonomy_plan'] ?? null ) ? $input['taxonomy_plan'] : array();

		$excerpt_mode = $this->normalize_metadata_plan_mode( $post_metadata_plan['excerpt_mode'] ?? 'auto' );
		$slug_mode = $this->normalize_metadata_plan_mode( $post_metadata_plan['slug_mode'] ?? 'auto' );

		$generated_excerpt = $this->sanitize_metadata_text(
			(string) (
				$input['generated_excerpt']
				?? $input['generated_excerpt_text']
				?? ''
			)
		);
		$generated_slug = $this->sanitize_metadata_slug( (string) ( $input['generated_slug'] ?? '' ) );

		$excerpt = $this->resolve_optional_metadata_text(
			$post_metadata_plan['excerpt'] ?? '',
			$excerpt_mode,
			$generated_excerpt
		);
		$slug = $this->resolve_optional_metadata_text(
			$post_metadata_plan['slug'] ?? '',
			$slug_mode,
			$generated_slug
		);
		$slug = '' !== $slug ? $this->sanitize_metadata_slug( $slug ) : '';

		return array(
			'success' => true,
			'data'    => array(
				'excerpt'    => $excerpt,
				'slug'       => $slug,
				'categories' => $this->pick_metadata_plan_terms( $post_metadata_plan, $taxonomy_plan, 'categories' ),
				'tags'       => $this->pick_metadata_plan_terms( $post_metadata_plan, $taxonomy_plan, 'tags' ),
				'publish_at' => sanitize_text_field( (string) ( $post_metadata_plan['publish_at'] ?? '' ) ),
				'author_id'  => $this->absint_value( $post_metadata_plan['author_id'] ?? 0 ),
				'template'   => sanitize_text_field( (string) ( $post_metadata_plan['template'] ?? '' ) ),
				'format'     => sanitize_key( (string) ( $post_metadata_plan['format'] ?? '' ) ),
			),
			'meta'    => array(
				'source'         => 'local_post_metadata_plan_resolution',
				'execution_mode' => 'deterministic',
			),
			'message' => __( 'Post metadata plan resolved.', 'magick-ai-abilities' ),
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
	 * Resolves local published posts that can be used as internal-link targets.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function resolve_internal_link_targets( $input ) {
		$input = is_array( $input ) ? $input : array();
		$current_post_id = $this->absint_value( $input['current_post_id'] ?? 0 );
		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$content = $this->normalize_plain_text( $input['content'] ?? '' );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$keywords = is_array( $input['keywords'] ?? null ) ? $input['keywords'] : array();
		$terms = $this->collect_focus_terms( $title, $focus_keyword, $keywords );
		$max_targets = max( 1, min( 6, $this->absint_value( $input['max_targets'] ?? 3 ) ) );

		$targets = array();
		foreach ( array_slice( $terms, 0, 4 ) as $term ) {
			$query = new \WP_Query(
				array(
					'post_type'      => 'any',
					'post_status'    => array( 'publish' ),
					'posts_per_page' => $max_targets,
					's'              => $term,
					'post__not_in'   => $current_post_id > 0 ? array( $current_post_id ) : array(),
					'fields'         => 'ids',
				)
			);
			foreach ( (array) $query->posts as $post_id ) {
				$post_id = $this->absint_value( $post_id );
				if ( $post_id <= 0 ) {
					continue;
				}
				$targets[ $post_id ] = array(
					'post_id'         => $post_id,
					'title'           => sanitize_text_field( (string) get_the_title( $post_id ) ),
					'url'             => esc_url_raw( (string) get_permalink( $post_id ) ),
					'anchor_text'     => $term,
					'reason'          => sprintf( __( 'Existing site content related to "%s" can be used as supplemental reading.', 'magick-ai-abilities' ), $term ),
					'relevance_score' => 0.72,
				);
				if ( count( $targets ) >= $max_targets ) {
					break 2;
				}
			}
		}

		$placement_plan = array();
		foreach ( array_values( $targets ) as $target ) {
			$placement_plan[] = array(
				'target_post_id' => $this->absint_value( $target['post_id'] ?? 0 ),
				'target_url'     => esc_url_raw( (string) ( $target['url'] ?? '' ) ),
				'anchor_text'    => sanitize_text_field( (string) ( $target['anchor_text'] ?? '' ) ),
				'placement_hint' => __( 'Insert one internal link after the first paragraph that explains the core concept.', 'magick-ai-abilities' ),
				'reason'         => $this->sanitize_metadata_text( (string) ( $target['reason'] ?? '' ) ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'targets'        => array_values( $targets ),
				'placement_plan' => $placement_plan,
				'no_link_zones'  => '' !== $content ? array( __( 'Keep titles, opening summaries, and CTA paragraphs link-free to avoid diluting the main answer.', 'magick-ai-abilities' ) ) : array(),
				'summary'        => array(
					'candidate_count' => count( $targets ),
					'placement_count' => count( $placement_plan ),
					'focus_terms'     => $terms,
				),
			),
			'meta'    => array(
				'source'         => 'local_internal_link_inventory',
				'execution_mode' => 'deterministic',
			),
			'message' => __( 'Internal link targets resolved.', 'magick-ai-abilities' ),
		);
	}

	/**
	 * Builds append-ready Gutenberg image blocks from uploaded media rows.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_inline_image_blocks( $input ) {
		$input = is_array( $input ) ? $input : array();
		$upload_rows = is_array( $input['uploaded_inline_media'] ?? null ) ? $input['uploaded_inline_media'] : array();
		$generated_upload_rows = is_array( $input['generated_inline_media'] ?? null ) ? $input['generated_inline_media'] : array();
		$plan_rows = is_array( $input['inline_plan'] ?? null ) ? $input['inline_plan'] : array();
		$blocks = array();

		$max_rows = max( count( $upload_rows ), count( $generated_upload_rows ), count( $plan_rows ) );
		for ( $index = 0; $index < $max_rows; ++$index ) {
			$upload_row = is_array( $upload_rows[ $index ] ?? null ) ? $upload_rows[ $index ] : array();
			if ( $this->absint_value( $upload_row['attachment_id'] ?? 0 ) <= 0 && is_array( $generated_upload_rows[ $index ] ?? null ) ) {
				$upload_row = $generated_upload_rows[ $index ];
			}
			$plan_row = is_array( $plan_rows[ $index ] ?? null ) ? $plan_rows[ $index ] : array();
			$attachment_id = $this->absint_value( $upload_row['attachment_id'] ?? 0 );
			$image_url = $this->esc_url_value( (string) ( $upload_row['url'] ?? '' ) );
			if ( $attachment_id <= 0 || '' === $image_url ) {
				continue;
			}

			$alt = sanitize_text_field( (string) ( $plan_row['alt'] ?? $upload_row['alt'] ?? '' ) );
			$caption = $this->sanitize_metadata_text( (string) ( $plan_row['caption'] ?? $upload_row['caption'] ?? '' ) );
			$placement_key = $this->sanitize_html_class_value( (string) ( $plan_row['placement_key'] ?? 'inline_' . max( 1, (int) $index + 1 ) ) );
			$img_tag = '<img src="' . $this->esc_attr_value( $image_url ) . '" alt="' . $this->esc_attr_value( $alt ) . '" class="wp-image-' . $attachment_id . '" />';
			$figcaption = '' !== $caption ? '<figcaption>' . $this->esc_html_value( $caption ) . '</figcaption>' : '';

			$blocks[] = array(
				'blockName'   => 'core/image',
				'attrs'       => array(
					'id'              => $attachment_id,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
					'alt'             => $alt,
					'className'       => 'magick-ai-inline-image ' . $placement_key,
				),
				'innerHTML'   => '<figure class="wp-block-image size-large magick-ai-inline-image ' . $this->esc_attr_value( $placement_key ) . '">' . $img_tag . $figcaption . '</figure>',
				'innerBlocks' => array(),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'blocks'  => $blocks,
				'summary' => array(
					'count' => count( $blocks ),
					'mode'  => 'append',
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Builds canonical media SEO enrichment asset rows from article media workflow outputs.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_media_seo_assets( $input ) {
		$input = is_array( $input ) ? $input : array();
		$article = is_array( $input['article'] ?? null ) ? $input['article'] : array();
		$resolved = is_array( $input['resolved_image_source'] ?? null ) ? $input['resolved_image_source'] : array();
		$featured = is_array( $resolved['featured'] ?? null ) ? $resolved['featured'] : array();
		$inline_plan = is_array( $resolved['inline'] ?? null ) ? $resolved['inline'] : array();
		$featured_upload = is_array( $input['featured_upload'] ?? null ) ? $input['featured_upload'] : array();
		$generated_featured_upload = is_array( $input['generated_featured_upload'] ?? null ) ? $input['generated_featured_upload'] : array();
		$inline_uploads = is_array( $input['inline_uploads'] ?? null ) ? $input['inline_uploads'] : array();
		$generated_inline_uploads = is_array( $input['generated_inline_uploads'] ?? null ) ? $input['generated_inline_uploads'] : array();
		$vision_fallback_mode = sanitize_key( (string) ( $input['vision_fallback_mode'] ?? 'auto' ) );
		if ( ! in_array( $vision_fallback_mode, array( 'off', 'auto' ), true ) ) {
			$vision_fallback_mode = 'auto';
		}

		$items = array();
		$featured_upload_row = $this->absint_value( $featured_upload['attachment_id'] ?? 0 ) > 0 ? $featured_upload : $generated_featured_upload;
		if ( $this->absint_value( $featured_upload_row['attachment_id'] ?? 0 ) > 0 ) {
			$items[] = $this->build_media_seo_asset_row( $featured, $featured_upload_row, 'featured', 'auto' === $vision_fallback_mode );
		}

		$max_inline_uploads = max( count( $inline_uploads ), count( $generated_inline_uploads ) );
		for ( $index = 0; $index < $max_inline_uploads; ++$index ) {
			$upload_row = is_array( $inline_uploads[ $index ] ?? null ) ? $inline_uploads[ $index ] : array();
			if ( $this->absint_value( $upload_row['attachment_id'] ?? 0 ) <= 0 && is_array( $generated_inline_uploads[ $index ] ?? null ) ) {
				$upload_row = $generated_inline_uploads[ $index ];
			}
			if ( $this->absint_value( $upload_row['attachment_id'] ?? 0 ) <= 0 ) {
				continue;
			}
			$descriptor = is_array( $inline_plan[ $index ] ?? null ) ? $inline_plan[ $index ] : array();
			$items[] = $this->build_media_seo_asset_row( $descriptor, $upload_row, 'inline', 'auto' === $vision_fallback_mode );
		}

		return array(
			'success' => true,
			'data'    => array(
				'items'   => $items,
				'summary' => array(
					'asset_count'            => count( $items ),
					'featured_attachment_id' => $this->absint_value( $featured_upload_row['attachment_id'] ?? 0 ),
					'inline_attachment_count' => max( 0, count( $items ) - ( $this->absint_value( $featured_upload_row['attachment_id'] ?? 0 ) > 0 ? 1 : 0 ) ),
					'article_title'          => sanitize_text_field( (string) ( $article['title'] ?? '' ) ),
					'vision_fallback_mode'   => $vision_fallback_mode,
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'deterministic',
			),
		);
	}

	/**
	 * Builds local GEO / AI visibility analysis.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function geo_analyze( $input ) {
		$input = is_array( $input ) ? $input : array();
		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$content = $this->normalize_analysis_plain_text( $input['content'] ?? '' );
		$excerpt = sanitize_textarea_field( (string) ( $input['excerpt'] ?? '' ) );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$entities = is_array( $input['entities'] ?? null ) ? $input['entities'] : array();
		$entities = $this->collect_article_focus_terms( $title, $focus_keyword, $entities );
		$questions = $this->collect_article_question_candidates( $content, $title, $focus_keyword );
		$issues = array();
		$recommendations = array();
		$evidence = array();

		if ( $this->strlen_value( $content ) < 280 ) {
			$issues[] = array(
				'id'       => 'thin_answer_surface',
				'severity' => 'high',
				'title'    => '正文偏短，AI 摘要可见度偏弱。',
				'detail'   => '建议补充直接回答型段落，覆盖"是什么 / 为什么 / 怎么做"。',
			);
		}

		if ( '' === $excerpt ) {
			$issues[] = array(
				'id'       => 'excerpt_missing',
				'severity' => 'medium',
				'title'    => '缺少可直接复用的摘要。',
				'detail'   => '建议补一个 1 到 2 句的直答摘要，方便 SERP 与 AI answer box 引用。',
			);
		}

		if ( empty( $questions ) ) {
			$issues[] = array(
				'id'       => 'faq_gap',
				'severity' => 'medium',
				'title'    => '正文里缺少显式问答结构。',
				'detail'   => '建议新增 FAQ 或小标题问答块，提高 GEO / AI answerability。',
			);
		}

		foreach ( $questions as $question ) {
			$recommendations[] = array(
				'type'     => 'faq_candidate',
				'priority' => 'medium',
				'title'    => sanitize_text_field( (string) ( $question['question'] ?? '' ) ),
				'detail'   => sanitize_textarea_field( (string) ( $question['answer_hint'] ?? '' ) ),
			);
		}

		foreach ( array_slice( $entities, 0, 5 ) as $entity ) {
			$evidence[] = array(
				'type'    => 'entity',
				'label'   => $entity,
				'support' => sprintf( '标题/焦点词已覆盖"%s"。', $entity ),
			);
		}

		$score = max( 0, min( 100, 84 - count( $issues ) * 12 + count( $questions ) * 4 ) );
		return $this->build_analysis_success_response(
			array(
				'score'               => $score,
				'ai_visibility_score' => $score,
				'entities'            => $entities,
				'issues'              => $issues,
				'recommendations'     => $recommendations,
				'evidence'            => $evidence,
				'answer_blocks'       => $questions,
				'summary'             => array(
					'entity_count'        => count( $entities ),
					'faq_candidate_count' => count( $questions ),
					'high_priority_count' => count(
						array_filter(
							$issues,
							static function ( array $issue ) {
								return 'high' === sanitize_key( (string) ( $issue['severity'] ?? '' ) );
							}
						)
					),
				),
			),
			array(
				'source'         => 'local_geo_analysis',
				'execution_mode' => 'deterministic',
			),
			'GEO analysis completed.'
		);
	}

	/**
	 * Builds media metadata optimization suggestions.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function optimize_media_metadata( $input ) {
		$input = is_array( $input ) ? $input : array();
		$article_title = sanitize_text_field( (string) ( $input['article_title'] ?? '' ) );
		$article_excerpt = sanitize_textarea_field( (string) ( $input['article_excerpt'] ?? '' ) );
		$article_content = (string) ( $input['article_content'] ?? '' );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$vision_fallback_mode = sanitize_key( (string) ( $input['vision_fallback_mode'] ?? 'auto' ) );
		if ( ! in_array( $vision_fallback_mode, array( 'off', 'auto' ), true ) ) {
			$vision_fallback_mode = 'auto';
		}
		$assets = $this->collect_media_assets_for_optimization(
			is_array( $input['media_assets'] ?? null ) ? $input['media_assets'] : array(),
			$article_content
		);

		$recommendations = array();
		$missing_alt_count = 0;
		$format_attention_count = 0;
		$vision_fallback_count = 0;
		foreach ( $assets as $index => $asset ) {
			$asset = is_array( $asset ) ? $asset : array();
			$alt = sanitize_text_field( (string) ( $asset['alt'] ?? '' ) );
			$title = sanitize_text_field( (string) ( $asset['title'] ?? '' ) );
			$caption = sanitize_textarea_field( (string) ( $asset['caption'] ?? '' ) );
			$description = sanitize_textarea_field( (string) ( $asset['description'] ?? '' ) );
			$mime_type = sanitize_text_field( (string) ( $asset['mime_type'] ?? '' ) );
			$section_heading = sanitize_text_field( (string) ( $asset['section_heading'] ?? '' ) );
			$section_summary = sanitize_textarea_field( (string) ( $asset['section_summary'] ?? '' ) );
			$generated_prompt = sanitize_textarea_field( (string) ( $asset['generated_prompt'] ?? '' ) );
			$image_profile = sanitize_key( (string) ( $asset['image_profile'] ?? '' ) );
			$provider_title = sanitize_text_field( (string) ( $asset['provider_title'] ?? '' ) );
			$provider_description = sanitize_textarea_field( (string) ( $asset['provider_description'] ?? $asset['alt_description'] ?? '' ) );
			$provider_hint = sanitize_key( (string) ( $asset['provider_hint'] ?? '' ) );
			$source_page_url = $this->esc_url_value( (string) ( $asset['source_page_url'] ?? '' ) );
			$photographer_name = sanitize_text_field( (string) ( $asset['photographer_name'] ?? '' ) );
			$attribution_text = sanitize_textarea_field( (string) ( $asset['attribution_text'] ?? '' ) );
			$license = sanitize_text_field( (string) ( $asset['license'] ?? '' ) );
			$copyright_notice = sanitize_textarea_field( (string) ( $asset['copyright_notice'] ?? '' ) );
			$file_name = $this->sanitize_file_name_value( (string) ( $asset['file_name'] ?? '' ) );
			$needs_format_attention = '' !== $mime_type && false === strpos( $mime_type, 'webp' ) && false === strpos( $mime_type, 'avif' );

			if ( '' === $alt ) {
				++$missing_alt_count;
			}
			if ( $needs_format_attention ) {
				++$format_attention_count;
			}

			$image_origin = $this->infer_media_image_origin( $asset );
			$strategy = 'manual_context';
			if ( 'ai_generated' === $image_origin ) {
				$strategy = 'ai_context';
			} elseif ( 'public_free' === $image_origin ) {
				$strategy = 'provider_metadata';
			}

			$title_seed = '';
			$caption_seed = '';
			$description_seed = '';
			$alt_context_parts = array();
			$metadata_confidence = 'low';
			if ( 'ai_generated' === $image_origin ) {
				$title_seed = '' !== $section_heading ? $section_heading : ( '' !== $article_title ? $article_title : $provider_title );
				$caption_seed = '' !== $section_summary ? $section_summary : $article_excerpt;
				$description_seed = '' !== $generated_prompt ? $generated_prompt : $caption_seed;
				$alt_context_parts = array_filter( array( $title_seed, $caption_seed, '' !== $image_profile ? '图像档位：' . $image_profile : '', '' !== $generated_prompt ? '生成提示：' . $generated_prompt : '' ) );
				$metadata_confidence = '' !== $generated_prompt ? 'high' : 'medium';
			} elseif ( 'public_free' === $image_origin ) {
				$title_seed = '' !== $provider_title ? $provider_title : ( '' !== $section_heading ? $section_heading : $article_title );
				$caption_seed = '' !== $section_summary ? $section_summary : $article_excerpt;
				$description_seed = '' !== $provider_description ? $provider_description : $caption_seed;
				$alt_context_parts = array_filter( array( $title_seed, $description_seed, '' !== $photographer_name ? '摄影：' . $photographer_name : '', '' !== $attribution_text ? '归因：' . $attribution_text : '' ) );
				$metadata_confidence = ( '' !== $provider_title || '' !== $provider_description || '' !== $photographer_name ) ? 'high' : 'medium';
			} else {
				$title_seed = '' !== $title ? $title : ( '' !== $section_heading ? $section_heading : $article_title );
				$caption_seed = '' !== $section_summary ? $section_summary : $article_excerpt;
				$description_seed = '' !== $description ? $description : $caption_seed;
				$alt_context_parts = array_filter( array( $title_seed, $description_seed, '' !== $file_name ? '文件：' . $file_name : '' ) );
				$manual_signal_count = 0;
				if ( '' !== $title || '' !== $caption || '' !== $description ) {
					++$manual_signal_count;
				}
				if ( '' !== $section_heading || '' !== $section_summary ) {
					++$manual_signal_count;
				}
				if ( '' !== $file_name ) {
					++$manual_signal_count;
				}
				$metadata_confidence = $manual_signal_count >= 2 ? 'medium' : 'low';
			}

			$vision_fallback_recommended = 'off' !== $vision_fallback_mode && ! empty( $asset['vision_fallback_allowed'] ) && 'low' === $metadata_confidence;
			if ( $vision_fallback_recommended ) {
				++$vision_fallback_count;
			}
			$license = $this->resolve_media_license_label( $asset, $image_origin );
			$disclosure_readiness = $this->resolve_media_disclosure_readiness( $image_origin, $metadata_confidence, $license, $source_page_url, $attribution_text, $generated_prompt );
			$recommended_alt = '' !== $alt
				? $alt
				: $this->trim_media_seo_text( trim( implode( ' - ', array_filter( array( $title_seed, '' !== $focus_keyword ? $focus_keyword : '', '' !== $section_heading && $section_heading !== $title_seed ? $section_heading : '' ) ) ) ), 140 );
			$recommended_title = '' !== $title ? $title : $this->trim_media_seo_text( $title_seed, 80 );
			$recommended_caption = '' !== $caption ? $caption : $this->trim_media_seo_text( $caption_seed, 160 );
			$recommended_description = '' !== $description ? $description : $this->trim_media_seo_text( $description_seed, 220 );

			if ( 'public_free' === $image_origin ) {
				$attribution_appendix = implode( ' ', array_filter( array( '' !== $attribution_text ? '归因：' . $attribution_text : '', '' !== $source_page_url ? '来源页：' . $source_page_url : '', '' !== $copyright_notice ? $copyright_notice : '' ) ) );
				if ( '' !== $attribution_appendix && false === strpos( $recommended_description, $attribution_appendix ) ) {
					$recommended_description = $this->trim_media_seo_text( trim( $recommended_description . ' ' . $attribution_appendix ), 280 );
				}
			}

			$recommendations[] = array(
				'attachment_id'                => $this->absint_value( $asset['attachment_id'] ?? 0 ),
				'role'                         => sanitize_key( (string) ( $asset['role'] ?? ( 0 === $index ? 'featured' : 'inline' ) ) ),
				'url'                          => $this->esc_url_value( (string) ( $asset['url'] ?? '' ) ),
				'image_origin'                 => $image_origin,
				'strategy'                     => $strategy,
				'metadata_confidence'          => $metadata_confidence,
				'vision_fallback_recommended'  => $vision_fallback_recommended,
				'disclosure_readiness'         => $disclosure_readiness,
				'alt_context'                  => $this->trim_media_seo_text( implode( '；', $alt_context_parts ), 280 ),
				'provider'                     => array(
					'hint'             => $provider_hint,
					'provider_title'   => $provider_title,
					'source_page_url'  => $source_page_url,
					'photographer_name' => $photographer_name,
					'attribution_text' => $attribution_text,
					'license'          => $license,
					'copyright_notice' => $copyright_notice,
				),
				'vision_fallback_target'       => array(),
				'issues'                       => array_values( array_filter( array( '' === $alt ? '缺少 alt 文本。' : '', $needs_format_attention ? '仍可评估是否转为 WebP/AVIF。' : '', in_array( $disclosure_readiness, array( 'needs_review', 'disclosure_recommended' ), true ) ? '建议补充来源、归因或披露说明。' : '', $vision_fallback_recommended ? '现有上下文较弱，后续可考虑追加视觉识别补强。' : '' ) ) ),
				'suggestions'                  => array(
					'title'       => $recommended_title,
					'alt'         => $recommended_alt,
					'caption'     => $recommended_caption,
					'description' => $recommended_description,
				),
				'attribution_persisted'        => 'public_free' === $image_origin && '' !== $attribution_text,
				'format_plan'                  => array(
					'should_convert'     => $needs_format_attention,
					'recommended_format' => $needs_format_attention ? 'webp' : sanitize_key( (string) preg_replace( '#^.*/#', '', $mime_type ) ),
					'reason'             => $needs_format_attention ? '当前资源仍是传统格式，可评估更轻量编码。' : '当前格式可继续沿用。',
				),
			);
		}

		return $this->build_analysis_success_response(
			array(
				'assets'  => $recommendations,
				'summary' => array(
					'asset_count'                  => count( $recommendations ),
					'missing_alt_count'            => $missing_alt_count,
					'format_attention_count'       => $format_attention_count,
					'vision_fallback_count'        => $vision_fallback_count,
					'vision_fallback_mode'         => $vision_fallback_mode,
					'vision_fallback_target_available' => false,
					'vision_fallback_target'       => array(),
					'disclosure_ready_count'       => count( array_filter( $recommendations, static function ( array $asset ) { return in_array( sanitize_key( (string) ( $asset['disclosure_readiness'] ?? '' ) ), array( 'ready_with_attribution', 'ready_with_source_note' ), true ); } ) ),
					'needs_review_count'           => count( array_filter( $recommendations, static function ( array $asset ) { return 'needs_review' === sanitize_key( (string) ( $asset['disclosure_readiness'] ?? '' ) ); } ) ),
					'origin_breakdown'             => array(
						'ai_generated'  => count( array_filter( $recommendations, static function ( array $asset ) { return 'ai_generated' === sanitize_key( (string) ( $asset['image_origin'] ?? '' ) ); } ) ),
						'public_free'   => count( array_filter( $recommendations, static function ( array $asset ) { return 'public_free' === sanitize_key( (string) ( $asset['image_origin'] ?? '' ) ); } ) ),
						'manual_upload' => count( array_filter( $recommendations, static function ( array $asset ) { return 'manual_upload' === sanitize_key( (string) ( $asset['image_origin'] ?? '' ) ); } ) ),
					),
					'attribution_persisted_count'  => count( array_filter( $recommendations, static function ( array $asset ) { return ! empty( $asset['attribution_persisted'] ); } ) ),
				),
			),
			array(
				'source'         => 'local_media_metadata_optimizer',
				'execution_mode' => 'deterministic',
			),
			'Media metadata optimization completed.'
		);
	}

	/**
	 * Positions inline image blocks after matching heading anchors or paragraph indexes.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function position_inline_image_blocks( $input ) {
		$input = is_array( $input ) ? $input : array();
		$existing_blocks = is_array( $input['existing_blocks'] ?? null ) ? array_values( $input['existing_blocks'] ) : array();
		$inline_blocks = is_array( $input['inline_blocks'] ?? null ) ? array_values( $input['inline_blocks'] ) : array();
		$inline_plan = is_array( $input['inline_plan'] ?? null ) ? array_values( $input['inline_plan'] ) : array();
		if ( empty( $inline_blocks ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'blocks'  => $existing_blocks,
					'summary' => array(
						'count'            => 0,
						'positioned_count' => 0,
						'appended_count'   => 0,
						'strategy'         => 'after_paragraph_or_heading',
					),
				),
				'meta'    => array(
					'contract_version' => 'v1',
					'execution_mode'   => 'projection_only',
				),
			);
		}

		$placement_keys = array();
		foreach ( $inline_plan as $plan_row ) {
			$plan_row = is_array( $plan_row ) ? $plan_row : array();
			$placement_key = $this->sanitize_html_class_value( (string) ( $plan_row['placement_key'] ?? '' ) );
			if ( '' !== $placement_key ) {
				$placement_keys[] = $placement_key;
			}
		}
		$existing_blocks = $this->filter_existing_inline_image_blocks( $existing_blocks, $placement_keys );

		$insertions_by_index = array();
		$append_blocks = array();
		$positioned_count = 0;
		$appended_count = 0;
		$matched_targets = array();
		foreach ( $inline_blocks as $index => $inline_block ) {
			$inline_block = is_array( $inline_block ) ? $inline_block : array();
			if ( empty( $inline_block ) ) {
				continue;
			}
			$plan_row = is_array( $inline_plan[ $index ] ?? null ) ? $inline_plan[ $index ] : array();
			$matched = false;
			$current_paragraph_index = 0;
			foreach ( $existing_blocks as $block_index => $existing_block ) {
				$existing_block = is_array( $existing_block ) ? $existing_block : array();
				if ( empty( $existing_block ) ) {
					continue;
				}
				$block_name = sanitize_text_field( (string) ( $existing_block['blockName'] ?? '' ) );
				$is_paragraph = in_array( $block_name, array( 'core/paragraph', 'core/freeform' ), true );
				if (
					$this->block_matches_inline_paragraph_target( $existing_block, $plan_row, $current_paragraph_index )
					|| $this->block_matches_inline_target( $existing_block, $plan_row )
				) {
					if ( ! isset( $insertions_by_index[ $block_index ] ) || ! is_array( $insertions_by_index[ $block_index ] ) ) {
						$insertions_by_index[ $block_index ] = array();
					}
					$insertions_by_index[ $block_index ][] = $inline_block;
					$matched = true;
					++$positioned_count;
					$matched_targets[] = array(
						'placement_key'    => $this->sanitize_html_class_value( (string) ( $plan_row['placement_key'] ?? '' ) ),
						'placement'        => sanitize_key( (string) ( $plan_row['placement'] ?? '' ) ),
						'paragraph_index'  => array_key_exists( 'paragraph_index', $plan_row ) ? $this->absint_value( $plan_row['paragraph_index'] ) : null,
						'section_anchor'   => $this->sanitize_metadata_slug( (string) ( $plan_row['section_anchor'] ?? '' ) ),
						'section_heading'  => sanitize_text_field( (string) ( $plan_row['section_heading'] ?? '' ) ),
					);
					break;
				}
				if ( $is_paragraph ) {
					++$current_paragraph_index;
				}
			}

			if ( ! $matched ) {
				$append_blocks[] = $inline_block;
				++$appended_count;
			}
		}

		$composed_blocks = array();
		foreach ( $existing_blocks as $block_index => $existing_block ) {
			$composed_blocks[] = $existing_block;
			if ( ! empty( $insertions_by_index[ $block_index ] ) && is_array( $insertions_by_index[ $block_index ] ) ) {
				foreach ( $insertions_by_index[ $block_index ] as $inserted_block ) {
					$composed_blocks[] = $inserted_block;
				}
			}
		}
		foreach ( $append_blocks as $append_block ) {
			$composed_blocks[] = $append_block;
		}

		return array(
			'success' => true,
			'data'    => array(
				'blocks'  => array_values( $composed_blocks ),
				'summary' => array(
					'count'            => count( $inline_blocks ),
					'positioned_count' => $positioned_count,
					'appended_count'   => $appended_count,
					'strategy'         => 'after_paragraph_or_heading',
					'matched_targets'  => array_values( $matched_targets ),
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Resolves one article publication handoff after review.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function resolve_article_publication_decision( $input ) {
		$input = is_array( $input ) ? $input : array();
		$policy_defaults = function_exists( 'magick_ai_get_article_workflow_policy_defaults' )
			? magick_ai_get_article_workflow_policy_defaults()
			: array();
		$requested_publish_mode = sanitize_key( (string) ( $input['publish_mode'] ?? ( $policy_defaults['publish_mode'] ?? 'draft' ) ) );
		if ( ! in_array( $requested_publish_mode, array( 'draft', 'review', 'schedule', 'publish' ), true ) ) {
			$requested_publish_mode = sanitize_key( (string) ( $policy_defaults['publish_mode'] ?? 'draft' ) );
			if ( ! in_array( $requested_publish_mode, array( 'draft', 'review', 'schedule', 'publish' ), true ) ) {
				$requested_publish_mode = 'draft';
			}
		}

		$review = is_array( $input['review'] ?? null ) ? $input['review'] : array();
		$duplicate_guard = is_array( $input['duplicate_guard'] ?? null ) ? $input['duplicate_guard'] : array();
		$effective_publish_mode = $requested_publish_mode;
		$publish_blocked = false;
		$gate_reason = '';
		$user_message = '';

		if ( in_array( $requested_publish_mode, array( 'schedule', 'publish' ), true ) && ! empty( $review['needs_human_review'] ) ) {
			$effective_publish_mode = 'review';
			$publish_blocked = true;
			if ( 'high' === sanitize_key( (string) ( $review['template_risk_level'] ?? '' ) ) ) {
				$gate_reason = 'template_style_requires_handoff';
				$user_message = __( 'Template style risk is high; review and polish the article before scheduling or publishing.', 'magick-ai-abilities' );
			} else {
				$gate_reason = 'quality_review_requires_handoff';
				$user_message = __( 'Quality review requires human review before scheduling or publishing.', 'magick-ai-abilities' );
			}
		} elseif ( in_array( $requested_publish_mode, array( 'schedule', 'publish' ), true ) && ! empty( $duplicate_guard['skip_recommended'] ) ) {
			$effective_publish_mode = 'review';
			$publish_blocked = true;
			$gate_reason = 'duplicate_production_candidate';
			$user_message = __( 'A duplicate production candidate was detected; review before scheduling or publishing.', 'magick-ai-abilities' );
		}

		return array(
			'success' => true,
			'data'    => array(
				'requested_publish_mode' => $requested_publish_mode,
				'effective_publish_mode' => $effective_publish_mode,
				'publish_blocked'        => $publish_blocked,
				'gate_reason'            => $gate_reason,
				'user_message'           => $user_message,
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'deterministic',
			),
		);
	}

	/**
	 * Builds one stable fingerprint for lightweight article production dedupe.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_production_fingerprint( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'success' => true,
			'data'    => array(
				'production_fingerprint' => $this->build_article_production_fingerprint_value( $input ),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Checks whether one lightweight production fingerprint already exists on a post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function check_article_production_duplicate( $input ) {
		$input = is_array( $input ) ? $input : array();
		$production_fingerprint = sanitize_text_field( (string) ( $input['production_fingerprint'] ?? '' ) );
		$write_guard_mode = sanitize_key( (string) ( $input['write_guard_mode'] ?? 'preserve_manual_edits' ) );
		$duplicate = array();

		if ( '' !== $production_fingerprint && function_exists( 'get_posts' ) ) {
			$posts = get_posts(
				array(
					'post_type'        => 'post',
					'post_status'      => array( 'draft', 'pending', 'future', 'publish', 'private' ),
					'posts_per_page'   => 1,
					'orderby'          => 'ID',
					'order'            => 'DESC',
					'meta_key'         => '_mai_article_production_fingerprint',
					'meta_value'       => $production_fingerprint,
					'fields'           => 'ids',
					'suppress_filters' => true,
				)
			);
			$post_id = $this->absint_value( is_array( $posts ) ? ( $posts[0] ?? 0 ) : 0 );
			if ( $post_id > 0 ) {
				$duplicate = array(
					'post_id'      => $post_id,
					'title'        => function_exists( 'get_the_title' ) ? sanitize_text_field( (string) get_the_title( $post_id ) ) : '',
					'status'       => function_exists( 'get_post_status' ) ? sanitize_key( (string) get_post_status( $post_id ) ) : '',
					'edit_link'    => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
					'preview_link' => function_exists( 'get_preview_post_link' ) ? $this->esc_url_value( (string) get_preview_post_link( $post_id ) ) : '',
					'public_link'  => function_exists( 'get_permalink' ) ? $this->esc_url_value( (string) get_permalink( $post_id ) ) : '',
				);
			}
		}

		$duplicate_found = ! empty( $duplicate['post_id'] );
		$skip_recommended = $duplicate_found && 'preserve_manual_edits' === $write_guard_mode;
		$summary_text = '';
		if ( $duplicate_found ) {
			$summary_text = sprintf(
				'检测到同指纹文章 #%d%s，建议先复核现有稿件。',
				$this->absint_value( $duplicate['post_id'] ?? 0 ),
				! empty( $duplicate['title'] ) ? '《' . sanitize_text_field( (string) $duplicate['title'] ) . '》' : ''
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'production_fingerprint' => $production_fingerprint,
				'duplicate_found'        => $duplicate_found,
				'skip_recommended'       => $skip_recommended,
				'soft_block_reason'      => $skip_recommended ? 'duplicate_production_candidate' : '',
				'summary_text'           => $summary_text,
				'duplicate_candidate'    => $duplicate,
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Extracts a lightweight writing-style profile from reference posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function extract_reference_post_style( $input ) {
		$input = is_array( $input ) ? $input : array();
		$reference_post_ids = is_array( $input['reference_post_ids'] ?? null ) ? $input['reference_post_ids'] : array();
		$reference_post_ids = array_values(
			array_unique(
				array_filter(
					array_map( array( $this, 'absint_value' ), $reference_post_ids )
				)
			)
		);

		if ( empty( $reference_post_ids ) ) {
			return $this->empty_style_profile_result( '' );
		}

		$posts = array();
		foreach ( array_slice( $reference_post_ids, 0, 5 ) as $post_id ) {
			$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) || empty( $post->ID ) ) {
				continue;
			}
			if ( function_exists( 'current_user_can' ) && ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$posts[] = $post;
		}

		$payload = $this->build_style_profile_from_posts( $posts );

		return array(
			'success' => true,
			'data'    => array(
				'profile' => is_array( $payload['profile'] ?? null ) ? $payload['profile'] : array(),
				'samples' => is_array( $payload['samples'] ?? null ) ? $payload['samples'] : array(),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Extracts a site or author style baseline from recent posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function extract_style_baseline( $input ) {
		$input = is_array( $input ) ? $input : array();
		$mode = sanitize_key( (string) ( $input['mode'] ?? 'site_recent' ) );
		if ( ! in_array( $mode, array( 'off', 'site_recent', 'author_recent' ), true ) ) {
			$mode = 'site_recent';
		}
		if ( 'off' === $mode ) {
			return $this->empty_style_profile_result( 'off' );
		}

		$author_id = $this->absint_value( $input['author_id'] ?? 0 );
		if ( $author_id <= 0 && 'author_recent' === $mode && function_exists( 'get_current_user_id' ) ) {
			$author_id = $this->absint_value( get_current_user_id() );
		}

		$cache_key = 'magick_ai_style_baseline_' . $mode . '_' . ( $author_id > 0 ? 'author_' . $author_id : 'site' ) . '_v' . $this->get_style_baseline_cache_version();
		if ( function_exists( 'get_transient' ) ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_array( $cached ) && isset( $cached['success'], $cached['data'] ) ) {
				return $cached;
			}
		}

		$limit = max( 1, min( 5, $this->absint_value( $input['limit'] ?? 4 ) ) );
		$query_args = array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => $limit,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
		);
		if ( 'author_recent' === $mode && $author_id > 0 ) {
			$query_args['author'] = $author_id;
		}

		$posts = function_exists( 'get_posts' ) ? get_posts( $query_args ) : array();
		$posts = is_array( $posts ) ? $posts : array();
		if ( empty( $posts ) && 'author_recent' === $mode ) {
			unset( $query_args['author'] );
			$posts = function_exists( 'get_posts' ) ? get_posts( $query_args ) : array();
			$posts = is_array( $posts ) ? $posts : array();
			$mode = 'site_recent';
		}

		$payload = $this->build_style_profile_from_posts( $posts );
		$result = array(
			'success' => true,
			'data'    => array(
				'profile' => is_array( $payload['profile'] ?? null ) ? $payload['profile'] : array(),
				'samples' => is_array( $payload['samples'] ?? null ) ? $payload['samples'] : array(),
				'source'  => $mode,
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);

		if ( function_exists( 'set_transient' ) ) {
			$ttl = defined( 'HOUR_IN_SECONDS' ) ? 12 * HOUR_IN_SECONDS : 12 * 60 * 60;
			set_transient( $cache_key, $result, $ttl );
		}

		return $result;
	}

	/**
	 * Composes article optimization report sections into one deterministic envelope.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_optimization_report( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post = is_array( $input['post'] ?? null ) ? $input['post'] : array();
		$seo = is_array( $input['seo'] ?? null ) ? $input['seo'] : array();
		$geo = is_array( $input['geo'] ?? null ) ? $input['geo'] : array();
		$internal_links = is_array( $input['internal_links'] ?? null ) ? $input['internal_links'] : array();
		$media = is_array( $input['media'] ?? null ) ? $input['media'] : array();

		$recommendations = array();
		foreach ( array( 'seo' => $seo, 'geo' => $geo ) as $section_key => $section ) {
			$section_recommendations = is_array( $section['recommendations'] ?? null ) ? $section['recommendations'] : array();
			foreach ( $section_recommendations as $item ) {
				$item = is_array( $item ) ? $item : array();
				$item['section'] = $section_key;
				$recommendations[] = $item;
			}
		}
		foreach ( array_slice( is_array( $internal_links['placement_plan'] ?? null ) ? $internal_links['placement_plan'] : array(), 0, 3 ) as $placement ) {
			$placement = is_array( $placement ) ? $placement : array();
			$recommendations[] = array(
				'section'  => 'internal_links',
				'type'     => 'placement',
				'priority' => 'medium',
				'title'    => sanitize_text_field( (string) ( $placement['anchor_text'] ?? '建议内链' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $placement['reason'] ?? '' ) ),
			);
		}
		foreach ( array_slice( is_array( $media['assets'] ?? null ) ? $media['assets'] : array(), 0, 3 ) as $asset ) {
			$asset = is_array( $asset ) ? $asset : array();
			$issues = is_array( $asset['issues'] ?? null ) ? $asset['issues'] : array();
			if ( empty( $issues ) ) {
				continue;
			}
			$recommendations[] = array(
				'section'  => 'media',
				'type'     => 'metadata',
				'priority' => 'medium',
				'title'    => '补齐媒体元数据',
				'detail'   => implode( ' ', array_map( array( $this, 'sanitize_text_value' ), $issues ) ),
			);
		}

		$high_priority_count = count(
			array_filter(
				$recommendations,
				static function ( array $recommendation ) {
					return 'high' === sanitize_key( (string) ( $recommendation['priority'] ?? '' ) );
				}
			)
		);

		return $this->build_analysis_success_response(
			array(
				'post'            => array(
					'post_id'   => $this->absint_value( $post['id'] ?? $post['post_id'] ?? 0 ),
					'title'     => sanitize_text_field( (string) ( $post['title'] ?? '' ) ),
					'status'    => sanitize_key( (string) ( $post['status'] ?? '' ) ),
					'edit_link' => $this->esc_url_value( (string) ( $post['edit_link'] ?? '' ) ),
				),
				'seo'             => $seo,
				'geo'             => $geo,
				'internal_links'  => $internal_links,
				'media'           => $media,
				'summary'         => array(
					'status'                => $high_priority_count > 0 ? 'needs_attention' : 'ready_for_review',
					'high_priority_count'   => $high_priority_count,
					'total_recommendations' => count( $recommendations ),
					'sections'              => array( 'seo', 'geo', 'internal_links', 'media' ),
				),
				'recommendations' => $recommendations,
			),
			array(
				'source'         => 'local_article_optimization_report',
				'execution_mode' => 'deterministic',
			),
			'Article optimization report built.'
		);
	}

	/**
	 * Builds lightweight SEO report context for article optimization workflows.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_seo_report_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		$content = $this->normalize_plain_text( $input['input'] ?? '' );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$issues = array();
		$recommendations = array();
		$score = 88;

		if ( '' !== $focus_keyword && ! $this->contains_text_ci( $content, $focus_keyword ) ) {
			$issues[] = array(
				'id'       => 'focus_keyword_missing',
				'severity' => 'high',
				'title'    => '焦点关键词未在正文中明显出现。',
				'detail'   => '建议在导语或第一个小标题附近自然补齐焦点关键词。',
			);
			$recommendations[] = array(
				'type'     => 'keyword',
				'priority' => 'high',
				'title'    => '补齐焦点关键词',
				'detail'   => '优先在首屏摘要和一个小标题中自然出现焦点关键词。',
			);
			$score -= 18;
		}

		if ( $this->measure_style_text_length( $content ) < 320 ) {
			$issues[] = array(
				'id'       => 'content_depth_thin',
				'severity' => 'medium',
				'title'    => '正文长度偏短，SEO 覆盖面不足。',
				'detail'   => '建议增加 1 到 2 段解释型内容，补齐背景或步骤。',
			);
			$recommendations[] = array(
				'type'     => 'depth',
				'priority' => 'medium',
				'title'    => '补内容深度',
				'detail'   => '增加对核心问题的背景解释、步骤或注意事项。',
			);
			$score -= 10;
		}

		return $this->build_analysis_success_response(
			array(
				'score'           => max( 0, $score ),
				'issues'          => $issues,
				'recommendations' => $recommendations,
				'summary'         => array(
					'high_priority_count' => count(
						array_filter(
							$issues,
							static function ( array $issue ) {
								return 'high' === sanitize_key( (string) ( $issue['severity'] ?? '' ) );
							}
						)
					),
					'focus_keyword'        => $focus_keyword,
				),
			),
			array(
				'source'         => 'local_seo_report_context',
				'execution_mode' => 'deterministic',
			),
			'SEO report context built.'
		);
	}

	/**
	 * Reads one post snapshot for internal optimization workflows.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function read_post_optimization_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'post_id is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_missing', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You are not allowed to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$categories = function_exists( 'get_the_terms' ) ? get_the_terms( $post_id, 'category' ) : array();
		$tags = function_exists( 'get_the_terms' ) ? get_the_terms( $post_id, 'post_tag' ) : array();
		$template = '';
		if ( function_exists( 'get_page_template_slug' ) ) {
			$template = sanitize_text_field( (string) get_page_template_slug( $post_id ) );
		}
		if ( '' === $template ) {
			$template = function_exists( 'get_post_meta' )
				? sanitize_text_field( (string) get_post_meta( $post_id, '_wp_page_template', true ) )
				: '';
		}
		$format = function_exists( 'get_post_format' ) ? sanitize_key( (string) get_post_format( $post_id ) ) : '';
		if ( '' === $format ) {
			$format = 'standard';
		}

		$seo_provider = $this->detect_seo_provider();
		$seo_meta_keys = $this->seo_meta_keys( $seo_provider );

		return $this->build_analysis_success_response(
			array(
				'id'         => $post_id,
				'title'      => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'status'     => sanitize_key( (string) $post->post_status ),
				'post_type'  => sanitize_key( (string) $post->post_type ),
				'excerpt'    => function_exists( 'has_excerpt' ) && has_excerpt( $post_id ) && function_exists( 'get_the_excerpt' ) ? $this->sanitize_metadata_text( (string) get_the_excerpt( $post_id ) ) : '',
				'content'    => (string) $post->post_content,
				'slug'       => $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) ),
				'edit_link'  => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
				'categories' => $this->format_term_name_list( $categories ),
				'tags'       => $this->format_term_name_list( $tags ),
				'seo'        => array(
					'provider'    => $seo_provider,
					'title'       => function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $post_id, (string) ( $seo_meta_keys['title'] ?? '' ), true ) ) : '',
					'description' => function_exists( 'get_post_meta' ) ? $this->sanitize_metadata_text( (string) get_post_meta( $post_id, (string) ( $seo_meta_keys['description'] ?? '' ), true ) ) : '',
				),
				'template'   => $template,
				'format'     => $format,
			),
			array(
				'source'         => 'local_post_optimization_context',
				'execution_mode' => 'deterministic',
			),
			'Post optimization context loaded.'
		);
	}

	/**
	 * Builds canonical single-article suggest envelope.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_single_optimization_suggest( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post = is_array( $input['post'] ?? null ) ? $input['post'] : array();
		$generated_excerpt = is_array( $input['generated_excerpt'] ?? null ) ? $input['generated_excerpt'] : array();
		$generated_seo = is_array( $input['generated_seo'] ?? null ) ? $input['generated_seo'] : array();
		$seo_analysis = is_array( $input['seo_analysis'] ?? null ) ? $input['seo_analysis'] : array();
		$geo_analysis = is_array( $input['geo_analysis'] ?? null ) ? $input['geo_analysis'] : array();
		$taxonomy_context = is_array( $input['taxonomy_context'] ?? null ) ? $input['taxonomy_context'] : array();
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$keyword_input = is_array( $input['keywords'] ?? null ) ? $input['keywords'] : array();
		$existing_content = $this->sanitize_metadata_text( (string) ( $post['content'] ?? '' ) );

		$current_title = sanitize_text_field( (string) ( $post['title'] ?? '' ) );
		$current_excerpt = $this->sanitize_metadata_text( (string) ( $post['excerpt'] ?? '' ) );
		$current_slug = $this->sanitize_metadata_slug( (string) ( $post['slug'] ?? '' ) );
		$current_seo = is_array( $post['seo'] ?? null ) ? $post['seo'] : array();

		$title_suggested = $current_title;
		$title_reason = '当前标题已可继续使用。';
		if ( '' !== $focus_keyword && ! $this->contains_text_ci( $current_title, $focus_keyword ) ) {
			$title_suggested = trim( $current_title . '｜' . $focus_keyword );
			$title_reason = '标题未覆盖焦点关键词，建议补齐到标题层。';
		}
		$title_suggestion_rows = $this->build_title_suggestions_from_context( $title_suggested, $existing_content, 3, $title_reason );

		$excerpt_suggested = $this->sanitize_metadata_text(
			(string) (
				$generated_excerpt['proposal_text']
				?? $generated_excerpt['excerpt']
				?? $generated_excerpt['text']
				?? ''
			)
		);
		if ( '' === $excerpt_suggested ) {
			$excerpt_suggested = $current_excerpt;
		}

		$seo_title_suggested = sanitize_text_field( (string) ( $generated_seo['meta_title'] ?? $generated_seo['title'] ?? '' ) );
		if ( '' === $seo_title_suggested ) {
			$seo_title_suggested = $title_suggested;
		}

		$seo_description_suggested = $this->sanitize_metadata_text( (string) ( $generated_seo['meta_description'] ?? $generated_seo['description'] ?? '' ) );
		if ( '' === $seo_description_suggested ) {
			$seo_description_suggested = $excerpt_suggested;
		}

		$slug_source = '' !== $focus_keyword ? $focus_keyword : $title_suggested;
		$slug_suggested = $this->sanitize_metadata_slug( $slug_source );
		if ( '' === $slug_suggested ) {
			$slug_suggested = $current_slug;
		}

		$keyword_candidates = $this->collect_article_suggest_keywords(
			array_merge(
				array( $focus_keyword, $title_suggested, $seo_title_suggested ),
				$keyword_input,
				is_array( $generated_seo['keywords'] ?? null ) ? $generated_seo['keywords'] : array()
			)
		);

		$category_candidates = is_array( $taxonomy_context['categories'] ?? null ) && ! empty( $taxonomy_context['categories'] )
			? array_values( $taxonomy_context['categories'] )
			: array_slice( $keyword_candidates, 0, 3 );
		$tag_candidates = is_array( $taxonomy_context['tags'] ?? null ) && ! empty( $taxonomy_context['tags'] )
			? array_values( $taxonomy_context['tags'] )
			: array_slice( $keyword_candidates, 0, 5 );

		$suggestions = array(
			'title'            => $this->build_article_suggest_field_row(
				'title',
				'标题',
				$current_title,
				$title_suggested,
				false,
				$title_reason
			),
			'title_suggestions' => $title_suggestion_rows,
			'categories'       => $this->build_article_suggest_taxonomy_row(
				'categories',
				'分类',
				is_array( $post['categories'] ?? null ) ? $post['categories'] : array(),
				$category_candidates,
				$this->get_article_suggest_taxonomy_catalog( 'category' )
			),
			'tags'             => $this->build_article_suggest_taxonomy_row(
				'tags',
				'标签',
				is_array( $post['tags'] ?? null ) ? $post['tags'] : array(),
				$tag_candidates,
				$this->get_article_suggest_taxonomy_catalog( 'post_tag' )
			),
			'excerpt'          => $this->build_article_suggest_field_row(
				'excerpt',
				'摘要',
				$current_excerpt,
				$excerpt_suggested,
				true,
				'摘要属于第一阶段低风险字段，可先预览再应用。'
			),
			'slug'             => $this->build_article_suggest_field_row(
				'slug',
				'别名 slug',
				$current_slug,
				$slug_suggested,
				true,
				'建议使用更短、更稳定、可读的 slug。'
			),
			'seo_title'        => $this->build_article_suggest_field_row(
				'seo_title',
				'SEO 标题',
				sanitize_text_field( (string) ( $current_seo['title'] ?? '' ) ),
				$seo_title_suggested,
				true,
				'建议让 SEO 标题与主题和焦点关键词对齐。'
			),
			'seo_description'  => $this->build_article_suggest_field_row(
				'seo_description',
				'SEO 描述',
				$this->sanitize_metadata_text( (string) ( $current_seo['description'] ?? '' ) ),
				$seo_description_suggested,
				true,
				'建议让 SEO 描述覆盖摘要主旨与核心关键词。'
			),
			'keywords'         => array(
				'field' => 'keywords',
				'label' => '关键词',
				'items' => $keyword_candidates,
			),
		);

		$content_improvements = array(
			array(
				'type'     => 'patch_proposal',
				'priority' => 'medium',
				'field'    => 'content',
				'section'  => 'article_body',
				'issue'    => '当前正文修改仍需停在 section-level proposal，不能直接走整篇自动改写。',
				'action'   => '先输出 patch plan、补充点和改写方向，再由人工决定如何修改正文。',
				'reason'   => '保持文章域默认写回边界不变，避免把 suggest 主线升级成整篇自动重写入口。',
				'title'    => '正文只停在 patch / section proposal',
				'detail'   => '当前主线只输出旧文章内容补强建议，不默认整篇自动重写。',
			),
		);
		if ( '' !== $focus_keyword && ! $this->contains_text_ci( $current_title, $focus_keyword ) ) {
			$content_improvements[] = array(
				'type'     => 'section_proposal',
				'priority' => 'medium',
				'field'    => 'content',
				'section'  => 'opening',
				'issue'    => '首屏还没有自然回答主题并覆盖焦点关键词。',
				'action'   => '在导语或第一个小标题附近补 1 段 answer-first 内容，先定义问题、给结论，再自然带入关键词。',
				'reason'   => '先补首屏定义和结论，能同时改善可回答性、关键词覆盖和读者进入正文的理解成本。',
				'title'    => '在导语或第一个小标题附近补齐焦点词',
				'detail'   => '建议在正文首屏增加 1 段直接回答型内容，并自然覆盖焦点关键词。',
			);
		}

		$seo_improvements = $this->build_analysis_improvement_rows( $seo_analysis, 'seo', 'seo_recommendation' );
		if ( empty( $seo_improvements ) ) {
			$seo_improvements[] = array(
				'type'     => 'seo_alignment',
				'priority' => 'medium',
				'field'    => 'seo',
				'title'    => '继续对齐标题、摘要与 SEO 元字段',
				'detail'   => 'SEO 改进块当前应继续围绕 title、seo_title、seo_description、slug 与 excerpt 协同收口。',
			);
		}

		$geo_improvements = $this->build_analysis_improvement_rows( $geo_analysis, 'content', 'geo_recommendation' );
		if ( empty( $geo_improvements ) ) {
			$geo_improvements[] = array(
				'type'     => 'answerability',
				'priority' => 'medium',
				'field'    => 'content',
				'title'    => '补一段 answer-first 摘要或 FAQ',
				'detail'   => 'GEO 改进块当前应继续围绕 answer-first summary、FAQ 和实体一致性给出建议。',
			);
		}

		$recommendations = $this->build_article_single_suggest_recommendations( $suggestions, $content_improvements, $seo_improvements, $geo_improvements );
		$high_priority_count = count(
			array_filter(
				$recommendations,
				static function ( array $row ) {
					return 'high' === sanitize_key( (string) ( $row['priority'] ?? '' ) );
				}
			)
		);
		$ai_risk_review = $this->build_article_ai_risk_review( $existing_content, 'generic', array(), 'medium', array(), array() );
		foreach ( array_slice( (array) ( $ai_risk_review['items'] ?? array() ), 0, 2 ) as $risk_item ) {
			$risk_item = is_array( $risk_item ) ? $risk_item : array();
			$recommendations[] = array(
				'priority' => sanitize_key( (string) ( $risk_item['severity'] ?? 'medium' ) ),
				'section'  => 'review',
				'title'    => sanitize_text_field( (string) ( $risk_item['title'] ?? 'AI 风险复核' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $risk_item['detail'] ?? '' ) ),
			);
		}

		return $this->build_analysis_success_response(
			array(
				'post'                 => array(
					'post_id'    => $this->absint_value( $post['id'] ?? $post['post_id'] ?? 0 ),
					'title'      => $current_title,
					'status'     => sanitize_key( (string) ( $post['status'] ?? '' ) ),
					'post_type'  => sanitize_key( (string) ( $post['post_type'] ?? '' ) ),
					'excerpt'    => $current_excerpt,
					'slug'       => $current_slug,
					'edit_link'  => $this->esc_url_value( (string) ( $post['edit_link'] ?? '' ) ),
					'categories' => is_array( $post['categories'] ?? null ) ? array_values( $post['categories'] ) : array(),
					'tags'       => is_array( $post['tags'] ?? null ) ? array_values( $post['tags'] ) : array(),
					'seo'        => array(
						'provider'    => sanitize_key( (string) ( $current_seo['provider'] ?? '' ) ),
						'title'       => sanitize_text_field( (string) ( $current_seo['title'] ?? '' ) ),
						'description' => $this->sanitize_metadata_text( (string) ( $current_seo['description'] ?? '' ) ),
					),
				),
				'suggestions'          => $suggestions,
				'content_improvements' => $content_improvements,
				'seo_improvements'     => $seo_improvements,
				'geo_improvements'     => $geo_improvements,
				'summary'              => array(
					'status'                => $high_priority_count > 0 ? 'needs_attention' : 'ready_for_review',
					'focus_keyword'         => $focus_keyword,
					'high_priority_count'   => $high_priority_count,
					'total_recommendations' => count( $recommendations ),
					'safe_apply_fields'     => array( 'excerpt', 'seo_title', 'seo_description', 'slug' ),
					'taxonomy_fields'       => array( 'categories', 'tags' ),
					'improvement_sections'  => array( 'content_improvements', 'seo_improvements', 'geo_improvements' ),
				),
				'review'               => array(
					'ai_risk_review' => $ai_risk_review,
				),
				'recommendations'      => $recommendations,
			),
			array(
				'source'         => 'local_article_single_optimization_suggest',
				'execution_mode' => 'deterministic',
			),
			'Article single optimization suggest built.'
		);
	}

	/**
	 * Builds one conservative apply plan from the read-only article optimization report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_optimization_apply_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post = is_array( $input['post'] ?? null ) ? $input['post'] : array();
		$report = is_array( $input['report'] ?? null ) ? $input['report'] : array();
		$optimization_plan = is_array( $input['optimization_plan'] ?? null ) ? $input['optimization_plan'] : array();
		$generated_excerpt = is_array( $input['generated_excerpt'] ?? null ) ? $input['generated_excerpt'] : array();
		$seo_meta = is_array( $input['seo_meta'] ?? null ) ? $input['seo_meta'] : array();

		$excerpt_mode = $this->read_plan_mode( $optimization_plan, 'excerpt_mode', 'keep' );
		$title_mode = $this->read_plan_mode( $optimization_plan, 'title_mode', 'keep' );
		$seo_mode = $this->read_plan_mode( $optimization_plan, 'seo_mode', 'suggest' );
		$geo_mode = $this->read_plan_mode( $optimization_plan, 'geo_mode', 'suggest' );
		$internal_link_mode = $this->read_plan_mode( $optimization_plan, 'internal_link_mode', 'suggest' );
		$faq_mode = $this->read_plan_mode( $optimization_plan, 'faq_mode', 'suggest' );
		$heading_mode = $this->read_plan_mode( $optimization_plan, 'heading_mode', 'keep' );
		$schema_hint_mode = $this->read_plan_mode( $optimization_plan, 'schema_hint_mode', 'suggest' );
		$risk_mode = $this->read_plan_mode( $optimization_plan, 'risk_mode', 'safe' );

		$regenerated_excerpt = $this->sanitize_metadata_text( (string) ( $generated_excerpt['proposal_text'] ?? '' ) );
		$current_excerpt = $this->sanitize_metadata_text( (string) ( $post['excerpt'] ?? '' ) );
		$excerpt_apply_generate = in_array( $excerpt_mode, array( 'regenerate', 'apply' ), true ) && '' !== $regenerated_excerpt;
		$recommendations = is_array( $report['recommendations'] ?? null ) ? $report['recommendations'] : array();
		$geo_summary = is_array( $report['geo']['summary'] ?? null ) ? $report['geo']['summary'] : array();
		$internal_summary = is_array( $report['internal_links']['summary'] ?? null ) ? $report['internal_links']['summary'] : array();
		$media_summary = is_array( $report['media']['summary'] ?? null ) ? $report['media']['summary'] : array();

		$actions = array(
			'excerpt'        => array(
				'mode'             => $excerpt_mode,
				'apply_generate'   => $excerpt_apply_generate,
				'current_excerpt'  => $current_excerpt,
				'proposed_excerpt' => $regenerated_excerpt,
				'write_supported'  => true,
			),
			'seo_meta'       => array(
				'mode'            => $seo_mode,
				'write_supported' => false,
				'proposal'        => $seo_meta,
			),
			'title'          => array(
				'mode'            => $title_mode,
				'write_supported' => false,
			),
			'geo'            => array(
				'mode'                => $geo_mode,
				'write_supported'     => false,
				'faq_candidate_count' => $this->absint_value( $geo_summary['faq_candidate_count'] ?? 0 ),
			),
			'internal_links' => array(
				'mode'            => $internal_link_mode,
				'write_supported' => false,
				'placement_count' => $this->absint_value( $internal_summary['placement_count'] ?? 0 ),
			),
			'faq'            => array(
				'mode'                => $faq_mode,
				'write_supported'     => false,
				'candidate_count'     => $this->absint_value( $geo_summary['faq_candidate_count'] ?? 0 ),
			),
			'headings'       => array(
				'mode'                 => $heading_mode,
				'write_supported'      => false,
				'recommendation_count' => $this->count_recommendation_section( $recommendations, 'seo' ) + $this->count_recommendation_section( $recommendations, 'geo' ),
			),
			'schema_hints'  => array(
				'mode'                => $schema_hint_mode,
				'write_supported'     => false,
				'faq_candidate_count' => $this->absint_value( $geo_summary['faq_candidate_count'] ?? 0 ),
			),
			'media'          => array(
				'mode'            => 'suggest',
				'write_supported' => false,
				'asset_count'     => $this->absint_value( $media_summary['asset_count'] ?? 0 ),
			),
		);

		$safe_write_actions = array();
		if ( $excerpt_apply_generate ) {
			$safe_write_actions[] = 'update_excerpt';
		}

		$advisory_sections = array();
		foreach ( array( 'seo_meta', 'title', 'geo', 'internal_links', 'faq', 'headings', 'schema_hints', 'media' ) as $section_key ) {
			$mode = sanitize_key( (string) ( $actions[ $section_key ]['mode'] ?? 'off' ) );
			if ( in_array( $mode, array( 'suggest', 'apply', 'replace', 'normalize', 'rewrite' ), true ) ) {
				$advisory_sections[] = $section_key;
			}
		}

		$summary = array(
			'post_id'               => $this->absint_value( $post['post_id'] ?? $post['id'] ?? 0 ),
			'safe_apply_supported'  => $safe_write_actions,
			'advisory_sections'     => array_values( array_unique( $advisory_sections ) ),
			'report_status'         => sanitize_key( (string) ( $report['summary']['status'] ?? 'ready_for_review' ) ),
			'high_priority_count'   => $this->absint_value( $report['summary']['high_priority_count'] ?? 0 ),
			'total_recommendations' => $this->absint_value( $report['summary']['total_recommendations'] ?? 0 ),
			'risk_mode'             => $risk_mode,
			'next_action'           => ! empty( $safe_write_actions ) ? 'safe_apply_available' : 'review_before_apply',
		);

		return $this->build_analysis_success_response(
			array(
				'post'    => array(
					'post_id' => $this->absint_value( $post['post_id'] ?? $post['id'] ?? 0 ),
					'title'   => sanitize_text_field( (string) ( $post['title'] ?? '' ) ),
					'status'  => sanitize_key( (string) ( $post['status'] ?? '' ) ),
				),
				'actions' => $actions,
				'summary' => $summary,
			),
			array(
				'source'         => 'local_article_optimization_apply_plan',
				'execution_mode' => 'deterministic',
			),
			'Article optimization apply plan built.'
		);
	}

	/**
	 * Composes one conservative optimization apply result envelope.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function compose_article_optimization_apply_result( $input ) {
		$input = is_array( $input ) ? $input : array();
		$report = is_array( $input['report'] ?? null ) ? $input['report'] : array();
		$apply_plan = is_array( $input['apply_plan'] ?? null ) ? $input['apply_plan'] : array();
		$apply_excerpt = is_array( $input['apply_excerpt'] ?? null ) ? $input['apply_excerpt'] : array();

		$plan_summary = is_array( $apply_plan['summary'] ?? null ) ? $apply_plan['summary'] : array();
		$actions = is_array( $apply_plan['actions'] ?? null ) ? $apply_plan['actions'] : array();
		$safe_apply_supported = is_array( $plan_summary['safe_apply_supported'] ?? null ) ? $plan_summary['safe_apply_supported'] : array();
		$advisory_sections = is_array( $plan_summary['advisory_sections'] ?? null ) ? $plan_summary['advisory_sections'] : array();
		$applied_changes = array();
		if ( true === (bool) ( $apply_excerpt['updated'] ?? false ) ) {
			$applied_changes[] = array(
				'type'    => 'excerpt',
				'post_id' => $this->absint_value( $apply_excerpt['post_id'] ?? 0 ),
				'changes' => is_array( $apply_excerpt['changes'] ?? null ) ? $apply_excerpt['changes'] : array(),
			);
		}

		$result_mode = empty( $applied_changes ) ? 'plan_only' : 'partial_apply';
		$summary_parts = array(
			'结果：' . ( 'partial_apply' === $result_mode ? '已安全应用部分优化' : '已生成应用计划' ),
			'安全可写项：' . ( ! empty( $safe_apply_supported ) ? implode( '、', array_map( array( $this, 'sanitize_text_value' ), $safe_apply_supported ) ) : '无' ),
		);
		if ( ! empty( $advisory_sections ) ) {
			$summary_parts[] = '仍停在建议层：' . implode( '、', array_map( array( $this, 'sanitize_text_value' ), $advisory_sections ) );
		}
		if ( ! empty( $applied_changes ) ) {
			$summary_parts[] = '已应用：' . implode(
				'、',
				array_map(
					static function ( array $row ): string {
						return sanitize_text_field( (string) ( $row['type'] ?? '' ) );
					},
					$applied_changes
				)
			);
		}

		return $this->build_analysis_success_response(
			array(
				'report'          => $report,
				'apply_plan'      => $apply_plan,
				'applied_changes' => $applied_changes,
				'summary'         => array(
					'result_mode'          => $result_mode,
					'next_action'          => ! empty( $advisory_sections ) ? 'review_remaining_changes' : 'safe_apply_complete',
					'safe_apply_supported' => $safe_apply_supported,
					'advisory_sections'    => $advisory_sections,
					'applied_count'        => count( $applied_changes ),
				),
				'summary_text'    => implode( '；', array_filter( $summary_parts ) ),
				'actions'         => $actions,
			),
			array(
				'source'         => 'local_article_optimization_apply_result',
				'execution_mode' => 'deterministic',
			),
			'Article optimization apply result built.'
		);
	}

	/**
	 * Builds lightweight local article production review signals.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function review_article_output_light( $input ) {
		$input = is_array( $input ) ? $input : array();
		$article = is_array( $input['article'] ?? null ) ? $input['article'] : array();
		$style_profile = is_array( $input['style_profile'] ?? null ) ? $input['style_profile'] : array();
		$media = is_array( $input['media'] ?? null ) ? $input['media'] : array();
		$platform_profile = sanitize_key( (string) ( $input['platform_profile'] ?? 'generic' ) );
		$human_signals = is_array( $input['human_signals'] ?? null ) ? $input['human_signals'] : array();
		$image_mode = sanitize_key( (string) ( $input['image_mode'] ?? 'featured_only' ) );
		$content = (string) ( $article['content'] ?? '' );
		$content_text = $this->normalize_plain_text( $content );
		$paragraphs = $this->extract_article_style_paragraphs( $content );
		$opening = $this->classify_article_opening_style( (string) ( $paragraphs[0] ?? '' ) );
		$voice = $this->classify_article_voice_profile( $content_text );

		$template_hits = 0;
		$template_findings = array();
		foreach ( array( '首先', '其次', '最后', '综上所述' ) as $phrase ) {
			if ( false !== strpos( $content_text, $phrase ) ) {
				++$template_hits;
				$template_findings[] = '命中套话短语：' . $phrase;
			}
		}

		$naturalness = max( 40, 92 - ( $template_hits * 12 ) );
		if ( count( $paragraphs ) <= 2 ) {
			$naturalness -= 10;
			$template_findings[] = '段落层次偏少，容易显得像模板摘要。';
		}

		$paragraph_lengths = array();
		foreach ( $paragraphs as $paragraph ) {
			$paragraph_text = trim( $this->strip_all_tags_value( (string) $paragraph ) );
			$paragraph_lengths[] = function_exists( 'mb_strlen' ) ? mb_strlen( $paragraph_text ) : strlen( $paragraph_text );
		}
		$paragraph_lengths = array_values( array_filter( $paragraph_lengths ) );
		if ( count( $paragraph_lengths ) >= 3 ) {
			$max_length = max( $paragraph_lengths );
			$min_length = min( $paragraph_lengths );
			if ( $max_length > 0 && ( $max_length - $min_length ) <= 18 ) {
				$template_findings[] = '段落长度过于整齐，缺少自然节奏变化。';
				$naturalness -= 6;
			}
		}

		$heading_count = preg_match_all( '/<h[1-6][^>]*>/i', $content, $heading_matches );
		unset( $heading_matches );
		if ( $heading_count >= 3 && count( $paragraph_lengths ) > 0 ) {
			$template_findings[] = '小标题较密，建议保留长短段交替避免教科书腔。';
		}

		$template_risk_level = 'low';
		if ( $template_hits >= 2 || count( $template_findings ) >= 3 ) {
			$template_risk_level = 'high';
		} elseif ( $template_hits >= 1 || count( $template_findings ) >= 1 ) {
			$template_risk_level = 'medium';
		}

		$style_match = 72;
		$style_findings = array();
		$target_opening = sanitize_key( (string) ( $style_profile['resolved_opening_style'] ?? '' ) );
		$target_voice = sanitize_key( (string) ( $style_profile['resolved_voice_profile'] ?? '' ) );
		if ( '' !== $target_opening && $target_opening === $opening ) {
			$style_match += 12;
		} elseif ( '' !== $target_opening ) {
			$style_match -= 12;
			$style_findings[] = '开头风格偏离目标：期望 ' . $target_opening . '，实际 ' . $opening . '。';
		}
		if ( '' !== $target_voice && $target_voice === $voice ) {
			$style_match += 10;
		} elseif ( '' !== $target_voice ) {
			$style_match -= 10;
			$style_findings[] = '语气风格偏离目标：期望 ' . $target_voice . '，实际 ' . $voice . '。';
		}
		$style_match = max( 0, $style_match );

		$image_relevance = 'none' === $image_mode ? 100 : 68;
		$position_summary = is_array( $media['position_inline_image_blocks']['summary'] ?? null ) ? $media['position_inline_image_blocks']['summary'] : array();
		$featured_success = ! empty( $media['featured_attached'] );
		if ( 'featured_only' === $image_mode ) {
			$image_relevance = $featured_success ? 88 : 56;
		} elseif ( 'featured_and_inline' === $image_mode ) {
			$image_relevance = $featured_success ? 78 : 58;
			$image_relevance += min( 12, max( 0, $this->absint_value( $position_summary['positioned_count'] ?? 0 ) ) * 4 );
		}

		$needs_human_review = $naturalness < 70 || $style_match < 70 || $image_relevance < 70;
		$next_action = $needs_human_review ? 'needs_human_review' : 'ready_for_editorial_review';
		if ( 'featured_and_inline' === $image_mode && $this->absint_value( $position_summary['appended_count'] ?? 0 ) > 0 ) {
			$next_action = 'needs_layout_review';
		}

		$ai_risk_review = $this->build_article_ai_risk_review( $content, $platform_profile, $human_signals, $template_risk_level, $template_findings, $style_findings );
		$ai_risk_review = $this->relax_article_ai_risk_review_for_reference_style( $ai_risk_review, min( 100, $style_match ), $style_findings );
		if ( 'high' === sanitize_key( (string) ( $ai_risk_review['highest_severity'] ?? '' ) ) ) {
			$needs_human_review = true;
			if ( 'ready_for_editorial_review' === $next_action ) {
				$next_action = 'needs_human_review';
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'writing_naturalness' => $naturalness,
				'style_match_score'   => min( 100, $style_match ),
				'image_relevance_score' => min( 100, $image_relevance ),
				'needs_human_review'  => $needs_human_review,
				'next_action'         => $next_action,
				'template_risk_level' => $template_risk_level,
				'platform_profile'    => $platform_profile,
				'ai_risk_review'      => $ai_risk_review,
				'signals'             => array(
					'detected_opening_style' => $opening,
					'detected_voice_profile' => $voice,
					'template_phrase_hits'   => $template_hits,
					'paragraph_count'        => count( $paragraphs ),
					'anti_template_findings' => array_values( array_unique( $template_findings ) ),
					'style_findings'         => array_values( array_unique( $style_findings ) ),
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Composes lightweight production result with degraded/result-mode semantics.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function compose_article_production_result( $input ) {
		$input = is_array( $input ) ? $input : array();
		$source_input = is_array( $input['input'] ?? null ) ? $input['input'] : array();
		$article = is_array( $input['article'] ?? null ) ? $input['article'] : array();
		$draft = is_array( $input['draft'] ?? null ) ? $input['draft'] : array();
		$media = is_array( $input['media'] ?? null ) ? $input['media'] : array();
		$metadata_plan_resolution = is_array( $input['metadata_plan_resolution'] ?? null ) ? $input['metadata_plan_resolution'] : array();
		$seo_analysis = is_array( $input['seo_analysis'] ?? null ) ? $input['seo_analysis'] : array();
		$geo_analysis = is_array( $input['geo_analysis'] ?? null ) ? $input['geo_analysis'] : array();
		$review = is_array( $input['review'] ?? null ) ? $input['review'] : array();
		$publication_decision = is_array( $input['publication_decision'] ?? null ) ? $input['publication_decision'] : array();
		$duplicate_guard = is_array( $input['duplicate_guard'] ?? null ) ? $input['duplicate_guard'] : array();
		$platform_profile = sanitize_key( (string) ( $source_input['platform_profile'] ?? 'generic' ) );
		$human_signals = is_array( $source_input['human_signals'] ?? null ) ? $source_input['human_signals'] : array();
		$image_mode = sanitize_key( (string) ( $source_input['image_mode'] ?? 'featured_only' ) );
		$write_guard_mode = sanitize_key( (string) ( $source_input['write_guard_mode'] ?? 'preserve_manual_edits' ) );
		if ( ! in_array( $write_guard_mode, array( 'preserve_manual_edits', 'workflow_owned' ), true ) ) {
			$write_guard_mode = 'preserve_manual_edits';
		}

		$featured_attached = ! empty( $media['featured_attached'] );
		$position_summary = is_array( $media['position_inline_image_blocks']['summary'] ?? null ) ? $media['position_inline_image_blocks']['summary'] : array();
		$degraded_reasons = array();
		if ( 'none' !== $image_mode && ! $featured_attached ) {
			$degraded_reasons[] = 'featured_media_unavailable';
		}
		if ( 'featured_and_inline' === $image_mode && $this->absint_value( $position_summary['appended_count'] ?? 0 ) > 0 ) {
			$degraded_reasons[] = 'inline_position_partial_fallback';
		}
		if ( ! empty( $review['needs_human_review'] ) ) {
			$degraded_reasons[] = 'quality_review_requires_handoff';
		}
		if ( 'high' === sanitize_key( (string) ( $review['template_risk_level'] ?? '' ) ) ) {
			$degraded_reasons[] = 'template_style_requires_handoff';
		}
		if ( ! empty( $publication_decision['publish_blocked'] ) ) {
			$degraded_reasons[] = 'publication_blocked_by_quality_gate';
		}
		if ( ! empty( $duplicate_guard['skip_recommended'] ) ) {
			$degraded_reasons[] = 'duplicate_production_candidate';
		}

		$media_seo_summary = is_array( $media['seo_summary'] ?? null ) ? $media['seo_summary'] : array();
		$review_signals = is_array( $review['signals'] ?? null ) ? $review['signals'] : array();
		$anti_template_findings = $this->sanitize_limited_text_list( is_array( $review_signals['anti_template_findings'] ?? null ) ? $review_signals['anti_template_findings'] : array(), 3 );
		$style_findings = $this->sanitize_limited_text_list( is_array( $review_signals['style_findings'] ?? null ) ? $review_signals['style_findings'] : array(), 3 );
		$review_summary = array(
			'next_action'            => sanitize_key( (string) ( $review['next_action'] ?? '' ) ),
			'needs_human_review'     => ! empty( $review['needs_human_review'] ),
			'template_risk_level'    => sanitize_key( (string) ( $review['template_risk_level'] ?? '' ) ),
			'platform_profile'       => $platform_profile,
			'writing_naturalness'    => max( 0, min( 100, $this->absint_value( $review['writing_naturalness'] ?? 0 ) ) ),
			'style_match_score'      => max( 0, min( 100, $this->absint_value( $review['style_match_score'] ?? 0 ) ) ),
			'image_relevance_score'  => max( 0, min( 100, $this->absint_value( $review['image_relevance_score'] ?? 0 ) ) ),
			'anti_template_findings' => $anti_template_findings,
			'style_findings'         => $style_findings,
			'ai_risk_review'         => is_array( $review['ai_risk_review'] ?? null ) ? $review['ai_risk_review'] : array(),
		);
		$primary_review_finding = '';
		if ( ! empty( $style_findings ) ) {
			$primary_review_finding = (string) $style_findings[0];
		} elseif ( ! empty( $anti_template_findings ) ) {
			$primary_review_finding = (string) $anti_template_findings[0];
		}

		$completed_stages = array( 'draft_created' );
		if ( ! empty( $draft['seo_meta'] ) ) {
			$completed_stages[] = 'seo_enriched';
		}
		if ( 'none' !== $image_mode ) {
			$completed_stages[] = $featured_attached ? 'featured_media_ready' : 'featured_media_missing';
		}
		if ( 'featured_and_inline' === $image_mode ) {
			$completed_stages[] = $this->absint_value( $position_summary['positioned_count'] ?? 0 ) > 0 ? 'inline_media_positioned' : 'inline_media_pending';
		}
		if ( ! empty( $duplicate_guard['duplicate_found'] ) ) {
			$completed_stages[] = 'duplicate_candidate_detected';
		}
		if ( ! empty( $media_seo_summary ) ) {
			$completed_stages[] = ! empty( $media_seo_summary['applied_count'] ) ? 'media_seo_applied' : 'media_seo_suggested';
		}
		$completed_stages[] = 'quality_review_ready';

		$result_mode = empty( $degraded_reasons ) ? 'full' : 'degraded';
		$production_fingerprint = sanitize_text_field( (string) ( $duplicate_guard['production_fingerprint'] ?? '' ) );
		if ( '' === $production_fingerprint ) {
			$production_fingerprint = $this->build_article_production_fingerprint_value( $source_input );
		}

		$summary_parts = array();
		$summary_parts[] = '结果：' . ( 'full' === $result_mode ? '完整完成' : '降级完成' );
		$next_action = sanitize_key( (string) ( $review['next_action'] ?? 'ready_for_editorial_review' ) );
		if ( 'ready_for_editorial_review' === $next_action ) {
			$summary_parts[] = '下一步进入编辑复核';
		} elseif ( 'needs_layout_review' === $next_action ) {
			$summary_parts[] = '下一步做版式复核';
		} elseif ( 'needs_human_review' === $next_action ) {
			$summary_parts[] = '下一步人工复核';
		}
		if ( ! empty( $duplicate_guard['skip_recommended'] ) ) {
			$summary_parts[] = '检测到可复用旧稿，建议先停在 dry-run';
		}
		if ( ! empty( $publication_decision['publish_blocked'] ) ) {
			$summary_parts[] = '发布门控：' . sanitize_text_field( (string) ( $publication_decision['user_message'] ?? '质量评估要求人工复核，已阻止自动发布。' ) );
		}
		if ( ! empty( $media_seo_summary ) ) {
			$media_seo_parts = array_filter(
				array(
					'媒体 SEO：' . $this->absint_value( $media_seo_summary['asset_count'] ?? 0 ) . ' 张',
					$this->absint_value( $media_seo_summary['vision_fallback_count'] ?? 0 ) > 0 ? '待视觉补强 ' . $this->absint_value( $media_seo_summary['vision_fallback_count'] ?? 0 ) . ' 张' : '',
					$this->absint_value( $media_seo_summary['attribution_persisted_count'] ?? 0 ) > 0 ? '归因已保留 ' . $this->absint_value( $media_seo_summary['attribution_persisted_count'] ?? 0 ) . ' 张' : '',
				)
			);
			if ( ! empty( $media_seo_parts ) ) {
				$summary_parts[] = implode( '，', $media_seo_parts );
			}
		}
		if ( ! empty( $degraded_reasons ) ) {
			$summary_parts[] = '原因：' . implode( '、', array_map( 'sanitize_text_field', $degraded_reasons ) );
		}
		if ( '' !== $primary_review_finding ) {
			$summary_parts[] = '复核提示：' . $primary_review_finding;
		}
		$summary_text = implode( '；', array_filter( $summary_parts ) );
		$content_improvements = array(
			array(
				'type'     => 'production_summary',
				'priority' => 'full' === $result_mode ? 'medium' : 'high',
				'title'    => '按 production 主链检查发布前停点',
				'detail'   => $summary_text,
			),
		);
		if ( ! empty( $duplicate_guard['skip_recommended'] ) ) {
			$content_improvements[] = array(
				'type'     => 'duplicate_guard',
				'priority' => 'high',
				'title'    => '先复核已有候选草稿或已发布文章',
				'detail'   => $this->sanitize_metadata_text( (string) ( $duplicate_guard['summary_text'] ?? '检测到可复用旧稿，建议先停在 review。' ) ),
			);
		}
		if ( ! empty( $publication_decision['publish_blocked'] ) ) {
			$content_improvements[] = array(
				'type'     => 'publication_gate',
				'priority' => 'high',
				'title'    => '发布门控要求人工继续复核',
				'detail'   => $this->sanitize_metadata_text( (string) ( $publication_decision['user_message'] ?? '质量评估要求人工复核，暂不执行自动排期或发布。' ) ),
			);
		}
		$ai_risk_items = is_array( $review_summary['ai_risk_review']['items'] ?? null ) ? $review_summary['ai_risk_review']['items'] : array();
		foreach ( array_slice( $ai_risk_items, 0, 2 ) as $risk_item ) {
			$risk_item = is_array( $risk_item ) ? $risk_item : array();
			$content_improvements[] = array(
				'type'     => 'ai_risk_review',
				'priority' => sanitize_key( (string) ( $risk_item['severity'] ?? 'medium' ) ),
				'title'    => sanitize_text_field( (string) ( $risk_item['title'] ?? 'AI 风险复核' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $risk_item['detail'] ?? '' ) ),
			);
		}

		$source_references = $this->extract_source_references( $human_signals );
		$evidence_notes = $this->extract_ai_risk_notes_by_key( $ai_risk_items, 'evidence_gap' );
		$claim_risk_notes = $this->extract_ai_risk_notes_by_key( $ai_risk_items, 'claim_confidence' );
		$image_attribution_summary = array(
			'provider'        => sanitize_text_field( (string) ( $media['resolved_image_source']['featured']['provider_title'] ?? $media['resolved_image_source']['featured']['provider_hint'] ?? '' ) ),
			'license'         => sanitize_text_field( (string) ( $media['resolved_image_source']['featured']['license'] ?? $media['resolved_image_source']['featured']['license_policy'] ?? '' ) ),
			'source_page_url' => $this->esc_url_value( (string) ( $media['resolved_image_source']['featured']['source_page_url'] ?? '' ) ),
		);

		$seo_improvements = $this->build_recommendation_improvements( $seo_analysis, 'seo_recommendation' );
		if ( empty( $seo_improvements ) ) {
			$seo_improvements[] = array(
				'type'     => 'seo_alignment',
				'priority' => 'medium',
				'title'    => '复核 title、excerpt、SEO metadata 与媒体 SEO',
				'detail'   => 'production 主链会保留 shared SEO output，并在需要时继续补媒体 SEO 建议或写回。',
			);
		}

		$geo_improvements = $this->build_recommendation_improvements( $geo_analysis, 'geo_recommendation' );
		if ( empty( $geo_improvements ) ) {
			$geo_improvements[] = array(
				'type'     => 'answerability',
				'priority' => 'medium',
				'title'    => '补 answer-first summary、FAQ 或可引用段落',
				'detail'   => 'production 主链继续复用 shared GEO output，不额外长第二套页面私有分析器。',
			);
		}

		$handoff = array(
			'stopping_point'    => sanitize_key( (string) ( $publication_decision['effective_publish_mode'] ?? 'review' ) ),
			'next_action'       => $next_action,
			'recommended_entry' => 'workflow/wordpress_article_production',
			'hints'             => array_values(
				array_filter(
					array(
						'draft、SEO/GEO review、媒体结果和 publish gate 都已收口到 production 主链结果里。',
						! empty( $publication_decision['publish_blocked'] ) ? '当前 publish/schedule 已被质量门控转回 review。' : '',
						! empty( $duplicate_guard['skip_recommended'] ) ? '检测到重复候选，建议先复核旧稿再继续。' : '',
						'none' !== $image_mode && ! $featured_attached ? '当前缺少可用 featured media，需人工补图或调整策略。' : '',
					)
				)
			),
		);

		return array(
			'success' => true,
			'data'    => array(
				'article'                  => $article,
				'draft'                    => array(
					'post_id'      => $this->absint_value( $draft['post_id'] ?? 0 ),
					'edit_link'    => $this->esc_url_value( (string) ( $draft['edit_link'] ?? '' ) ),
					'preview_link' => $this->esc_url_value( (string) ( $draft['preview_link'] ?? '' ) ),
					'public_link'  => $this->esc_url_value( (string) ( $draft['public_link'] ?? '' ) ),
					'seo_meta'     => is_array( $draft['seo_meta'] ?? null ) ? $draft['seo_meta'] : array(),
				),
				'media_plan'               => $media,
				'metadata_plan_resolution' => $metadata_plan_resolution,
				'content_improvements'     => $content_improvements,
				'seo_improvements'         => $seo_improvements,
				'geo_improvements'         => $geo_improvements,
				'review'                   => $review_summary,
				'handoff'                  => $handoff,
				'result_mode'              => $result_mode,
				'degraded_reasons'         => array_values( $degraded_reasons ),
				'completed_stages'         => array_values( $completed_stages ),
				'production_fingerprint'   => $production_fingerprint,
				'write_guard_mode'         => $write_guard_mode,
				'skip_recommended'         => ! empty( $duplicate_guard['skip_recommended'] ),
				'duplicate_candidate'      => is_array( $duplicate_guard['duplicate_candidate'] ?? null ) ? $duplicate_guard['duplicate_candidate'] : array(),
				'summary_text'             => $summary_text,
				'review_payload'           => $review,
				'review_summary'           => $review_summary,
				'evidence_notes'           => $evidence_notes,
				'claim_risk_notes'         => $claim_risk_notes,
				'source_references'        => $source_references,
				'image_attribution_summary' => $image_attribution_summary,
				'publication_decision'     => $publication_decision,
				'media_seo_summary'        => $media_seo_summary,
				'preview_link'             => $this->esc_url_value( (string) ( $draft['preview_link'] ?? '' ) ),
				'public_link'              => $this->esc_url_value( (string) ( $draft['public_link'] ?? '' ) ),
				'next_action'              => $next_action,
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Composes one canonical draft workflow result envelope.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function compose_article_draft_result( $input ) {
		$input = is_array( $input ) ? $input : array();
		$source_input = is_array( $input['input'] ?? null ) ? $input['input'] : array();
		$article = is_array( $input['article'] ?? null ) ? $input['article'] : array();
		$draft = is_array( $input['draft'] ?? null ) ? $input['draft'] : array();
		$generated_seo = is_array( $input['generated_seo'] ?? null ) ? $input['generated_seo'] : array();
		$metadata_plan_resolution = is_array( $input['metadata_plan_resolution'] ?? null ) ? $input['metadata_plan_resolution'] : array();
		$seo_analysis = is_array( $input['seo_analysis'] ?? null ) ? $input['seo_analysis'] : array();
		$geo_analysis = is_array( $input['geo_analysis'] ?? null ) ? $input['geo_analysis'] : array();
		$quality_scoring = is_array( $input['quality_scoring'] ?? null ) ? $input['quality_scoring'] : array();
		$ai_slop_detection = is_array( $input['ai_slop_detection'] ?? null ) ? $input['ai_slop_detection'] : array();
		$review = is_array( $input['review'] ?? null ) ? $input['review'] : array();
		$duplicate_guard = is_array( $input['duplicate_guard'] ?? null ) ? $input['duplicate_guard'] : array();
		$preview_only = ! empty( $source_input['preview_only'] );
		$platform_profile = sanitize_key( (string) ( $source_input['platform_profile'] ?? 'generic' ) );
		$human_signals = is_array( $source_input['human_signals'] ?? null ) ? $source_input['human_signals'] : array();

		$content_improvements = array(
			array(
				'type'     => $preview_only ? 'draft_preview_handoff' : 'draft_handoff',
				'priority' => ! empty( $review['needs_human_review'] ) ? 'high' : 'medium',
				'title'    => $preview_only ? '先停在 draft preview，再决定是否创建真实草稿' : '先停在 draft workbench，再进入人工编辑',
				'detail'   => $preview_only
					? 'draft workflow 当前可承接新文建议 preview，不创建真实草稿，先返回 metadata handoff 和轻量 review。'
					: 'draft workflow 默认只收口到本地草稿、metadata handoff 和轻量 review，不直接扩成 production / publish 主链。',
			),
		);
		if ( ! empty( $duplicate_guard['skip_recommended'] ) ) {
			$content_improvements[] = array(
				'type'     => 'duplicate_guard',
				'priority' => 'high',
				'title'    => '先检查已有候选草稿',
				'detail'   => $this->sanitize_metadata_text( (string) ( $duplicate_guard['summary_text'] ?? '检测到潜在重复生产候选，建议先停在 review。' ) ),
			);
		}

		$seo_improvements = $this->build_recommendation_improvements( $seo_analysis, 'seo_recommendation' );
		$seo_overall_score = isset( $seo_analysis['overall_score'] ) ? $this->absint_value( $seo_analysis['overall_score'] ) : null;
		$seo_meta_suggestions = is_array( $seo_analysis['meta_suggestions'] ?? null ) ? $seo_analysis['meta_suggestions'] : array();
		$seo_internal_links = is_array( $seo_analysis['internal_link_suggestions'] ?? null ) ? $seo_analysis['internal_link_suggestions'] : array();
		if ( null !== $seo_overall_score ) {
			array_unshift(
				$seo_improvements,
				array(
					'type'     => 'seo_score',
					'priority' => $seo_overall_score < 60 ? 'high' : ( $seo_overall_score < 80 ? 'medium' : 'low' ),
					'title'    => sprintf( 'SEO 综合评分：%d / 100', $seo_overall_score ),
					'detail'   => $seo_overall_score >= 80
						? 'SEO 综合评分良好，建议保持当前优化水平。'
						: 'SEO 综合评分偏低，建议关注关键词密度、标题层级和内部链接。',
				)
			);
		}
		foreach ( $seo_internal_links as $link ) {
			$link = is_array( $link ) ? $link : array();
			$seo_improvements[] = array(
				'type'     => 'internal_link',
				'priority' => 'medium',
				'title'    => sanitize_text_field( (string) ( $link['anchor_text'] ?? ( $link['target_search'] ?? '内链建议' ) ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $link['note'] ?? '' ) ),
			);
		}
		if ( ! empty( $seo_meta_suggestions ) ) {
			$meta_title = sanitize_text_field( (string) ( $seo_meta_suggestions['seo_title'] ?? '' ) );
			$meta_desc = $this->sanitize_metadata_text( (string) ( $seo_meta_suggestions['seo_description'] ?? '' ) );
			if ( '' !== $meta_title || '' !== $meta_desc ) {
				$seo_improvements[] = array(
					'type'     => 'seo_meta',
					'priority' => 'medium',
					'title'    => 'SEO 元数据建议',
					'detail'   => trim( $meta_title . ' / ' . $meta_desc, ' /' ),
				);
			}
		}
		if ( empty( $seo_improvements ) ) {
			$seo_improvements[] = array(
				'type'     => 'seo_alignment',
				'priority' => 'medium',
				'title'    => '校对 title、excerpt 与 SEO metadata',
				'detail'   => 'draft 主线默认输出 generated SEO metadata，并保留后续编辑校对停点。',
			);
		}

		$geo_improvements = $this->build_recommendation_improvements( $geo_analysis, 'geo_recommendation' );
		$geo_score = isset( $geo_analysis['geo_score'] ) ? $geo_analysis['geo_score'] : null;
		$ai_summary_block = is_array( $geo_analysis['ai_summary_block'] ?? null ) ? $geo_analysis['ai_summary_block'] : null;
		$faq_page_schema = is_array( $geo_analysis['faq_page_schema'] ?? null ) ? $geo_analysis['faq_page_schema'] : null;
		$citation_annotations = is_array( $geo_analysis['citation_annotations'] ?? null ) ? $geo_analysis['citation_annotations'] : array();
		if ( null !== $geo_score && is_numeric( $geo_score ) ) {
			$geo_score_int = $this->absint_value( $geo_score );
			$geo_improvements[] = array(
				'type'     => 'geo_score',
				'priority' => $geo_score_int < 60 ? 'high' : ( $geo_score_int < 80 ? 'medium' : 'low' ),
				'title'    => sprintf( 'GEO 优化评分：%d / 100', $geo_score_int ),
				'detail'   => $geo_score_int >= 80
					? 'GEO 优化评分良好，AI 引擎可发现性和引用结构较完整。'
					: 'GEO 优化评分偏低，建议补 answer-first 摘要和 FAQ Schema。',
			);
		}
		if ( ! empty( $citation_annotations ) ) {
			$citation_count = count( $citation_annotations );
			$geo_improvements[] = array(
				'type'     => 'citation_coverage',
				'priority' => 'medium',
				'title'    => sprintf( '引用标注：%d 处', $citation_count ),
				'detail'   => $citation_count > 0
					? 'GEO 分析已标注引用来源，建议在编辑时保留关键引用。'
					: '未检测到引用标注，建议补充来源引用以提高 GEO 评分。',
			);
		}
		if ( ! empty( $faq_page_schema ) ) {
			$geo_improvements[] = array(
				'type'     => 'faq_schema',
				'priority' => 'medium',
				'title'    => 'FAQ Schema 已生成',
				'detail'   => 'GEO 分析已生成 FAQPage Schema.org 结构化数据，可随草稿一起发布。',
			);
		}
		if ( ! empty( $ai_summary_block ) ) {
			$geo_improvements[] = array(
				'type'     => 'ai_summary_block',
				'priority' => 'medium',
				'title'    => 'AI 摘要块已生成',
				'detail'   => 'GEO 分析已生成 AI 引擎可直接引用的摘要块。',
			);
		}
		if ( empty( $geo_improvements ) ) {
			$geo_improvements[] = array(
				'type'     => 'answerability',
				'priority' => 'medium',
				'title'    => '补 answer-first summary 或 FAQ',
				'detail'   => 'GEO 改进默认作为 shared output 返回，不单独长成第二套 workflow。',
			);
		}

		$quality_overall = isset( $quality_scoring['overall_score'] ) ? $quality_scoring['overall_score'] : null;
		$quality_breakdown = is_array( $quality_scoring['breakdown'] ?? null ) ? $quality_scoring['breakdown'] : array();
		$quality_suggestions = is_array( $quality_scoring['improvement_suggestions'] ?? null ) ? $quality_scoring['improvement_suggestions'] : array();
		if ( null !== $quality_overall && is_numeric( $quality_overall ) ) {
			$quality_overall_int = $this->absint_value( $quality_overall );
			$content_improvements[] = array(
				'type'     => 'quality_score',
				'priority' => $quality_overall_int < 60 ? 'high' : ( $quality_overall_int < 80 ? 'medium' : 'low' ),
				'title'    => sprintf( '内容质量评分：%d / 100', $quality_overall_int ),
				'detail'   => $quality_overall_int >= 80
					? '内容质量评分良好。'
					: '内容质量评分偏低，建议关注主题相关性、结构、语言流畅度和平台适配性。',
			);
		}
		foreach ( array_slice( $quality_suggestions, 0, 3 ) as $suggestion ) {
			$suggestion = $this->sanitize_metadata_text( (string) $suggestion );
			if ( '' !== $suggestion ) {
				$content_improvements[] = array(
					'type'     => 'quality_suggestion',
					'priority' => 'medium',
					'title'    => $this->truncate_utf8_text( $suggestion, 40 ),
					'detail'   => $suggestion,
				);
			}
		}

		$editorial_feedback = is_array( $quality_scoring['editorial_feedback'] ?? null ) ? $quality_scoring['editorial_feedback'] : array();
		foreach ( array_slice( $editorial_feedback, 0, 5 ) as $fb ) {
			$fb = is_array( $fb ) ? $fb : array();
			$location = sanitize_text_field( (string) ( $fb['location'] ?? '' ) );
			$issue = sanitize_text_field( (string) ( $fb['issue'] ?? '' ) );
			$suggestion = $this->sanitize_metadata_text( (string) ( $fb['suggestion'] ?? '' ) );
			if ( '' !== $location && '' !== $issue ) {
				$title = sprintf( '%s：%s', $location, $issue );
				$content_improvements[] = array(
					'type'     => 'editorial_feedback',
					'priority' => 'high',
					'title'    => $this->truncate_utf8_text( $title, 50 ),
					'detail'   => '' !== $suggestion ? $suggestion : $issue,
				);
			}
		}

		$ai_slop_flags = is_array( $ai_slop_detection['ai_slop_flags'] ?? null ) ? $ai_slop_detection['ai_slop_flags'] : array();
		$slop_stats = is_array( $ai_slop_detection['stats'] ?? null ) ? $ai_slop_detection['stats'] : array();
		$high_ai_slop_flags = $this->filter_high_ai_slop_flags( $ai_slop_flags );
		if ( ! empty( $ai_slop_flags ) ) {
			$content_improvements[] = array(
				'type'     => 'ai_slop_detection',
				'priority' => ! empty( $high_ai_slop_flags ) ? 'high' : 'medium',
				'title'    => sprintf( 'AI 痕迹检测：%d 处标记', count( $ai_slop_flags ) ),
				'detail'   => ! empty( $high_ai_slop_flags )
					? sprintf( '检测到 %d 处高优先级 AI 典型表达，建议重点润色。', count( $high_ai_slop_flags ) )
					: '检测到少量 AI 典型表达，建议编辑时留意并润色。',
			);
			foreach ( array_slice( $ai_slop_flags, 0, 3 ) as $flag ) {
				$flag = is_array( $flag ) ? $flag : array();
				$severity = sanitize_key( (string) ( $flag['severity'] ?? 'medium' ) );
				$content_improvements[] = array(
					'type'     => 'ai_slop_flag',
					'priority' => in_array( $severity, array( 'high', 'critical' ), true ) ? 'high' : 'medium',
					'title'    => sanitize_text_field( (string) ( $flag['phrase'] ?? ( $flag['category'] ?? 'AI 典型表达' ) ) ),
					'detail'   => $this->sanitize_metadata_text( (string) ( $flag['suggestion'] ?? ( $flag['category'] ?? '' ) ) ),
				);
			}
		}

		$next_action = 'editorial_review';
		$recommended_entry = 'workflow/wordpress_article_draft';
		$publish_mode = sanitize_key( (string) ( $source_input['publish_mode'] ?? '' ) );
		$image_mode = sanitize_key( (string) ( $source_input['image_mode'] ?? 'none' ) );
		if ( ! empty( $duplicate_guard['skip_recommended'] ) ) {
			$next_action = 'review_existing_candidate';
		} elseif ( in_array( $publish_mode, array( 'publish', 'review', 'schedule' ), true ) || 'none' !== $image_mode ) {
			$next_action = 'handoff_to_wordpress_article_production';
			$recommended_entry = 'workflow/wordpress_article_production';
		}
		if ( $preview_only && 'workflow/wordpress_article_draft' === $recommended_entry ) {
			$next_action = 'review_preview';
		}

		$handoff_hints = array(
			'stopping_point'    => $preview_only ? 'draft_preview' : 'draft_created',
			'next_action'       => $next_action,
			'recommended_entry' => $recommended_entry,
			'hints'             => array_values(
				array_filter(
					array(
						$preview_only
							? 'draft workflow 当前在 preview 路径里返回 draft content、metadata resolution 和 shared SEO/GEO review，但不创建真实 draft。'
							: 'draft workflow 负责 draft content、metadata resolution 和 shared SEO/GEO review。',
						'需要特色图、inline 图片、schedule 或 publish handoff 时，再进入 workflow/wordpress_article_production。',
						! empty( $review['needs_human_review'] ) ? '当前轻量 review 已提示需要人工继续润色。' : '',
					)
				)
			),
		);

		$review_summary = array(
			'writing_naturalness' => max( 0, min( 100, $this->absint_value( $review['writing_naturalness'] ?? 0 ) ) ),
			'style_match_score'   => max( 0, min( 100, $this->absint_value( $review['style_match_score'] ?? 0 ) ) ),
			'template_risk_level' => sanitize_key( (string) ( $review['template_risk_level'] ?? '' ) ),
			'needs_human_review'  => ! empty( $review['needs_human_review'] ),
			'next_action'         => sanitize_key( (string) ( $review['next_action'] ?? '' ) ),
			'focus_keyword'       => sanitize_text_field( (string) ( $source_input['topic'] ?? '' ) ),
			'platform_profile'    => $platform_profile,
			'ai_risk_review'      => is_array( $review['ai_risk_review'] ?? null ) ? $review['ai_risk_review'] : array(),
		);
		$ai_risk_items = is_array( $review_summary['ai_risk_review']['items'] ?? null ) ? $review_summary['ai_risk_review']['items'] : array();
		foreach ( array_slice( $ai_risk_items, 0, 2 ) as $risk_item ) {
			$risk_item = is_array( $risk_item ) ? $risk_item : array();
			$content_improvements[] = array(
				'type'     => 'ai_risk_review',
				'priority' => sanitize_key( (string) ( $risk_item['severity'] ?? 'medium' ) ),
				'title'    => sanitize_text_field( (string) ( $risk_item['title'] ?? 'AI 风险复核' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $risk_item['detail'] ?? '' ) ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'article'                  => $article,
				'draft'                    => array(
					'post_id'            => $this->absint_value( $draft['post_id'] ?? 0 ),
					'edit_link'          => $this->esc_url_value( (string) ( $draft['edit_link'] ?? '' ) ),
					'preview_link'       => $this->esc_url_value( (string) ( $draft['preview_link'] ?? '' ) ),
					'seo_meta'           => $generated_seo,
					'preview_only'       => $preview_only,
					'real_draft_created' => ! $preview_only && $this->absint_value( $draft['post_id'] ?? 0 ) > 0,
				),
				'metadata_plan_resolution' => $metadata_plan_resolution,
				'content_improvements'     => $content_improvements,
				'seo_improvements'         => $seo_improvements,
				'geo_improvements'         => $geo_improvements,
				'quality_scoring'          => array(
					'overall_score'          => $quality_overall,
					'breakdown'              => $quality_breakdown,
					'improvement_suggestions' => $quality_suggestions,
				),
				'ai_slop_detection'        => array(
					'flags_count'         => count( $ai_slop_flags ),
					'high_priority_count' => count( $high_ai_slop_flags ),
					'stats'               => $slop_stats,
				),
				'review'                   => $review_summary,
				'evidence_notes'           => $this->extract_ai_risk_notes_by_key( $ai_risk_items, 'evidence_gap' ),
				'claim_risk_notes'         => $this->extract_ai_risk_notes_by_key( $ai_risk_items, 'claim_confidence' ),
				'source_references'        => $this->extract_source_references( $human_signals ),
				'handoff'                  => $handoff_hints,
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Builds one merged article style profile from baseline, references, and explicit hints.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_style_profile( $input ) {
		$input = is_array( $input ) ? $input : array();
		$reference_profile = is_array( $input['reference_profile'] ?? null ) ? $input['reference_profile'] : array();
		$baseline_profile = is_array( $input['baseline_profile'] ?? null ) ? $input['baseline_profile'] : array();
		$voice_profile = sanitize_text_field( (string) ( $input['voice_profile'] ?? '' ) );
		$opening_style = sanitize_text_field( (string) ( $input['opening_style'] ?? '' ) );
		$structure_style = sanitize_text_field( (string) ( $input['structure_style'] ?? '' ) );

		$resolved_voice = '' !== $voice_profile
			? $voice_profile
			: sanitize_key( (string) ( $reference_profile['dominant_voice_profile'] ?? $baseline_profile['dominant_voice_profile'] ?? '' ) );
		$resolved_opening = '' !== $opening_style
			? $opening_style
			: sanitize_key( (string) ( $reference_profile['dominant_opening_style'] ?? $baseline_profile['dominant_opening_style'] ?? '' ) );
		$resolved_structure = '' !== $structure_style
			? $structure_style
			: sanitize_text_field( (string) ( $reference_profile['structure_style'] ?? $baseline_profile['structure_style'] ?? '' ) );

		$style_brief_parts = array();
		foreach ( array( $baseline_profile['style_brief'] ?? '', $reference_profile['style_brief'] ?? '' ) as $brief_part ) {
			$brief_part = $this->sanitize_metadata_text( (string) $brief_part );
			if ( '' !== $brief_part && ! in_array( $brief_part, $style_brief_parts, true ) ) {
				$style_brief_parts[] = $brief_part;
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'profile' => array(
					'resolved_voice_profile'     => $resolved_voice,
					'resolved_opening_style'     => $resolved_opening,
					'resolved_structure_style'   => $resolved_structure,
					'baseline_profile'           => $baseline_profile,
					'reference_profile'          => $reference_profile,
					'style_brief'                => implode( '; ', $style_brief_parts ),
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
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
	 * Gets an agent-ready context bundle for one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'post_id is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$include_content = ! array_key_exists( 'include_content', $input ) || ! empty( $input['include_content'] );
		$include_blocks = ! array_key_exists( 'include_blocks', $input ) || ! empty( $input['include_blocks'] );
		$include_terms = ! array_key_exists( 'include_terms', $input ) || ! empty( $input['include_terms'] );
		$include_media = ! array_key_exists( 'include_media', $input ) || ! empty( $input['include_media'] );
		$include_revisions = ! empty( $input['include_revisions'] );
		$include_meta = ! empty( $input['include_meta'] );

		$content = (string) ( $post->post_content ?? '' );
		$plain_text = $this->strip_all_tags_value( $content );
		$parsed_blocks = $include_blocks && function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		$blocks = $include_blocks ? $this->normalize_block_tree( is_array( $parsed_blocks ) ? $parsed_blocks : array(), true ) : array();
		if ( $include_blocks && empty( $blocks ) && '' !== trim( $content ) ) {
			$blocks[] = array(
				'blockName'   => 'core/freeform',
				'attrs'       => array(),
				'innerHTML'   => $content,
				'innerBlocks' => array(),
			);
		}

		$author_id = $this->absint_value( $post->post_author ?? 0 );
		$author_name = '';
		if ( $author_id > 0 && function_exists( 'get_the_author_meta' ) ) {
			$author_name = sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) );
		}

		$data = array(
			'post'  => array(
				'id'         => $post_id,
				'title'      => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'post_type'  => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'slug'       => $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) ),
				'author_id'  => $author_id,
				'author'     => $author_name,
				'excerpt'    => sanitize_textarea_field( (string) ( $post->post_excerpt ?? '' ) ),
				'date'       => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
				'modified'   => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
				'permalink'  => function_exists( 'get_permalink' ) ? $this->esc_url_value( (string) get_permalink( $post_id ) ) : '',
				'edit_link'  => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
				'content'    => $include_content ? $content : '',
				'text_excerpt' => $this->trim_words_value( $plain_text, 80 ),
			),
			'stats' => array(
				'content_length'        => strlen( $content ),
				'plain_text_length'     => $this->strlen_value( $plain_text ),
				'word_count'            => str_word_count( $plain_text ),
				'image_count'           => substr_count( strtolower( $content ), '<img' ),
				'block_count'           => count( $blocks ),
				'reading_time_minutes'  => max( 1, (int) ceil( max( 1, str_word_count( $plain_text ) ) / 200 ) ),
			),
		);

		if ( $include_terms ) {
			$data['terms'] = $this->collect_post_terms_context( $post_id, sanitize_key( (string) ( $post->post_type ?? '' ) ) );
		}
		if ( $include_media ) {
			$data['media'] = $this->collect_post_media_context( $post_id );
		}
		if ( $include_blocks ) {
			$data['blocks'] = $blocks;
		}
		if ( $include_revisions ) {
			$revisions = $this->list_post_revisions(
				array(
					'post_id'  => $post_id,
					'per_page' => 5,
					'page'     => 1,
				)
			);
			$data['revisions'] = is_array( $revisions ) ? array_values( $revisions['items'] ?? array() ) : array();
		}
		if ( $include_meta ) {
			$data['meta'] = $this->get_scoped_post_meta( $post_id, $input['meta_keys'] ?? array() );
		}

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'            => 'local_post_context',
				'execution_mode'    => 'deterministic',
				'included_sections' => array_values(
					array_filter(
						array(
							'post',
							'stats',
							$include_terms ? 'terms' : '',
							$include_media ? 'media' : '',
							$include_blocks ? 'blocks' : '',
							$include_revisions ? 'revisions' : '',
							$include_meta ? 'meta' : '',
						)
					)
				),
			),
			'Post context loaded.'
		);
	}

	/**
	 * Builds a deterministic pre-publish checklist for one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_content_publishing_checklist( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'post_id is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$target_status = sanitize_key( (string) ( $input['target_status'] ?? 'publish' ) );
		if ( ! in_array( $target_status, array( 'publish', 'future', 'draft' ), true ) ) {
			$target_status = 'publish';
		}

		$title = sanitize_text_field( (string) get_the_title( $post_id ) );
		$content = (string) ( $post->post_content ?? '' );
		$plain_text = $this->strip_all_tags_value( $content );
		$excerpt = sanitize_textarea_field( (string) ( $post->post_excerpt ?? '' ) );
		$slug = $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) );
		$post_type = sanitize_key( (string) ( $post->post_type ?? '' ) );
		$author_id = $this->absint_value( $post->post_author ?? 0 );
		$seo_provider = $this->detect_seo_provider();
		$seo_meta_keys = $this->seo_meta_keys( $seo_provider );
		$seo_title = function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $post_id, (string) ( $seo_meta_keys['title'] ?? '' ), true ) ) : '';
		$seo_description = function_exists( 'get_post_meta' ) ? $this->sanitize_metadata_text( (string) get_post_meta( $post_id, (string) ( $seo_meta_keys['description'] ?? '' ), true ) ) : '';
		$featured_media_id = function_exists( 'get_post_thumbnail_id' ) ? $this->absint_value( get_post_thumbnail_id( $post_id ) ) : 0;
		$category_names = function_exists( 'get_the_terms' ) ? $this->format_term_name_list( get_the_terms( $post_id, 'category' ) ) : array();

		$checks = array();
		$this->append_publishing_check( $checks, 'title', '' !== $title, 'fail', __( 'Title is present.', 'magick-ai-abilities' ), __( 'Add a clear post title before publishing.', 'magick-ai-abilities' ) );
		$this->append_publishing_check( $checks, 'content', $this->strlen_value( $plain_text ) >= 120, 'fail', __( 'Body content has enough text for review.', 'magick-ai-abilities' ), __( 'Add more body content before publishing.', 'magick-ai-abilities' ) );
		$this->append_publishing_check( $checks, 'slug', '' !== $slug, 'fail', __( 'Slug is set.', 'magick-ai-abilities' ), __( 'Set a stable URL slug before publishing.', 'magick-ai-abilities' ) );
		$this->append_publishing_check( $checks, 'author', $author_id > 0, 'fail', __( 'Author is assigned.', 'magick-ai-abilities' ), __( 'Assign an author before publishing.', 'magick-ai-abilities' ) );

		if ( 'post' === $post_type && function_exists( 'get_the_terms' ) ) {
			$this->append_publishing_check( $checks, 'categories', ! empty( $category_names ), 'fail', __( 'At least one category is assigned.', 'magick-ai-abilities' ), __( 'Assign at least one category before publishing.', 'magick-ai-abilities' ) );
		} elseif ( 'post' === $post_type ) {
			$this->append_publishing_check( $checks, 'categories', false, 'warning', __( 'Category lookup is available.', 'magick-ai-abilities' ), __( 'Category lookup is unavailable in this runtime; verify categories in WordPress.', 'magick-ai-abilities' ) );
		}

		$this->append_publishing_check( $checks, 'excerpt', '' !== $excerpt, 'warning', __( 'Excerpt is present.', 'magick-ai-abilities' ), __( 'Add an excerpt for archive and social previews.', 'magick-ai-abilities' ) );
		$this->append_publishing_check( $checks, 'featured_media', $featured_media_id > 0, 'warning', __( 'Featured image is assigned.', 'magick-ai-abilities' ), __( 'Add a featured image when the layout or channel expects one.', 'magick-ai-abilities' ) );
		$this->append_publishing_check( $checks, 'seo_title', '' !== $seo_title, 'warning', __( 'SEO title metadata is present.', 'magick-ai-abilities' ), __( 'Add SEO title metadata if the site SEO plugin uses it.', 'magick-ai-abilities' ) );
		$this->append_publishing_check( $checks, 'seo_description', '' !== $seo_description, 'warning', __( 'SEO description metadata is present.', 'magick-ai-abilities' ), __( 'Add SEO description metadata for search and share snippets.', 'magick-ai-abilities' ) );

		if ( 'future' === $target_status ) {
			$future_date = strtotime( (string) ( $post->post_date ?? '' ) );
			$this->append_publishing_check( $checks, 'schedule_date', false !== $future_date && $future_date > time(), 'fail', __( 'Scheduled publish date is in the future.', 'magick-ai-abilities' ), __( 'Set a future publish date before scheduling.', 'magick-ai-abilities' ) );
		}

		$missing = array();
		$warnings = array();
		foreach ( $checks as $check ) {
			$key = sanitize_key( (string) ( $check['key'] ?? '' ) );
			$status = sanitize_key( (string) ( $check['status'] ?? '' ) );
			$severity = sanitize_key( (string) ( $check['severity'] ?? '' ) );
			if ( 'fail' === $status && '' !== $key ) {
				$missing[] = $key;
			}
			if ( 'warning' === $status && 'warning' === $severity && '' !== $key ) {
				$warnings[] = $key;
			}
		}

		return $this->build_analysis_success_response(
			array(
				'ready'         => empty( $missing ),
				'post_id'       => $post_id,
				'post_type'     => $post_type,
				'current_status' => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'target_status' => $target_status,
				'checks'        => $checks,
				'missing'       => $missing,
				'warnings'      => $warnings,
				'summary'       => array(
					'check_count'       => count( $checks ),
					'fail_count'        => count( $missing ),
					'warning_count'     => count( $warnings ),
					'seo_provider'      => $seo_provider,
					'word_count'        => str_word_count( $plain_text ),
					'featured_media_id' => $featured_media_id,
					'categories'        => $category_names,
				),
			),
			array(
				'source'         => 'local_content_publishing_checklist',
				'execution_mode' => 'deterministic',
			),
			'Content publishing checklist built.'
		);
	}

	/**
	 * Builds an inventory health report for a bounded content set.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_content_inventory_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read content inventory.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		if ( '' === $post_type ) {
			$post_type = 'post';
		}
		if ( function_exists( 'post_type_exists' ) && ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_type_invalid', __( 'Post type does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$status = sanitize_key( (string) ( $input['status'] ?? 'any' ) );
		if ( '' === $status ) {
			$status = 'any';
		}
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$stale_days = max( 30, min( 3650, $this->absint_value( $input['stale_days'] ?? 365 ) ) );
		$target_status = sanitize_key( (string) ( $input['target_status'] ?? 'publish' ) );
		if ( ! in_array( $target_status, array( 'publish', 'future', 'draft' ), true ) ) {
			$target_status = 'publish';
		}

		$cache_key = $this->build_read_cache_key(
			'content_inventory_health',
			array(
				'post_type'     => $post_type,
				'status'        => $status,
				'per_page'      => $per_page,
				'page'          => $page,
				'stale_days'    => $stale_days,
				'target_status' => $target_status,
			)
		);
		$cached = $this->get_cached_read_response( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$query_result = $this->query_inventory_posts( $post_type, $status, $per_page, $page );
		$post_ids = is_array( $query_result['post_ids'] ?? null ) ? $query_result['post_ids'] : array();
		$total = (int) ( $query_result['total'] ?? count( $post_ids ) );
		$issue_counts = array();
		$items = array();
		$now = time();
		$day_in_seconds = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;
		$stale_cutoff = $now - ( $stale_days * $day_in_seconds );

		foreach ( $post_ids as $post_id ) {
			$post_id = $this->absint_value( $post_id );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			$checklist = $this->get_content_publishing_checklist(
				array(
					'post_id'       => $post_id,
					'target_status' => $target_status,
				)
			);
			$check_data = is_array( $checklist ) && is_array( $checklist['data'] ?? null ) ? $checklist['data'] : array();
			$missing = is_array( $check_data['missing'] ?? null ) ? $check_data['missing'] : array();
			$warnings = is_array( $check_data['warnings'] ?? null ) ? $check_data['warnings'] : array();
			$issues = array_values( array_unique( array_merge( $missing, $warnings ) ) );

			$modified_ts = strtotime( (string) ( $post->post_modified ?? $post->post_date ?? '' ) );
			if ( false !== $modified_ts && $modified_ts > 0 && $modified_ts < $stale_cutoff ) {
				$issues[] = 'stale_content';
			}

			foreach ( $issues as $issue ) {
				$issue = sanitize_key( (string) $issue );
				if ( '' === $issue ) {
					continue;
				}
				$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
			}

			$items[] = array(
				'post_id'       => $post_id,
				'title'         => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'post_type'     => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'status'        => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'modified'      => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
				'ready'         => empty( $missing ),
				'issue_count'   => count( $issues ),
				'issues'        => $issues,
				'edit_link'     => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
			);
		}

		$total_issue_instances = array_sum( array_map( 'intval', $issue_counts ) );
		$health_score = 100;
		if ( count( $items ) > 0 ) {
			$health_score = max( 0, 100 - min( 100, (int) round( ( $total_issue_instances / max( 1, count( $items ) ) ) * 12 ) ) );
		}

		arsort( $issue_counts );

		$result = $this->build_analysis_success_response(
			array(
				'total'        => $total,
				'page'         => $page,
				'per_page'     => $per_page,
				'health_score' => $health_score,
				'issue_counts' => $issue_counts,
				'items'        => $items,
				'summary'      => array(
					'scanned_count'          => count( $items ),
					'posts_with_issues'      => count(
						array_filter(
							$items,
							static function ( array $item ) {
								return (int) ( $item['issue_count'] ?? 0 ) > 0;
							}
						)
					),
					'total_issue_instances' => $total_issue_instances,
					'post_type'             => $post_type,
					'status'                => $status,
					'stale_days'            => $stale_days,
				),
			),
			array(
				'source'         => 'local_content_inventory_health',
				'execution_mode' => 'deterministic',
			),
			'Content inventory health report built.'
		);
		$this->set_cached_read_response( $cache_key, $result, 'content_inventory_health' );
		return $result;
	}

	/**
	 * Runs publishing checklists for a bounded list of posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_bulk_publishing_checklist( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_ids = is_array( $input['post_ids'] ?? null ) ? $input['post_ids'] : array();
		$post_ids = array_values(
			array_unique(
				array_filter(
					array_map( array( $this, 'absint_value' ), $post_ids )
				)
			)
		);
		$post_ids = array_slice( $post_ids, 0, 50 );
		if ( empty( $post_ids ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_ids_required', __( 'At least one post id is required.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$target_status = sanitize_key( (string) ( $input['target_status'] ?? 'publish' ) );
		if ( ! in_array( $target_status, array( 'publish', 'future', 'draft' ), true ) ) {
			$target_status = 'publish';
		}

		$items = array();
		$ready_count = 0;
		$blocked_count = 0;
		$warning_count = 0;
		$issue_counts = array();

		foreach ( $post_ids as $post_id ) {
			$checklist = $this->get_content_publishing_checklist(
				array(
					'post_id'       => $post_id,
					'target_status' => $target_status,
				)
			);
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $checklist ) ) {
				$items[] = array(
					'post_id' => $post_id,
					'ready'   => false,
					'error'   => sanitize_text_field( (string) $checklist->get_error_message() ),
				);
				++$blocked_count;
				continue;
			}

			$data = is_array( $checklist['data'] ?? null ) ? $checklist['data'] : array();
			$missing = is_array( $data['missing'] ?? null ) ? $data['missing'] : array();
			$warnings = is_array( $data['warnings'] ?? null ) ? $data['warnings'] : array();
			foreach ( array_merge( $missing, $warnings ) as $issue ) {
				$issue = sanitize_key( (string) $issue );
				if ( '' !== $issue ) {
					$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
				}
			}

			$ready = ! empty( $data['ready'] );
			if ( $ready ) {
				++$ready_count;
			} else {
				++$blocked_count;
			}
			if ( ! empty( $warnings ) ) {
				++$warning_count;
			}

			$items[] = array(
				'post_id'        => $post_id,
				'post_type'      => sanitize_key( (string) ( $data['post_type'] ?? '' ) ),
				'current_status' => sanitize_key( (string) ( $data['current_status'] ?? '' ) ),
				'target_status'  => $target_status,
				'ready'          => $ready,
				'missing'        => $missing,
				'warnings'       => $warnings,
				'summary'        => is_array( $data['summary'] ?? null ) ? $data['summary'] : array(),
			);
		}

		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'         => count( $post_ids ),
				'ready_count'   => $ready_count,
				'blocked_count' => $blocked_count,
				'warning_count' => $warning_count,
				'items'         => $items,
				'summary'       => array(
					'target_status' => $target_status,
					'issue_counts'  => $issue_counts,
				),
			),
			array(
				'source'         => 'local_bulk_publishing_checklist',
				'execution_mode' => 'deterministic',
			),
			'Bulk publishing checklist built.'
		);
	}

	/**
	 * Builds a post-id based internal-link opportunity report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_internal_link_opportunity_report( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'post_id is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$max_targets = max( 1, min( 10, $this->absint_value( $input['max_targets'] ?? 5 ) ) );
		$title = sanitize_text_field( (string) get_the_title( $post_id ) );
		$content = (string) ( $post->post_content ?? '' );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		if ( '' === $focus_keyword ) {
			$focus_keyword = $this->guess_focus_keyword_from_post( $post_id, $title );
		}
		$terms = $this->collect_focus_terms( $title, $focus_keyword, array() );
		$targets = $this->collect_internal_link_targets_for_post( $post_id, $terms, $max_targets );
		$placement_plan = array();

		foreach ( $targets as $target ) {
			$anchor = sanitize_text_field( (string) ( $target['anchor_text'] ?? '' ) );
			$placement_plan[] = array(
				'target_post_id' => $this->absint_value( $target['post_id'] ?? 0 ),
				'target_url'     => $this->esc_url_value( (string) ( $target['url'] ?? '' ) ),
				'anchor_text'    => $anchor,
				'placement_hint' => __( 'Place one contextual internal link after the first paragraph that introduces this concept.', 'magick-ai-abilities' ),
				'reason'         => $this->sanitize_metadata_text( (string) ( $target['reason'] ?? '' ) ),
			);
		}

		return $this->build_analysis_success_response(
			array(
				'source_post'    => array(
					'post_id'      => $post_id,
					'title'        => $title,
					'post_type'    => sanitize_key( (string) ( $post->post_type ?? '' ) ),
					'status'       => sanitize_key( (string) ( $post->post_status ?? '' ) ),
					'focus_keyword' => $focus_keyword,
					'edit_link'    => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
				),
				'targets'        => $targets,
				'placement_plan' => $placement_plan,
				'summary'        => array(
					'candidate_count' => count( $targets ),
					'placement_count' => count( $placement_plan ),
					'focus_terms'     => $terms,
					'content_length'  => $this->strlen_value( $this->strip_all_tags_value( $content ) ),
				),
			),
			array(
				'source'         => 'local_internal_link_opportunity_report',
				'execution_mode' => 'deterministic',
			),
			'Internal link opportunity report built.'
		);
	}

	/**
	 * Builds a bounded site operations dashboard.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_site_operations_dashboard( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read site operations.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		if ( '' === $post_type ) {
			$post_type = 'post';
		}
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$stale_days = max( 30, min( 3650, $this->absint_value( $input['stale_days'] ?? 365 ) ) );
		$status_counts = array();
		foreach ( array( 'publish', 'draft', 'pending', 'future', 'private' ) as $status ) {
			$status_counts[ $status ] = (int) ( $this->query_inventory_posts( $post_type, $status, 1, 1 )['total'] ?? 0 );
		}

		$inventory = $this->get_content_inventory_health(
			array(
				'post_type'  => $post_type,
				'status'     => 'any',
				'per_page'   => $per_page,
				'page'       => 1,
				'stale_days' => $stale_days,
			)
		);
		$taxonomy = $this->get_taxonomy_inventory_health(
			array(
				'taxonomy' => 'category',
				'per_page' => min( 50, $per_page ),
			)
		);
		$media = $this->get_media_inventory_health(
			array(
				'mime_type' => 'image',
				'per_page'  => min( 50, $per_page ),
			)
		);
		$comment_count = $this->count_comments_for_status( 'hold' );

		$inventory_data = is_array( $inventory['data'] ?? null ) ? $inventory['data'] : array();
		$taxonomy_data = is_array( $taxonomy['data'] ?? null ) ? $taxonomy['data'] : array();
		$media_data = is_array( $media['data'] ?? null ) ? $media['data'] : array();
		$attention_items = array();
		foreach ( array_slice( is_array( $inventory_data['items'] ?? null ) ? $inventory_data['items'] : array(), 0, 10 ) as $item ) {
			if ( (int) ( $item['issue_count'] ?? 0 ) > 0 ) {
				$attention_items[] = array(
					'type'       => 'content',
					'post_id'    => $this->absint_value( $item['post_id'] ?? 0 ),
					'title'      => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
					'issue_count' => (int) ( $item['issue_count'] ?? 0 ),
					'issues'     => is_array( $item['issues'] ?? null ) ? $item['issues'] : array(),
				);
			}
		}

		return $this->build_analysis_success_response(
			array(
				'post_type'       => $post_type,
				'status_counts'   => $status_counts,
				'inventory'       => array(
					'health_score' => (int) ( $inventory_data['health_score'] ?? 100 ),
					'issue_counts' => is_array( $inventory_data['issue_counts'] ?? null ) ? $inventory_data['issue_counts'] : array(),
					'summary'      => is_array( $inventory_data['summary'] ?? null ) ? $inventory_data['summary'] : array(),
				),
				'taxonomy'        => array(
					'health_score' => (int) ( $taxonomy_data['health_score'] ?? 100 ),
					'issue_counts' => is_array( $taxonomy_data['issue_counts'] ?? null ) ? $taxonomy_data['issue_counts'] : array(),
					'summary'      => is_array( $taxonomy_data['summary'] ?? null ) ? $taxonomy_data['summary'] : array(),
				),
				'media'           => array(
					'health_score' => (int) ( $media_data['health_score'] ?? 100 ),
					'issue_counts' => is_array( $media_data['issue_counts'] ?? null ) ? $media_data['issue_counts'] : array(),
					'summary'      => is_array( $media_data['summary'] ?? null ) ? $media_data['summary'] : array(),
				),
				'comments'        => array(
					'pending_count' => $comment_count,
				),
				'attention_items' => $attention_items,
				'summary'         => array(
					'total_content'       => array_sum( array_map( 'intval', $status_counts ) ),
					'draft_backlog'       => (int) ( $status_counts['draft'] ?? 0 ) + (int) ( $status_counts['pending'] ?? 0 ),
					'pending_comments'    => $comment_count,
					'content_issue_count' => array_sum( array_map( 'intval', is_array( $inventory_data['issue_counts'] ?? null ) ? $inventory_data['issue_counts'] : array() ) ),
					'next_action'         => ! empty( $attention_items ) || $comment_count > 0 ? 'review_attention_items' : 'operations_clear',
				),
			),
			array(
				'source'         => 'local_site_operations_dashboard',
				'execution_mode' => 'deterministic',
			),
			'Site operations dashboard built.'
		);
	}

	/**
	 * Builds one post publish risk report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_publish_risk_report( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'post_id is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}

		$target_status = sanitize_key( (string) ( $input['target_status'] ?? 'publish' ) );
		if ( ! in_array( $target_status, array( 'publish', 'future', 'draft' ), true ) ) {
			$target_status = 'publish';
		}
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$risk_items = array();
		$risk_score = 0;

		$checklist = $this->get_content_publishing_checklist(
			array(
				'post_id'       => $post_id,
				'target_status' => $target_status,
			)
		);
		$checklist_data = is_array( $checklist['data'] ?? null ) ? $checklist['data'] : array();
		foreach ( (array) ( $checklist_data['missing'] ?? array() ) as $key ) {
			$risk_items[] = $this->build_publish_risk_item( $key, 'high', 'Required publishing checklist item is missing.' );
			$risk_score += 18;
		}
		foreach ( (array) ( $checklist_data['warnings'] ?? array() ) as $key ) {
			$risk_items[] = $this->build_publish_risk_item( $key, 'medium', 'Publishing checklist warning needs review.' );
			$risk_score += 8;
		}

		$seo_geo = $this->get_post_seo_geo_readiness(
			array(
				'post_id'       => $post_id,
				'focus_keyword' => $focus_keyword,
			)
		);
		$seo_data = is_array( $seo_geo['data'] ?? null ) ? $seo_geo['data'] : array();
		if ( 'ready' !== sanitize_key( (string) ( $seo_data['status'] ?? '' ) ) ) {
			$risk_items[] = $this->build_publish_risk_item( 'seo_geo_readiness', 'medium', 'SEO/GEO readiness is not fully ready.' );
			$risk_score += 'not_ready' === sanitize_key( (string) ( $seo_data['status'] ?? '' ) ) ? 16 : 10;
		}

		$revision = $this->get_revision_change_risk_report(
			array(
				'post_id'       => $post_id,
				'max_revisions' => 5,
			)
		);
		$revision_data = is_array( $revision['data'] ?? null ) ? $revision['data'] : array();
		$revision_risk = sanitize_key( (string) ( $revision_data['risk_level'] ?? 'none' ) );
		if ( in_array( $revision_risk, array( 'high', 'medium' ), true ) ) {
			$risk_items[] = $this->build_publish_risk_item( 'revision_change_risk', $revision_risk, 'Recent revision changes should be reviewed before publishing.' );
			$risk_score += 'high' === $revision_risk ? 16 : 8;
		}

		$link_report = $this->get_internal_link_opportunity_report(
			array(
				'post_id'       => $post_id,
				'focus_keyword' => $focus_keyword,
				'max_targets'   => 3,
			)
		);
		$link_data = is_array( $link_report['data'] ?? null ) ? $link_report['data'] : array();
		if ( 0 === (int) ( $link_data['summary']['candidate_count'] ?? 0 ) ) {
			$risk_items[] = $this->build_publish_risk_item( 'internal_link_gap', 'low', 'No obvious internal-link target was found in the bounded scan.' );
			$risk_score += 5;
		}

		$risk_score = max( 0, min( 100, $risk_score ) );
		$risk_level = $risk_score >= 60 ? 'high' : ( $risk_score >= 30 ? 'medium' : ( $risk_score > 0 ? 'low' : 'none' ) );

		return $this->build_analysis_success_response(
			array(
				'post'          => array(
					'post_id' => $post_id,
					'title'   => sanitize_text_field( (string) get_the_title( $post_id ) ),
					'status'  => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				),
				'risk_score'    => $risk_score,
				'risk_level'    => $risk_level,
				'risk_items'    => $risk_items,
				'checklist'     => $checklist_data,
				'seo_geo'       => $seo_data,
				'revision'      => $revision_data,
				'internal_links' => $link_data,
				'summary'       => array(
					'target_status' => $target_status,
					'risk_count'    => count( $risk_items ),
					'next_action'   => in_array( $risk_level, array( 'high', 'medium' ), true ) ? 'resolve_publish_risks' : 'ready_for_editorial_review',
				),
			),
			array(
				'source'         => 'local_post_publish_risk_report',
				'execution_mode' => 'deterministic',
			),
			'Post publish risk report built.'
		);
	}

	/**
	 * Builds one host-side article publish preflight context bundle.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_article_publish_preflight_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'post_id is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$target_status = sanitize_key( (string) ( $input['target_status'] ?? 'publish' ) );
		if ( ! in_array( $target_status, array( 'publish', 'future', 'draft' ), true ) ) {
			$target_status = 'publish';
		}
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$window_days = max( 1, min( 365, $this->absint_value( $input['window_days'] ?? 30 ) ) );
		$include_content = ! empty( $input['include_content'] );
		$sections = array();

		$post_context = $this->get_post_context(
			array(
				'post_id'           => $post_id,
				'include_content'   => $include_content,
				'include_blocks'    => true,
				'include_terms'     => true,
				'include_media'     => true,
				'include_revisions' => true,
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $post_context ) ) {
			return $post_context;
		}
		$sections[] = 'post_context';

		$checklist = $this->get_content_publishing_checklist(
			array(
				'post_id'       => $post_id,
				'target_status' => $target_status,
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $checklist ) ) {
			return $checklist;
		}
		$sections[] = 'publishing_checklist';

		$risk = $this->get_post_publish_risk_report(
			array(
				'post_id'       => $post_id,
				'target_status' => $target_status,
				'focus_keyword' => $focus_keyword,
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $risk ) ) {
			return $risk;
		}
		$sections[] = 'publish_risk';

		$workflow_context = $this->build_article_workflow_context(
			array(
				'workflow'   => 'publish',
				'post_id'    => $post_id,
				'topic_seed' => $focus_keyword,
			)
		);
		if ( is_array( $workflow_context ) ) {
			$sections[] = 'workflow_context';
		}

		$calendar = $this->get_publishing_calendar_context(
			array(
				'post_type'   => is_array( $post_context ) ? sanitize_key( (string) ( $post_context['data']['post']['post_type'] ?? 'post' ) ) : 'post',
				'window_days' => $window_days,
				'per_page'    => 50,
			)
		);
		if ( is_array( $calendar ) ) {
			$sections[] = 'publishing_calendar';
		}

		$checklist_data = is_array( $checklist['data'] ?? null ) ? $checklist['data'] : array();
		$risk_data = is_array( $risk['data'] ?? null ) ? $risk['data'] : array();
		$ready = ! empty( $checklist_data['ready'] ) && ! in_array( sanitize_key( (string) ( $risk_data['risk_level'] ?? '' ) ), array( 'high', 'medium' ), true );

		return $this->build_analysis_success_response(
			array(
				'recipe'               => 'workflow/wordpress_article_publish_preflight',
				'post_context'         => is_array( $post_context ) ? ( $post_context['data'] ?? array() ) : array(),
				'publishing_checklist' => $checklist_data,
				'publish_risk'         => $risk_data,
				'workflow_context'     => is_array( $workflow_context ) ? ( $workflow_context['data'] ?? array() ) : array(),
				'publishing_calendar'  => is_array( $calendar ) ? ( $calendar['data'] ?? array() ) : array(),
				'sections'             => $sections,
				'summary'              => array(
					'ready_for_host_approval' => $ready,
					'target_status'           => $target_status,
					'risk_level'              => sanitize_key( (string) ( $risk_data['risk_level'] ?? 'none' ) ),
					'section_count'           => count( $sections ),
					'next_action'             => $ready ? 'request_host_publish_or_schedule_approval' : 'resolve_preflight_findings',
				),
			),
			array(
				'source'         => 'local_article_publish_preflight_context',
				'execution_mode' => 'deterministic',
			),
			'Article publish preflight context built.'
		);
	}

	/**
	 * Builds content refresh opportunities.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_content_refresh_opportunities( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read content refresh opportunities.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$stale_days = max( 30, min( 3650, $this->absint_value( $input['stale_days'] ?? 365 ) ) );
		$min_word_count = max( 50, min( 5000, $this->absint_value( $input['min_word_count'] ?? 500 ) ) );
		$query_result = $this->query_inventory_posts( '' !== $post_type ? $post_type : 'post', '' !== $status ? $status : 'publish', $per_page, $page );
		$post_ids = is_array( $query_result['post_ids'] ?? null ) ? $query_result['post_ids'] : array();
		$cutoff = time() - ( $stale_days * ( defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400 ) );
		$issue_counts = array();
		$items = array();

		foreach ( $post_ids as $post_id ) {
			$post_id = $this->absint_value( $post_id );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$title = sanitize_text_field( (string) get_the_title( $post_id ) );
			$content = (string) ( $post->post_content ?? '' );
			$plain_text = $this->strip_all_tags_value( $content );
			$word_count = str_word_count( $plain_text );
			$modified_ts = strtotime( (string) ( $post->post_modified ?? $post->post_date ?? '' ) );
			$seo_provider = $this->detect_seo_provider();
			$seo_keys = $this->seo_meta_keys( $seo_provider );
			$seo_title = function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $post_id, (string) ( $seo_keys['title'] ?? '' ), true ) ) : '';
			$seo_description = function_exists( 'get_post_meta' ) ? $this->sanitize_metadata_text( (string) get_post_meta( $post_id, (string) ( $seo_keys['description'] ?? '' ), true ) ) : '';
			$questions = $this->collect_article_question_candidates( $plain_text, $title, $this->guess_focus_keyword_from_post( $post_id, $title ) );
			$outbound_count = count( $this->extract_post_internal_links( $post_id, $content, array() ) );
			$issues = array();
			if ( false !== $modified_ts && $modified_ts > 0 && $modified_ts < $cutoff ) {
				$issues[] = 'stale_content';
			}
			if ( $word_count < $min_word_count ) {
				$issues[] = 'thin_content';
			}
			if ( '' === $seo_title ) {
				$issues[] = 'missing_seo_title';
			}
			if ( '' === $seo_description ) {
				$issues[] = 'missing_seo_description';
			}
			if ( empty( $questions ) ) {
				$issues[] = 'answer_structure_gap';
			}
			if ( $outbound_count < 1 ) {
				$issues[] = 'low_internal_links';
			}
			foreach ( $issues as $issue ) {
				$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
			}
			if ( empty( $issues ) ) {
				continue;
			}
			$priority_score = count( $issues ) * 10 + ( in_array( 'stale_content', $issues, true ) ? 15 : 0 ) + ( in_array( 'thin_content', $issues, true ) ? 10 : 0 );
			$items[] = array(
				'post_id'        => $post_id,
				'title'          => $title,
				'status'         => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'modified'       => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
				'word_count'     => $word_count,
				'outbound_links' => $outbound_count,
				'issues'         => $issues,
				'priority'       => $priority_score >= 45 ? 'high' : ( $priority_score >= 25 ? 'medium' : 'low' ),
				'priority_score' => $priority_score,
				'edit_link'      => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
			);
		}

		usort(
			$items,
			static function ( array $a, array $b ) {
				return (int) ( $b['priority_score'] ?? 0 ) <=> (int) ( $a['priority_score'] ?? 0 );
			}
		);
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'        => (int) ( $query_result['total'] ?? count( $post_ids ) ),
				'page'         => $page,
				'per_page'     => $per_page,
				'items'        => $items,
				'issue_counts' => $issue_counts,
				'summary'      => array(
					'opportunity_count' => count( $items ),
					'post_type'         => $post_type,
					'status'            => $status,
					'stale_days'        => $stale_days,
					'min_word_count'    => $min_word_count,
				),
			),
			array(
				'source'         => 'local_content_refresh_opportunities',
				'execution_mode' => 'deterministic',
			),
			'Content refresh opportunities built.'
		);
	}

	/**
	 * Builds one host-side old article refresh context bundle.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_old_article_refresh_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read article refresh context.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$stale_days = max( 30, min( 3650, $this->absint_value( $input['stale_days'] ?? 365 ) ) );
		$topic_seed = sanitize_text_field( (string) ( $input['topic_seed'] ?? '' ) );
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? $topic_seed ) );
		$sections = array();

		$refresh = $this->get_content_refresh_opportunities(
			array(
				'post_type'  => '' !== $post_type ? $post_type : 'post',
				'status'     => '' !== $status ? $status : 'publish',
				'per_page'   => $per_page,
				'page'       => $page,
				'stale_days' => $stale_days,
			)
		);
		if ( is_array( $refresh ) ) {
			$sections[] = 'refresh_opportunities';
		}

		$gap_report = $this->get_seo_geo_gap_report(
			array(
				'post_type'  => '' !== $post_type ? $post_type : 'post',
				'status'     => '' !== $status ? $status : 'publish',
				'per_page'   => $per_page,
				'page'       => $page,
				'topic_seed' => $topic_seed,
			)
		);
		if ( is_array( $gap_report ) ) {
			$sections[] = 'seo_geo_gap_report';
		}

		$style = $this->get_site_style_baseline( array( 'mode' => 'site_recent', 'limit' => 5 ) );
		if ( is_array( $style ) ) {
			$sections[] = 'style_baseline';
		}

		$link_graph = $this->get_internal_link_graph_health(
			array(
				'post_type' => '' !== $post_type ? $post_type : 'post',
				'status'    => '' !== $status ? $status : 'publish',
				'per_page'  => $per_page,
				'page'      => $page,
			)
		);
		if ( is_array( $link_graph ) ) {
			$sections[] = 'internal_link_graph';
		}

		$link_opportunity = array();
		if ( $post_id > 0 ) {
			$link_opportunity_result = $this->get_internal_link_opportunity_report(
				array(
					'post_id'       => $post_id,
					'focus_keyword' => $focus_keyword,
					'max_targets'   => 5,
				)
			);
			if ( is_array( $link_opportunity_result ) ) {
				$link_opportunity = is_array( $link_opportunity_result['data'] ?? null ) ? $link_opportunity_result['data'] : array();
				$sections[] = 'selected_post_link_opportunity';
			}
		}

		$refresh_data = is_array( $refresh['data'] ?? null ) ? $refresh['data'] : array();
		$gap_data = is_array( $gap_report['data'] ?? null ) ? $gap_report['data'] : array();

		return $this->build_analysis_success_response(
			array(
				'recipe'                    => 'workflow/wordpress_old_article_refresh_discovery',
				'refresh_opportunities'     => $refresh_data,
				'seo_geo_gap_report'        => $gap_data,
				'style_baseline'            => is_array( $style ) ? ( $style['data'] ?? array() ) : array(),
				'internal_link_graph'       => is_array( $link_graph ) ? ( $link_graph['data'] ?? array() ) : array(),
				'selected_link_opportunity' => $link_opportunity,
				'sections'                  => $sections,
				'summary'                   => array(
					'post_type'         => $post_type,
					'status'            => $status,
					'opportunity_count' => (int) ( $refresh_data['summary']['opportunity_count'] ?? 0 ),
					'gap_count'         => (int) ( $gap_data['summary']['gap_count'] ?? 0 ),
					'section_count'     => count( $sections ),
					'next_action'       => (int) ( $refresh_data['summary']['opportunity_count'] ?? 0 ) > 0 ? 'select_refresh_candidate' : 'no_refresh_candidate_detected',
				),
			),
			array(
				'source'         => 'local_old_article_refresh_context',
				'execution_mode' => 'deterministic',
			),
			'Old article refresh context built.'
		);
	}

	/**
	 * Builds an internal-link graph health report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_internal_link_graph_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read internal link graph health.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$min_outbound = max( 0, min( 20, $this->absint_value( $input['min_outbound_links'] ?? 1 ) ) );
		$max_outbound = max( 1, min( 100, $this->absint_value( $input['max_outbound_links'] ?? 20 ) ) );
		$query_result = $this->query_inventory_posts( '' !== $post_type ? $post_type : 'post', '' !== $status ? $status : 'publish', $per_page, $page );
		$post_ids = is_array( $query_result['post_ids'] ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $query_result['post_ids'] ) ) : array();
		$url_map = array();
		$incoming = array();
		foreach ( $post_ids as $post_id ) {
			$url_map[ $post_id ] = $this->post_graph_url( $post_id );
			$incoming[ $post_id ] = 0;
		}

		$rows = array();
		$issue_counts = array();
		$candidate_pairs = array();
		$post_terms = array();
		foreach ( $post_ids as $post_id ) {
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$content = (string) ( $post->post_content ?? '' );
			$links = $this->extract_post_internal_links( $post_id, $content, $url_map );
			foreach ( $links as $target_id ) {
				if ( isset( $incoming[ $target_id ] ) ) {
					++$incoming[ $target_id ];
				}
			}
			$title = sanitize_text_field( (string) get_the_title( $post_id ) );
			$post_terms[ $post_id ] = $this->collect_topic_terms_for_post( $post_id, $title, $this->strip_all_tags_value( $content ), '' );
			$rows[ $post_id ] = array(
				'post_id'        => $post_id,
				'title'          => $title,
				'outbound_count' => count( $links ),
				'incoming_count' => 0,
				'issues'         => array(),
				'edit_link'      => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
			);
		}

		foreach ( $rows as $post_id => $row ) {
			$row['incoming_count'] = (int) ( $incoming[ $post_id ] ?? 0 );
			if ( (int) $row['incoming_count'] <= 0 ) {
				$row['issues'][] = 'orphan_post';
			}
			if ( (int) $row['outbound_count'] < $min_outbound ) {
				$row['issues'][] = 'low_outbound_links';
			}
			if ( (int) $row['outbound_count'] > $max_outbound ) {
				$row['issues'][] = 'excessive_outbound_links';
			}
			foreach ( $row['issues'] as $issue ) {
				$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
			}
			$rows[ $post_id ] = $row;
		}

		$ids = array_keys( $rows );
		foreach ( $ids as $source_id ) {
			foreach ( $ids as $target_id ) {
				if ( $source_id === $target_id || count( $candidate_pairs ) >= 20 ) {
					continue;
				}
				$overlap = array_values( array_intersect( $post_terms[ $source_id ] ?? array(), $post_terms[ $target_id ] ?? array() ) );
				if ( empty( $overlap ) ) {
					continue;
				}
				$candidate_pairs[] = array(
					'source_post_id' => $source_id,
					'target_post_id' => $target_id,
					'source_title'   => sanitize_text_field( (string) ( $rows[ $source_id ]['title'] ?? '' ) ),
					'target_title'   => sanitize_text_field( (string) ( $rows[ $target_id ]['title'] ?? '' ) ),
					'shared_terms'   => array_slice( $overlap, 0, 3 ),
					'reason'         => __( 'Posts share topic terms and may support a contextual internal link.', 'magick-ai-abilities' ),
				);
			}
		}
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'           => (int) ( $query_result['total'] ?? count( $post_ids ) ),
				'page'            => $page,
				'per_page'        => $per_page,
				'items'           => array_values( $rows ),
				'issue_counts'    => $issue_counts,
				'candidate_pairs' => $candidate_pairs,
				'summary'         => array(
					'scanned_count'         => count( $rows ),
					'orphan_count'          => (int) ( $issue_counts['orphan_post'] ?? 0 ),
					'low_outbound_count'    => (int) ( $issue_counts['low_outbound_links'] ?? 0 ),
					'candidate_pair_count'  => count( $candidate_pairs ),
					'min_outbound_links'    => $min_outbound,
					'max_outbound_links'    => $max_outbound,
				),
			),
			array(
				'source'         => 'local_internal_link_graph_health',
				'execution_mode' => 'deterministic',
			),
			'Internal link graph health built.'
		);
	}

	/**
	 * Builds media cleanup opportunities.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_media_cleanup_opportunities( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read media cleanup opportunities.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$mime_type = sanitize_text_field( (string) ( $input['mime_type'] ?? '' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$query_result = $this->query_media_inventory( $mime_type, '', $per_page, $page );
		$items = array();
		$issue_counts = array();
		foreach ( (array) ( $query_result['attachment_ids'] ?? array() ) as $attachment_id ) {
			$attachment_id = $this->absint_value( $attachment_id );
			if ( $attachment_id <= 0 || ! current_user_can( 'edit_post', $attachment_id ) ) {
				continue;
			}
			$row = $this->build_media_inventory_health_row( $attachment_id );
			$issues = is_array( $row['issues'] ?? null ) ? $row['issues'] : array();
			$attached_to = get_post( $attachment_id );
			if ( is_object( $attached_to ) && 0 === $this->absint_value( $attached_to->post_parent ?? 0 ) ) {
				$issues[] = 'possibly_unattached';
			}
			foreach ( $issues as $issue ) {
				$issue = sanitize_key( (string) $issue );
				if ( '' !== $issue ) {
					$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
				}
			}
			if ( empty( $issues ) ) {
				continue;
			}
			$row['issues'] = array_values( array_unique( $issues ) );
			$row['issue_count'] = count( $row['issues'] );
			$row['priority'] = in_array( 'missing_alt', $row['issues'], true ) || in_array( 'possibly_unattached', $row['issues'], true ) ? 'medium' : 'low';
			$items[] = $row;
		}
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'        => (int) ( $query_result['total'] ?? 0 ),
				'page'         => $page,
				'per_page'     => $per_page,
				'items'        => $items,
				'issue_counts' => $issue_counts,
				'summary'      => array(
					'opportunity_count' => count( $items ),
					'mime_type'         => $mime_type,
				),
			),
			array(
				'source'         => 'local_media_cleanup_opportunities',
				'execution_mode' => 'deterministic',
			),
			'Media cleanup opportunities built.'
		);
	}

	/**
	 * Builds taxonomy consolidation suggestions.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_taxonomy_consolidation_suggestions( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'manage_categories' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read taxonomy consolidation suggestions.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? 'post_tag' ) );
		if ( '' === $taxonomy ) {
			$taxonomy = 'post_tag';
		}
		if ( function_exists( 'taxonomy_exists' ) && ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'magick_ai_abilities_taxonomy_invalid', __( 'Taxonomy does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$hide_empty = ! empty( $input['hide_empty'] );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 100 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$query_result = $this->query_taxonomy_inventory_terms( $taxonomy, $hide_empty, $per_page, $page );
		$terms = is_array( $query_result['terms'] ?? null ) ? $query_result['terms'] : array();
		$by_key = array();
		$items = array();
		$suggestions = array();

		foreach ( $terms as $term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}
			$row = array(
				'term_id' => $this->absint_value( $term->term_id ?? 0 ),
				'name'    => sanitize_text_field( (string) ( $term->name ?? '' ) ),
				'slug'    => sanitize_title( (string) ( $term->slug ?? '' ) ),
				'count'   => $this->absint_value( $term->count ?? 0 ),
			);
			$key = $this->normalize_taxonomy_consolidation_key( $row['name'] ?: $row['slug'] );
			if ( '' !== $key ) {
				$by_key[ $key ][] = $row;
			}
			$items[] = $row;
			if ( 0 === (int) $row['count'] ) {
				$suggestions[] = array(
					'type'      => 'unused_term',
					'priority'  => 'medium',
					'term_ids'  => array( (int) $row['term_id'] ),
					'terms'     => array( $row['name'] ),
					'reason'    => __( 'Term has no associated content in the current scan.', 'magick-ai-abilities' ),
				);
			}
		}

		foreach ( $by_key as $rows ) {
			if ( count( $rows ) < 2 ) {
				continue;
			}
			$suggestions[] = array(
				'type'     => 'duplicate_or_near_duplicate',
				'priority' => 'high',
				'term_ids' => array_values( array_map( 'intval', array_column( $rows, 'term_id' ) ) ),
				'terms'    => array_values( array_map( 'strval', array_column( $rows, 'name' ) ) ),
				'reason'   => __( 'Terms normalize to the same consolidation key.', 'magick-ai-abilities' ),
			);
		}

		return $this->build_analysis_success_response(
			array(
				'taxonomy'    => $taxonomy,
				'total'       => (int) ( $query_result['total'] ?? count( $items ) ),
				'items'       => $items,
				'suggestions' => array_slice( $suggestions, 0, 50 ),
				'summary'     => array(
					'scanned_count'    => count( $items ),
					'suggestion_count' => count( $suggestions ),
					'hide_empty'       => $hide_empty,
				),
			),
			array(
				'source'         => 'local_taxonomy_consolidation_suggestions',
				'execution_mode' => 'deterministic',
			),
			'Taxonomy consolidation suggestions built.'
		);
	}

	/**
	 * Builds page structure health.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_page_structure_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read page structure health.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$page_id = $this->absint_value( $input['page_id'] ?? 0 );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		$max_pages = max( 1, min( 100, $this->absint_value( $input['max_pages'] ?? 50 ) ) );
		$page_ids = array();
		if ( $page_id > 0 ) {
			$page_ids = array( $page_id );
		} else {
			$query_result = $this->query_inventory_posts( 'page', '' !== $status ? $status : 'publish', $max_pages, 1 );
			$page_ids = is_array( $query_result['post_ids'] ?? null ) ? $query_result['post_ids'] : array();
		}

		$items = array();
		$issue_counts = array();
		foreach ( $page_ids as $current_page_id ) {
			$current_page_id = $this->absint_value( $current_page_id );
			$page = $current_page_id > 0 ? get_post( $current_page_id ) : null;
			if ( ! is_object( $page ) || ! current_user_can( 'edit_post', $current_page_id ) ) {
				continue;
			}
			$content = (string) ( $page->post_content ?? '' );
			$plain_text = $this->strip_all_tags_value( $content );
			$heading_count = preg_match_all( '/<h[1-6][^>]*>/i', $content, $heading_matches );
			$heading_count = is_int( $heading_count ) ? $heading_count : 0;
			$cta_count = preg_match_all( '/<a\\s[^>]*href=|wp:button|wp:buttons|class=["\'][^"\']*(?:button|cta)/i', $content, $cta_matches );
			$cta_count = is_int( $cta_count ) ? $cta_count : 0;
			$block_count = count( $this->parse_content_blocks( $content ) );
			$issues = array();
			if ( $this->strlen_value( $plain_text ) < 160 ) {
				$issues[] = 'thin_page_content';
			}
			if ( $heading_count < 1 ) {
				$issues[] = 'missing_heading_structure';
			}
			if ( $cta_count < 1 ) {
				$issues[] = 'missing_cta';
			}
			if ( $block_count < 2 ) {
				$issues[] = 'low_block_structure';
			}
			foreach ( $issues as $issue ) {
				$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
			}
			$items[] = array(
				'page_id'           => $current_page_id,
				'title'             => sanitize_text_field( (string) get_the_title( $current_page_id ) ),
				'status'            => sanitize_key( (string) ( $page->post_status ?? '' ) ),
				'plain_text_length' => $this->strlen_value( $plain_text ),
				'heading_count'     => $heading_count,
				'cta_count'         => $cta_count,
				'block_count'       => $block_count,
				'issue_count'       => count( $issues ),
				'issues'            => $issues,
				'edit_link'         => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $current_page_id, 'raw' ) ) : '',
			);
		}
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'        => count( $items ),
				'items'        => $items,
				'issue_counts' => $issue_counts,
				'summary'      => array(
					'scanned_count'     => count( $items ),
					'pages_with_issues' => count(
						array_filter(
							$items,
							static function ( array $item ) {
								return (int) ( $item['issue_count'] ?? 0 ) > 0;
							}
						)
					),
				),
			),
			array(
				'source'         => 'local_page_structure_health',
				'execution_mode' => 'deterministic',
			),
			'Page structure health built.'
		);
	}

	/**
	 * Builds SEO/GEO gap report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_seo_geo_gap_report( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$topic_seed = sanitize_text_field( (string) ( $input['topic_seed'] ?? '' ) );

		$cache_key = $this->build_read_cache_key(
			'seo_geo_gap_report',
			array(
				'post_type'  => $post_type,
				'status'     => $status,
				'per_page'   => $per_page,
				'page'       => $page,
				'topic_seed' => $topic_seed,
			)
		);
		$cached = $this->get_cached_read_response( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$coverage = $this->get_site_topic_coverage_report(
			array(
				'post_type'  => '' !== $post_type ? $post_type : 'post',
				'status'     => '' !== $status ? $status : 'publish',
				'per_page'   => $per_page,
				'page'       => $page,
				'topic_seed' => $topic_seed,
			)
		);
		$refresh = $this->get_content_refresh_opportunities(
			array(
				'post_type'  => '' !== $post_type ? $post_type : 'post',
				'status'     => '' !== $status ? $status : 'publish',
				'per_page'   => $per_page,
				'page'       => $page,
			)
		);
		$coverage_data = is_array( $coverage['data'] ?? null ) ? $coverage['data'] : array();
		$refresh_data = is_array( $refresh['data'] ?? null ) ? $refresh['data'] : array();
		$refresh_issue_counts = is_array( $refresh_data['issue_counts'] ?? null ) ? $refresh_data['issue_counts'] : array();
		$gaps = array();
		foreach ( (array) ( $coverage_data['coverage_gaps'] ?? array() ) as $gap ) {
			$gaps[] = array(
				'type'     => 'topic_coverage_gap',
				'priority' => 'medium',
				'topic'    => sanitize_text_field( (string) ( is_array( $gap ) ? ( $gap['topic'] ?? '' ) : '' ) ),
				'reason'   => sanitize_text_field( (string) ( is_array( $gap ) ? ( $gap['reason'] ?? '' ) : '' ) ),
			);
		}
		foreach ( array( 'missing_seo_title', 'missing_seo_description', 'answer_structure_gap', 'thin_content', 'low_internal_links' ) as $issue ) {
			if ( (int) ( $refresh_issue_counts[ $issue ] ?? 0 ) <= 0 ) {
				continue;
			}
			$gaps[] = array(
				'type'     => $issue,
				'priority' => in_array( $issue, array( 'missing_seo_description', 'answer_structure_gap' ), true ) ? 'high' : 'medium',
				'count'    => (int) $refresh_issue_counts[ $issue ],
				'reason'   => __( 'Refresh scan found repeated SEO/GEO readiness gaps.', 'magick-ai-abilities' ),
			);
		}

		$result = $this->build_analysis_success_response(
			array(
				'gaps'       => $gaps,
				'coverage'   => $coverage_data,
				'refresh'    => $refresh_data,
				'summary'    => array(
					'gap_count'      => count( $gaps ),
					'post_type'      => $post_type,
					'status'         => $status,
					'topic_seed'     => $topic_seed,
					'next_action'    => count( $gaps ) > 0 ? 'prioritize_gap_closure' : 'no_major_gap_detected',
				),
			),
			array(
				'source'         => 'local_seo_geo_gap_report',
				'execution_mode' => 'deterministic',
			),
			'SEO/GEO gap report built.'
		);
		$this->set_cached_read_response( $cache_key, $result, 'seo_geo_gap_report' );
		return $result;
	}

	/**
	 * Builds a compact site style baseline.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function get_site_style_baseline( $input ) {
		$input = is_array( $input ) ? $input : array();
		$mode = sanitize_key( (string) ( $input['mode'] ?? 'site_recent' ) );
		if ( ! in_array( $mode, array( 'site_recent', 'author_recent' ), true ) ) {
			$mode = 'site_recent';
		}
		$baseline = $this->extract_style_baseline(
			array(
				'mode'      => $mode,
				'author_id' => $this->absint_value( $input['author_id'] ?? 0 ),
				'limit'     => max( 1, min( 5, $this->absint_value( $input['limit'] ?? 5 ) ) ),
			)
		);
		if ( is_array( $baseline ) ) {
			$baseline['message'] = __( 'Site style baseline built.', 'magick-ai-abilities' );
			$baseline['meta']['source'] = 'local_site_style_baseline';
			return $baseline;
		}

		return $this->build_analysis_success_response(
			array(
				'profile' => array(),
				'samples' => array(),
				'source'  => 'unavailable',
			),
			array(
				'source'         => 'local_site_style_baseline',
				'execution_mode' => 'deterministic',
			),
			'Site style baseline built.'
		);
	}

	/**
	 * Builds one article workflow context bundle.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_article_workflow_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		$workflow = sanitize_key( (string) ( $input['workflow'] ?? 'new_article' ) );
		if ( ! in_array( $workflow, array( 'new_article', 'refresh', 'publish' ), true ) ) {
			$workflow = 'new_article';
		}
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		$topic_seed = sanitize_text_field( (string) ( $input['topic_seed'] ?? '' ) );
		$data = array(
			'workflow'   => $workflow,
			'topic_seed' => $topic_seed,
			'sections'   => array(),
		);

		if ( $post_id > 0 ) {
			$post_context = $this->get_post_context(
				array(
					'post_id'           => $post_id,
					'include_meta'      => true,
					'include_terms'     => true,
					'include_media'     => true,
					'include_revisions' => 'publish' === $workflow,
				)
			);
			if ( is_array( $post_context ) ) {
				$data['post_context'] = $post_context['data'] ?? array();
				$data['sections'][] = 'post_context';
			}
			if ( in_array( $workflow, array( 'publish', 'refresh' ), true ) ) {
				$risk = $this->get_post_publish_risk_report(
					array(
						'post_id'       => $post_id,
						'focus_keyword' => $topic_seed,
					)
				);
				if ( is_array( $risk ) ) {
					$data['publish_risk'] = $risk['data'] ?? array();
					$data['sections'][] = 'publish_risk';
				}
			}
		}

		$gap_report = $this->get_seo_geo_gap_report(
			array(
				'post_type'  => 'post',
				'status'     => 'any',
				'per_page'   => 20,
				'topic_seed' => $topic_seed,
			)
		);
		if ( is_array( $gap_report ) ) {
			$data['seo_geo_gap_report'] = $gap_report['data'] ?? array();
			$data['sections'][] = 'seo_geo_gap_report';
		}
		$style = $this->get_site_style_baseline( array( 'mode' => 'site_recent', 'limit' => 5 ) );
		if ( is_array( $style ) ) {
			$data['style_baseline'] = $style['data'] ?? array();
			$data['sections'][] = 'style_baseline';
		}
		$link_graph = $this->get_internal_link_graph_health(
			array(
				'post_type' => 'post',
				'status'    => 'any',
				'per_page'  => 20,
			)
		);
		if ( is_array( $link_graph ) ) {
			$data['internal_link_graph'] = $link_graph['data'] ?? array();
			$data['sections'][] = 'internal_link_graph';
		}
		$data['summary'] = array(
			'section_count' => count( $data['sections'] ),
			'next_action'   => 'publish' === $workflow ? 'review_risk_then_prepare_write_handoff' : 'compose_agent_plan_from_context',
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_article_workflow_context',
				'execution_mode' => 'deterministic',
			),
			'Article workflow context built.'
		);
	}

	/**
	 * Builds publishing calendar context.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_publishing_calendar_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read publishing calendar context.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		if ( '' === $post_type ) {
			$post_type = 'post';
		}
		$window_days = max( 1, min( 365, $this->absint_value( $input['window_days'] ?? 30 ) ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$status_counts = array();
		foreach ( array( 'future', 'draft', 'pending', 'publish' ) as $status ) {
			$status_counts[ $status ] = (int) ( $this->query_inventory_posts( $post_type, $status, 1, 1 )['total'] ?? 0 );
		}
		$future = $this->query_inventory_posts( $post_type, 'future', $per_page, 1 );
		$drafts = $this->query_inventory_posts( $post_type, 'draft', $per_page, 1 );
		$pending = $this->query_inventory_posts( $post_type, 'pending', $per_page, 1 );
		$window_end = time() + ( $window_days * ( defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400 ) );
		$scheduled = array();
		foreach ( (array) ( $future['post_ids'] ?? array() ) as $post_id ) {
			$post_id = $this->absint_value( $post_id );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) ) {
				continue;
			}
			$post_time = strtotime( (string) ( $post->post_date ?? '' ) );
			if ( false !== $post_time && $post_time > $window_end ) {
				continue;
			}
			$scheduled[] = $this->build_calendar_post_row( $post_id, $post );
		}

		return $this->build_analysis_success_response(
			array(
				'post_type'      => $post_type,
				'window_days'    => $window_days,
				'status_counts'  => $status_counts,
				'scheduled'      => $scheduled,
				'draft_backlog'  => $this->build_calendar_rows_from_ids( (array) ( $drafts['post_ids'] ?? array() ) ),
				'pending_review' => $this->build_calendar_rows_from_ids( (array) ( $pending['post_ids'] ?? array() ) ),
				'summary'        => array(
					'scheduled_count' => count( $scheduled ),
					'draft_count'     => (int) ( $status_counts['draft'] ?? 0 ),
					'pending_count'   => (int) ( $status_counts['pending'] ?? 0 ),
					'next_action'     => (int) ( $status_counts['draft'] ?? 0 ) + (int) ( $status_counts['pending'] ?? 0 ) > 0 ? 'review_backlog_and_calendar' : 'calendar_clear',
				),
			),
			array(
				'source'         => 'local_publishing_calendar_context',
				'execution_mode' => 'deterministic',
			),
			'Publishing calendar context built.'
		);
	}

	/**
	 * Builds a media inventory health report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_media_inventory_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read media inventory.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$mime_type = sanitize_text_field( (string) ( $input['mime_type'] ?? '' ) );
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$query_result = $this->query_media_inventory( $mime_type, $search, $per_page, $page );
		$attachment_ids = is_array( $query_result['attachment_ids'] ?? null ) ? $query_result['attachment_ids'] : array();
		$items = array();
		$issue_counts = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = $this->absint_value( $attachment_id );
			if ( $attachment_id <= 0 || ! current_user_can( 'edit_post', $attachment_id ) ) {
				continue;
			}
			$row = $this->build_media_inventory_health_row( $attachment_id );
			foreach ( (array) ( $row['issues'] ?? array() ) as $issue ) {
				$issue = sanitize_key( (string) $issue );
				if ( '' !== $issue ) {
					$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
				}
			}
			$items[] = $row;
		}

		$total_issue_instances = array_sum( array_map( 'intval', $issue_counts ) );
		$health_score = count( $items ) > 0
			? max( 0, 100 - min( 100, (int) round( ( $total_issue_instances / max( 1, count( $items ) ) ) * 14 ) ) )
			: 100;
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'        => (int) ( $query_result['total'] ?? count( $attachment_ids ) ),
				'page'         => $page,
				'per_page'     => $per_page,
				'health_score' => $health_score,
				'issue_counts' => $issue_counts,
				'items'        => $items,
				'summary'      => array(
					'scanned_count'          => count( $items ),
					'assets_with_issues'     => count(
						array_filter(
							$items,
							static function ( array $item ) {
								return (int) ( $item['issue_count'] ?? 0 ) > 0;
							}
						)
					),
					'total_issue_instances' => $total_issue_instances,
					'mime_type'             => $mime_type,
					'search'                => $search,
				),
			),
			array(
				'source'         => 'local_media_inventory_health',
				'execution_mode' => 'deterministic',
			),
			'Media inventory health report built.'
		);
	}

	/**
	 * Builds one deterministic SEO/GEO readiness snapshot for a post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_seo_geo_readiness( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'post_id is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$title = sanitize_text_field( (string) get_the_title( $post_id ) );
		$content = (string) ( $post->post_content ?? '' );
		$plain_text = $this->strip_all_tags_value( $content );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		if ( '' === $focus_keyword ) {
			$focus_keyword = $this->guess_focus_keyword_from_post( $post_id, $title );
		}
		$seo_provider = $this->detect_seo_provider();
		$seo_keys = $this->seo_meta_keys( $seo_provider );
		$seo_title = function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $post_id, (string) ( $seo_keys['title'] ?? '' ), true ) ) : '';
		$seo_description = function_exists( 'get_post_meta' ) ? $this->sanitize_metadata_text( (string) get_post_meta( $post_id, (string) ( $seo_keys['description'] ?? '' ), true ) ) : '';
		$word_count = str_word_count( $plain_text );
		$question_candidates = $this->collect_article_question_candidates( $plain_text, $title, $focus_keyword );
		$featured_media_id = function_exists( 'get_post_thumbnail_id' ) ? $this->absint_value( get_post_thumbnail_id( $post_id ) ) : 0;
		$checks = array();
		$recommendations = array();

		$this->append_readiness_check( $checks, $recommendations, 'focus_keyword', '' !== $focus_keyword && $this->contains_text_ci( $plain_text . ' ' . $title, $focus_keyword ), 'high', __( 'Focus keyword appears in title or content.', 'magick-ai-abilities' ), __( 'Add or confirm a focus keyword and place it naturally in the title or lead section.', 'magick-ai-abilities' ) );
		$this->append_readiness_check( $checks, $recommendations, 'seo_title', '' !== $seo_title, 'medium', __( 'SEO title metadata is present.', 'magick-ai-abilities' ), __( 'Add SEO title metadata before publication or optimization handoff.', 'magick-ai-abilities' ) );
		$this->append_readiness_check( $checks, $recommendations, 'seo_description', '' !== $seo_description, 'medium', __( 'SEO description metadata is present.', 'magick-ai-abilities' ), __( 'Add SEO description metadata for search and share snippets.', 'magick-ai-abilities' ) );
		$this->append_readiness_check( $checks, $recommendations, 'content_depth', $this->strlen_value( $plain_text ) >= 320, 'medium', __( 'Content has enough depth for baseline SEO review.', 'magick-ai-abilities' ), __( 'Expand the content with clearer context, steps, evidence, or examples.', 'magick-ai-abilities' ) );
		$this->append_readiness_check( $checks, $recommendations, 'answerability', ! empty( $question_candidates ), 'medium', __( 'Content has answer-oriented question candidates.', 'magick-ai-abilities' ), __( 'Add direct answer sections or FAQ-style questions for GEO readiness.', 'magick-ai-abilities' ) );
		$this->append_readiness_check( $checks, $recommendations, 'featured_media', $featured_media_id > 0, 'low', __( 'Featured media is assigned.', 'magick-ai-abilities' ), __( 'Add featured media when the channel or theme expects one.', 'magick-ai-abilities' ) );

		$penalty = 0;
		foreach ( $checks as $check ) {
			if ( 'pass' === sanitize_key( (string) ( $check['status'] ?? '' ) ) ) {
				continue;
			}
			$severity = sanitize_key( (string) ( $check['severity'] ?? '' ) );
			$penalty += 'high' === $severity ? 22 : ( 'medium' === $severity ? 14 : 7 );
		}
		$readiness_score = max( 0, 100 - min( 100, $penalty ) );

		return $this->build_analysis_success_response(
			array(
				'post'            => array(
					'post_id'       => $post_id,
					'title'         => $title,
					'post_type'     => sanitize_key( (string) ( $post->post_type ?? '' ) ),
					'status'        => sanitize_key( (string) ( $post->post_status ?? '' ) ),
					'focus_keyword' => $focus_keyword,
					'edit_link'     => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
				),
				'readiness_score' => $readiness_score,
				'status'          => $readiness_score >= 80 ? 'ready' : ( $readiness_score >= 60 ? 'needs_attention' : 'not_ready' ),
				'checks'          => $checks,
				'recommendations' => $recommendations,
				'summary'         => array(
					'word_count'               => $word_count,
					'plain_text_length'        => $this->strlen_value( $plain_text ),
					'seo_provider'             => $seo_provider,
					'featured_media_id'        => $featured_media_id,
					'question_candidate_count' => count( $question_candidates ),
					'question_candidates'      => $question_candidates,
				),
			),
			array(
				'source'         => 'local_post_seo_geo_readiness',
				'execution_mode' => 'deterministic',
			),
			'Post SEO/GEO readiness built.'
		);
	}

	/**
	 * Builds a bounded site topic coverage report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_site_topic_coverage_report( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read topic coverage.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		if ( '' === $post_type ) {
			$post_type = 'post';
		}
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		if ( '' === $status ) {
			$status = 'publish';
		}
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$topic_seed = sanitize_text_field( (string) ( $input['topic_seed'] ?? '' ) );
		$query_result = $this->query_inventory_posts( $post_type, $status, $per_page, $page );
		$post_ids = is_array( $query_result['post_ids'] ?? null ) ? $query_result['post_ids'] : array();
		$topic_map = array();
		$representative_posts = array();

		foreach ( $post_ids as $post_id ) {
			$post_id = $this->absint_value( $post_id );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$title = sanitize_text_field( (string) get_the_title( $post_id ) );
			$content = $this->strip_all_tags_value( (string) ( $post->post_content ?? '' ) );
			$terms = $this->collect_topic_terms_for_post( $post_id, $title, $content, $topic_seed );
			foreach ( $terms as $term ) {
				if ( ! isset( $topic_map[ $term ] ) ) {
					$topic_map[ $term ] = array(
						'topic'    => $term,
						'count'    => 0,
						'post_ids' => array(),
					);
				}
				++$topic_map[ $term ]['count'];
				if ( count( $topic_map[ $term ]['post_ids'] ) < 5 ) {
					$topic_map[ $term ]['post_ids'][] = $post_id;
				}
			}
			$representative_posts[] = array(
				'post_id'      => $post_id,
				'title'        => $title,
				'status'       => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'primary_terms' => array_slice( $terms, 0, 5 ),
				'edit_link'    => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
			);
		}

		$topics = array_values( $topic_map );
		usort(
			$topics,
			static function ( array $a, array $b ) {
				return (int) ( $b['count'] ?? 0 ) <=> (int) ( $a['count'] ?? 0 );
			}
		);
		$coverage_gaps = array();
		foreach ( array_slice( $topics, 0, 12 ) as $topic ) {
			if ( (int) ( $topic['count'] ?? 0 ) <= 1 ) {
				$coverage_gaps[] = array(
					'topic'  => sanitize_text_field( (string) ( $topic['topic'] ?? '' ) ),
					'reason' => __( 'Only one scanned post covers this topic; consider adding supporting content or internal links.', 'magick-ai-abilities' ),
				);
			}
		}

		return $this->build_analysis_success_response(
			array(
				'total'                => (int) ( $query_result['total'] ?? count( $post_ids ) ),
				'topics'               => array_slice( $topics, 0, 20 ),
				'coverage_gaps'        => $coverage_gaps,
				'representative_posts' => array_slice( $representative_posts, 0, 20 ),
				'summary'              => array(
					'scanned_count'       => count( $representative_posts ),
					'unique_topic_count'  => count( $topics ),
					'coverage_gap_count'  => count( $coverage_gaps ),
					'post_type'           => $post_type,
					'status'              => $status,
					'topic_seed'          => $topic_seed,
				),
			),
			array(
				'source'         => 'local_site_topic_coverage_report',
				'execution_mode' => 'deterministic',
			),
			'Site topic coverage report built.'
		);
	}

	/**
	 * Builds a taxonomy inventory health report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_taxonomy_inventory_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'manage_categories' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read taxonomy inventory.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? 'category' ) );
		if ( '' === $taxonomy ) {
			$taxonomy = 'category';
		}
		if ( function_exists( 'taxonomy_exists' ) && ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'magick_ai_abilities_taxonomy_invalid', __( 'Taxonomy does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$hide_empty = ! empty( $input['hide_empty'] );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$query_result = $this->query_taxonomy_inventory_terms( $taxonomy, $hide_empty, $per_page, $page );
		$terms = is_array( $query_result['terms'] ?? null ) ? $query_result['terms'] : array();
		$items = array();
		$issue_counts = array();
		$seen_slugs = array();

		foreach ( $terms as $term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}
			$slug = sanitize_title( (string) ( $term->slug ?? '' ) );
			$row = $this->build_taxonomy_inventory_health_row( $taxonomy, $term, isset( $seen_slugs[ $slug ] ) );
			if ( '' !== $slug ) {
				$seen_slugs[ $slug ] = true;
			}
			foreach ( (array) ( $row['issues'] ?? array() ) as $issue ) {
				$issue = sanitize_key( (string) $issue );
				if ( '' !== $issue ) {
					$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
				}
			}
			$items[] = $row;
		}

		$total_issue_instances = array_sum( array_map( 'intval', $issue_counts ) );
		$health_score = count( $items ) > 0
			? max( 0, 100 - min( 100, (int) round( ( $total_issue_instances / max( 1, count( $items ) ) ) * 16 ) ) )
			: 100;
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'taxonomy'     => $taxonomy,
				'total'        => (int) ( $query_result['total'] ?? count( $terms ) ),
				'page'         => $page,
				'per_page'     => $per_page,
				'health_score' => $health_score,
				'issue_counts' => $issue_counts,
				'items'        => $items,
				'summary'      => array(
					'scanned_count'          => count( $items ),
					'terms_with_issues'      => count(
						array_filter(
							$items,
							static function ( array $item ) {
								return (int) ( $item['issue_count'] ?? 0 ) > 0;
							}
						)
					),
					'total_issue_instances' => $total_issue_instances,
					'hide_empty'            => $hide_empty,
				),
			),
			array(
				'source'         => 'local_taxonomy_inventory_health',
				'execution_mode' => 'deterministic',
			),
			'Taxonomy inventory health report built.'
		);
	}

	/**
	 * Builds a revision change-risk report for one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_revision_change_risk_report( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'post_id is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post revision history.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$max_revisions = max( 1, min( 20, $this->absint_value( $input['max_revisions'] ?? 10 ) ) );
		$revisions = array_slice( $this->get_revision_objects_for_post( $post_id ), 0, $max_revisions );
		$current_title = sanitize_text_field( (string) get_the_title( $post_id ) );
		$current_content = (string) ( $post->post_content ?? '' );
		$current_text_length = $this->strlen_value( $this->strip_all_tags_value( $current_content ) );
		$current_block_count = count( $this->parse_content_blocks( $current_content ) );
		$latest = is_object( $revisions[0] ?? null ) ? $revisions[0] : null;
		$risk_flags = array();
		$recent_rows = array();

		if ( ! is_object( $latest ) ) {
			$risk_flags[] = 'no_revisions';
		}

		$latest_summary = array();
		$change_summary = array(
			'title_changed'        => false,
			'content_length_delta' => 0,
			'block_count_delta'    => 0,
			'latest_modified'      => '',
		);

		if ( is_object( $latest ) ) {
			$latest_title = sanitize_text_field( (string) ( $latest->post_title ?? '' ) );
			$latest_content = (string) ( $latest->post_content ?? '' );
			$latest_text_length = $this->strlen_value( $this->strip_all_tags_value( $latest_content ) );
			$latest_block_count = count( $this->parse_content_blocks( $latest_content ) );
			$content_delta = $current_text_length - $latest_text_length;
			$block_delta = $current_block_count - $latest_block_count;
			$title_changed = '' !== $latest_title && $latest_title !== $current_title;
			if ( $title_changed ) {
				$risk_flags[] = 'title_changed';
			}
			if ( abs( $content_delta ) >= 800 || ( $latest_text_length > 0 && abs( $content_delta ) / max( 1, $latest_text_length ) >= 0.35 ) ) {
				$risk_flags[] = 'large_content_delta';
			}
			if ( abs( $block_delta ) >= 4 ) {
				$risk_flags[] = 'large_block_delta';
			}

			$modified = sanitize_text_field( (string) ( $latest->post_modified_gmt ?? $latest->post_modified ?? $latest->post_date ?? '' ) );
			$latest_summary = array(
				'revision_id'         => $this->absint_value( $latest->ID ?? 0 ),
				'modified'            => $modified,
				'author_id'           => $this->absint_value( $latest->post_author ?? 0 ),
				'title'               => $latest_title,
				'content_text_length' => $latest_text_length,
				'block_count'         => $latest_block_count,
				'excerpt'             => $this->build_revision_excerpt( $latest_content ),
			);
			$change_summary = array(
				'title_changed'        => $title_changed,
				'content_length_delta' => $content_delta,
				'block_count_delta'    => $block_delta,
				'latest_modified'      => $modified,
			);
		}

		foreach ( $revisions as $revision ) {
			if ( ! is_object( $revision ) ) {
				continue;
			}
			$content = (string) ( $revision->post_content ?? '' );
			$recent_rows[] = array(
				'revision_id'         => $this->absint_value( $revision->ID ?? 0 ),
				'modified'            => sanitize_text_field( (string) ( $revision->post_modified_gmt ?? $revision->post_modified ?? $revision->post_date ?? '' ) ),
				'author_id'           => $this->absint_value( $revision->post_author ?? 0 ),
				'title'               => sanitize_text_field( (string) ( $revision->post_title ?? '' ) ),
				'content_text_length' => $this->strlen_value( $this->strip_all_tags_value( $content ) ),
				'block_count'         => count( $this->parse_content_blocks( $content ) ),
				'excerpt'             => $this->build_revision_excerpt( $content ),
			);
		}

		$risk_flags = array_values( array_unique( array_map( 'sanitize_key', $risk_flags ) ) );
		$risk_level = 'none';
		if ( in_array( 'large_content_delta', $risk_flags, true ) || in_array( 'large_block_delta', $risk_flags, true ) ) {
			$risk_level = 'high';
		} elseif ( in_array( 'title_changed', $risk_flags, true ) || in_array( 'no_revisions', $risk_flags, true ) ) {
			$risk_level = 'medium';
		} elseif ( count( $recent_rows ) > 0 ) {
			$risk_level = 'low';
		}

		return $this->build_analysis_success_response(
			array(
				'post'             => array(
					'post_id'             => $post_id,
					'title'               => $current_title,
					'post_type'           => sanitize_key( (string) ( $post->post_type ?? '' ) ),
					'status'              => sanitize_key( (string) ( $post->post_status ?? '' ) ),
					'content_text_length' => $current_text_length,
					'block_count'         => $current_block_count,
					'edit_link'           => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
				),
				'revision_count'   => count( $revisions ),
				'risk_level'       => $risk_level,
				'risk_flags'       => $risk_flags,
				'latest_revision'  => $latest_summary,
				'change_summary'   => $change_summary,
				'recent_revisions' => $recent_rows,
				'summary'          => array(
					'max_revisions' => $max_revisions,
					'next_action'   => 'high' === $risk_level ? 'review_latest_revision_before_write' : 'continue_with_standard_review',
				),
			),
			array(
				'source'         => 'local_revision_change_risk_report',
				'execution_mode' => 'deterministic',
			),
			'Revision change risk report built.'
		);
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
	 * Collects taxonomy terms for a post context payload.
	 *
	 * @param int    $post_id Post id.
	 * @param string $post_type Post type.
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	private function collect_post_terms_context( $post_id, $post_type ) {
		$terms_context = array();
		if ( ! function_exists( 'get_object_taxonomies' ) || ! function_exists( 'get_the_terms' ) ) {
			return $terms_context;
		}

		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		foreach ( ( is_array( $taxonomies ) ? $taxonomies : array() ) as $taxonomy => $taxonomy_object ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			if ( '' === $taxonomy ) {
				continue;
			}
			$terms = get_the_terms( $post_id, $taxonomy );
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) {
				continue;
			}

			$rows = array();
			foreach ( ( is_array( $terms ) ? $terms : array() ) as $term ) {
				if ( ! is_object( $term ) ) {
					continue;
				}
				$rows[] = array(
					'id'          => $this->absint_value( $term->term_id ?? 0 ),
					'name'        => sanitize_text_field( (string) ( $term->name ?? '' ) ),
					'slug'        => sanitize_title( (string) ( $term->slug ?? '' ) ),
					'taxonomy'    => $taxonomy,
					'description' => $this->sanitize_metadata_text( (string) ( $term->description ?? '' ) ),
				);
			}

			if ( ! empty( $rows ) || ! empty( $taxonomy_object->show_ui ) ) {
				$terms_context[ $taxonomy ] = $rows;
			}
		}

		return $terms_context;
	}

	/**
	 * Collects featured and attached media for a post context payload.
	 *
	 * @param int $post_id Post id.
	 * @return array<string,mixed>
	 */
	private function collect_post_media_context( $post_id ) {
		$featured_id = function_exists( 'get_post_thumbnail_id' ) ? $this->absint_value( get_post_thumbnail_id( $post_id ) ) : 0;
		$featured = null;
		if ( $featured_id > 0 ) {
			$featured = $this->build_media_context_row( $featured_id, 'featured' );
		}

		$attachments = array();
		if ( function_exists( 'get_attached_media' ) ) {
			foreach ( (array) get_attached_media( '', $post_id ) as $attachment ) {
				$attachment_id = is_object( $attachment ) ? $this->absint_value( $attachment->ID ?? 0 ) : $this->absint_value( $attachment );
				if ( $attachment_id <= 0 ) {
					continue;
				}
				$attachments[] = $this->build_media_context_row( $attachment_id, 'attachment' );
				if ( count( $attachments ) >= 20 ) {
					break;
				}
			}
		}

		return array(
			'featured'    => $featured,
			'attachments' => $attachments,
			'total'       => count( $attachments ) + ( $featured ? 1 : 0 ),
		);
	}

	/**
	 * Queries post ids for inventory scans with an isolated-runtime fallback.
	 *
	 * @param string $post_type Post type.
	 * @param string $status Post status.
	 * @param int    $per_page Per page.
	 * @param int    $page Page.
	 * @return array<string,mixed>
	 */
	private function query_inventory_posts( $post_type, $status, $per_page, $page ) {
		$args = array(
			'post_type'              => $post_type,
			'post_status'            => 'any' === $status ? array( 'publish', 'draft', 'pending', 'future', 'private' ) : $status,
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( class_exists( '\WP_Query' ) ) {
			$query = new \WP_Query( $args );
			return array(
				'post_ids' => is_array( $query->posts ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $query->posts ) ) : array(),
				'total'    => (int) ( $query->found_posts ?? 0 ),
			);
		}

		$posts = function_exists( 'get_posts' ) ? get_posts( $args ) : array();
		$post_ids = array();
		foreach ( ( is_array( $posts ) ? $posts : array() ) as $post ) {
			$post_id = is_object( $post ) ? $this->absint_value( $post->ID ?? 0 ) : $this->absint_value( $post );
			if ( $post_id > 0 ) {
				$post_ids[] = $post_id;
			}
		}

		return array(
			'post_ids' => $post_ids,
			'total'    => count( $post_ids ),
		);
	}

	/**
	 * Queries media attachment ids for a bounded inventory scan.
	 *
	 * @param string $mime_type Mime type filter.
	 * @param string $search Search term.
	 * @param int    $per_page Per page.
	 * @param int    $page Page.
	 * @return array<string,mixed>
	 */
	private function query_media_inventory( $mime_type, $search, $per_page, $page ) {
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

		if ( class_exists( '\WP_Query' ) ) {
			$query = new \WP_Query( $args );
			return array(
				'attachment_ids' => is_array( $query->posts ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $query->posts ) ) : array(),
				'total'          => (int) ( $query->found_posts ?? 0 ),
			);
		}

		$attachments = isset( $GLOBALS['maa_unit_style_posts'] ) && is_array( $GLOBALS['maa_unit_style_posts'] )
			? array_values( $GLOBALS['maa_unit_style_posts'] )
			: array();
		$filtered = array();
		foreach ( $attachments as $attachment ) {
			if ( ! is_object( $attachment ) || 'attachment' !== sanitize_key( (string) ( $attachment->post_type ?? '' ) ) ) {
				continue;
			}
			$current_mime = sanitize_text_field( (string) ( $attachment->post_mime_type ?? '' ) );
			if ( '' !== $mime_type && false === strpos( $current_mime, $mime_type ) ) {
				continue;
			}
			$title = sanitize_text_field( (string) ( $attachment->post_title ?? '' ) );
			if ( '' !== $search && false === stripos( $title, $search ) ) {
				continue;
			}
			$filtered[] = $this->absint_value( $attachment->ID ?? 0 );
		}

		return array(
			'attachment_ids' => array_slice( array_filter( $filtered ), ( $page - 1 ) * $per_page, $per_page ),
			'total'          => count( $filtered ),
		);
	}

	/**
	 * Queries taxonomy terms for a bounded inventory scan.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param bool   $hide_empty Hide empty terms.
	 * @param int    $per_page Per page.
	 * @param int    $page Page.
	 * @return array<string,mixed>
	 */
	private function query_taxonomy_inventory_terms( $taxonomy, $hide_empty, $per_page, $page ) {
		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => (bool) $hide_empty,
			'number'     => $per_page,
			'offset'     => ( $page - 1 ) * $per_page,
			'orderby'    => 'count',
			'order'      => 'DESC',
		);

		if ( function_exists( 'get_terms' ) ) {
			$terms = get_terms( $args );
			if ( is_wp_error( $terms ) ) {
				return array(
					'terms' => array(),
					'total' => 0,
				);
			}

			$count_args = $args;
			$count_args['fields'] = 'count';
			$count_args['number'] = 0;
			$count_args['offset'] = 0;
			$total = get_terms( $count_args );
			return array(
				'terms' => is_array( $terms ) ? $terms : array(),
				'total' => is_wp_error( $total ) ? count( is_array( $terms ) ? $terms : array() ) : (int) $total,
			);
		}

		$terms = isset( $GLOBALS['maa_unit_terms'][ $taxonomy ] ) && is_array( $GLOBALS['maa_unit_terms'][ $taxonomy ] )
			? array_values( $GLOBALS['maa_unit_terms'][ $taxonomy ] )
			: array();
		if ( $hide_empty ) {
			$terms = array_values(
				array_filter(
					$terms,
					static function ( $term ) {
						return is_object( $term ) && (int) ( $term->count ?? 0 ) > 0;
					}
				)
			);
		}

		return array(
			'terms' => array_slice( $terms, ( $page - 1 ) * $per_page, $per_page ),
			'total' => count( $terms ),
		);
	}

	/**
	 * Builds one taxonomy inventory health row.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param object $term Term object.
	 * @param bool   $duplicate_slug Whether slug already appeared in the scan.
	 * @return array<string,mixed>
	 */
	private function build_taxonomy_inventory_health_row( $taxonomy, $term, $duplicate_slug ) {
		$term_id = $this->absint_value( $term->term_id ?? 0 );
		$name = sanitize_text_field( (string) ( $term->name ?? '' ) );
		$slug = sanitize_title( (string) ( $term->slug ?? '' ) );
		$description = $this->sanitize_metadata_text( (string) ( $term->description ?? '' ) );
		$count = $this->absint_value( $term->count ?? 0 );
		$parent = $this->absint_value( $term->parent ?? 0 );
		$issues = array();

		if ( '' === $name ) {
			$issues[] = 'empty_name';
		}
		if ( '' === $slug ) {
			$issues[] = 'empty_slug';
		}
		if ( '' === $description ) {
			$issues[] = 'missing_description';
		}
		if ( 0 === $count ) {
			$issues[] = 'unused_term';
		}
		if ( $duplicate_slug ) {
			$issues[] = 'duplicate_slug_in_scan';
		}
		if ( $parent > 0 && function_exists( 'get_term' ) ) {
			$parent_term = get_term( $parent, $taxonomy );
			if ( ! is_object( $parent_term ) || ( function_exists( 'is_wp_error' ) && is_wp_error( $parent_term ) ) ) {
				$issues[] = 'missing_parent';
			}
		}

		return array(
			'term_id'     => $term_id,
			'name'        => $name,
			'slug'        => $slug,
			'description' => $description,
			'count'       => $count,
			'parent'      => $parent,
			'issue_count' => count( $issues ),
			'issues'      => array_values( array_unique( $issues ) ),
			'edit_link'   => function_exists( 'get_edit_term_link' ) ? $this->esc_url_value( (string) get_edit_term_link( $term_id, $taxonomy ) ) : '',
		);
	}

	/**
	 * Reads revisions for a post with a unit-test fallback.
	 *
	 * @param int $post_id Post id.
	 * @return array<int,object>
	 */
	private function get_revision_objects_for_post( $post_id ) {
		$post_id = $this->absint_value( $post_id );
		$revisions = function_exists( 'wp_get_post_revisions' )
			? wp_get_post_revisions(
				$post_id,
				array(
					'check_enabled' => false,
					'order'         => 'DESC',
					'orderby'       => 'date ID',
				)
			)
			: array();
		$revisions = is_array( $revisions ) ? array_values( $revisions ) : array();

		if ( empty( $revisions ) && isset( $GLOBALS['maa_unit_style_posts'] ) && is_array( $GLOBALS['maa_unit_style_posts'] ) ) {
			foreach ( $GLOBALS['maa_unit_style_posts'] as $post ) {
				if ( is_object( $post ) && 'revision' === sanitize_key( (string) ( $post->post_type ?? '' ) ) && $post_id === $this->absint_value( $post->post_parent ?? 0 ) ) {
					$revisions[] = $post;
				}
			}
		}

		usort(
			$revisions,
			static function ( $a, $b ) {
				$a_time = strtotime( (string) ( is_object( $a ) ? ( $a->post_modified_gmt ?? $a->post_modified ?? $a->post_date ?? '' ) : '' ) );
				$b_time = strtotime( (string) ( is_object( $b ) ? ( $b->post_modified_gmt ?? $b->post_modified ?? $b->post_date ?? '' ) : '' ) );
				$a_time = false === $a_time ? 0 : $a_time;
				$b_time = false === $b_time ? 0 : $b_time;
				if ( $a_time === $b_time ) {
					return (int) ( is_object( $b ) ? ( $b->ID ?? 0 ) : 0 ) <=> (int) ( is_object( $a ) ? ( $a->ID ?? 0 ) : 0 );
				}
				return $b_time <=> $a_time;
			}
		);

		return array_values(
			array_filter(
				$revisions,
				static function ( $revision ) {
					return is_object( $revision );
				}
			)
		);
	}

	/**
	 * Parses Gutenberg blocks with a non-block fallback count.
	 *
	 * @param string $content Post content.
	 * @return array<int,mixed>
	 */
	private function parse_content_blocks( $content ) {
		if ( function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( (string) $content );
			if ( is_array( $blocks ) && ! empty( $blocks ) ) {
				return $blocks;
			}
		}

		$plain = trim( $this->strip_all_tags_value( (string) $content ) );
		return '' === $plain ? array() : array( array( 'blockName' => 'core/freeform' ) );
	}

	/**
	 * Builds one media inventory health row.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return array<string,mixed>
	 */
	private function build_media_inventory_health_row( $attachment_id ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$attachment = get_post( $attachment_id );
		$title = is_object( $attachment ) ? sanitize_text_field( (string) ( $attachment->post_title ?? '' ) ) : '';
		$caption = is_object( $attachment ) ? $this->sanitize_metadata_text( (string) ( $attachment->post_excerpt ?? '' ) ) : '';
		$description = is_object( $attachment ) ? $this->sanitize_metadata_text( (string) ( $attachment->post_content ?? '' ) ) : '';
		$mime_type = function_exists( 'get_post_mime_type' )
			? sanitize_text_field( (string) get_post_mime_type( $attachment_id ) )
			: ( is_object( $attachment ) ? sanitize_text_field( (string) ( $attachment->post_mime_type ?? '' ) ) : '' );
		$alt = function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) : '';
		$source_url = function_exists( 'get_post_meta' ) ? $this->esc_url_value( (string) get_post_meta( $attachment_id, '_magick_ai_source_page_url', true ) ) : '';
		$attribution = function_exists( 'get_post_meta' ) ? $this->sanitize_metadata_text( (string) get_post_meta( $attachment_id, '_magick_ai_attribution_text', true ) ) : '';
		$issues = array();
		if ( '' === $alt && 0 === strpos( $mime_type, 'image/' ) ) {
			$issues[] = 'missing_alt';
		}
		if ( '' === $caption ) {
			$issues[] = 'missing_caption';
		}
		if ( '' === $description ) {
			$issues[] = 'missing_description';
		}
		if ( '' === $source_url && '' === $attribution ) {
			$issues[] = 'missing_source';
		}
		if ( '' !== $mime_type && 0 === strpos( $mime_type, 'image/' ) && false === strpos( $mime_type, 'webp' ) && false === strpos( $mime_type, 'avif' ) ) {
			$issues[] = 'format_attention';
		}

		return array(
			'attachment_id' => $attachment_id,
			'title'         => $title,
			'mime_type'     => $mime_type,
			'url'           => function_exists( 'wp_get_attachment_url' ) ? $this->esc_url_value( (string) wp_get_attachment_url( $attachment_id ) ) : '',
			'alt'           => $alt,
			'caption'       => $caption,
			'description'   => $description,
			'issue_count'   => count( $issues ),
			'issues'        => $issues,
			'edit_link'     => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $attachment_id, 'raw' ) ) : '',
		);
	}

	/**
	 * Appends one SEO/GEO readiness check and recommendation.
	 *
	 * @param array<int,array<string,mixed>> $checks Checks.
	 * @param array<int,array<string,mixed>> $recommendations Recommendations.
	 * @param string                         $key Check key.
	 * @param bool                           $passed Passed.
	 * @param string                         $severity Severity.
	 * @param string                         $passed_detail Passed detail.
	 * @param string                         $failed_detail Failed detail.
	 * @return void
	 */
	private function append_readiness_check( array &$checks, array &$recommendations, $key, $passed, $severity, $passed_detail, $failed_detail ) {
		$key = sanitize_key( (string) $key );
		$severity = sanitize_key( (string) $severity );
		if ( ! in_array( $severity, array( 'high', 'medium', 'low' ), true ) ) {
			$severity = 'medium';
		}
		$checks[] = array(
			'key'      => $key,
			'status'   => $passed ? 'pass' : 'fail',
			'severity' => $passed ? 'none' : $severity,
			'detail'   => $passed ? sanitize_text_field( (string) $passed_detail ) : sanitize_text_field( (string) $failed_detail ),
		);
		if ( ! $passed ) {
			$recommendations[] = array(
				'key'      => $key,
				'priority' => $severity,
				'detail'   => sanitize_text_field( (string) $failed_detail ),
			);
		}
	}

	/**
	 * Collects topic terms from title, terms, content, and optional seed.
	 *
	 * @param int    $post_id Post id.
	 * @param string $title Title.
	 * @param string $content Plain content.
	 * @param string $topic_seed Optional topic seed.
	 * @return array<int,string>
	 */
	private function collect_topic_terms_for_post( $post_id, $title, $content, $topic_seed ) {
		$taxonomy_terms = array();
		if ( function_exists( 'get_the_terms' ) ) {
			foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
				$taxonomy_terms = array_merge( $taxonomy_terms, $this->format_term_name_list( get_the_terms( $post_id, $taxonomy ) ) );
			}
		}

		$terms = $this->collect_focus_terms( $title . ' ' . $this->trim_words_value( $content, 24 ), $topic_seed, $taxonomy_terms );
		$stop_words = array( 'the', 'and', 'for', 'with', 'this', 'that', 'from', 'your', 'you', 'are', 'was', 'were', 'post', 'page', 'article' );
		$filtered = array();
		foreach ( $terms as $term ) {
			$term = trim( sanitize_text_field( (string) $term ) );
			if ( '' === $term || in_array( strtolower( $term ), $stop_words, true ) || $this->strlen_value( $term ) < 3 ) {
				continue;
			}
			$filtered[] = $term;
		}

		return array_slice( array_values( array_unique( $filtered ) ), 0, 8 );
	}

	/**
	 * Guesses a focus keyword from SEO metadata or title.
	 *
	 * @param int    $post_id Post id.
	 * @param string $title Post title.
	 * @return string
	 */
	private function guess_focus_keyword_from_post( $post_id, $title ) {
		$seo_provider = $this->detect_seo_provider();
		$seo_keys = $this->seo_meta_keys( $seo_provider );
		$candidates = array(
			'_yoast_wpseo_focuskw',
			'rank_math_focus_keyword',
			'aioseo_keywords',
			(string) ( $seo_keys['focus_keyword'] ?? '' ),
		);

		if ( function_exists( 'get_post_meta' ) ) {
			foreach ( array_unique( array_filter( $candidates ) ) as $meta_key ) {
				$value = sanitize_text_field( (string) get_post_meta( $post_id, $meta_key, true ) );
				if ( '' !== $value ) {
					$parts = preg_split( '/[,，、|]+/u', $value );
					$parts = is_array( $parts ) ? $parts : array();
					$first = trim( (string) ( $parts[0] ?? '' ) );
					if ( '' !== $first ) {
						return sanitize_text_field( $first );
					}
				}
			}
		}

		$title = $this->normalize_analysis_plain_text( $title );
		$tokens = preg_split( '/[\s,，、\-\/|:：]+/u', $title );
		foreach ( is_array( $tokens ) ? $tokens : array() as $token ) {
			$token = trim( (string) $token );
			if ( $this->strlen_value( $token ) >= 3 ) {
				return sanitize_text_field( $token );
			}
		}

		return sanitize_text_field( $title );
	}

	/**
	 * Collects internal-link targets for a post.
	 *
	 * @param int               $current_post_id Current post id.
	 * @param array<int,string> $terms Focus terms.
	 * @param int               $max_targets Max targets.
	 * @return array<int,array<string,mixed>>
	 */
	private function collect_internal_link_targets_for_post( $current_post_id, array $terms, $max_targets ) {
		$targets = array();
		foreach ( array_slice( $terms, 0, 6 ) as $term ) {
			$term = sanitize_text_field( (string) $term );
			if ( '' === $term ) {
				continue;
			}

			$post_ids = $this->query_internal_link_candidate_ids( $current_post_id, $term, $max_targets );
			foreach ( $post_ids as $post_id ) {
				$post_id = $this->absint_value( $post_id );
				if ( $post_id <= 0 || $post_id === $this->absint_value( $current_post_id ) || isset( $targets[ $post_id ] ) ) {
					continue;
				}
				$post = get_post( $post_id );
				if ( ! is_object( $post ) || ! current_user_can( 'edit_post', $post_id ) ) {
					continue;
				}
				$targets[ $post_id ] = array(
					'post_id'         => $post_id,
					'title'           => sanitize_text_field( (string) get_the_title( $post_id ) ),
					'post_type'       => sanitize_key( (string) ( $post->post_type ?? '' ) ),
					'url'             => function_exists( 'get_permalink' ) ? $this->esc_url_value( (string) get_permalink( $post_id ) ) : '',
					'edit_link'       => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
					'anchor_text'     => $term,
					'reason'          => sprintf( __( 'Existing content is related to "%s" and can support the source post with contextual reading.', 'magick-ai-abilities' ), $term ),
					'relevance_score' => 0.72,
				);
				if ( count( $targets ) >= $max_targets ) {
					break 2;
				}
			}
		}

		return array_values( $targets );
	}

	/**
	 * Queries internal-link candidate ids for one term.
	 *
	 * @param int    $current_post_id Current post id.
	 * @param string $term Search term.
	 * @param int    $max_targets Max targets.
	 * @return array<int,int>
	 */
	private function query_internal_link_candidate_ids( $current_post_id, $term, $max_targets ) {
		$args = array(
			'post_type'      => 'any',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => $max_targets,
			's'              => $term,
			'post__not_in'   => $current_post_id > 0 ? array( $current_post_id ) : array(),
			'fields'         => 'ids',
		);

		if ( class_exists( '\WP_Query' ) ) {
			$query = new \WP_Query( $args );
			return is_array( $query->posts ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $query->posts ) ) : array();
		}

		$posts = function_exists( 'get_posts' ) ? get_posts( $args ) : array();
		$post_ids = array();
		foreach ( ( is_array( $posts ) ? $posts : array() ) as $post ) {
			$post_id = is_object( $post ) ? $this->absint_value( $post->ID ?? 0 ) : $this->absint_value( $post );
			if ( $post_id > 0 && $post_id !== $this->absint_value( $current_post_id ) ) {
				$post_ids[] = $post_id;
			}
		}

		return $post_ids;
	}

	/**
	 * Builds one publish risk item.
	 *
	 * @param string $key Risk key.
	 * @param string $severity Risk severity.
	 * @param string $detail Detail.
	 * @return array<string,string>
	 */
	private function build_publish_risk_item( $key, $severity, $detail ) {
		$severity = sanitize_key( (string) $severity );
		if ( ! in_array( $severity, array( 'high', 'medium', 'low' ), true ) ) {
			$severity = 'medium';
		}

		return array(
			'key'      => sanitize_key( (string) $key ),
			'severity' => $severity,
			'detail'   => sanitize_text_field( (string) $detail ),
		);
	}

	/**
	 * Counts comments for one status with isolated-runtime fallback.
	 *
	 * @param string $status Comment status.
	 * @return int
	 */
	private function count_comments_for_status( $status ) {
		$status = sanitize_key( (string) $status );
		if ( ! function_exists( 'get_comments' ) ) {
			return 0;
		}
		$count = get_comments(
			array(
				'status' => '' !== $status ? $status : 'hold',
				'count'  => true,
				'number' => 0,
				'offset' => 0,
			)
		);
		if ( is_numeric( $count ) ) {
			return max( 0, (int) $count );
		}
		if ( is_array( $count ) ) {
			return count( $count );
		}

		return 0;
	}

	/**
	 * Extracts links from content that point at scanned posts.
	 *
	 * @param int               $source_post_id Source post id.
	 * @param string            $content Content.
	 * @param array<int,string> $url_map Post id to URL map.
	 * @return array<int,int>
	 */
	private function extract_post_internal_links( $source_post_id, $content, array $url_map ) {
		$links = array();
		$source_post_id = $this->absint_value( $source_post_id );
		$content = (string) $content;
		preg_match_all( '/href=["\']([^"\']+)["\']/i', $content, $matches );
		$hrefs = is_array( $matches[1] ?? null ) ? $matches[1] : array();
		foreach ( $url_map as $target_id => $url ) {
			$target_id = $this->absint_value( $target_id );
			if ( $target_id <= 0 || $target_id === $source_post_id ) {
				continue;
			}
			$url = (string) $url;
			foreach ( $hrefs as $href ) {
				$href = (string) $href;
				if ( '' !== $url && false !== strpos( $href, $url ) ) {
					$links[ $target_id ] = $target_id;
					break;
				}
				if ( false !== strpos( $href, 'p=' . $target_id ) || false !== strpos( $href, '/?p=' . $target_id ) ) {
					$links[ $target_id ] = $target_id;
					break;
				}
			}
		}

		if ( empty( $url_map ) ) {
			foreach ( $hrefs as $href ) {
				$href = (string) $href;
				if ( 1 === preg_match( '/(?:^|[?&])p=(\d+)/', $href, $id_match ) ) {
					$target_id = $this->absint_value( $id_match[1] ?? 0 );
					if ( $target_id > 0 && $target_id !== $source_post_id ) {
						$links[ $target_id ] = $target_id;
					}
				}
			}
		}

		return array_values( $links );
	}

	/**
	 * Returns a stable URL for graph matching.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function post_graph_url( $post_id ) {
		$post_id = $this->absint_value( $post_id );
		if ( function_exists( 'get_permalink' ) ) {
			return $this->esc_url_value( (string) get_permalink( $post_id ) );
		}

		return 'https://example.test/?p=' . $post_id;
	}

	/**
	 * Normalizes a term name for consolidation grouping.
	 *
	 * @param string $value Term name or slug.
	 * @return string
	 */
	private function normalize_taxonomy_consolidation_key( $value ) {
		$value = function_exists( 'remove_accents' ) ? remove_accents( (string) $value ) : (string) $value;
		$value = strtolower( trim( $value ) );
		$value = preg_replace( '/[^a-z0-9\p{Han}]+/u', '', $value );
		return sanitize_key( (string) $value );
	}

	/**
	 * Builds calendar rows from ids.
	 *
	 * @param array<int,int> $post_ids Post ids.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_calendar_rows_from_ids( array $post_ids ) {
		$rows = array();
		foreach ( $post_ids as $post_id ) {
			$post_id = $this->absint_value( $post_id );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( is_object( $post ) ) {
				$rows[] = $this->build_calendar_post_row( $post_id, $post );
			}
		}

		return $rows;
	}

	/**
	 * Builds one calendar post row.
	 *
	 * @param int    $post_id Post id.
	 * @param object $post Post object.
	 * @return array<string,mixed>
	 */
	private function build_calendar_post_row( $post_id, $post ) {
		return array(
			'post_id'   => $this->absint_value( $post_id ),
			'title'     => sanitize_text_field( (string) get_the_title( $post_id ) ),
			'status'    => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'post_date' => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
			'modified'  => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
			'edit_link' => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
		);
	}

	/**
	 * Builds one normalized media context row.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $role Media role.
	 * @return array<string,mixed>
	 */
	private function build_media_context_row( $attachment_id, $role ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$attachment = $attachment_id > 0 ? get_post( $attachment_id ) : null;
		$alt = function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) : '';

		return array(
			'id'          => $attachment_id,
			'role'        => sanitize_key( (string) $role ),
			'title'       => is_object( $attachment ) ? sanitize_text_field( (string) ( $attachment->post_title ?? '' ) ) : '',
			'mime_type'   => is_object( $attachment ) ? sanitize_text_field( (string) ( $attachment->post_mime_type ?? '' ) ) : '',
			'url'         => function_exists( 'wp_get_attachment_url' ) ? $this->esc_url_value( (string) wp_get_attachment_url( $attachment_id ) ) : '',
			'alt'         => $alt,
			'caption'     => is_object( $attachment ) ? $this->sanitize_metadata_text( (string) ( $attachment->post_excerpt ?? '' ) ) : '',
			'description' => is_object( $attachment ) ? $this->sanitize_metadata_text( (string) ( $attachment->post_content ?? '' ) ) : '',
		);
	}

	/**
	 * Appends one publishing checklist row.
	 *
	 * @param array<int,array<string,mixed>> $checks Checklist rows.
	 * @param string                         $key Check key.
	 * @param bool                           $passed Whether the check passed.
	 * @param string                         $failure_severity fail or warning.
	 * @param string                         $passed_detail Passed detail.
	 * @param string                         $failed_detail Failed detail.
	 * @return void
	 */
	private function append_publishing_check( array &$checks, $key, $passed, $failure_severity, $passed_detail, $failed_detail ) {
		$failure_severity = sanitize_key( (string) $failure_severity );
		if ( ! in_array( $failure_severity, array( 'fail', 'warning' ), true ) ) {
			$failure_severity = 'warning';
		}
		$checks[] = array(
			'key'      => sanitize_key( (string) $key ),
			'status'   => $passed ? 'pass' : ( 'fail' === $failure_severity ? 'fail' : 'warning' ),
			'severity' => $passed ? 'none' : $failure_severity,
			'detail'   => $passed ? sanitize_text_field( (string) $passed_detail ) : sanitize_text_field( (string) $failed_detail ),
		);
	}

	/**
	 * Trims plain text by words with an isolated-runtime fallback.
	 *
	 * @param string $text Text.
	 * @param int    $word_count Word count.
	 * @return string
	 */
	private function trim_words_value( $text, $word_count ) {
		if ( function_exists( 'wp_trim_words' ) ) {
			return wp_trim_words( (string) $text, (int) $word_count );
		}

		$text = trim( preg_replace( '/\s+/', ' ', (string) $text ) ?? '' );
		if ( '' === $text ) {
			return '';
		}
		$words = preg_split( '/\s+/', $text );
		$words = is_array( $words ) ? $words : array();
		if ( count( $words ) <= $word_count ) {
			return $text;
		}

		return implode( ' ', array_slice( $words, 0, max( 1, (int) $word_count ) ) ) . '...';
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
	 * Normalizes metadata-plan mode flags.
	 *
	 * @param mixed $mode Raw mode.
	 * @return string
	 */
	private function normalize_metadata_plan_mode( $mode ) {
		$mode = sanitize_key( (string) $mode );
		return in_array( $mode, array( 'auto', 'explicit', 'skip' ), true ) ? $mode : 'auto';
	}

	/**
	 * Resolves one optional metadata text field.
	 *
	 * @param mixed  $value Raw explicit value.
	 * @param string $mode Normalized mode.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private function resolve_optional_metadata_text( $value, $mode, $fallback = '' ) {
		if ( 'skip' === $mode ) {
			return '';
		}

		$resolved = 'explicit' === $mode
			? $this->sanitize_metadata_text( (string) $value )
			: (string) $fallback;

		return trim( $resolved );
	}

	/**
	 * Sanitizes metadata-plan text in WordPress and isolated test runtimes.
	 *
	 * @param string $value Raw text.
	 * @return string
	 */
	private function sanitize_metadata_text( $value ) {
		if ( function_exists( 'sanitize_textarea_field' ) ) {
			return sanitize_textarea_field( (string) $value );
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitizes one plain text value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_text_value( $value ) {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Reads one apply-plan mode value.
	 *
	 * @param array<string,mixed> $plan Plan input.
	 * @param string              $key Plan key.
	 * @param string              $default Default mode.
	 * @return string
	 */
	private function read_plan_mode( array $plan, $key, $default ) {
		$value = sanitize_key( (string) ( $plan[ $key ] ?? $default ) );
		return '' !== $value ? $value : $default;
	}

	/**
	 * Counts recommendations in one section.
	 *
	 * @param array<int,mixed> $rows Recommendation rows.
	 * @param string           $section Section key.
	 * @return int
	 */
	private function count_recommendation_section( array $rows, $section ) {
		$count = 0;
		foreach ( $rows as $row ) {
			$row = is_array( $row ) ? $row : array();
			if ( $section === sanitize_key( (string) ( $row['section'] ?? '' ) ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Normalizes plain text for local analysis.
	 *
	 * @param mixed $value Raw text.
	 * @return string
	 */
	private function normalize_analysis_plain_text( $value ) {
		$text = $this->strip_all_tags_value( (string) $value );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return is_string( $text ) ? trim( $text ) : '';
	}

	/**
	 * Splits local analysis text into sentences.
	 *
	 * @param string $text Text.
	 * @return array<int,string>
	 */
	private function split_analysis_sentences( $text ) {
		$parts = preg_split( '/(?<=[。！？!?\.])\s*/u', (string) $text );
		return array_values(
			array_filter(
				array_map(
					static function ( $part ) {
						return trim( (string) $part );
					},
					is_array( $parts ) ? $parts : array()
				)
			)
		);
	}

	/**
	 * Collects article focus terms.
	 *
	 * @param string           $title Title.
	 * @param string           $focus_keyword Focus keyword.
	 * @param array<int,mixed> $keywords Keywords.
	 * @return array<int,string>
	 */
	private function collect_article_focus_terms( $title, $focus_keyword = '', array $keywords = array() ) {
		$terms = array();
		$title = $this->normalize_analysis_plain_text( $title );
		$focus_keyword = $this->normalize_analysis_plain_text( $focus_keyword );
		if ( '' !== $focus_keyword ) {
			$terms[] = $focus_keyword;
		}
		if ( '' !== $title ) {
			$terms[] = $title;
			$title_tokens = preg_split( '/[\s,，、\-\/|]+/u', $title );
			foreach ( is_array( $title_tokens ) ? $title_tokens : array() as $token ) {
				$token = trim( (string) $token );
				if ( '' !== $token && $this->strlen_value( $token ) >= 2 ) {
					$terms[] = $token;
				}
			}
		}
		foreach ( $keywords as $keyword ) {
			$keyword = $this->normalize_analysis_plain_text( $keyword );
			if ( '' !== $keyword ) {
				$terms[] = $keyword;
			}
		}

		return array_slice( array_values( array_unique( array_filter( $terms ) ) ), 0, 8 );
	}

	/**
	 * Collects question candidates from article content.
	 *
	 * @param string $content Content.
	 * @param string $title Title.
	 * @param string $focus_keyword Focus keyword.
	 * @return array<int,array<string,string>>
	 */
	private function collect_article_question_candidates( $content, $title = '', $focus_keyword = '' ) {
		$sentences = $this->split_analysis_sentences( $content );
		$questions = array();
		foreach ( $sentences as $sentence ) {
			if ( false === strpos( $sentence, '？' ) && false === strpos( $sentence, '?' ) ) {
				continue;
			}
			$answer_hint = '';
			foreach ( $sentences as $candidate ) {
				if ( $candidate !== $sentence && $this->strlen_value( $candidate ) >= 18 ) {
					$answer_hint = $candidate;
					break;
				}
			}
			$questions[] = array(
				'question'    => $sentence,
				'answer_hint' => $answer_hint,
			);
		}
		if ( empty( $questions ) ) {
			$focus = '' !== $focus_keyword ? $focus_keyword : $title;
			if ( '' !== $focus ) {
				$questions[] = array(
					'question'    => sprintf( '%s 是什么？', $focus ),
					'answer_hint' => sprintf( '建议在正文中补一段直接回答"%s 是什么、适合谁、为什么重要"的摘要。', $focus ),
				);
				$questions[] = array(
					'question'    => sprintf( '%s 如何落地？', $focus ),
					'answer_hint' => sprintf( '建议补一段步骤化回答，直接说明 %s 的执行路径和注意事项。', $focus ),
				);
			}
		}

		return array_slice( $questions, 0, 3 );
	}

	/**
	 * Collects media assets from explicit rows or inline image HTML.
	 *
	 * @param array<int,array<string,mixed>> $media_assets Media assets.
	 * @param string                         $article_content Article content.
	 * @return array<int,array<string,mixed>>
	 */
	private function collect_media_assets_for_optimization( array $media_assets, $article_content ) {
		$assets = array();
		foreach ( $media_assets as $asset ) {
			$asset = is_array( $asset ) ? $asset : array();
			$assets[] = array(
				'attachment_id'           => $this->absint_value( $asset['attachment_id'] ?? 0 ),
				'url'                     => $this->esc_url_value( (string) ( $asset['url'] ?? '' ) ),
				'title'                   => sanitize_text_field( (string) ( $asset['title'] ?? '' ) ),
				'alt'                     => sanitize_text_field( (string) ( $asset['alt'] ?? '' ) ),
				'caption'                 => sanitize_textarea_field( (string) ( $asset['caption'] ?? '' ) ),
				'description'             => sanitize_textarea_field( (string) ( $asset['description'] ?? '' ) ),
				'image_origin'            => sanitize_key( (string) ( $asset['image_origin'] ?? '' ) ),
				'generated_prompt'        => sanitize_textarea_field( (string) ( $asset['generated_prompt'] ?? '' ) ),
				'image_profile'           => sanitize_key( (string) ( $asset['image_profile'] ?? '' ) ),
				'mime_type'               => sanitize_text_field( (string) ( $asset['mime_type'] ?? '' ) ),
				'role'                    => sanitize_key( (string) ( $asset['role'] ?? '' ) ),
				'provider_hint'           => sanitize_key( (string) ( $asset['provider_hint'] ?? '' ) ),
				'provider_title'          => sanitize_text_field( (string) ( $asset['provider_title'] ?? '' ) ),
				'provider_description'    => sanitize_textarea_field( (string) ( $asset['provider_description'] ?? '' ) ),
				'source_page_url'         => $this->esc_url_value( (string) ( $asset['source_page_url'] ?? '' ) ),
				'photographer_name'       => sanitize_text_field( (string) ( $asset['photographer_name'] ?? '' ) ),
				'attribution_text'        => sanitize_textarea_field( (string) ( $asset['attribution_text'] ?? '' ) ),
				'license'                 => sanitize_text_field( (string) ( $asset['license'] ?? $asset['license_policy'] ?? '' ) ),
				'copyright_notice'        => sanitize_textarea_field( (string) ( $asset['copyright_notice'] ?? '' ) ),
				'section_heading'         => sanitize_text_field( (string) ( $asset['section_heading'] ?? '' ) ),
				'section_summary'         => sanitize_textarea_field( (string) ( $asset['section_summary'] ?? '' ) ),
				'file_name'               => sanitize_text_field( (string) ( $asset['file_name'] ?? '' ) ),
				'vision_fallback_allowed' => ! empty( $asset['vision_fallback_allowed'] ),
			);
		}
		if ( ! empty( $assets ) ) {
			return $assets;
		}

		if ( preg_match_all( '/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/iu', (string) $article_content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$html = (string) ( $match[0] ?? '' );
				$url = $this->esc_url_value( (string) ( $match[1] ?? '' ) );
				$alt = '';
				if ( preg_match( '/alt=["\']([^"\']*)["\']/iu', $html, $alt_match ) ) {
					$alt = sanitize_text_field( (string) ( $alt_match[1] ?? '' ) );
				}
				if ( '' === $url ) {
					continue;
				}
				$assets[] = array(
					'attachment_id' => 0,
					'url'           => $url,
					'title'         => '',
					'alt'           => $alt,
					'caption'       => '',
					'description'   => '',
					'mime_type'     => '',
					'role'          => 'inline',
				);
			}
		}

		return $assets;
	}

	/**
	 * Trims media SEO text.
	 *
	 * @param string $value Value.
	 * @param int    $max_len Max length.
	 * @return string
	 */
	private function trim_media_seo_text( $value, $max_len = 180 ) {
		$value = trim( preg_replace( '/\s+/u', ' ', (string) $value ) ?? '' );
		if ( '' === $value ) {
			return '';
		}

		return $this->strlen_value( $value ) > $max_len ? trim( $this->substr_value( $value, 0, max( 0, $max_len - 1 ) ) ) : $value;
	}

	/**
	 * Resolves media license label.
	 *
	 * @param array<string,mixed> $asset Asset.
	 * @param string              $image_origin Image origin.
	 * @return string
	 */
	private function resolve_media_license_label( array $asset, $image_origin ) {
		$license = sanitize_text_field( (string) ( $asset['license'] ?? $asset['license_policy'] ?? '' ) );
		if ( '' !== $license ) {
			return $license;
		}
		$source_page_url = $this->esc_url_value( (string) ( $asset['source_page_url'] ?? '' ) );
		$attribution_text = sanitize_textarea_field( (string) ( $asset['attribution_text'] ?? '' ) );
		$photographer_name = sanitize_text_field( (string) ( $asset['photographer_name'] ?? '' ) );
		if ( 'public_free' === $image_origin && ( '' !== $source_page_url || '' !== $attribution_text || '' !== $photographer_name ) ) {
			return 'attribution_required';
		}
		if ( 'ai_generated' === $image_origin ) {
			return 'generated_content';
		}

		return '';
	}

	/**
	 * Resolves media disclosure readiness.
	 *
	 * @param string $image_origin Image origin.
	 * @param string $metadata_confidence Metadata confidence.
	 * @param string $license License.
	 * @param string $source_page_url Source page URL.
	 * @param string $attribution_text Attribution text.
	 * @param string $generated_prompt Generated prompt.
	 * @return string
	 */
	private function resolve_media_disclosure_readiness( $image_origin, $metadata_confidence, $license, $source_page_url, $attribution_text, $generated_prompt ) {
		$image_origin = sanitize_key( (string) $image_origin );
		$metadata_confidence = sanitize_key( (string) $metadata_confidence );
		if ( 'public_free' === $image_origin ) {
			return '' !== $license && '' !== $attribution_text ? 'ready_with_attribution' : 'needs_review';
		}
		if ( 'ai_generated' === $image_origin ) {
			return '' !== $generated_prompt || in_array( $metadata_confidence, array( 'high', 'medium' ), true ) ? 'disclosure_recommended' : 'needs_review';
		}
		if ( 'manual_upload' === $image_origin ) {
			if ( 'low' === $metadata_confidence ) {
				return 'needs_review';
			}
			return '' !== $license && '' !== $source_page_url ? 'ready_with_source_note' : 'needs_review';
		}

		return '';
	}

	/**
	 * Returns string length with mbstring fallback.
	 *
	 * @param string $value Value.
	 * @return int
	 */
	private function strlen_value( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value, 'UTF-8' ) : strlen( (string) $value );
	}

	/**
	 * Returns substring with mbstring fallback.
	 *
	 * @param string $value Value.
	 * @param int    $start Start.
	 * @param int    $length Length.
	 * @return string
	 */
	private function substr_value( $value, $start, $length ) {
		return function_exists( 'mb_substr' ) ? mb_substr( (string) $value, (int) $start, (int) $length, 'UTF-8' ) : substr( (string) $value, (int) $start, (int) $length );
	}

	/**
	 * Builds the migrated analysis success envelope shape.
	 *
	 * @param array<string,mixed> $data Data payload.
	 * @param array<string,mixed> $meta Meta payload.
	 * @param string              $message Message.
	 * @return array<string,mixed>
	 */
	private function build_analysis_success_response( array $data, array $meta, $message ) {
		$meta = array_merge(
			array(
				'contract_version' => 'v1',
			),
			$meta
		);

		return array(
			'success' => true,
			'data'    => $data,
			'meta'    => $meta,
			'message' => sanitize_text_field( (string) $message ),
		);
	}

	/**
	 * Builds a versioned read-cache key for bounded read-only ability responses.
	 *
	 * @param string              $namespace Cache namespace.
	 * @param array<string,mixed> $input Cache input.
	 * @return string
	 */
	private function build_read_cache_key( $namespace, array $input ) {
		$user_id = function_exists( 'get_current_user_id' ) ? $this->absint_value( get_current_user_id() ) : 0;
		$payload = array(
			'namespace' => sanitize_key( (string) $namespace ),
			'input'     => $input,
			'user_id'   => $user_id,
			'version'   => $this->get_read_cache_version(),
		);
		$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $payload ) : json_encode( $payload );

		return 'maa_read_' . substr( md5( (string) $encoded ), 0, 32 );
	}

	/**
	 * Returns one cached read response when available.
	 *
	 * @param string $cache_key Cache key.
	 * @return array<string,mixed>|null
	 */
	private function get_cached_read_response( $cache_key ) {
		if ( ! function_exists( 'get_transient' ) ) {
			return null;
		}
		$cached = get_transient( sanitize_key( (string) $cache_key ) );
		if ( ! is_array( $cached ) || ! isset( $cached['success'], $cached['data'] ) ) {
			return null;
		}
		if ( ! isset( $cached['meta'] ) || ! is_array( $cached['meta'] ) ) {
			$cached['meta'] = array();
		}
		$cached['meta']['cache_hit'] = true;

		return $cached;
	}

	/**
	 * Stores one bounded read-only response in a transient.
	 *
	 * @param string              $cache_key Cache key.
	 * @param array<string,mixed> $response Response payload.
	 * @param string              $source Cache source.
	 * @return void
	 */
	private function set_cached_read_response( $cache_key, array $response, $source ) {
		if ( ! function_exists( 'set_transient' ) ) {
			return;
		}
		if ( ! isset( $response['meta'] ) || ! is_array( $response['meta'] ) ) {
			$response['meta'] = array();
		}
		$ttl = defined( 'MINUTE_IN_SECONDS' ) ? 10 * MINUTE_IN_SECONDS : 10 * 60;
		$response['meta']['cache_source'] = sanitize_key( (string) $source );
		$response['meta']['cache_ttl'] = $ttl;
		set_transient( sanitize_key( (string) $cache_key ), $response, $ttl );
	}

	/**
	 * Returns the current read-cache version.
	 *
	 * @return int
	 */
	private function get_read_cache_version() {
		if ( function_exists( 'get_option' ) ) {
			return max( 1, (int) get_option( 'magick_ai_abilities_read_cache_version', 1 ) );
		}
		return 1;
	}

	/**
	 * Case-insensitive substring check with mbstring fallback.
	 *
	 * @param string $haystack Haystack.
	 * @param string $needle Needle.
	 * @return bool
	 */
	private function contains_text_ci( $haystack, $needle ) {
		$haystack = (string) $haystack;
		$needle = (string) $needle;
		if ( '' === $needle ) {
			return false;
		}
		if ( function_exists( 'mb_stripos' ) ) {
			return false !== mb_stripos( $haystack, $needle, 0, 'UTF-8' );
		}
		return false !== stripos( $haystack, $needle );
	}

	/**
	 * Formats a WordPress term list into sanitized names.
	 *
	 * @param mixed $terms Raw term result.
	 * @return array<int,string>
	 */
	private function format_term_name_list( $terms ) {
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) {
			return array();
		}
		$terms = is_array( $terms ) ? $terms : array();

		return array_values(
			array_filter(
				array_map(
					static function ( $term ) {
						return is_object( $term ) ? sanitize_text_field( (string) ( $term->name ?? '' ) ) : '';
					},
					$terms
				)
			)
		);
	}

	/**
	 * Detects the active SEO metadata provider with a standalone fallback.
	 *
	 * @return string
	 */
	private function detect_seo_provider() {
		if ( function_exists( 'magick_ai_bridge_detect_seo_provider' ) ) {
			return sanitize_key( (string) magick_ai_bridge_detect_seo_provider() );
		}

		return 'seo_adapter';
	}

	/**
	 * Returns SEO meta keys for one provider.
	 *
	 * @param string $provider SEO provider.
	 * @return array<string,string>
	 */
	private function seo_meta_keys( $provider ) {
		$provider = sanitize_key( (string) $provider );
		if ( function_exists( 'magick_ai_bridge_seo_meta_keys' ) ) {
			$keys = magick_ai_bridge_seo_meta_keys( $provider );
			if ( is_array( $keys ) ) {
				return $keys;
			}
		}

		return array(
			'title'       => '_yoast_wpseo_title',
			'description' => '_yoast_wpseo_metadesc',
		);
	}

	/**
	 * Builds deterministic title suggestion rows from context.
	 *
	 * @param string $title Candidate title.
	 * @param string $content Content context.
	 * @param int    $limit Max suggestions.
	 * @param string $fallback_reason Reason fallback.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_title_suggestions_from_context( $title, $content, $limit, $fallback_reason ) {
		if ( function_exists( 'magick_ai_build_title_suggestions_from_context' ) ) {
			$suggestions = magick_ai_build_title_suggestions_from_context( (string) $title, (string) $content, (int) $limit );
			if ( is_array( $suggestions ) ) {
				return $suggestions;
			}
		}

		return array(
			array(
				'title'      => sanitize_text_field( (string) $title ),
				'reason'     => sanitize_text_field( (string) $fallback_reason ),
				'confidence' => 0.72,
			),
		);
	}

	/**
	 * Collects de-duplicated keyword candidates for one article suggest run.
	 *
	 * @param array<int|string,mixed> $parts Raw text parts.
	 * @return array<int,string>
	 */
	private function collect_article_suggest_keywords( array $parts ) {
		$keywords = array();
		foreach ( $parts as $part ) {
			$text = trim( sanitize_text_field( (string) $part ) );
			if ( '' === $text ) {
				continue;
			}

			$keywords[] = $text;
			$tokens = preg_split( '/[\s,，、\/|;；:：]+/u', $text );
			foreach ( is_array( $tokens ) ? $tokens : array() as $token ) {
				$token = trim( sanitize_text_field( (string) $token ) );
				$length = function_exists( 'mb_strlen' ) ? mb_strlen( $token, 'UTF-8' ) : strlen( $token );
				if ( $length >= 2 ) {
					$keywords[] = $token;
				}
			}
		}

		return array_values(
			array_unique(
				array_filter(
					$keywords,
					static function ( $value ) {
						return '' !== (string) $value;
					}
				)
			)
		);
	}

	/**
	 * Returns lightweight term catalog for one taxonomy.
	 *
	 * @param string $taxonomy Taxonomy id.
	 * @return array<int,array<string,string>>
	 */
	private function get_article_suggest_taxonomy_catalog( $taxonomy ) {
		if ( ! function_exists( 'get_terms' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => sanitize_key( (string) $taxonomy ),
				'hide_empty' => false,
				'number'     => 50,
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) {
			return array();
		}
		if ( ! is_array( $terms ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					array( $this, 'format_article_suggest_term' ),
					$terms
				)
			)
		);
	}

	/**
	 * Formats one term object for article suggestions.
	 *
	 * @param mixed $term Term object.
	 * @return array<string,string>
	 */
	private function format_article_suggest_term( $term ) {
		if ( ! is_object( $term ) ) {
			return array();
		}

		return array(
			'name' => sanitize_text_field( (string) ( $term->name ?? '' ) ),
			'slug' => $this->sanitize_metadata_slug( (string) ( $term->slug ?? '' ) ),
		);
	}

	/**
	 * Matches keyword candidates against site taxonomy catalog.
	 *
	 * @param array<int,string>               $keywords Keyword candidates.
	 * @param array<int,array<string,string>> $catalog Site term catalog.
	 * @return array<int,array<string,string>>
	 */
	private function match_article_suggest_taxonomy_terms( array $keywords, array $catalog ) {
		$matches = array();
		foreach ( $catalog as $term ) {
			$name = sanitize_text_field( (string) ( $term['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}

			foreach ( $keywords as $keyword ) {
				$keyword = sanitize_text_field( (string) $keyword );
				if ( '' === $keyword ) {
					continue;
				}
				if ( $this->contains_text_ci( $name, $keyword ) || $this->contains_text_ci( $keyword, $name ) ) {
					$matches[] = array(
						'name' => $name,
						'slug' => $this->sanitize_metadata_slug( (string) ( $term['slug'] ?? '' ) ),
					);
					break;
				}
			}
		}

		$unique = array();
		foreach ( $matches as $term ) {
			$key = sanitize_key( $this->sanitize_metadata_slug( (string) ( $term['slug'] ?? $term['name'] ?? '' ) ) );
			if ( '' !== $key ) {
				$unique[ $key ] = $term;
			}
		}

		return array_values( $unique );
	}

	/**
	 * Builds one scalar field suggestion row.
	 *
	 * @param string $field Field id.
	 * @param string $label UI label.
	 * @param string $current Current value.
	 * @param string $suggested Suggested value.
	 * @param bool   $safe_apply Whether field is low-risk writable.
	 * @param string $reason Suggestion reason.
	 * @return array<string,mixed>
	 */
	private function build_article_suggest_field_row( $field, $label, $current, $suggested, $safe_apply, $reason ) {
		$field = sanitize_key( (string) $field );
		$current = in_array( $field, array( 'excerpt', 'seo_description' ), true )
			? $this->sanitize_metadata_text( (string) $current )
			: sanitize_text_field( (string) $current );
		$suggested = in_array( $field, array( 'excerpt', 'seo_description' ), true )
			? $this->sanitize_metadata_text( (string) $suggested )
			: sanitize_text_field( (string) $suggested );

		return array(
			'field'            => $field,
			'label'            => sanitize_text_field( (string) $label ),
			'current_value'    => $current,
			'suggested_value'  => $suggested,
			'recommend_apply'  => '' !== $suggested && $suggested !== $current,
			'safe_apply'       => (bool) $safe_apply,
			'reason'           => $this->sanitize_metadata_text( (string) $reason ),
		);
	}

	/**
	 * Builds one taxonomy suggestion row with site matching.
	 *
	 * @param string                          $field Field id.
	 * @param string                          $label UI label.
	 * @param array<int,mixed>                $current_items Current term names.
	 * @param array<int,mixed>                $suggested_items Suggested term candidates.
	 * @param array<int,array<string,string>> $catalog Site term catalog.
	 * @return array<string,mixed>
	 */
	private function build_article_suggest_taxonomy_row( $field, $label, array $current_items, array $suggested_items, array $catalog ) {
		$current_items = array_values(
			array_filter(
				array_map( 'sanitize_text_field', $current_items )
			)
		);
		$suggested_items = array_values(
			array_filter(
				array_map( 'sanitize_text_field', $suggested_items )
			)
		);
		$matched_terms = $this->match_article_suggest_taxonomy_terms( $suggested_items, $catalog );
		$matched_names = array_values(
			array_filter(
				array_map(
					static function ( array $term ) {
						return sanitize_text_field( (string) ( $term['name'] ?? '' ) );
					},
					$matched_terms
				)
			)
		);

		return array(
			'field'           => sanitize_key( (string) $field ),
			'label'           => sanitize_text_field( (string) $label ),
			'current_items'   => $current_items,
			'suggested_items' => array_slice( $suggested_items, 0, 5 ),
			'matched_items'   => $matched_names,
			'recommend_apply' => ! empty( $matched_names ),
			'safe_apply'      => false,
			'risk_note'       => empty( $matched_names )
				? '未匹配到站点现有词库，当前只建议人工判断，不默认新建。'
				: '已匹配到站点现有词库，第一阶段仍建议人工确认后再应用。',
		);
	}

	/**
	 * Builds normalized improvement rows from analysis recommendations.
	 *
	 * @param array<string,mixed> $analysis Analysis result.
	 * @param string              $field Field id.
	 * @param string              $fallback_type Fallback type.
	 * @return array<int,array<string,string>>
	 */
	private function build_analysis_improvement_rows( array $analysis, $field, $fallback_type ) {
		$rows = array();
		foreach ( (array) ( $analysis['recommendations'] ?? array() ) as $item ) {
			$item = is_array( $item ) ? $item : array();
			$rows[] = array(
				'type'     => sanitize_key( (string) ( $item['type'] ?? $fallback_type ) ),
				'priority' => sanitize_key( (string) ( $item['priority'] ?? 'medium' ) ),
				'field'    => sanitize_key( (string) $field ),
				'title'    => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $item['detail'] ?? '' ) ),
			);
		}

		return $rows;
	}

	/**
	 * Builds the summary recommendation list for single-article suggestions.
	 *
	 * @param array<string,mixed> $suggestions Suggestion map.
	 * @param array<int,mixed>    $content_improvements Content improvements.
	 * @param array<int,mixed>    $seo_improvements SEO improvements.
	 * @param array<int,mixed>    $geo_improvements GEO improvements.
	 * @return array<int,array<string,string>>
	 */
	private function build_article_single_suggest_recommendations( array $suggestions, array $content_improvements, array $seo_improvements, array $geo_improvements ) {
		$recommendations = array();
		foreach ( array( 'title', 'excerpt', 'slug', 'seo_title', 'seo_description' ) as $field_key ) {
			$field = is_array( $suggestions[ $field_key ] ?? null ) ? $suggestions[ $field_key ] : array();
			if ( empty( $field['recommend_apply'] ) ) {
				continue;
			}
			$recommendations[] = array(
				'priority' => in_array( $field_key, array( 'title', 'seo_title', 'seo_description' ), true ) ? 'high' : 'medium',
				'section'  => $field_key,
				'title'    => '建议更新' . sanitize_text_field( (string) ( $field['label'] ?? $field_key ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $field['suggested_value'] ?? '' ) ),
			);
		}
		foreach ( array( 'categories', 'tags' ) as $field_key ) {
			$field = is_array( $suggestions[ $field_key ] ?? null ) ? $suggestions[ $field_key ] : array();
			$items = (array) ( $field['matched_items'] ?? $field['suggested_items'] ?? array() );
			$detail = implode( '、', array_map( 'sanitize_text_field', $items ) );
			if ( '' === $detail ) {
				continue;
			}
			$recommendations[] = array(
				'priority' => 'medium',
				'section'  => $field_key,
				'title'    => '建议检查' . sanitize_text_field( (string) ( $field['label'] ?? $field_key ) ),
				'detail'   => $detail,
			);
		}
		foreach ( array( 'content_improvements' => $content_improvements, 'seo_improvements' => $seo_improvements, 'geo_improvements' => $geo_improvements ) as $section_key => $items ) {
			foreach ( array_slice( $items, 0, 2 ) as $item ) {
				$item = is_array( $item ) ? $item : array();
				$recommendations[] = array(
					'priority' => sanitize_key( (string) ( $item['priority'] ?? 'medium' ) ),
					'section'  => sanitize_key( (string) $section_key ),
					'title'    => sanitize_text_field( (string) ( $item['title'] ?? $section_key ) ),
					'detail'   => $this->sanitize_metadata_text( (string) ( $item['detail'] ?? '' ) ),
				);
			}
		}

		return $recommendations;
	}

	/**
	 * Sanitizes metadata-plan slugs in WordPress and isolated test runtimes.
	 *
	 * @param string $value Raw slug.
	 * @return string
	 */
	private function sanitize_metadata_slug( $value ) {
		if ( function_exists( 'sanitize_title' ) ) {
			return sanitize_title( (string) $value );
		}

		$value = strtolower( trim( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9_\-\s]+/i', '', $value );
		$value = preg_replace( '/[\s\-]+/', '-', is_string( $value ) ? $value : '' );
		return is_string( $value ) ? trim( $value, '-' ) : '';
	}

	/**
	 * Picks metadata-plan taxonomy terms with taxonomy-plan fallback.
	 *
	 * @param array<string,mixed> $post_metadata_plan Metadata plan.
	 * @param array<string,mixed> $taxonomy_plan Fallback taxonomy plan.
	 * @param string              $key Term list key.
	 * @return array<int,int>
	 */
	private function pick_metadata_plan_terms( array $post_metadata_plan, array $taxonomy_plan, $key ) {
		$metadata_terms = $this->normalize_metadata_plan_id_list( $post_metadata_plan[ $key ] ?? array() );
		if ( ! empty( $metadata_terms ) ) {
			return $metadata_terms;
		}

		return $this->normalize_metadata_plan_id_list( $taxonomy_plan[ $key ] ?? array() );
	}

	/**
	 * Normalizes ID-like list input into positive unique integers.
	 *
	 * @param mixed $value Raw list.
	 * @return array<int,int>
	 */
	private function normalize_metadata_plan_id_list( $value ) {
		$items = is_array( $value ) ? $value : array();
		return array_values(
			array_unique(
				array_filter(
					array_map(
						array( $this, 'absint_value' ),
						$items
					),
					static function ( $item ) {
						return (int) $item > 0;
					}
				)
			)
		);
	}

	/**
	 * Builds one stable fingerprint value for article production dedupe and summaries.
	 *
	 * @param array<string,mixed> $input Source input.
	 * @return string
	 */
	private function build_article_production_fingerprint_value( array $input ) {
		$reference_post_ids = is_array( $input['reference_post_ids'] ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $input['reference_post_ids'] ) ) : array();
		sort( $reference_post_ids );

		$fingerprint_seed = array(
			'topic'              => sanitize_text_field( (string) ( $input['topic'] ?? $input['prompt'] ?? '' ) ),
			'image_mode'         => sanitize_key( (string) ( $input['image_mode'] ?? 'featured_only' ) ),
			'publish_mode'       => sanitize_key( (string) ( $input['publish_mode'] ?? 'draft' ) ),
			'content_format'     => sanitize_key( (string) ( $input['content_format'] ?? 'html' ) ),
			'voice_profile'      => sanitize_key( (string) ( $input['voice_profile'] ?? '' ) ),
			'opening_style'      => sanitize_key( (string) ( $input['opening_style'] ?? '' ) ),
			'structure_style'    => sanitize_key( (string) ( $input['structure_style'] ?? '' ) ),
			'reference_post_ids' => $reference_post_ids,
		);
		$encoded_seed = function_exists( 'wp_json_encode' )
			? wp_json_encode( $fingerprint_seed )
			: json_encode( $fingerprint_seed );

		return substr( md5( (string) $encoded_seed ), 0, 16 );
	}

	/**
	 * Sanitizes and limits a list of text values.
	 *
	 * @param array<int,mixed> $values Raw values.
	 * @param int              $limit Maximum values.
	 * @return array<int,string>
	 */
	private function sanitize_limited_text_list( array $values, $limit ) {
		$items = array();
		foreach ( $values as $value ) {
			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value ) {
				$items[] = $value;
			}
			if ( count( $items ) >= (int) $limit ) {
				break;
			}
		}
		return array_values( $items );
	}

	/**
	 * Builds normalized recommendation improvement rows.
	 *
	 * @param array<string,mixed> $analysis Analysis payload.
	 * @param string              $fallback_type Fallback type.
	 * @return array<int,array<string,string>>
	 */
	private function build_recommendation_improvements( array $analysis, $fallback_type ) {
		$improvements = array();
		$recommendations = is_array( $analysis['recommendations'] ?? null ) ? $analysis['recommendations'] : array();
		foreach ( $recommendations as $item ) {
			$item = is_array( $item ) ? $item : array();
			$improvements[] = array(
				'type'     => sanitize_key( (string) ( $item['type'] ?? $fallback_type ) ),
				'priority' => sanitize_key( (string) ( $item['priority'] ?? 'medium' ) ),
				'title'    => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $item['detail'] ?? '' ) ),
			);
		}
		return $improvements;
	}

	/**
	 * Extracts source-reference notes from human signals.
	 *
	 * @param array<int,mixed> $human_signals Human signals.
	 * @return array<int,string>
	 */
	private function extract_source_references( array $human_signals ) {
		$references = array();
		foreach ( $human_signals as $value ) {
			$value = $this->sanitize_metadata_text( (string) $value );
			if ( false !== strpos( $value, '案例/数据来源：' ) ) {
				$reference = trim( str_replace( '案例/数据来源：', '', $value ) );
				if ( '' !== $reference ) {
					$references[] = $reference;
				}
			}
		}
		return array_values( $references );
	}

	/**
	 * Extracts AI-risk details by risk key.
	 *
	 * @param array<int,mixed> $risk_items Risk items.
	 * @param string           $key Risk key.
	 * @return array<int,string>
	 */
	private function extract_ai_risk_notes_by_key( array $risk_items, $key ) {
		$notes = array();
		$key = sanitize_key( (string) $key );
		foreach ( $risk_items as $risk_item ) {
			$risk_item = is_array( $risk_item ) ? $risk_item : array();
			if ( $key !== sanitize_key( (string) ( $risk_item['key'] ?? '' ) ) ) {
				continue;
			}
			$detail = $this->sanitize_metadata_text( (string) ( $risk_item['detail'] ?? '' ) );
			if ( '' !== $detail ) {
				$notes[] = $detail;
			}
		}
		return array_values( $notes );
	}

	/**
	 * Truncates UTF-8 text and uses the legacy Chinese ellipsis for migrated article contracts.
	 *
	 * @param string $text Raw text.
	 * @param int    $max_chars Maximum characters.
	 * @return string
	 */
	private function truncate_utf8_text( $text, $max_chars ) {
		$text = (string) $text;
		$max_chars = max( 1, (int) $max_chars );
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			return mb_strlen( $text, 'UTF-8' ) > $max_chars ? mb_substr( $text, 0, $max_chars, 'UTF-8' ) . '…' : $text;
		}
		return strlen( $text ) > $max_chars ? substr( $text, 0, $max_chars ) . '...' : $text;
	}

	/**
	 * Filters high-priority AI slop flags.
	 *
	 * @param array<int,mixed> $flags Flags.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_high_ai_slop_flags( array $flags ) {
		$high_flags = array();
		foreach ( $flags as $flag ) {
			$flag = is_array( $flag ) ? $flag : array();
			if ( in_array( sanitize_key( (string) ( $flag['severity'] ?? '' ) ), array( 'high', 'critical' ), true ) ) {
				$high_flags[] = $flag;
			}
		}
		return $high_flags;
	}

	/**
	 * Normalizes one positive integer in WordPress and isolated test runtimes.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	private function absint_value( $value ) {
		return function_exists( 'absint' ) ? absint( $value ) : abs( (int) $value );
	}

	/**
	 * Normalizes plain text in WordPress and isolated test runtimes.
	 *
	 * @param mixed $value Raw text.
	 * @return string
	 */
	private function normalize_plain_text( $value ) {
		$text = function_exists( 'wp_strip_all_tags' )
			? wp_strip_all_tags( (string) $value )
			: strip_tags( (string) $value );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( is_string( $text ) ? $text : '' );
	}

	/**
	 * Returns an empty style-profile response.
	 *
	 * @param string $source Optional baseline source.
	 * @return array<string,mixed>
	 */
	private function empty_style_profile_result( string $source ) {
		$data = array(
			'profile' => array(),
			'samples' => array(),
		);
		if ( '' !== $source ) {
			$data['source'] = $source;
		}

		return array(
			'success' => true,
			'data'    => $data,
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Builds a style profile from raw post objects.
	 *
	 * @param array<int,mixed> $posts Posts.
	 * @return array<string,mixed>
	 */
	private function build_style_profile_from_posts( array $posts ) {
		$opening_counts = array();
		$ending_counts = array();
		$voice_counts = array();
		$heading_total = 0;
		$paragraph_total = 0;
		$paragraph_lengths = array();
		$samples = array();

		foreach ( $posts as $post ) {
			if ( ! is_object( $post ) || empty( $post->ID ) ) {
				continue;
			}
			$content = (string) ( $post->post_content ?? '' );
			$paragraphs = $this->extract_article_style_paragraphs( $content );
			$opening = $this->classify_article_opening_style( (string) ( $paragraphs[0] ?? '' ) );
			$ending = $this->classify_article_ending_style( (string) ( ! empty( $paragraphs ) ? $paragraphs[ count( $paragraphs ) - 1 ] : '' ) );
			$voice = $this->classify_article_voice_profile( $content );
			$heading_count = $this->count_article_heading_blocks( $content );

			foreach ( $paragraphs as $paragraph ) {
				++$paragraph_total;
				$paragraph_lengths[] = $this->measure_style_text_length( (string) $paragraph );
			}

			$heading_total += $heading_count;
			$opening_counts[ $opening ] = (int) ( $opening_counts[ $opening ] ?? 0 ) + 1;
			$ending_counts[ $ending ] = (int) ( $ending_counts[ $ending ] ?? 0 ) + 1;
			$voice_counts[ $voice ] = (int) ( $voice_counts[ $voice ] ?? 0 ) + 1;
			$samples[] = array(
				'post_id'         => (int) $post->ID,
				'title'           => sanitize_text_field( function_exists( 'get_the_title' ) ? (string) get_the_title( $post ) : (string) ( $post->post_title ?? '' ) ),
				'opening_style'   => $opening,
				'ending_style'    => $ending,
				'voice_profile'   => $voice,
				'heading_count'   => $heading_count,
				'paragraph_count' => count( $paragraphs ),
			);
		}

		$sample_count = count( $samples );
		$average_paragraph_length = $paragraph_total > 0 ? (int) round( array_sum( $paragraph_lengths ) / $paragraph_total ) : 0;
		$short_paragraph_count = 0;
		foreach ( $paragraph_lengths as $paragraph_length ) {
			if ( $paragraph_length > 0 && $paragraph_length <= 48 ) {
				++$short_paragraph_count;
			}
		}
		$short_paragraph_ratio = $paragraph_total > 0 ? round( $short_paragraph_count / $paragraph_total, 2 ) : 0;
		$dominant_opening = $this->pick_dominant_style_value( $opening_counts );
		$dominant_ending = $this->pick_dominant_style_value( $ending_counts );
		$dominant_voice = $this->pick_dominant_style_value( $voice_counts );
		$heading_density = $sample_count > 0 ? round( $heading_total / $sample_count, 1 ) : 0;
		$structure_style = $short_paragraph_ratio >= 0.35 ? 'mixed_paragraph_lengths' : 'longer_paragraph_sections';
		if ( $heading_density >= 4 ) {
			$structure_style .= '_with_dense_headings';
		}

		$style_brief = array();
		if ( '' !== $dominant_opening ) {
			$style_brief[] = '开头多为 ' . $dominant_opening;
		}
		if ( '' !== $dominant_voice ) {
			$style_brief[] = '语气偏 ' . $dominant_voice;
		}
		if ( $average_paragraph_length > 0 ) {
			$style_brief[] = '段落平均长度约 ' . $average_paragraph_length . ' 字';
		}
		if ( $heading_density > 0 ) {
			$style_brief[] = '平均每篇约 ' . $heading_density . ' 个小标题';
		}
		if ( '' !== $dominant_ending ) {
			$style_brief[] = '结尾多为 ' . $dominant_ending;
		}

		return array(
			'profile' => array(
				'sample_count'             => $sample_count,
				'dominant_opening_style'   => $dominant_opening,
				'dominant_ending_style'    => $dominant_ending,
				'dominant_voice_profile'   => $dominant_voice,
				'structure_style'          => $structure_style,
				'average_paragraph_length' => $average_paragraph_length,
				'short_paragraph_ratio'    => $short_paragraph_ratio,
				'average_heading_count'    => $heading_density,
				'style_brief'              => implode( '，', $style_brief ),
			),
			'samples' => $samples,
		);
	}

	/**
	 * Extracts readable paragraph strings from article content.
	 *
	 * @param string $content Article content.
	 * @return array<int,string>
	 */
	private function extract_article_style_paragraphs( string $content ): array {
		$paragraphs = array();
		$parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		if ( ! empty( $parsed_blocks ) ) {
			foreach ( $parsed_blocks as $block ) {
				$block = is_array( $block ) ? $block : array();
				$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
				if ( ! in_array( $block_name, array( 'core/paragraph', 'core/quote', 'core/freeform' ), true ) ) {
					continue;
				}
				$text = trim( $this->strip_all_tags_value( (string) ( $block['innerHTML'] ?? '' ) ) );
				if ( '' !== $text ) {
					$paragraphs[] = $text;
				}
			}
		}

		if ( empty( $paragraphs ) ) {
			$raw_paragraphs = preg_split( '/\n\s*\n/u', trim( $this->strip_all_tags_value( $content ) ) ) ?: array();
			foreach ( $raw_paragraphs as $paragraph ) {
				$paragraph = trim( (string) $paragraph );
				if ( '' !== $paragraph ) {
					$paragraphs[] = $paragraph;
				}
			}
		}

		return array_values( $paragraphs );
	}

	/**
	 * Measures a text length with multibyte support.
	 *
	 * @param string $text Text.
	 * @return int
	 */
	private function measure_style_text_length( string $text ) {
		$text = trim( $text );
		if ( '' === $text ) {
			return 0;
		}
		if ( function_exists( 'mb_strlen' ) ) {
			return (int) mb_strlen( $text, 'UTF-8' );
		}
		return strlen( $text );
	}

	/**
	 * Counts heading blocks in one article body.
	 *
	 * @param string $content Article content.
	 * @return int
	 */
	private function count_article_heading_blocks( string $content ) {
		$parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		$heading_count = 0;
		foreach ( $parsed_blocks as $block ) {
			$block_name = sanitize_text_field( (string) ( is_array( $block ) ? ( $block['blockName'] ?? '' ) : '' ) );
			if ( 'core/heading' === $block_name ) {
				++$heading_count;
			}
		}
		return $heading_count;
	}

	/**
	 * Classifies one opening paragraph pattern.
	 *
	 * @param string $paragraph Paragraph text.
	 * @return string
	 */
	private function classify_article_opening_style( string $paragraph ): string {
		$paragraph = trim( $paragraph );
		if ( '' === $paragraph ) {
			return 'unknown';
		}
		if ( false !== strpos( $paragraph, '?' ) || false !== strpos( $paragraph, '？' ) ) {
			return 'question';
		}
		if ( preg_match( '/^(当|如果|最近|上周|有一次|很多时候|在)/u', $paragraph ) ) {
			return 'scene';
		}
		return 'direct_judgement';
	}

	/**
	 * Classifies one ending paragraph pattern.
	 *
	 * @param string $paragraph Paragraph text.
	 * @return string
	 */
	private function classify_article_ending_style( string $paragraph ) {
		$paragraph = trim( $paragraph );
		if ( '' === $paragraph ) {
			return 'unknown';
		}
		if ( preg_match( '/(建议|可以|不妨|值得|下一步)/u', $paragraph ) ) {
			return 'action';
		}
		if ( preg_match( '/(总的来说|总之|归根结底|一句话)/u', $paragraph ) ) {
			return 'summary';
		}
		return 'observation';
	}

	/**
	 * Classifies one article voice profile.
	 *
	 * @param string $content Plain content.
	 * @return string
	 */
	private function classify_article_voice_profile( string $content ): string {
		$content = $this->strip_all_tags_value( $content );
		if ( preg_match_all( '/(我|我们|团队|实操|踩坑)/u', $content ) >= 3 ) {
			return 'experiential_editorial';
		}
		if ( preg_match_all( '/[0-9]{2,}|%|增长|下降|数据|指标/u', $content ) >= 3 ) {
			return 'analytical_editorial';
		}
		return 'practical_editorial';
	}

	/**
	 * Picks one dominant style value from a count map.
	 *
	 * @param array<string,int> $counts Count map.
	 * @return string
	 */
	private function pick_dominant_style_value( array $counts ) {
		if ( empty( $counts ) ) {
			return '';
		}
		arsort( $counts );
		$key = key( $counts );
		return is_string( $key ) ? sanitize_key( $key ) : '';
	}

	/**
	 * Returns the current style baseline cache version.
	 *
	 * @return int
	 */
	private function get_style_baseline_cache_version() {
		if ( function_exists( 'get_option' ) ) {
			return max( 1, (int) get_option( 'magick_ai_style_baseline_cache_version', 1 ) );
		}
		return 1;
	}

	/**
	 * Builds a canonical lightweight AI-risk review payload.
	 *
	 * @param string            $content Article content.
	 * @param string            $platform_profile Platform profile.
	 * @param array<int,mixed>  $human_signals Human signal strings.
	 * @param string            $template_risk_level Template risk level.
	 * @param array<int,string> $template_findings Template findings.
	 * @param array<int,string> $style_findings Style findings.
	 * @return array<string,mixed>
	 */
	private function build_article_ai_risk_review( string $content, string $platform_profile, array $human_signals, string $template_risk_level, array $template_findings, array $style_findings ): array {
		$platform_profile = sanitize_key( $platform_profile );
		if ( ! in_array( $platform_profile, array( 'generic', 'wechat', 'xiaohongshu' ), true ) ) {
			$platform_profile = 'generic';
		}

		$items = array();
		$explicit_signal_count = 0;
		$source_reference_count = 0;
		foreach ( $human_signals as $signal ) {
			$signal = $this->sanitize_metadata_text( (string) $signal );
			if ( '' === $signal ) {
				continue;
			}
			if ( false !== strpos( $signal, '真实经历：' ) || false !== strpos( $signal, '作者观点：' ) || false !== strpos( $signal, '想强调的结论：' ) ) {
				++$explicit_signal_count;
			}
			if ( false !== strpos( $signal, '案例/数据来源：' ) ) {
				++$source_reference_count;
			}
		}

		if ( 'high' === $template_risk_level || ! empty( $template_findings ) ) {
			$items[] = array(
				'key'      => 'template_tone',
				'severity' => 'high' === $template_risk_level ? 'high' : 'medium',
				'title'    => '模板味偏重',
				'detail'   => ! empty( $template_findings ) ? $this->sanitize_metadata_text( (string) $template_findings[0] ) : '当前表达仍偏模板化，建议压缩套话并加入更具体的人类判断。',
			);
		}

		if ( 0 === $explicit_signal_count ) {
			$items[] = array(
				'key'      => 'human_signal_gap',
				'severity' => 'high',
				'title'    => '真实信息不足',
				'detail'   => '当前缺少明确的真实经历、个人观点或想强调的结论，更适合先停在编辑草稿或结构建议。',
			);
		}

		if ( 0 === $source_reference_count ) {
			$items[] = array(
				'key'      => 'evidence_gap',
				'severity' => 'medium',
				'title'    => '来源支撑不足',
				'detail'   => '当前未提供明确案例、数据或来源说明，涉及事实判断时建议先补可审来源。',
			);
		}

		if ( 'generic' !== $platform_profile && ! empty( $style_findings ) ) {
			$items[] = array(
				'key'      => 'platform_fit',
				'severity' => 'medium',
				'title'    => '平台风格匹配不足',
				'detail'   => $this->sanitize_metadata_text( (string) $style_findings[0] ),
			);
		}

		$content_text = $this->normalize_plain_text( $content );
		foreach ( array( '一定', '绝对', '全网', '爆款', '立刻', '毫无疑问' ) as $marker ) {
			$contains_marker = function_exists( 'mb_strpos' )
				? false !== mb_strpos( $content_text, $marker )
				: false !== strpos( $content_text, $marker );
			if ( $contains_marker ) {
				$items[] = array(
					'key'      => 'claim_confidence',
					'severity' => 'medium',
					'title'    => '表达可能偏夸张',
					'detail'   => '检测到较强确定性或营销化表述，建议补来源或改成更可核验的说法。',
				);
				break;
			}
		}

		$highest_severity = 'low';
		foreach ( $items as $item ) {
			$severity = sanitize_key( (string) ( $item['severity'] ?? 'low' ) );
			if ( 'high' === $severity ) {
				$highest_severity = 'high';
				break;
			}
			if ( 'medium' === $severity ) {
				$highest_severity = 'medium';
			}
		}

		return array(
			'platform_profile'       => $platform_profile,
			'human_signals_present'  => ! empty( $human_signals ),
			'explicit_signal_count'  => $explicit_signal_count,
			'source_reference_count' => $source_reference_count,
			'status'                 => empty( $items ) ? 'ok' : 'needs_review',
			'highest_severity'       => $highest_severity,
			'items'                  => array_values( $items ),
		);
	}

	/**
	 * Relaxes false-positive AI-risk blockers when the article strongly matches reference style.
	 *
	 * @param array<string,mixed> $ai_risk_review AI-risk review payload.
	 * @param int                 $style_match_score Style match score.
	 * @param array<int,string>   $style_findings Style findings.
	 * @return array<string,mixed>
	 */
	private function relax_article_ai_risk_review_for_reference_style( array $ai_risk_review, int $style_match_score, array $style_findings ): array {
		if ( $style_match_score < 90 || ! empty( $style_findings ) ) {
			return $ai_risk_review;
		}

		$items = array_values(
			array_filter(
				is_array( $ai_risk_review['items'] ?? null ) ? $ai_risk_review['items'] : array(),
				static function ( $item ): bool {
					$item = is_array( $item ) ? $item : array();
					$key = sanitize_key( (string) ( $item['key'] ?? '' ) );
					return ! in_array( $key, array( 'human_signal_gap', 'evidence_gap' ), true );
				}
			)
		);

		$highest_severity = 'low';
		foreach ( $items as $item ) {
			$severity = sanitize_key( (string) ( is_array( $item ) ? ( $item['severity'] ?? 'low' ) : 'low' ) );
			if ( 'high' === $severity ) {
				$highest_severity = 'high';
				break;
			}
			if ( 'medium' === $severity ) {
				$highest_severity = 'medium';
			}
		}

		$ai_risk_review['items'] = $items;
		$ai_risk_review['highest_severity'] = $highest_severity;
		$ai_risk_review['status'] = empty( $items ) ? 'ok' : 'needs_review';
		$ai_risk_review['reference_style_relaxed'] = true;

		return $ai_risk_review;
	}

	/**
	 * Collects stable search terms for article-related read-only analysis.
	 *
	 * @param string             $title Article title.
	 * @param string             $focus_keyword Focus keyword.
	 * @param array<int,mixed>   $keywords Additional keywords.
	 * @return array<int,string>
	 */
	private function collect_focus_terms( $title, $focus_keyword = '', array $keywords = array() ) {
		$candidates = array_merge( array( $focus_keyword, $title ), $keywords );
		$terms = array();

		foreach ( $candidates as $candidate ) {
			$candidate = sanitize_text_field( (string) $candidate );
			if ( '' === $candidate ) {
				continue;
			}

			$parts = preg_split( '/[,，、;；|]+/u', $candidate );
			foreach ( is_array( $parts ) ? $parts : array( $candidate ) as $part ) {
				$part = trim( sanitize_text_field( (string) $part ) );
				if ( '' !== $part ) {
					$terms[] = $part;
				}
			}
		}

		return array_slice( array_values( array_unique( $terms ) ), 0, 8 );
	}

	/**
	 * Builds one canonical media SEO asset row.
	 *
	 * @param array<string,mixed> $descriptor Media descriptor.
	 * @param array<string,mixed> $upload_row Upload result.
	 * @param string              $fallback_role Fallback asset role.
	 * @param bool                $vision_allowed Whether vision fallback may be used.
	 * @return array<string,mixed>
	 */
	private function build_media_seo_asset_row( array $descriptor, array $upload_row, $fallback_role, $vision_allowed ) {
		$image_origin = $this->infer_media_image_origin( $descriptor );

		return array(
			'attachment_id'           => $this->absint_value( $upload_row['attachment_id'] ?? 0 ),
			'url'                     => $this->esc_url_value( (string) ( $descriptor['source_url'] ?? $upload_row['url'] ?? '' ) ),
			'title'                   => sanitize_text_field( (string) ( $descriptor['title'] ?? '' ) ),
			'alt'                     => sanitize_text_field( (string) ( $descriptor['alt'] ?? '' ) ),
			'caption'                 => $this->sanitize_metadata_text( (string) ( $descriptor['caption'] ?? '' ) ),
			'description'             => $this->sanitize_metadata_text( (string) ( $descriptor['description'] ?? '' ) ),
			'image_origin'            => $image_origin,
			'generated_prompt'        => $this->sanitize_metadata_text( (string) ( $descriptor['prompt'] ?? '' ) ),
			'image_profile'           => sanitize_key( (string) ( $descriptor['model_profile'] ?? '' ) ),
			'role'                    => sanitize_key( (string) ( $descriptor['role'] ?? $fallback_role ) ),
			'provider_hint'           => sanitize_key( (string) ( $descriptor['provider_hint'] ?? '' ) ),
			'provider_title'          => sanitize_text_field( (string) ( $descriptor['provider_title'] ?? '' ) ),
			'provider_description'    => $this->sanitize_metadata_text( (string) ( $descriptor['provider_description'] ?? '' ) ),
			'source_page_url'         => $this->esc_url_value( (string) ( $descriptor['source_page_url'] ?? '' ) ),
			'photographer_name'       => sanitize_text_field( (string) ( $descriptor['photographer_name'] ?? '' ) ),
			'attribution_text'        => $this->sanitize_metadata_text( (string) ( $descriptor['attribution_text'] ?? '' ) ),
			'copyright_notice'        => $this->sanitize_metadata_text( (string) ( $descriptor['copyright_notice'] ?? '' ) ),
			'section_heading'         => sanitize_text_field( (string) ( $descriptor['section_heading'] ?? '' ) ),
			'section_summary'         => $this->sanitize_metadata_text( (string) ( $descriptor['section_summary'] ?? '' ) ),
			'file_name'               => $this->sanitize_file_name_value( (string) ( $upload_row['file_name'] ?? '' ) ),
			'vision_fallback_allowed' => (bool) $vision_allowed && 'manual_upload' === $image_origin,
		);
	}

	/**
	 * Infers one canonical media origin from descriptor fields.
	 *
	 * @param array<string,mixed> $asset Media descriptor.
	 * @return string
	 */
	private function infer_media_image_origin( array $asset ) {
		$image_origin = sanitize_key( (string) ( $asset['image_origin'] ?? '' ) );
		if ( in_array( $image_origin, array( 'ai_generated', 'public_free', 'manual_upload' ), true ) ) {
			return $image_origin;
		}

		$generated_prompt = $this->sanitize_metadata_text( (string) ( $asset['generated_prompt'] ?? $asset['prompt'] ?? '' ) );
		$provider_hint = sanitize_key( (string) ( $asset['provider_hint'] ?? '' ) );
		$source_page_url = $this->esc_url_value( (string) ( $asset['source_page_url'] ?? '' ) );
		$photographer_name = sanitize_text_field( (string) ( $asset['photographer_name'] ?? '' ) );
		$attribution_text = $this->sanitize_metadata_text( (string) ( $asset['attribution_text'] ?? '' ) );
		if ( '' !== $source_page_url || '' !== $photographer_name || '' !== $attribution_text || in_array( $provider_hint, array( 'pexels', 'openverse', 'unsplash', 'pixabay', 'public_free' ), true ) ) {
			return 'public_free';
		}
		if ( '' !== sanitize_text_field( (string) ( $asset['model_profile'] ?? $asset['image_profile'] ?? '' ) ) || '' !== $generated_prompt || in_array( $provider_hint, array( 'generated', 'local_model', 'image_generate', 'ai' ), true ) ) {
			return 'ai_generated';
		}

		return 'manual_upload';
	}

	/**
	 * Filters previously inserted inline image blocks for matching placement keys.
	 *
	 * @param array<int,mixed>  $blocks Existing blocks.
	 * @param array<int,string> $placement_keys Placement keys.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_existing_inline_image_blocks( array $blocks, array $placement_keys ) {
		$placement_keys = array_values(
			array_filter(
				array_map(
					array( $this, 'sanitize_html_class_value' ),
					$placement_keys
				)
			)
		);
		if ( empty( $placement_keys ) ) {
			return array_values(
				array_filter(
					$blocks,
					'is_array'
				)
			);
		}

		$filtered = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$class_names = $this->extract_inline_block_class_names( $block );
			$is_inline_image = in_array( 'magick-ai-inline-image', $class_names, true );
			$matches_key = false;
			foreach ( $placement_keys as $placement_key ) {
				if ( in_array( $placement_key, $class_names, true ) ) {
					$matches_key = true;
					break;
				}
			}
			if ( $is_inline_image && $matches_key ) {
				continue;
			}
			$filtered[] = $block;
		}

		return array_values( $filtered );
	}

	/**
	 * Extracts class names from one block.
	 *
	 * @param array<string,mixed> $block Block payload.
	 * @return array<int,string>
	 */
	private function extract_inline_block_class_names( array $block ) {
		$class_name = '';
		$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		if ( '' !== sanitize_text_field( (string) ( $attrs['className'] ?? '' ) ) ) {
			$class_name = (string) $attrs['className'];
		} elseif ( '' !== sanitize_text_field( (string) ( $attrs['classname'] ?? '' ) ) ) {
			$class_name = (string) $attrs['classname'];
		} else {
			$inner_html = (string) ( $block['innerHTML'] ?? '' );
			if ( '' !== $inner_html && preg_match( '/class="([^"]+)"/', $inner_html, $matches ) ) {
				$class_name = (string) ( $matches[1] ?? '' );
			}
		}

		$parts = preg_split( '/\s+/', trim( (string) $class_name ) );
		return array_values(
			array_filter(
				array_map(
					array( $this, 'sanitize_html_class_value' ),
					is_array( $parts ) ? $parts : array()
				)
			)
		);
	}

	/**
	 * Returns whether a block matches one paragraph-index inline image target.
	 *
	 * @param array<string,mixed> $block Block payload.
	 * @param array<string,mixed> $target Target descriptor.
	 * @param int                 $paragraph_index Current paragraph index.
	 * @return bool
	 */
	private function block_matches_inline_paragraph_target( array $block, array $target, $paragraph_index ) {
		if ( 'after_paragraph' !== sanitize_key( (string) ( $target['placement'] ?? '' ) ) ) {
			return false;
		}
		if ( ! array_key_exists( 'paragraph_index', $target ) ) {
			return false;
		}
		$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
		if ( ! in_array( $block_name, array( 'core/paragraph', 'core/freeform' ), true ) ) {
			return false;
		}
		return (int) $paragraph_index === $this->absint_value( $target['paragraph_index'] );
	}

	/**
	 * Returns whether a heading block matches one anchor or heading target.
	 *
	 * @param array<string,mixed> $block Block payload.
	 * @param array<string,mixed> $target Target descriptor.
	 * @return bool
	 */
	private function block_matches_inline_target( array $block, array $target ) {
		$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
		if ( 'core/heading' !== $block_name ) {
			return false;
		}

		$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		$block_anchor = $this->sanitize_metadata_slug( (string) ( $attrs['anchor'] ?? '' ) );
		$target_anchor = $this->sanitize_metadata_slug( (string) ( $target['section_anchor'] ?? '' ) );
		if ( '' !== $target_anchor && '' !== $block_anchor && $target_anchor === $block_anchor ) {
			return true;
		}

		$target_heading = html_entity_decode( sanitize_text_field( (string) ( $target['section_heading'] ?? '' ) ), ENT_QUOTES, 'UTF-8' );
		$target_heading = preg_replace( '/\s+/u', ' ', trim( (string) $target_heading ) );
		$target_heading = is_string( $target_heading ) ? $target_heading : '';
		$heading_text = $this->extract_heading_text_from_block( $block );
		return '' !== $target_heading && '' !== $heading_text && $target_heading === $heading_text;
	}

	/**
	 * Extracts normalized heading text from one block.
	 *
	 * @param array<string,mixed> $block Block payload.
	 * @return string
	 */
	private function extract_heading_text_from_block( array $block ) {
		$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
		if ( 'core/heading' !== $block_name ) {
			return '';
		}
		$heading_text = html_entity_decode( $this->strip_all_tags_value( (string) ( $block['innerHTML'] ?? '' ) ), ENT_QUOTES, 'UTF-8' );
		$heading_text = preg_replace( '/\s+/u', ' ', trim( (string) $heading_text ) );
		return is_string( $heading_text ) ? $heading_text : '';
	}

	/**
	 * Sanitizes one HTML class token in WordPress and isolated test runtimes.
	 *
	 * @param string $value Raw class.
	 * @return string
	 */
	private function sanitize_html_class_value( $value ) {
		if ( function_exists( 'sanitize_html_class' ) ) {
			return sanitize_html_class( (string) $value );
		}

		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value );
	}

	/**
	 * Sanitizes one file name in WordPress and isolated test runtimes.
	 *
	 * @param string $value Raw file name.
	 * @return string
	 */
	private function sanitize_file_name_value( $value ) {
		if ( function_exists( 'sanitize_file_name' ) ) {
			return sanitize_file_name( (string) $value );
		}

		$value = basename( (string) $value );
		return preg_replace( '/[^A-Za-z0-9._-]/', '', $value );
	}

	/**
	 * Sanitizes one URL in WordPress and isolated test runtimes.
	 *
	 * @param string $value Raw URL.
	 * @return string
	 */
	private function esc_url_value( $value ) {
		if ( function_exists( 'esc_url_raw' ) ) {
			return esc_url_raw( (string) $value );
		}

		$url = filter_var( (string) $value, FILTER_SANITIZE_URL );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * Escapes one attribute in WordPress and isolated test runtimes.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function esc_attr_value( $value ) {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( (string) $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escapes one HTML text node in WordPress and isolated test runtimes.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function esc_html_value( $value ) {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( (string) $value );
		}

		return htmlspecialchars( (string) $value, ENT_NOQUOTES, 'UTF-8' );
	}

	/**
	 * Strips tags in WordPress and isolated test runtimes.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function strip_all_tags_value( $value ) {
		return function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( (string) $value ) : strip_tags( (string) $value );
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
