<?php
/**
 * Core WordPress read-only ability package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

use Magick_AI_Abilities\Packages\Read_Definitions\Agent_Usage_Metadata;
use Magick_AI_Abilities\Packages\Read_Definitions\Core_WordPress_Read_Definitions;
use Magick_AI_Abilities\Packages\Read_Definitions\WordPress_Diagnostics_Definitions;
use Magick_AI_Abilities\Registry\Ability_Registrar;
use Magick_AI_Abilities\Registry\Category_Registrar;
use Magick_AI_Abilities\Workflow\Workflow_Definition_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers low-risk WordPress read abilities migrated from the Magick AI plugin.
 */
final class Core_Read_Package {
	use Article_Optimization_Read_Methods;
	use Article_Production_Read_Methods;
	use Content_Inventory_Read_Methods;
	use Content_Refresh_SEO_Read_Methods;
	use Diagnostics_Read_Methods;
	use Media_Read_Methods;
	use Page_Read_Methods;
	use Publishing_Workflow_Read_Methods;
	use Style_Read_Methods;
	use Taxonomy_Read_Methods;

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
			$this->categories->add(
				'magick-ai-abilities-workflows',
				array(
					'label'       => __( 'Workflow Recipe Definitions', 'magick-ai-abilities' ),
					'description' => __( 'Read-only workflow recipe definitions for host-side ability composition.', 'magick-ai-abilities' ),
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
				'magick-ai-abilities/list-workflow-recipes' => array(
					'label'            => __( 'List Workflow Recipes', 'magick-ai-abilities' ),
					'description'      => __( 'Returns read-only workflow recipe definitions for host-side ability composition without executing workflow steps.', 'magick-ai-abilities' ),
					'category'         => 'magick-ai-abilities-workflows',
					'capability'       => 'manage_options',
					'contract_version' => 'v1',
					'source'           => 'official',
					'input_schema'     => array(
						'type'                 => 'object',
						'properties'           => array(),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'       => 'object',
						'properties' => array(
							'schema_version' => array( 'type' => 'string' ),
							'purpose'        => array( 'type' => 'string' ),
							'cases'          => array( 'type' => 'object', 'additionalProperties' => true ),
						),
						'required'   => array( 'schema_version', 'cases' ),
					),
					'execute_callback' => array( $this, 'list_workflow_recipes' ),
				),
				'magick-ai-abilities/get-workflow-recipe'  => array(
					'label'            => __( 'Get Workflow Recipe', 'magick-ai-abilities' ),
					'description'      => __( 'Returns one read-only workflow recipe definition by recipe id or case id without executing workflow steps.', 'magick-ai-abilities' ),
					'category'         => 'magick-ai-abilities-workflows',
					'capability'       => 'manage_options',
					'contract_version' => 'v1',
					'source'           => 'official',
					'input_schema'     => array(
						'type'                 => 'object',
						'properties'           => array(
							'recipe_id' => array( 'type' => 'string' ),
						),
						'required'             => array( 'recipe_id' ),
						'additionalProperties' => false,
					),
					'output_schema'    => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
					'execute_callback' => array( $this, 'get_workflow_recipe' ),
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
						'include_sample_posts' => array( 'type' => 'boolean', 'default' => false ),
						'sample_post_limit' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 5, 'default' => 3 ),
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
									'sample_posts' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
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
						'include_sample_posts' => array( 'type' => 'boolean', 'default' => false ),
						'sample_post_limit' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 5, 'default' => 3 ),
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
									'sample_posts' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
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
			'magick-ai/get-test-content-inventory' => array(
				'label'            => __( 'Get Test Content Inventory', 'magick-ai-abilities' ),
				'description'      => __( 'Detects bounded smoke, fixture, and test content that may distort content, taxonomy, comment, and operations diagnostics without mutating the site.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read', 'taxonomy.read', 'comments.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'patterns'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'post_types'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array( 'post', 'page' ) ),
						'statuses'     => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array( 'publish', 'draft', 'pending', 'future', 'private' ) ),
						'include_posts' => array( 'type' => 'boolean', 'default' => true ),
						'include_terms' => array( 'type' => 'boolean', 'default' => true ),
						'include_comments' => array( 'type' => 'boolean', 'default' => true ),
						'per_page'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
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
				'execute_callback' => array( $this, 'get_test_content_inventory' ),
			),
			'magick-ai/build-test-content-cleanup-plan' => array(
				'label'            => __( 'Build Test Content Cleanup Plan', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a read-only cleanup plan for detected test content, mapping each candidate to existing governed write abilities without trashing or deleting anything.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read', 'taxonomy.read', 'comments.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'patterns'        => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'post_types'      => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array( 'post', 'page' ) ),
						'statuses'        => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array( 'publish', 'draft', 'pending', 'future', 'private' ) ),
						'include_terms'   => array( 'type' => 'boolean', 'default' => true ),
						'include_comments' => array( 'type' => 'boolean', 'default' => true ),
						'per_page'        => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'max_actions'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
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
				'execute_callback' => array( $this, 'build_test_content_cleanup_plan' ),
			),
			'magick-ai/build-content-inventory-fix-plan' => array(
				'label'            => __( 'Build Content Inventory Fix Plan', 'magick-ai-abilities' ),
				'description'      => __( 'Maps bounded content inventory issues to reviewable fix actions that reuse existing governed write abilities without mutating posts.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'post.read',
				'required_scopes'  => array( 'post.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_ids'     => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
						'post_type'    => array( 'type' => 'string', 'default' => 'post' ),
						'status'       => array( 'type' => 'string', 'default' => 'any' ),
						'issue_types'  => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array( 'seo_title', 'seo_description', 'slug', 'excerpt', 'featured_media', 'content' ) ),
						'per_page'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20 ),
						'page'         => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'max_actions'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'target_status' => array( 'type' => 'string', 'enum' => array( 'publish', 'future', 'draft' ), 'default' => 'publish' ),
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
				'execute_callback' => array( $this, 'build_content_inventory_fix_plan' ),
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
			'magick-ai/build-media-inventory-fix-plan' => array(
				'label'            => __( 'Build Media Inventory Fix Plan', 'magick-ai-abilities' ),
				'description'      => __( 'Maps bounded media inventory issues to reviewable metadata and cleanup actions that reuse existing governed write abilities without mutating media.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'upload_files',
				'required_scope'   => 'media.read',
				'required_scopes'  => array( 'media.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'attachment_ids'              => array( 'type' => 'array', 'maxItems' => 50, 'items' => array( 'type' => 'integer', 'minimum' => 1 ) ),
						'mime_type'                   => array( 'type' => 'string' ),
						'search'                      => array( 'type' => 'string' ),
						'issue_types'                 => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'default' => array( 'missing_alt', 'missing_caption', 'missing_description', 'missing_source', 'format_attention', 'possibly_unattached' ) ),
						'article_title'               => array( 'type' => 'string' ),
						'article_excerpt'             => array( 'type' => 'string' ),
						'focus_keyword'               => array( 'type' => 'string' ),
						'per_page'                    => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20 ),
						'page'                        => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
						'max_actions'                 => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50 ),
						'include_delete_candidates'   => array( 'type' => 'boolean', 'default' => false ),
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
				'execute_callback' => array( $this, 'build_media_inventory_fix_plan' ),
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
			'magick-ai/propose-post-taxonomy-terms' => array(
				'label'            => __( 'Propose Post Taxonomy Terms', 'magick-ai-abilities' ),
				'description'      => __( 'Builds a deterministic post taxonomy assignment proposal from existing terms without mutating the post or creating terms.', 'magick-ai-abilities' ),
				'category'         => 'magick-ai-data',
				'capability'       => 'edit_posts',
				'required_scope'   => 'taxonomy.read',
				'required_scopes'  => array( 'post.read', 'taxonomy.read' ),
				'contract_version' => 'v1',
				'source'           => 'official',
				'input_schema'     => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'            => array( 'type' => 'integer', 'minimum' => 1 ),
						'taxonomy'           => array( 'type' => 'string', 'default' => 'post_tag' ),
						'mode'               => array(
							'type'    => 'string',
							'enum'    => array( 'replace', 'append', 'remove' ),
							'default' => 'append',
						),
						'candidate_term_ids' => array(
							'type'     => 'array',
							'maxItems' => 20,
							'items'    => array( 'type' => 'integer', 'minimum' => 1 ),
						),
						'candidate_terms'    => array(
							'type'     => 'array',
							'maxItems' => 20,
							'items'    => array( 'type' => 'string' ),
						),
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
				'execute_callback' => array( $this, 'propose_post_taxonomy_terms' ),
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
		);

		$definitions = array_merge(
			Core_WordPress_Read_Definitions::definitions( $this ),
			WordPress_Diagnostics_Definitions::definitions( $this ),
			$definitions
		);
		$definitions = Agent_Usage_Metadata::apply( $definitions );

		$ordered = array();
		foreach ( array_keys( Core_Read_Pack_Classifier::known_pack_map() ) as $ability_id ) {
			if ( isset( $definitions[ $ability_id ] ) ) {
				$ordered[ $ability_id ] = $definitions[ $ability_id ];
				unset( $definitions[ $ability_id ] );
			}
		}

			return $ordered + $definitions;
		}

		/**
		 * Returns read-only workflow recipe definitions.
		 *
		 * @return array<string,mixed>
		 */
		public function list_workflow_recipes() {
			return Workflow_Definition_Provider::manifest();
		}

		/**
		 * Returns one read-only workflow recipe definition.
		 *
		 * @param mixed $input Ability input.
		 * @return array<string,mixed>|\WP_Error
		 */
		public function get_workflow_recipe( $input ) {
			$input     = is_array( $input ) ? $input : array();
			$recipe_id = isset( $input['recipe_id'] ) ? sanitize_text_field( (string) $input['recipe_id'] ) : '';
			$recipe    = Workflow_Definition_Provider::get( $recipe_id );
			if ( null === $recipe ) {
				return new \WP_Error( 'magick_ai_abilities_workflow_recipe_not_found', __( 'Workflow recipe definition was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
			}

			return $recipe;
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
		$include_current_user = ! array_key_exists( 'include_current_user', $input ) || ! empty( $input['include_current_user'] );
		$include_object_cache = ! array_key_exists( 'include_object_cache', $input ) || ! empty( $input['include_object_cache'] );
		$include_rewrite = ! array_key_exists( 'include_rewrite', $input ) || ! empty( $input['include_rewrite'] );
		$include_https = ! array_key_exists( 'include_https', $input ) || ! empty( $input['include_https'] );

		return array(
			'summary_version' => 'v1',
			'generated_at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'redacted'        => true,
			'site'            => $this->build_site_diagnostics_summary(),
			'wordpress'       => $this->build_wordpress_diagnostics_summary(),
			'php'             => $this->build_php_diagnostics_summary(),
			'theme'           => $include_theme ? $this->build_theme_diagnostics_summary() : array( 'included' => false ),
			'plugins'         => $include_plugins ? $this->build_plugin_diagnostics_summary() : array( 'included' => false ),
			'current_user'    => $include_current_user ? $this->build_current_user_diagnostics_summary() : array( 'included' => false ),
			'object_cache'    => $include_object_cache ? $this->build_object_cache_diagnostics_summary() : array( 'included' => false ),
			'rewrite'         => $include_rewrite ? $this->build_rewrite_diagnostics_summary() : array( 'included' => false ),
			'https'           => $include_https ? $this->build_https_diagnostics_summary() : array( 'included' => false ),
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
	 * Returns bounded operations diagnostics without leaking raw secrets or paths.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function wp_ops_diagnostics_detail( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		$include_plugins = ! array_key_exists( 'include_plugins', $input ) || ! empty( $input['include_plugins'] );
		$include_active_plugins = ! array_key_exists( 'include_active_plugins', $input ) || ! empty( $input['include_active_plugins'] );
		$include_inactive_plugins = ! empty( $input['include_inactive_plugins'] );
		$include_plugin_updates = ! array_key_exists( 'include_plugin_updates', $input ) || ! empty( $input['include_plugin_updates'] );
		$include_must_use_plugins = ! array_key_exists( 'include_must_use_plugins', $input ) || ! empty( $input['include_must_use_plugins'] );
		$include_dropins = ! array_key_exists( 'include_dropins', $input ) || ! empty( $input['include_dropins'] );
		$include_current_user = ! array_key_exists( 'include_current_user', $input ) || ! empty( $input['include_current_user'] );
		$include_php = ! array_key_exists( 'include_php', $input ) || ! empty( $input['include_php'] );
		$include_object_cache = ! array_key_exists( 'include_object_cache', $input ) || ! empty( $input['include_object_cache'] );
		$include_rewrite = ! array_key_exists( 'include_rewrite', $input ) || ! empty( $input['include_rewrite'] );
		$include_https = ! array_key_exists( 'include_https', $input ) || ! empty( $input['include_https'] );
		$include_server = ! array_key_exists( 'include_server', $input ) || ! empty( $input['include_server'] );
		$include_database = ! array_key_exists( 'include_database', $input ) || ! empty( $input['include_database'] );
		$include_cron_events = ! array_key_exists( 'include_cron_events', $input ) || ! empty( $input['include_cron_events'] );
		$include_error_log = ! array_key_exists( 'include_error_log', $input ) || ! empty( $input['include_error_log'] );
		$include_log_contents = ! empty( $input['include_log_contents'] );
		$include_content_types = ! array_key_exists( 'include_content_types', $input ) || ! empty( $input['include_content_types'] );
		$include_roles = ! array_key_exists( 'include_roles', $input ) || ! empty( $input['include_roles'] );
		$include_widgets = ! array_key_exists( 'include_widgets', $input ) || ! empty( $input['include_widgets'] );
		$include_block_theme = ! array_key_exists( 'include_block_theme', $input ) || ! empty( $input['include_block_theme'] );
		$include_search = ! array_key_exists( 'include_search', $input ) || ! empty( $input['include_search'] );
		$include_integrations = ! array_key_exists( 'include_integrations', $input ) || ! empty( $input['include_integrations'] );
		$include_summaries = ! array_key_exists( 'include_summaries', $input ) || ! empty( $input['include_summaries'] );
		$max_cron_events = isset( $input['max_cron_events'] ) ? absint( $input['max_cron_events'] ) : 20;
		$max_cron_events = max( 1, min( 50, $max_cron_events ) );
		$max_plugins_per_group = isset( $input['max_plugins_per_group'] ) ? absint( $input['max_plugins_per_group'] ) : 100;
		$max_plugins_per_group = max( 1, min( 500, $max_plugins_per_group ) );
		$tail_lines = isset( $input['tail_lines'] ) ? absint( $input['tail_lines'] ) : 50;
		$tail_lines = max( 1, min( 200, $tail_lines ) );
		$since_minutes = isset( $input['since_minutes'] ) ? absint( $input['since_minutes'] ) : 0;
		$since_minutes = min( 10080, $since_minutes );
		$severity_filter = $this->normalize_diagnostics_log_severity_filter( $input['severity'] ?? array() );

		$plugins = $include_plugins ? $this->build_plugin_diagnostics_summary(
			array(
				'include_active'      => $include_active_plugins,
				'include_inactive'    => $include_inactive_plugins,
				'include_updates'     => $include_plugin_updates,
				'include_must_use'    => $include_must_use_plugins,
				'include_dropins'     => $include_dropins,
				'max_plugins_per_group' => $max_plugins_per_group,
			)
		) : array( 'included' => false );
		$current_user = $include_current_user ? $this->build_current_user_diagnostics_summary() : array( 'included' => false );
		$php = $include_php ? $this->build_php_diagnostics_summary() : array( 'included' => false );
		$object_cache = $include_object_cache ? $this->build_object_cache_diagnostics_summary() : array( 'included' => false );
		$rewrite = $include_rewrite ? $this->build_rewrite_diagnostics_summary() : array( 'included' => false );
		$https = $include_https ? $this->build_https_diagnostics_summary() : array( 'included' => false );
		$updates = $this->build_updates_diagnostics_summary();
		$cron_summary = $this->build_cron_diagnostics_summary();

		return array(
			'detail_version' => 'v1',
			'generated_at'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'redacted'       => true,
			'plugins'        => $plugins,
			'current_user'   => $current_user,
			'php'            => $php,
			'object_cache'   => $object_cache,
			'rewrite'        => $rewrite,
			'https'          => $https,
			'server'         => $include_server ? $this->build_server_diagnostics_detail() : array( 'included' => false ),
			'database'       => $include_database ? $this->build_database_diagnostics_detail() : array( 'included' => false ),
			'cron_events'    => $include_cron_events ? $this->build_cron_events_diagnostics_detail( $max_cron_events ) : array( 'included' => false ),
			'error_log'      => $include_error_log ? $this->build_error_log_diagnostics_detail( $include_log_contents, $tail_lines, $since_minutes, $severity_filter ) : array( 'included' => false ),
			'content_types'  => $include_content_types ? $this->build_content_type_diagnostics_detail() : array( 'included' => false ),
			'roles'          => $include_roles ? $this->build_roles_diagnostics_detail() : array( 'included' => false ),
			'widgets'        => $include_widgets ? $this->build_widgets_diagnostics_detail() : array( 'included' => false ),
			'block_theme'    => $include_block_theme ? $this->build_block_theme_diagnostics_detail() : array( 'included' => false ),
			'search'         => $include_search ? $this->build_search_diagnostics_detail() : array( 'included' => false ),
			'integrations'   => $include_integrations ? $this->build_integrations_diagnostics_detail( $plugins ) : array( 'included' => false ),
			'seo_summary'    => $include_summaries ? $this->build_seo_diagnostics_summary( $plugins, $rewrite ) : array( 'included' => false ),
			'security_summary' => $include_summaries ? $this->build_security_diagnostics_summary( $https, $current_user ) : array( 'included' => false ),
			'performance_summary' => $include_summaries ? $this->build_performance_diagnostics_summary( $object_cache, $php, $cron_summary, $updates ) : array( 'included' => false ),
			'omitted'        => array(
				'database_name',
				'database_table_prefix',
				'database_table_names',
				'filesystem_paths',
				$include_log_contents ? 'unredacted_error_log_contents' : 'error_log_contents',
				'cron_event_args',
				'user_password_hashes',
				'api_keys',
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
			$user_id = absint( $user->ID ?? 0 );
			$items[] = array(
				'id'           => $user_id,
				'display_name' => sanitize_text_field( (string) ( $user->display_name ?? '' ) ),
				'user_login'   => sanitize_user( (string) ( $user->user_login ?? '' ), true ),
				'roles'        => array_values( array_map( 'sanitize_key', $roles ) ),
				'author_profile' => array(
					'user_nicename' => sanitize_title( (string) ( $user->user_nicename ?? '' ) ),
					'url'           => esc_url_raw( (string) ( $user->user_url ?? '' ) ),
					'description'   => sanitize_textarea_field( (string) get_user_meta( $user_id, 'description', true ) ),
					'registered'    => sanitize_text_field( (string) ( $user->user_registered ?? '' ) ),
					'post_counts'   => $this->build_user_author_post_counts( $user_id ),
				),
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
	 * Builds bounded author post counts for public post types.
	 *
	 * @param int $user_id User ID.
	 * @return array<string,int>
	 */
	private function build_user_author_post_counts( $user_id ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 || ! function_exists( 'count_user_posts' ) ) {
			return array();
		}

		$post_types = function_exists( 'get_post_types' ) ? get_post_types( array( 'public' => true ), 'names' ) : array( 'post' );
		$post_types = is_array( $post_types ) ? array_values( $post_types ) : array( 'post' );
		$counts = array();
		foreach ( array_slice( $post_types, 0, 20 ) as $post_type ) {
			$post_type = sanitize_key( (string) $post_type );
			if ( '' !== $post_type ) {
				$counts[ $post_type ] = absint( count_user_posts( $user_id, $post_type, true ) );
			}
		}

		return $counts;
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
			$comment_post = $comment_post_id > 0 ? get_post( $comment_post_id ) : null;
			$items[] = array(
				'id'         => absint( $comment->comment_ID ?? 0 ),
				'parent_id'  => absint( $comment->comment_parent ?? 0 ),
				'author'     => sanitize_text_field( (string) ( $comment->comment_author ?? '' ) ),
				'author_user_id' => absint( $comment->user_id ?? 0 ),
				'date'       => sanitize_text_field( (string) ( $comment->comment_date ?? '' ) ),
				'date_gmt'   => sanitize_text_field( (string) ( $comment->comment_date_gmt ?? '' ) ),
				'status'     => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'type'       => sanitize_key( (string) ( $comment->comment_type ?? '' ) ),
				'post_id'    => $comment_post_id,
				'post_title' => $comment_post_id > 0 ? sanitize_text_field( (string) get_the_title( $comment_post_id ) ) : '',
				'post_type'  => is_object( $comment_post ) ? sanitize_key( (string) ( $comment_post->post_type ?? '' ) ) : '',
				'post_status' => is_object( $comment_post ) ? sanitize_key( (string) ( $comment_post->post_status ?? '' ) ) : '',
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
			'tree'  => $this->build_menu_item_tree( $items ),
		);
	}

	/**
	 * Builds a nested menu item tree from flat menu rows.
	 *
	 * @param array<int,array<string,mixed>> $items Flat menu items.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_menu_item_tree( array $items ) {
		$nodes = array();
		foreach ( $items as $item ) {
			$item = is_array( $item ) ? $item : array();
			$id = absint( $item['id'] ?? 0 );
			if ( $id <= 0 ) {
				continue;
			}
			$item['children'] = array();
			$nodes[ $id ] = $item;
		}

		$tree = array();
		foreach ( $nodes as $id => &$node ) {
			$parent_id = absint( $node['parent_id'] ?? 0 );
			if ( $parent_id > 0 && isset( $nodes[ $parent_id ] ) ) {
				$nodes[ $parent_id ]['children'][] = &$node;
			} else {
				$tree[] = &$node;
			}
		}
		unset( $node );

		return array_values( $tree );
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
						/* translators: %s: Related search term. */
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
	 * Lists posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_posts( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status_input = $input['status'] ?? $input['post_status'] ?? 'publish';
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$author_id = absint( $input['author_id'] ?? 0 );
		$orderby = sanitize_key( (string) ( $input['orderby'] ?? 'date' ) );
		$order = strtoupper( sanitize_key( (string) ( $input['order'] ?? 'DESC' ) ) );
		$date_after = sanitize_text_field( (string) ( $input['date_after'] ?? '' ) );
		$date_before = sanitize_text_field( (string) ( $input['date_before'] ?? '' ) );
		$modified_after = sanitize_text_field( (string) ( $input['modified_after'] ?? '' ) );
		$modified_before = sanitize_text_field( (string) ( $input['modified_before'] ?? '' ) );
		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? '' ) );
		$term_id = absint( $input['term_id'] ?? 0 );
		$term_slug = sanitize_title( (string) ( $input['term_slug'] ?? '' ) );

		$statuses = is_array( $status_input ) ? $status_input : explode( ',', (string) $status_input );
		$statuses = array_values( array_filter( array_map( 'sanitize_key', $statuses ) ) );
		if ( empty( $statuses ) ) {
			$statuses = array( 'publish' );
		}
		if ( ! in_array( $orderby, array( 'date', 'modified', 'title', 'id', 'menu_order', 'comment_count' ), true ) ) {
			$orderby = 'date';
		}
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_type_invalid', __( 'Post type does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'        => $post_type,
			'post_status'      => 1 === count( $statuses ) ? $statuses[0] : $statuses,
			'posts_per_page'   => $per_page,
			'paged'            => $page,
			'orderby'          => 'id' === $orderby ? 'ID' : $orderby,
			'order'            => $order,
			'fields'           => 'ids',
			'suppress_filters' => false,
		);
		if ( $author_id > 0 ) {
			$args['author'] = $author_id;
		}
		if ( '' !== $search ) {
			$args['s'] = $search;
		}
		if ( '' !== $date_after || '' !== $date_before ) {
			$args['date_query'] = array( array( 'column' => 'post_date' ) );
			if ( '' !== $date_after ) {
				$args['date_query'][0]['after'] = $date_after;
			}
			if ( '' !== $date_before ) {
				$args['date_query'][0]['before'] = $date_before;
			}
		}
		if ( '' !== $modified_after || '' !== $modified_before ) {
			$args['date_query'] = is_array( $args['date_query'] ?? null ) ? $args['date_query'] : array();
			$modified_query = array( 'column' => 'post_modified' );
			if ( '' !== $modified_after ) {
				$modified_query['after'] = $modified_after;
			}
			if ( '' !== $modified_before ) {
				$modified_query['before'] = $modified_before;
			}
			$args['date_query'][] = $modified_query;
		}
		if ( '' !== $taxonomy && ( $term_id > 0 || '' !== $term_slug ) && taxonomy_exists( $taxonomy ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => $term_id > 0 ? 'term_id' : 'slug',
					'terms'    => $term_id > 0 ? array( $term_id ) : array( $term_slug ),
				),
			);
		}

		$query = new \WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post_id ) {
			$post_id = absint( $post_id );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$item_author_id = absint( $post->post_author ?? 0 );
			$status_value = sanitize_key( (string) ( $post->post_status ?? '' ) );
			$items[] = array(
				'id'          => $post_id,
				'title'       => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'status'      => $status_value,
				'post_status' => $status_value,
				'slug'        => $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) ),
				'date'        => sanitize_text_field( (string) get_post_field( 'post_date', $post_id ) ),
				'modified'    => sanitize_text_field( (string) get_post_field( 'post_modified', $post_id ) ),
				'post_type'   => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'author_id'   => $item_author_id,
				'author'      => $item_author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $item_author_id ) ) : '',
				'excerpt'     => wp_trim_words( wp_strip_all_tags( (string) ( $post->post_excerpt ?: $post->post_content ) ), 30 ),
				'comment_count' => absint( $post->comment_count ?? 0 ),
				'permalink'   => function_exists( 'get_permalink' ) ? $this->esc_url_value( (string) get_permalink( $post_id ) ) : '',
				'edit_link'   => get_edit_post_link( $post_id, 'raw' ),
			);
		}

		return array(
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
			'filters'  => array(
				'post_type'       => $post_type,
				'status'          => $statuses,
				'search'          => $search,
				'author_id'       => $author_id,
				'orderby'         => $orderby,
				'order'           => $order,
				'date_after'      => $date_after,
				'date_before'     => $date_before,
				'modified_after'  => $modified_after,
				'modified_before' => $modified_before,
				'taxonomy'        => $taxonomy,
				'term_id'         => $term_id,
				'term_slug'       => $term_slug,
			),
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
		$template = function_exists( 'get_page_template_slug' ) ? sanitize_text_field( (string) get_page_template_slug( $post_id ) ) : '';
		if ( '' === $template && function_exists( 'get_post_meta' ) ) {
			$template = sanitize_text_field( (string) get_post_meta( $post_id, '_wp_page_template', true ) );
		}
		$seo_provider = $this->detect_seo_provider();
		$seo_keys = $this->seo_meta_keys( $seo_provider );
		$featured_media_id = function_exists( 'get_post_thumbnail_id' ) ? $this->absint_value( get_post_thumbnail_id( $post_id ) ) : 0;

		$data = array(
			'post'  => array(
				'id'         => $post_id,
				'title'      => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'post_type'  => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'slug'       => $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) ),
				'parent_id'  => $this->absint_value( $post->post_parent ?? 0 ),
				'menu_order' => (int) ( $post->menu_order ?? 0 ),
				'author_id'  => $author_id,
				'author'     => $author_name,
				'excerpt'    => sanitize_textarea_field( (string) ( $post->post_excerpt ?? '' ) ),
				'date'       => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
				'date_gmt'   => sanitize_text_field( (string) ( $post->post_date_gmt ?? '' ) ),
				'modified'   => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
				'modified_gmt' => sanitize_text_field( (string) ( $post->post_modified_gmt ?? '' ) ),
				'comment_status' => sanitize_key( (string) ( $post->comment_status ?? '' ) ),
				'ping_status' => sanitize_key( (string) ( $post->ping_status ?? '' ) ),
				'comment_count' => absint( $post->comment_count ?? 0 ),
				'template'   => $template,
				'format'     => function_exists( 'get_post_format' ) ? sanitize_key( (string) get_post_format( $post_id ) ) : '',
				'featured_media_id' => $featured_media_id,
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
			'seo'   => array(
				'provider'      => $seo_provider,
				'title'         => $this->get_first_post_meta_text( $post_id, $seo_keys['title'] ?? '' ),
				'description'   => $this->sanitize_metadata_text( $this->get_first_post_meta_text( $post_id, $seo_keys['description'] ?? '' ) ),
				'focus_keyword' => $this->get_first_post_meta_text( $post_id, $seo_keys['focus_keyword'] ?? array( '_yoast_wpseo_focuskw', 'rank_math_focus_keyword', 'aioseo_keywords' ) ),
				'meta_keys'     => $seo_keys,
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
								'seo',
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
			$path = wp_parse_url( $url, PHP_URL_PATH );
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
	 * Builds one non-executing plan action row.
	 *
	 * @param string   $action_id Action id.
	 * @param string   $ability_id Target ability id.
	 * @param array<string,mixed> $input Target ability input.
	 * @param string[] $required_scopes Required scopes.
	 * @param string   $risk Risk level.
	 * @param string   $reason Reason.
	 * @param string[] $requires_input Fields still requiring caller input.
	 * @return array<string,mixed>
	 */
	private function build_plan_action( $action_id, $ability_id, array $input, array $required_scopes, $risk, $reason, array $requires_input = array() ) {
		return array(
			'action_id'          => sanitize_key( (string) $action_id ),
			'target_ability_id'  => sanitize_text_field( (string) $ability_id ),
			'input'              => array_merge(
				$input,
				array(
					'dry_run' => true,
					'commit'  => false,
				)
			),
			'requires_approval'  => true,
			'commit_execution'   => false,
			'required_scopes'    => array_values( array_map( 'sanitize_key', $required_scopes ) ),
			'risk'               => sanitize_key( (string) $risk ),
			'reason'             => sanitize_text_field( (string) $reason ),
			'requires_input'     => array_values( array_map( 'sanitize_key', $requires_input ) ),
			'proposal_ready'     => empty( $requires_input ),
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
	 * Builds bounded reverse post samples for a term.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param int    $term_id Term ID.
	 * @param int    $limit Maximum posts.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_term_sample_posts( $taxonomy, $term_id, $limit ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		$term_id = absint( $term_id );
		$limit = max( 1, min( 5, absint( $limit ) ) );
		if ( '' === $taxonomy || $term_id <= 0 ) {
			return array();
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => array( $term_id ),
					),
				),
			)
		);

		$posts = array();
		foreach ( (array) $query->posts as $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$post = get_post( $post_id );
			if ( ! is_object( $post ) ) {
				continue;
			}
			$posts[] = array(
				'id'        => $post_id,
				'title'     => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'post_type' => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'status'    => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'date'      => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
			);
		}

		return $posts;
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
	 * Returns the first scalar post meta value from one key or a key list.
	 *
	 * @param int          $post_id Post ID.
	 * @param string|string[] $keys Meta key or keys.
	 * @return string
	 */
	private function get_first_post_meta_text( $post_id, $keys ) {
		if ( ! function_exists( 'get_post_meta' ) ) {
			return '';
		}

		$keys = is_array( $keys ) ? $keys : array( $keys );
		foreach ( $keys as $key ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			$value = get_post_meta( absint( $post_id ), $key, true );
			if ( is_scalar( $value ) && '' !== (string) $value ) {
				return sanitize_text_field( (string) $value );
			}
		}

		return '';
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
		$text = wp_strip_all_tags( (string) $value );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( is_string( $text ) ? $text : '' );
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
		return wp_strip_all_tags( (string) $value );
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
