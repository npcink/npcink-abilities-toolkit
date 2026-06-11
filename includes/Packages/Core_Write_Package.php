<?php
/**
 * Core WordPress host-governed write ability package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;
use Npcink_Abilities_Toolkit\Registry\Category_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers low-risk WordPress write abilities migrated from the Npcink AI plugin.
 */
final class Core_Write_Package {
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
			'npcink-abilities-toolkit-write',
			array(
				'label'       => __( 'WordPress Write Abilities', 'npcink-abilities-toolkit' ),
				'description' => __( 'Host-governed WordPress write abilities with dry-run previews and external approval.', 'npcink-abilities-toolkit' ),
			)
		);

		foreach ( $this->definitions() as $ability_id => $definition ) {
			$definition['source']                    = 'official';
			$definition['project_to_npcink_catalog'] = true;
			$definition = $this->with_agent_usage_metadata( $ability_id, $definition );
			$this->abilities->add_write_host_governed( $ability_id, $definition );
		}
	}

	/**
	 * Returns package ability definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function definitions() {
		$post_id = array( 'type' => 'integer', 'minimum' => 1 );
		$text    = array( 'type' => 'string' );

		return array(
			'npcink-abilities-toolkit/create-draft'       => array(
				'label'           => __( 'Create Draft', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Creates a draft post or page after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_type'          => array( 'type' => 'string', 'default' => 'post' ),
						'status'             => array(
							'type'    => 'string',
							'enum'    => array( 'draft' ),
							'default' => 'draft',
						),
						'title'              => array( 'type' => 'string', 'minLength' => 1 ),
						'content'            => $text,
						'content_format'     => array(
							'type' => 'string',
							'enum' => array( 'html', 'markdown', 'plain' ),
						),
						'excerpt'            => $text,
						'soft_block_reason'  => $text,
						'soft_block_summary' => $text,
						'meta'               => array(
							'type'                 => 'object',
							'additionalProperties' => array(
								'type' => array( 'string', 'number', 'integer', 'boolean' ),
							),
						),
					),
					array( 'title' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'        => array( 'type' => 'integer' ),
						'status'         => array( 'type' => 'string' ),
						'content_format' => array( 'type' => 'string' ),
						'edit_link'      => array( 'type' => 'string' ),
						'preview_link'   => array( 'type' => 'string' ),
						'dry_run'        => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'status', 'dry_run' )
				),
				'execute_callback' => array( $this, 'create_draft' ),
			),
			'npcink-abilities-toolkit/update-post'        => array(
				'label'           => __( 'Update Post', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates a post title, content, or excerpt after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'        => $post_id,
						'title'          => $text,
						'content'        => $text,
						'content_format' => array(
							'type' => 'string',
							'enum' => array( 'html', 'markdown', 'plain' ),
						),
						'excerpt'        => $text,
					),
					array( 'post_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'   => array( 'type' => 'integer' ),
						'updated'   => array( 'type' => 'boolean' ),
						'status'    => array( 'type' => 'string' ),
						'edit_link' => array( 'type' => 'string' ),
						'changes'   => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'   => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'updated', 'dry_run' )
				),
				'execute_callback' => array( $this, 'update_post' ),
			),
			'npcink-abilities-toolkit/set-post-seo-meta'  => array(
				'label'           => __( 'Set Post SEO Meta', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates SEO title and description metadata for one post after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'         => $post_id,
						'seo_title'       => array( 'type' => 'string', 'maxLength' => 255 ),
						'seo_description' => array( 'type' => 'string', 'maxLength' => 500 ),
					),
					array( 'post_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'   => array( 'type' => 'integer' ),
						'updated'   => array( 'type' => 'boolean' ),
						'status'    => array( 'type' => 'string' ),
						'provider'  => array( 'type' => 'string' ),
						'changes'   => array( 'type' => 'object', 'additionalProperties' => true ),
						'current'   => array( 'type' => 'object', 'additionalProperties' => true ),
						'preview'   => array( 'type' => 'object', 'additionalProperties' => true ),
						'edit_link' => array( 'type' => 'string' ),
						'dry_run'   => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'updated', 'status', 'provider', 'changes', 'dry_run' )
				),
				'execute_callback' => array( $this, 'set_post_seo_meta' ),
			),
			'npcink-abilities-toolkit/patch-post-content' => array(
				'label'           => __( 'Patch Post Content', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Applies text patch operations to saved post content after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'    => $post_id,
						'operations' => array(
							'type'     => 'array',
							'minItems' => 1,
							'items'    => array(
								'type'                 => 'object',
								'properties'           => array(
									'op'             => array(
										'type' => 'string',
										'enum' => array( 'replace', 'delete', 'insert_before', 'insert_after' ),
									),
									'find'           => array( 'type' => 'string', 'minLength' => 1 ),
									'replace'        => $text,
									'limit'          => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 1 ),
									'case_sensitive' => array( 'type' => 'boolean', 'default' => true ),
								),
								'required'             => array( 'op', 'find' ),
								'additionalProperties' => false,
							),
						),
					),
					array( 'post_id', 'operations' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'               => array( 'type' => 'integer' ),
						'updated'               => array( 'type' => 'boolean' ),
						'status'                => array( 'type' => 'string' ),
						'edit_link'             => array( 'type' => 'string' ),
						'content_length_before' => array( 'type' => 'integer' ),
						'content_length_after'  => array( 'type' => 'integer' ),
						'impact_ranges'         => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
						'patch_preview'         => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
						'diff_preview'          => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'               => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'updated', 'content_length_before', 'content_length_after', 'impact_ranges', 'patch_preview', 'dry_run' )
				),
				'execute_callback' => array( $this, 'patch_post_content' ),
			),
			'npcink-abilities-toolkit/patch-setting-value' => array(
				'label'           => __( 'Patch Setting Value', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Applies exact text replacement operations to one option or theme mod after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'manage_options',
				'required_scopes' => array( 'site.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'target_type' => array(
							'type' => 'string',
							'enum' => array( 'option', 'theme_mod' ),
						),
						'target_name' => array( 'type' => 'string', 'minLength' => 1 ),
						'operations'  => array(
							'type'     => 'array',
							'minItems' => 1,
							'items'    => array(
								'type'                 => 'object',
								'properties'           => array(
									'op'             => array(
										'type' => 'string',
										'enum' => array( 'replace' ),
									),
									'find'           => array( 'type' => 'string', 'minLength' => 1 ),
									'replace'        => $text,
									'limit'          => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 1 ),
									'case_sensitive' => array( 'type' => 'boolean', 'default' => true ),
								),
								'required'             => array( 'op', 'find', 'replace' ),
								'additionalProperties' => false,
							),
						),
					),
					array( 'target_type', 'target_name', 'operations' )
				),
				'output_schema'   => $this->schema(
					array(
						'target_type'  => array( 'type' => 'string' ),
						'target_name'  => array( 'type' => 'string' ),
						'updated'      => array( 'type' => 'boolean' ),
						'value_type'   => array( 'type' => 'string' ),
						'impact_ranges' => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
						'patch_preview' => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
						'diff_preview' => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'      => array( 'type' => 'boolean' ),
					),
					array( 'target_type', 'target_name', 'updated', 'value_type', 'impact_ranges', 'patch_preview', 'dry_run' )
				),
				'execute_callback' => array( $this, 'patch_setting_value' ),
			),
				'npcink-abilities-toolkit/update-post-blocks' => array(
				'label'           => __( 'Update Post Blocks', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Writes Gutenberg blocks into post content after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'            => $post_id,
						'mode'               => array(
							'type'    => 'string',
							'enum'    => array( 'replace', 'append' ),
							'default' => 'replace',
						),
						'validate_roundtrip' => array( 'type' => 'boolean', 'default' => true ),
						'blocks'             => array(
							'type'     => 'array',
							'minItems' => 1,
							'items'    => array( 'type' => 'object', 'additionalProperties' => true ),
						),
					),
					array( 'post_id', 'blocks' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'               => array( 'type' => 'integer' ),
						'updated'               => array( 'type' => 'boolean' ),
						'status'                => array( 'type' => 'string' ),
						'mode'                  => array( 'type' => 'string' ),
						'edit_link'             => array( 'type' => 'string' ),
						'block_count_before'    => array( 'type' => 'integer' ),
						'block_count_after'     => array( 'type' => 'integer' ),
						'content_length_before' => array( 'type' => 'integer' ),
						'content_length_after'  => array( 'type' => 'integer' ),
						'validation'            => array( 'type' => 'object', 'additionalProperties' => true ),
						'impact_ranges'         => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
						'diff_preview'          => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'               => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'updated', 'mode', 'block_count_before', 'block_count_after', 'validation', 'dry_run' )
				),
					'execute_callback' => array( $this, 'update_post_blocks' ),
				),
				'npcink-abilities-toolkit/update-template-blocks' => array(
					'label'           => __( 'Update Template Blocks', 'npcink-abilities-toolkit' ),
					'description'     => __( 'Writes reviewed Gutenberg blocks into a wp_template entity after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
					'category'        => 'npcink-abilities-toolkit-write',
					'capability'      => 'edit_theme_options',
					'required_scopes' => array( 'site.write' ),
					'channels'        => array( 'agent', 'mcp' ),
					'meta'            => $this->write_meta(),
					'input_schema'    => $this->schema(
						array(
							'post_id'            => $post_id,
							'mode'               => array(
								'type'    => 'string',
								'enum'    => array( 'replace' ),
								'default' => 'replace',
							),
							'validate_roundtrip' => array( 'type' => 'boolean', 'default' => true ),
							'blocks'             => array(
								'type'     => 'array',
								'minItems' => 1,
								'items'    => array( 'type' => 'object', 'additionalProperties' => true ),
							),
						),
						array( 'post_id', 'blocks' )
					),
					'output_schema'   => $this->schema(
						array(
							'post_id'               => array( 'type' => 'integer' ),
							'post_type'             => array( 'type' => 'string' ),
							'updated'               => array( 'type' => 'boolean' ),
							'status'                => array( 'type' => 'string' ),
							'mode'                  => array( 'type' => 'string' ),
							'edit_link'             => array( 'type' => 'string' ),
							'block_count_before'    => array( 'type' => 'integer' ),
							'block_count_after'     => array( 'type' => 'integer' ),
							'validation'            => array( 'type' => 'object', 'additionalProperties' => true ),
							'dry_run'               => array( 'type' => 'boolean' ),
						),
						array( 'post_id', 'post_type', 'updated', 'mode', 'block_count_before', 'block_count_after', 'validation', 'dry_run' )
					),
					'execute_callback' => array( $this, 'update_template_blocks' ),
				),
				'npcink-abilities-toolkit/upsert-template-blocks' => array(
					'label'           => __( 'Upsert Template Blocks', 'npcink-abilities-toolkit' ),
					'description'     => __( 'Creates or updates a reviewed wp_template override after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
					'category'        => 'npcink-abilities-toolkit-write',
					'capability'      => 'edit_theme_options',
					'required_scopes' => array( 'site.write' ),
					'channels'        => array( 'agent', 'mcp' ),
					'meta'            => $this->write_meta(),
					'input_schema'    => $this->schema(
						array(
							'post_id'            => $post_id,
							'slug'               => array( 'type' => 'string' ),
							'theme'              => array( 'type' => 'string' ),
							'title'              => array( 'type' => 'string' ),
							'source_template_id' => array( 'type' => 'string' ),
							'mode'               => array(
								'type'    => 'string',
								'enum'    => array( 'replace' ),
								'default' => 'replace',
							),
							'validate_roundtrip' => array( 'type' => 'boolean', 'default' => true ),
							'blocks'             => array(
								'type'     => 'array',
								'minItems' => 1,
								'items'    => array( 'type' => 'object', 'additionalProperties' => true ),
							),
						),
						array( 'slug', 'blocks' )
					),
					'output_schema'   => $this->schema(
						array(
							'post_id'               => array( 'type' => 'integer' ),
							'post_type'             => array( 'type' => 'string' ),
							'slug'                  => array( 'type' => 'string' ),
							'theme'                 => array( 'type' => 'string' ),
							'source_template_id'    => array( 'type' => 'string' ),
							'created'               => array( 'type' => 'boolean' ),
							'updated'               => array( 'type' => 'boolean' ),
							'status'                => array( 'type' => 'string' ),
							'mode'                  => array( 'type' => 'string' ),
							'edit_link'             => array( 'type' => 'string' ),
							'block_count_before'    => array( 'type' => 'integer' ),
							'block_count_after'     => array( 'type' => 'integer' ),
							'content_length_before' => array( 'type' => 'integer' ),
							'content_length_after'  => array( 'type' => 'integer' ),
							'validation'            => array( 'type' => 'object', 'additionalProperties' => true ),
							'impact_ranges'         => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
							'diff_preview'          => array( 'type' => 'object', 'additionalProperties' => true ),
							'preview'               => array( 'type' => 'object', 'additionalProperties' => true ),
							'dry_run'               => array( 'type' => 'boolean' ),
						),
						array( 'post_id', 'post_type', 'slug', 'created', 'updated', 'mode', 'block_count_before', 'block_count_after', 'validation', 'dry_run' )
					),
					'execute_callback' => array( $this, 'upsert_template_blocks' ),
				),
				'npcink-abilities-toolkit/update-template-part-blocks' => array(
					'label'           => __( 'Update Template Part Blocks', 'npcink-abilities-toolkit' ),
					'description'     => __( 'Writes reviewed Gutenberg blocks into a wp_template_part entity after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
					'category'        => 'npcink-abilities-toolkit-write',
					'capability'      => 'edit_theme_options',
					'required_scopes' => array( 'site.write' ),
					'channels'        => array( 'agent', 'mcp' ),
					'meta'            => $this->write_meta(),
					'input_schema'    => $this->schema(
						array(
							'post_id'            => $post_id,
							'mode'               => array(
								'type'    => 'string',
								'enum'    => array( 'replace' ),
								'default' => 'replace',
							),
							'validate_roundtrip' => array( 'type' => 'boolean', 'default' => true ),
							'blocks'             => array(
								'type'     => 'array',
								'minItems' => 1,
								'items'    => array( 'type' => 'object', 'additionalProperties' => true ),
							),
						),
						array( 'post_id', 'blocks' )
					),
					'output_schema'   => $this->schema(
						array(
							'post_id'               => array( 'type' => 'integer' ),
							'post_type'             => array( 'type' => 'string' ),
							'updated'               => array( 'type' => 'boolean' ),
							'status'                => array( 'type' => 'string' ),
							'mode'                  => array( 'type' => 'string' ),
							'edit_link'             => array( 'type' => 'string' ),
							'block_count_before'    => array( 'type' => 'integer' ),
							'block_count_after'     => array( 'type' => 'integer' ),
							'validation'            => array( 'type' => 'object', 'additionalProperties' => true ),
							'dry_run'               => array( 'type' => 'boolean' ),
						),
						array( 'post_id', 'post_type', 'updated', 'mode', 'block_count_before', 'block_count_after', 'validation', 'dry_run' )
					),
					'execute_callback' => array( $this, 'update_template_part_blocks' ),
				),
				'npcink-abilities-toolkit/set-post-slug'      => array(
				'label'           => __( 'Set Post Slug', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates a post slug after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id' => $post_id,
						'slug'    => array( 'type' => 'string', 'minLength' => 1 ),
					),
					array( 'post_id', 'slug' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'     => array( 'type' => 'integer' ),
						'updated'     => array( 'type' => 'boolean' ),
						'status'      => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'before_slug' => array( 'type' => 'string' ),
						'edit_link'   => array( 'type' => 'string' ),
						'dry_run'     => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'updated', 'slug', 'dry_run' )
				),
				'execute_callback' => array( $this, 'set_post_slug' ),
			),
			'npcink-abilities-toolkit/set-post-author'    => array(
				'label'           => __( 'Set Post Author', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates a post author after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'   => $post_id,
						'author_id' => array( 'type' => 'integer', 'minimum' => 1 ),
					),
					array( 'post_id', 'author_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'          => array( 'type' => 'integer' ),
						'updated'          => array( 'type' => 'boolean' ),
						'author_id'        => array( 'type' => 'integer' ),
						'before_author_id' => array( 'type' => 'integer' ),
						'edit_link'        => array( 'type' => 'string' ),
						'dry_run'          => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'updated', 'author_id', 'before_author_id', 'dry_run' )
				),
				'execute_callback' => array( $this, 'set_post_author' ),
			),
			'npcink-abilities-toolkit/set-post-template'  => array(
				'label'           => __( 'Set Post Template', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates a post template after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'  => $post_id,
						'template' => array( 'type' => 'string', 'minLength' => 1 ),
					),
					array( 'post_id', 'template' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'         => array( 'type' => 'integer' ),
						'updated'         => array( 'type' => 'boolean' ),
						'template'        => array( 'type' => 'string' ),
						'before_template' => array( 'type' => 'string' ),
						'edit_link'       => array( 'type' => 'string' ),
						'dry_run'         => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'updated', 'template', 'before_template', 'dry_run' )
				),
				'execute_callback' => array( $this, 'set_post_template' ),
			),
			'npcink-abilities-toolkit/set-post-format'    => array(
				'label'           => __( 'Set Post Format', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates a post format after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id' => $post_id,
						'format'  => array(
							'type' => 'string',
							'enum' => array( 'standard', 'aside', 'image', 'video', 'quote', 'link', 'gallery', 'audio', 'chat', 'status' ),
						),
					),
					array( 'post_id', 'format' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'       => array( 'type' => 'integer' ),
						'updated'       => array( 'type' => 'boolean' ),
						'format'        => array( 'type' => 'string' ),
						'before_format' => array( 'type' => 'string' ),
						'edit_link'     => array( 'type' => 'string' ),
						'dry_run'       => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'updated', 'format', 'before_format', 'dry_run' )
				),
				'execute_callback' => array( $this, 'set_post_format' ),
			),
			'npcink-abilities-toolkit/create-term'        => array(
				'label'           => __( 'Create Term', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Creates a taxonomy term after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'manage_categories',
				'required_scopes' => array( 'taxonomy.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'taxonomy'    => array( 'type' => 'string', 'default' => 'category' ),
						'name'        => array( 'type' => 'string', 'minLength' => 1 ),
						'slug'        => $text,
						'description' => $text,
						'parent'      => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
					),
					array( 'taxonomy', 'name' )
				),
				'output_schema'   => $this->schema(
					array(
						'taxonomy' => array( 'type' => 'string' ),
						'term_id'  => array( 'type' => 'integer' ),
						'created'  => array( 'type' => 'boolean' ),
						'name'     => array( 'type' => 'string' ),
						'slug'     => array( 'type' => 'string' ),
						'dry_run'  => array( 'type' => 'boolean' ),
					),
					array( 'taxonomy', 'term_id', 'created', 'dry_run' )
				),
				'execute_callback' => array( $this, 'create_term' ),
			),
			'npcink-abilities-toolkit/update-term'        => array(
				'label'           => __( 'Update Term', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates a taxonomy term after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'manage_categories',
				'required_scopes' => array( 'taxonomy.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'taxonomy'    => array( 'type' => 'string', 'default' => 'category' ),
						'term_id'     => array( 'type' => 'integer', 'minimum' => 1 ),
						'name'        => $text,
						'slug'        => $text,
						'description' => $text,
						'parent'      => array( 'type' => 'integer', 'minimum' => 0 ),
					),
					array( 'taxonomy', 'term_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'taxonomy'    => array( 'type' => 'string' ),
						'term_id'     => array( 'type' => 'integer' ),
						'updated'     => array( 'type' => 'boolean' ),
						'name'        => array( 'type' => 'string' ),
						'slug'        => array( 'type' => 'string' ),
						'description' => array( 'type' => 'string' ),
						'parent'      => array( 'type' => 'integer' ),
						'changes'     => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'     => array( 'type' => 'boolean' ),
					),
					array( 'taxonomy', 'term_id', 'updated', 'dry_run' )
				),
				'execute_callback' => array( $this, 'update_term' ),
			),
			'npcink-abilities-toolkit/set-post-terms'     => array(
				'label'           => __( 'Set Post Terms', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates a post taxonomy terms in replace, append, or remove mode after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'taxonomy.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'        => $post_id,
						'taxonomy'       => array( 'type' => 'string', 'default' => 'post_tag' ),
						'mode'           => array(
							'type'    => 'string',
							'enum'    => array( 'replace', 'append', 'remove' ),
							'default' => 'replace',
						),
						'term_ids'       => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer', 'minimum' => 1 ),
						),
						'terms'          => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'create_missing' => array( 'type' => 'boolean', 'default' => false ),
					),
					array( 'post_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'          => array( 'type' => 'integer' ),
						'taxonomy'         => array( 'type' => 'string' ),
						'mode'             => array( 'type' => 'string' ),
						'updated'          => array( 'type' => 'boolean' ),
						'before'           => array( 'type' => 'object', 'additionalProperties' => true ),
						'after'            => array( 'type' => 'object', 'additionalProperties' => true ),
						'added_term_ids'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'removed_term_ids' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'dry_run'          => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'taxonomy', 'mode', 'updated', 'dry_run' )
				),
				'execute_callback' => array( $this, 'set_post_terms' ),
			),
			'npcink-abilities-toolkit/update-media-details' => array(
				'label'           => __( 'Update Media Details', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Updates attachment title, alt, caption, description, and attribution fields after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'upload_files',
				'required_scopes' => array( 'media.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'attachment_id'     => array( 'type' => 'integer', 'minimum' => 1 ),
						'title'             => $text,
						'alt'               => $text,
						'caption'           => $text,
						'description'       => $text,
						'source_type'       => array(
							'type' => 'string',
							'enum' => array( 'owned', 'ai_generated', 'stock', 'external', 'test' ),
						),
						'source_page_url'   => $text,
						'photographer_name' => $text,
						'attribution_text'  => $text,
						'copyright_notice'  => $text,
					),
					array( 'attachment_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'attachment_id'     => array( 'type' => 'integer' ),
						'updated'           => array( 'type' => 'boolean' ),
						'title'             => array( 'type' => 'string' ),
						'alt'               => array( 'type' => 'string' ),
						'caption'           => array( 'type' => 'string' ),
						'description'       => array( 'type' => 'string' ),
						'source_type'       => array( 'type' => 'string' ),
						'source_page_url'   => array( 'type' => 'string' ),
						'photographer_name' => array( 'type' => 'string' ),
						'attribution_text'  => array( 'type' => 'string' ),
						'copyright_notice'  => array( 'type' => 'string' ),
						'edit_link'         => array( 'type' => 'string' ),
						'changes'           => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'           => array( 'type' => 'boolean' ),
					),
					array( 'attachment_id', 'updated', 'dry_run' )
				),
				'execute_callback' => array( $this, 'update_media_details' ),
			),
			'npcink-abilities-toolkit/upload-media-from-url' => array(
				'label'           => __( 'Upload Media From URL', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Downloads one remote media asset into the WordPress media library after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'upload_files',
				'required_scopes' => array( 'media.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'url'               => array( 'type' => 'string', 'format' => 'uri', 'minLength' => 1 ),
						'title'             => $text,
						'file_name'         => array( 'type' => 'string', 'maxLength' => 120 ),
						'alt'               => $text,
						'caption'           => $text,
						'description'       => $text,
						'source_type'       => array(
							'type'    => 'string',
							'enum'    => array( 'owned', 'ai_generated', 'stock', 'external', 'test' ),
							'default' => 'external',
						),
						'source_page_url'   => $text,
						'photographer_name' => $text,
						'attribution_text'  => $text,
						'copyright_notice'  => $text,
						'attach_to_post_id' => array( 'type' => 'integer', 'minimum' => 1 ),
					),
					array( 'url' )
				),
				'output_schema'   => $this->schema(
					array(
						'attachment_id'     => array( 'type' => 'integer' ),
						'url'               => array( 'type' => 'string' ),
						'file_name'         => array( 'type' => 'string' ),
						'sizes'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'attach_to_post_id' => array( 'type' => 'integer' ),
						'source_type'       => array( 'type' => 'string' ),
						'source_page_url'   => array( 'type' => 'string' ),
						'photographer_name' => array( 'type' => 'string' ),
						'attribution_text'  => array( 'type' => 'string' ),
						'copyright_notice'  => array( 'type' => 'string' ),
						'edit_link'         => array( 'type' => 'string' ),
						'dry_run'           => array( 'type' => 'boolean' ),
					),
					array( 'attachment_id', 'url', 'dry_run' )
				),
				'execute_callback' => array( $this, 'upload_media_from_url' ),
			),
			'npcink-abilities-toolkit/optimize-media-asset' => array(
				'label'           => __( 'Optimize Media Asset', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Generates an optimized derivative for one image attachment after host approval, preserving the original file.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'upload_files',
				'required_scopes' => array( 'media.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'attachment_id'    => array( 'type' => 'integer', 'minimum' => 1 ),
						'target_max_width' => array( 'type' => 'integer', 'minimum' => 320, 'maximum' => 7680, 'default' => 1920 ),
						'preferred_format' => array( 'type' => 'string', 'enum' => array( 'webp', 'jpeg', 'png' ), 'default' => 'webp' ),
						'quality'          => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 82 ),
						'derivative_suffix' => array( 'type' => 'string', 'maxLength' => 48, 'default' => 'optimized' ),
					),
					array( 'attachment_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'attachment_id'       => array( 'type' => 'integer' ),
						'optimized'           => array( 'type' => 'boolean' ),
						'original_preserved'  => array( 'type' => 'boolean' ),
						'replace_original'    => array( 'type' => 'boolean' ),
						'source'              => array( 'type' => 'object', 'additionalProperties' => true ),
						'derivative'          => array( 'type' => 'object', 'additionalProperties' => true ),
						'derivative_url'      => array( 'type' => 'string' ),
						'derivative_relative_file' => array( 'type' => 'string' ),
						'derivatives'         => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'edit_link'           => array( 'type' => 'string' ),
						'preview'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'             => array( 'type' => 'boolean' ),
					),
					array( 'attachment_id', 'optimized', 'original_preserved', 'replace_original', 'dry_run' )
				),
				'execute_callback' => array( $this, 'optimize_media_asset' ),
			),
			'npcink-abilities-toolkit/replace-media-file' => array(
				'label'           => __( 'Replace Media File', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Replaces one attachment main file with a previously generated optimized derivative after host approval, preserving backup metadata for restore-media-backup.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'upload_files',
				'required_scopes' => array( 'media.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'attachment_id'                 => array( 'type' => 'integer', 'minimum' => 1 ),
						'derivative_relative_file'      => array( 'type' => 'string', 'minLength' => 1 ),
						'expected_current_relative_file' => array( 'type' => 'string' ),
						'expected_current_mime_type'    => array( 'type' => 'string' ),
						'expected_derivative_mime_type' => array( 'type' => 'string' ),
						'backup_suffix'                 => array( 'type' => 'string', 'maxLength' => 48, 'default' => 'npcink-abilities-toolkit-backup' ),
					),
					array( 'attachment_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'attachment_id'      => array( 'type' => 'integer' ),
						'mode'               => array( 'type' => 'string' ),
						'replaced'           => array( 'type' => 'boolean' ),
						'rolled_back'        => array( 'type' => 'boolean' ),
						'original_preserved' => array( 'type' => 'boolean' ),
						'replacement_id'     => array( 'type' => 'string' ),
						'before'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'after'              => array( 'type' => 'object', 'additionalProperties' => true ),
						'backup'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'content_reference_repairs' => array( 'type' => 'object', 'additionalProperties' => true ),
						'verification'       => array( 'type' => 'object', 'additionalProperties' => true ),
						'history'            => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'edit_link'          => array( 'type' => 'string' ),
						'preview'            => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'            => array( 'type' => 'boolean' ),
					),
					array( 'attachment_id', 'mode', 'replaced', 'rolled_back', 'original_preserved', 'dry_run' )
				),
				'execute_callback' => array( $this, 'replace_media_file' ),
			),
			'npcink-abilities-toolkit/restore-media-backup' => array(
				'label'           => __( 'Restore Media Backup', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Restores one attachment by copying a recorded backup back to the original media path and URL after host approval.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'upload_files',
				'required_scopes' => array( 'media.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'attachment_id'                  => array( 'type' => 'integer', 'minimum' => 1 ),
						'backup_id'                      => array( 'type' => 'string', 'minLength' => 1 ),
						'expected_current_relative_file' => array( 'type' => 'string' ),
						'expected_current_mime_type'     => array( 'type' => 'string' ),
						'target_conflict_mode'           => array( 'type' => 'string', 'enum' => array( 'fail', 'overwrite' ), 'default' => 'fail' ),
					),
					array( 'attachment_id', 'backup_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'attachment_id'      => array( 'type' => 'integer' ),
						'backup_id'          => array( 'type' => 'string' ),
						'replacement_id'     => array( 'type' => 'string' ),
						'restored'           => array( 'type' => 'boolean' ),
						'rolled_back'        => array( 'type' => 'boolean' ),
						'original_preserved' => array( 'type' => 'boolean' ),
						'before'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'after'              => array( 'type' => 'object', 'additionalProperties' => true ),
						'backup'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'current_backup'     => array( 'type' => 'object', 'additionalProperties' => true ),
						'content_reference_repairs' => array( 'type' => 'object', 'additionalProperties' => true ),
						'verification'       => array( 'type' => 'object', 'additionalProperties' => true ),
						'history'            => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'edit_link'          => array( 'type' => 'string' ),
						'preview'            => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'            => array( 'type' => 'boolean' ),
					),
					array( 'attachment_id', 'backup_id', 'restored', 'rolled_back', 'original_preserved', 'dry_run' )
				),
				'execute_callback' => array( $this, 'restore_media_backup' ),
				),
				'npcink-abilities-toolkit/rename-media-file' => array(
					'label'           => __( 'Rename Media File', 'npcink-abilities-toolkit' ),
					'description'     => __( 'Renames one attachment main file within its current uploads directory after host approval, preserving a backup and rollback metadata.', 'npcink-abilities-toolkit' ),
					'category'        => 'npcink-abilities-toolkit-write',
					'capability'      => 'upload_files',
					'required_scopes' => array( 'media.write' ),
					'channels'        => array( 'agent', 'mcp' ),
					'meta'            => $this->write_meta(),
					'input_schema'    => $this->schema(
						array(
							'attachment_id'                  => array( 'type' => 'integer', 'minimum' => 1 ),
							'target_file_name'               => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 120 ),
							'expected_current_relative_file' => array( 'type' => 'string' ),
							'expected_current_mime_type'     => array( 'type' => 'string' ),
							'expected_current_md5'           => array( 'type' => 'string', 'minLength' => 32, 'maxLength' => 36 ),
							'expected_current_sha256'        => array( 'type' => 'string', 'minLength' => 64, 'maxLength' => 71 ),
							'conflict_mode'                  => array( 'type' => 'string', 'enum' => array( 'fail', 'unique' ), 'default' => 'fail' ),
							'backup_suffix'                  => array( 'type' => 'string', 'maxLength' => 48, 'default' => 'npcink-abilities-toolkit-rename-backup' ),
						),
						array( 'attachment_id', 'target_file_name' )
					),
					'output_schema'   => $this->schema(
						array(
							'attachment_id'      => array( 'type' => 'integer' ),
							'renamed'            => array( 'type' => 'boolean' ),
							'original_preserved' => array( 'type' => 'boolean' ),
							'rename_id'          => array( 'type' => 'string' ),
							'before'             => array( 'type' => 'object', 'additionalProperties' => true ),
							'after'              => array( 'type' => 'object', 'additionalProperties' => true ),
							'backup'             => array( 'type' => 'object', 'additionalProperties' => true ),
							'history'            => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
							'edit_link'          => array( 'type' => 'string' ),
							'preview'            => array( 'type' => 'object', 'additionalProperties' => true ),
							'dry_run'            => array( 'type' => 'boolean' ),
						),
						array( 'attachment_id', 'renamed', 'original_preserved', 'dry_run' )
					),
					'execute_callback' => array( $this, 'rename_media_file' ),
				),
				'npcink-abilities-toolkit/adopt-cloud-media-derivative' => array(
					'label'           => __( 'Adopt Cloud Media Derivative', 'npcink-abilities-toolkit' ),
					'description'     => __( 'Adopts one approved short-lived Cloud derivative artifact as the attachment main file, with local backup and rollback metadata.', 'npcink-abilities-toolkit' ),
					'category'        => 'npcink-abilities-toolkit-write',
					'capability'      => 'upload_files',
					'required_scopes' => array( 'media.write' ),
					'channels'        => array( 'agent', 'mcp' ),
					'meta'            => $this->write_meta(),
					'input_schema'    => $this->schema(
						array(
							'attachment_id'                  => array( 'type' => 'integer', 'minimum' => 1 ),
							'derivative_artifact'            => array( 'type' => 'object', 'additionalProperties' => true ),
							'expected_current_relative_file' => array( 'type' => 'string' ),
							'expected_current_mime_type'     => array( 'type' => 'string' ),
							'expected_derivative_mime_type'  => array( 'type' => 'string' ),
							'file_name'                      => array( 'type' => 'string', 'maxLength' => 120 ),
							'expected_content_reference_post_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer', 'minimum' => 1 ), 'maxItems' => 50 ),
							'expected_content_reference_post_count' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 50 ),
							'expected_content_reference_replacement_count' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 1000 ),
							'backup_suffix'                  => array( 'type' => 'string', 'maxLength' => 48, 'default' => 'npcink-abilities-toolkit-cloud-backup' ),
						),
						array( 'attachment_id', 'derivative_artifact' )
				),
				'output_schema'   => $this->schema(
					array(
						'attachment_id'      => array( 'type' => 'integer' ),
						'replaced'           => array( 'type' => 'boolean' ),
						'original_preserved' => array( 'type' => 'boolean' ),
						'replacement_id'     => array( 'type' => 'string' ),
						'before'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'after'              => array( 'type' => 'object', 'additionalProperties' => true ),
						'backup'             => array( 'type' => 'object', 'additionalProperties' => true ),
						'artifact'           => array( 'type' => 'object', 'additionalProperties' => true ),
						'proposed_filename'  => array( 'type' => 'string' ),
						'filename_policy'    => array( 'type' => 'object', 'additionalProperties' => true ),
						'content_reference_repairs' => array( 'type' => 'object', 'additionalProperties' => true ),
						'verification'       => array( 'type' => 'object', 'additionalProperties' => true ),
						'history'            => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => true ) ),
						'edit_link'          => array( 'type' => 'string' ),
						'preview'            => array( 'type' => 'object', 'additionalProperties' => true ),
						'dry_run'            => array( 'type' => 'boolean' ),
					),
					array( 'attachment_id', 'replaced', 'original_preserved', 'dry_run' )
				),
				'execute_callback' => array( $this, 'adopt_cloud_media_derivative' ),
			),
			'npcink-abilities-toolkit/set-post-featured-image' => array(
				'label'           => __( 'Set Post Featured Image', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Sets a post featured image from an attachment ID or approved remote media URL, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'media.write' ),
				'channels'        => array( 'agent' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'       => $post_id,
						'attachment_id' => array( 'type' => 'integer', 'minimum' => 1 ),
						'media_url'     => array( 'type' => 'string', 'format' => 'uri' ),
						'media_title'   => $text,
					),
					array( 'post_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'            => array( 'type' => 'integer' ),
						'attachment_id'      => array( 'type' => 'integer' ),
						'featured_image_url' => array( 'type' => 'string' ),
						'updated'            => array( 'type' => 'boolean' ),
						'source'             => array( 'type' => 'string' ),
						'edit_link'          => array( 'type' => 'string' ),
						'dry_run'            => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'attachment_id', 'updated', 'dry_run' )
				),
				'execute_callback' => array( $this, 'set_post_featured_image' ),
			),
			'npcink-abilities-toolkit/schedule-post' => array(
				'label'           => __( 'Schedule Post', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Schedules a post for future publication after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'publish_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id'    => $post_id,
						'publish_at' => array( 'type' => 'string', 'minLength' => 1 ),
						'timezone'   => array(
							'type'    => 'string',
							'enum'    => array( 'site', 'utc' ),
							'default' => 'site',
						),
					),
					array( 'post_id', 'publish_at' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'        => array( 'type' => 'integer' ),
						'scheduled'      => array( 'type' => 'boolean' ),
						'status'         => array( 'type' => 'string' ),
						'publish_at'     => array( 'type' => 'string' ),
						'publish_at_gmt' => array( 'type' => 'string' ),
						'edit_link'      => array( 'type' => 'string' ),
						'dry_run'        => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'scheduled', 'status', 'dry_run' )
				),
				'execute_callback' => array( $this, 'schedule_post' ),
			),
			'npcink-abilities-toolkit/publish-post' => array(
				'label'           => __( 'Publish Post', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Publishes a draft or pending post after host approval. Only treat the post as publicly accessible after this succeeds.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'publish_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id' => $post_id,
					),
					array( 'post_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'   => array( 'type' => 'integer' ),
						'published' => array( 'type' => 'boolean' ),
						'status'    => array( 'type' => 'string' ),
						'edit_link' => array( 'type' => 'string' ),
						'permalink' => array( 'type' => 'string' ),
						'dry_run'   => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'published', 'status', 'dry_run' )
				),
				'execute_callback' => array( $this, 'publish_post' ),
			),
			'npcink-abilities-toolkit/restore-post' => array(
				'label'           => __( 'Restore Post', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Restores a trashed post or page after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id' => $post_id,
					),
					array( 'post_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id'  => array( 'type' => 'integer' ),
						'restored' => array( 'type' => 'boolean' ),
						'status'   => array( 'type' => 'string' ),
						'edit_link' => array( 'type' => 'string' ),
						'dry_run'  => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'restored', 'status', 'dry_run' )
				),
				'execute_callback' => array( $this, 'restore_post' ),
			),
			'npcink-abilities-toolkit/approve-comment'      => array(
				'label'           => __( 'Approve Comment', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Approves one comment after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'moderate_comments',
				'required_scopes' => array( 'comments.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'comment_id' => array( 'type' => 'integer', 'minimum' => 1 ),
					),
					array( 'comment_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'comment_id'     => array( 'type' => 'integer' ),
						'post_id'        => array( 'type' => 'integer' ),
						'updated'        => array( 'type' => 'boolean' ),
						'comment_status' => array( 'type' => 'string' ),
						'dry_run'        => array( 'type' => 'boolean' ),
					),
					array( 'comment_id', 'post_id', 'updated', 'comment_status', 'dry_run' )
				),
				'execute_callback' => array( $this, 'approve_comment' ),
			),
			'npcink-abilities-toolkit/reply-comment'        => array(
				'label'           => __( 'Reply Comment', 'npcink-abilities-toolkit' ),
				'description'     => __( 'Replies to one comment as the current user after host approval, or returns a dry-run preview by default.', 'npcink-abilities-toolkit' ),
				'category'        => 'npcink-abilities-toolkit-write',
				'capability'      => 'moderate_comments',
				'required_scopes' => array( 'comments.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->write_meta(),
				'input_schema'    => $this->schema(
					array(
						'comment_id'     => array( 'type' => 'integer', 'minimum' => 1 ),
						'content'        => array( 'type' => 'string', 'minLength' => 1 ),
						'content_format' => array(
							'type' => 'string',
							'enum' => array( 'html', 'markdown', 'plain' ),
						),
					),
					array( 'comment_id', 'content' )
				),
				'output_schema'   => $this->schema(
					array(
						'comment_id'     => array( 'type' => 'integer' ),
						'parent_id'      => array( 'type' => 'integer' ),
						'post_id'        => array( 'type' => 'integer' ),
						'created'        => array( 'type' => 'boolean' ),
						'comment_status' => array( 'type' => 'string' ),
						'content_format' => array( 'type' => 'string' ),
						'edit_link'      => array( 'type' => 'string' ),
						'dry_run'        => array( 'type' => 'boolean' ),
					),
					array( 'comment_id', 'parent_id', 'post_id', 'created', 'comment_status', 'dry_run' )
				),
				'execute_callback' => array( $this, 'reply_comment' ),
			),
		);
	}

	/**
	 * Creates one draft post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function create_draft( $input ) {
		$input     = is_array( $input ) ? $input : array();
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_type_invalid', __( 'Post type is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object || empty( $post_type_object->cap->create_posts ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_type_invalid', __( 'Post type does not support creation.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$create_capability = sanitize_key( (string) $post_type_object->cap->create_posts );
		if ( '' !== $create_capability && ! current_user_can( $create_capability ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to create this post type.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		if ( '' === $title ) {
			return new \WP_Error( 'npcink_abilities_toolkit_title_required', __( 'Title is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$content_payload    = $this->normalize_content_input( $input, 'content', $title );
		$content            = (string) ( $content_payload['content'] ?? '' );
		$excerpt            = array_key_exists( 'excerpt', $input ) ? sanitize_textarea_field( (string) $input['excerpt'] ) : '';
		$soft_block_reason  = sanitize_key( (string) ( $input['soft_block_reason'] ?? '' ) );
		$soft_block_summary = sanitize_textarea_field( (string) ( $input['soft_block_summary'] ?? '' ) );

		$payload = array(
			'post_id'        => 0,
			'status'         => 'dry_run',
			'content_format' => (string) ( $content_payload['content_format'] ?? 'html' ),
			'edit_link'      => '',
			'preview_link'   => '',
			'preview'        => array(
				'action'             => ( $this->should_dry_run( $input ) && '' !== $soft_block_reason ) ? 'create_draft_soft_blocked' : 'create_draft',
				'post_type'          => $post_type,
				'title'              => $title,
				'content_format'     => (string) ( $content_payload['content_format'] ?? 'html' ),
				'content_length'     => strlen( $content ),
				'excerpt_length'     => strlen( $excerpt ),
				'soft_block_reason'  => $soft_block_reason,
				'soft_block_summary' => $soft_block_summary,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/create-draft', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => $post_type,
				'post_status'  => 'draft',
				'post_title'   => $title,
				'post_content' => $content,
				'post_excerpt' => $excerpt,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		$post_id = absint( $post_id );

		$meta = is_array( $input['meta'] ?? null ) ? $input['meta'] : array();
		foreach ( $meta as $meta_key => $meta_value ) {
			$meta_key = sanitize_key( (string) $meta_key );
			if ( '' === $meta_key || is_array( $meta_value ) || is_object( $meta_value ) ) {
				continue;
			}
			update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $meta_value ) );
		}

		return array(
			'post_id'        => $post_id,
			'status'         => $this->post_status( $post_id ),
			'content_format' => (string) ( $content_payload['content_format'] ?? 'html' ),
			'edit_link'      => $this->edit_link( $post_id ),
			'preview_link'   => function_exists( 'get_preview_post_link' ) ? (string) get_preview_post_link( $post_id ) : '',
			'dry_run'        => false,
		);
	}

	/**
	 * Updates title/content/excerpt on one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function update_post( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$changes = array();
		$update  = array( 'ID' => $post_id );
		if ( array_key_exists( 'title', $input ) ) {
			$new_title            = sanitize_text_field( (string) $input['title'] );
			$update['post_title'] = $new_title;
			$changes['title']     = array(
				'before' => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'after'  => $new_title,
			);
		}
		if ( array_key_exists( 'content', $input ) ) {
			$title_for_content      = array_key_exists( 'title', $input ) ? sanitize_text_field( (string) $input['title'] ) : (string) ( $post->post_title ?? '' );
			$content_payload        = $this->normalize_content_input( $input, 'content', $title_for_content );
			$new_content            = (string) ( $content_payload['content'] ?? '' );
			$update['post_content'] = $new_content;
			$changes['content']     = array(
				'before'         => (string) ( $post->post_content ?? '' ),
				'after'          => $new_content,
				'content_format' => (string) ( $content_payload['content_format'] ?? 'html' ),
			);
		}
		if ( array_key_exists( 'excerpt', $input ) ) {
			$new_excerpt            = sanitize_textarea_field( (string) $input['excerpt'] );
			$update['post_excerpt'] = $new_excerpt;
			$changes['excerpt']     = array(
				'before' => sanitize_textarea_field( (string) ( $post->post_excerpt ?? '' ) ),
				'after'  => $new_excerpt,
			);
		}
		if ( empty( $changes ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_no_changes', __( 'No update fields were provided.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$payload = array(
			'post_id'   => $post_id,
			'updated'   => false,
			'status'    => 'dry_run',
			'edit_link' => $this->edit_link( $post_id ),
			'changes'   => $changes,
			'preview'   => array(
				'action'         => 'update_post',
				'target_status'  => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'changed_fields' => array_keys( $changes ),
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/update-post', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = wp_update_post( $update, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$payload['updated'] = true;
		$payload['status']  = $this->post_status( $post_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates SEO title and description metadata on one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function set_post_seo_meta( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$current          = $this->read_post_seo_meta( $post_id );
		$has_title        = array_key_exists( 'seo_title', $input );
		$has_description  = array_key_exists( 'seo_description', $input );
		if ( ! $has_title && ! $has_description ) {
			return new \WP_Error( 'npcink_abilities_toolkit_no_changes', __( 'No SEO metadata fields were provided.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$current_title       = sanitize_text_field( (string) ( $current['title'] ?? '' ) );
		$current_description = sanitize_textarea_field( (string) ( $current['description'] ?? '' ) );
		$next_title          = $has_title ? sanitize_text_field( (string) $input['seo_title'] ) : $current_title;
		$next_description    = $has_description ? sanitize_textarea_field( (string) $input['seo_description'] ) : $current_description;
		$changes             = array();
		if ( $has_title ) {
			$changes['seo_title'] = $next_title;
		}
		if ( $has_description ) {
			$changes['seo_description'] = $next_description;
		}
		$payload          = array(
			'post_id'   => $post_id,
			'updated'   => false,
			'status'    => 'dry_run',
			'provider'  => sanitize_key( (string) ( $current['provider'] ?? 'seo_adapter' ) ),
			'changes'   => $changes,
			'current'   => array(
				'title'       => $current_title,
				'description' => $current_description,
			),
			'preview'   => array(
				'action'         => 'seo_meta_write',
				'changed_fields' => array_keys( $changes ),
			),
			'edit_link' => $this->edit_link( $post_id ),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/set-post-seo-meta', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$written = $this->write_post_seo_meta(
			$post_id,
			array(
				'title'       => $next_title,
				'description' => $next_description,
			)
		);
		if ( is_wp_error( $written ) ) {
			return $written;
		}
		$written = is_array( $written ) ? $written : array();

		return array(
			'post_id'   => $post_id,
			'updated'   => true,
			'status'    => $this->post_status( $post_id ),
			'provider'  => sanitize_key( (string) ( $written['provider'] ?? $current['provider'] ?? 'seo_adapter' ) ),
			'changes'   => array(
				'seo_title'       => sanitize_text_field( (string) ( $written['title'] ?? $next_title ) ),
				'seo_description' => sanitize_textarea_field( (string) ( $written['description'] ?? $next_description ) ),
			),
			'edit_link' => $this->edit_link( $post_id ),
			'dry_run'   => false,
		);
	}

	/**
	 * Applies patch operations to one post's stored content.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function patch_post_content( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$before_content = (string) ( $post->post_content ?? '' );
		$patch_result   = $this->apply_patch_operations( $before_content, is_array( $input['operations'] ?? null ) ? $input['operations'] : array() );
		if ( is_wp_error( $patch_result ) ) {
			return $patch_result;
		}

		$after_content = (string) ( $patch_result['content'] ?? $before_content );
		$updated       = $before_content !== $after_content;
		$impact_ranges = is_array( $patch_result['impact_ranges'] ?? null ) ? $patch_result['impact_ranges'] : array();
		$patch_preview = is_array( $patch_result['patch_preview'] ?? null ) ? $patch_result['patch_preview'] : array();

		$payload = array(
			'post_id'               => $post_id,
			'updated'               => $updated,
			'status'                => 'dry_run',
			'edit_link'             => $this->edit_link( $post_id ),
			'content_length_before' => strlen( $before_content ),
			'content_length_after'  => strlen( $after_content ),
			'impact_ranges'         => $impact_ranges,
			'patch_preview'         => $patch_preview,
			'diff_preview'          => $this->build_text_diff_preview( $before_content, $after_content ),
			'preview'               => array(
				'action'             => 'patch_post_content',
				'applied_operations' => count(
					array_filter(
						$patch_preview,
						static function ( $row ) {
							return absint( $row['applied'] ?? 0 ) > 0;
						}
					)
				),
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/patch-post-content', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		if ( $updated ) {
			$result = wp_update_post( array( 'ID' => $post_id, 'post_content' => $after_content ), true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$payload['status']  = $this->post_status( $post_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Applies exact patch operations to one WordPress setting value.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function patch_setting_value( $input ) {
		$input = is_array( $input ) ? $input : array();
		$target_type = sanitize_key( (string) ( $input['target_type'] ?? '' ) );
		$target_name = sanitize_key( (string) ( $input['target_name'] ?? '' ) );
		if ( ! in_array( $target_type, array( 'option', 'theme_mod' ), true ) || '' === $target_name ) {
			return new \WP_Error( 'npcink_abilities_toolkit_setting_target_invalid', __( 'Setting target is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$required_cap = 'theme_mod' === $target_type ? 'edit_theme_options' : 'manage_options';
		if ( ! current_user_can( $required_cap ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to patch this setting.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$before_value = $this->get_patchable_setting_value( $target_type, $target_name );
		$patch_result = $this->apply_patch_operations_to_value( $before_value, is_array( $input['operations'] ?? null ) ? $input['operations'] : array() );
		if ( is_wp_error( $patch_result ) ) {
			return $patch_result;
		}
		$after_value = $patch_result['value'] ?? $before_value;
		$before_text = $this->setting_value_preview_text( $before_value );
		$after_text = $this->setting_value_preview_text( $after_value );
		$updated = $before_text !== $after_text;

		$payload = array(
			'target_type'   => $target_type,
			'target_name'   => $target_name,
			'updated'       => $updated,
			'value_type'    => $this->setting_value_type( $before_value ),
			'impact_ranges' => is_array( $patch_result['impact_ranges'] ?? null ) ? $patch_result['impact_ranges'] : array(),
			'patch_preview' => is_array( $patch_result['patch_preview'] ?? null ) ? $patch_result['patch_preview'] : array(),
			'diff_preview'  => $this->build_text_diff_preview( $before_text, $after_text ),
			'preview'       => array(
				'action' => 'patch_setting_value',
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}

		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/patch-setting-value', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		if ( $updated ) {
			if ( 'theme_mod' === $target_type ) {
				if ( ! function_exists( 'set_theme_mod' ) ) {
					return new \WP_Error( 'npcink_abilities_toolkit_theme_mod_unavailable', __( 'Theme mod writes are unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 501 ) );
				}
				set_theme_mod( $target_name, $after_value );
			} else {
				if ( ! function_exists( 'update_option' ) ) {
					return new \WP_Error( 'npcink_abilities_toolkit_option_unavailable', __( 'Option writes are unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 501 ) );
				}
				update_option( $target_name, $after_value, null );
			}
		}

		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Writes Gutenberg blocks into one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function update_post_blocks( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$mode = sanitize_key( (string) ( $input['mode'] ?? 'replace' ) );
		if ( ! in_array( $mode, array( 'replace', 'append' ), true ) ) {
			$mode = 'replace';
		}
		$validate_roundtrip = ! array_key_exists( 'validate_roundtrip', $input ) || ! empty( $input['validate_roundtrip'] );
		$raw_blocks         = is_array( $input['blocks'] ?? null ) ? $input['blocks'] : array();
		if ( empty( $raw_blocks ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_required', __( 'Blocks are required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$block_errors            = array();
		$normalized_input_blocks = $this->normalize_blocks_input( $raw_blocks, $block_errors, 'blocks' );
		if ( ! empty( $block_errors ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_invalid', __( 'Blocks structure is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400, 'errors' => $block_errors ) );
		}

		$before_content       = (string) ( $post->post_content ?? '' );
		$before_parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $before_content ) : array();
		$before_block_count   = $this->count_blocks_recursive( $before_parsed_blocks );
		$target_blocks        = $normalized_input_blocks;
		if ( 'append' === $mode ) {
			$existing_errors = array();
			$existing_blocks = $this->normalize_blocks_input( $before_parsed_blocks, $existing_errors, 'existing' );
			$target_blocks   = array_merge( $existing_blocks, $normalized_input_blocks );
		}

		$after_content            = $this->serialize_blocks_native( $target_blocks );
		$after_parsed_blocks      = function_exists( 'parse_blocks' ) ? parse_blocks( $after_content ) : array();
		$after_block_count        = $this->count_blocks_recursive( $target_blocks );
		$parsed_top_level_count   = is_array( $after_parsed_blocks ) ? count( $after_parsed_blocks ) : 0;
		$expected_top_level_count = count( $target_blocks );
		$roundtrip_checked        = $validate_roundtrip && function_exists( 'parse_blocks' );
		$roundtrip_ok             = true;
		if ( $roundtrip_checked ) {
			$roundtrip_ok = $expected_top_level_count === $parsed_top_level_count;
		}
		$validation = array(
			'valid'                    => empty( $block_errors ) && $roundtrip_ok,
			'roundtrip_checked'        => $roundtrip_checked,
			'roundtrip_ok'             => $roundtrip_ok,
			'parse_available'          => function_exists( 'parse_blocks' ),
			'expected_top_level_count' => $expected_top_level_count,
			'parsed_top_level_count'   => $parsed_top_level_count,
			'errors'                   => $block_errors,
		);
		$updated = $before_content !== $after_content;

		$payload = array(
			'post_id'               => $post_id,
			'updated'               => $updated,
			'status'                => 'dry_run',
			'mode'                  => $mode,
			'edit_link'             => $this->edit_link( $post_id ),
			'block_count_before'    => $before_block_count,
			'block_count_after'     => $after_block_count,
			'content_length_before' => strlen( $before_content ),
			'content_length_after'  => strlen( $after_content ),
			'validation'            => $validation,
			'impact_ranges'         => $this->build_impact_ranges_from_diff( $before_content, $after_content ),
			'diff_preview'          => $this->build_text_diff_preview( $before_content, $after_content ),
			'preview'               => array(
				'action' => 'update_post_blocks',
				'mode'   => $mode,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/update-post-blocks', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		if ( $roundtrip_checked && ! $roundtrip_ok ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_roundtrip_invalid', __( 'Blocks roundtrip validation failed; write was blocked.', 'npcink-abilities-toolkit' ), array( 'status' => 400, 'validation' => $validation ) );
		}

		if ( $updated ) {
			$result = wp_update_post( array( 'ID' => $post_id, 'post_content' => $after_content ), true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$payload['status']  = $this->post_status( $post_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates one block theme template block tree.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function update_template_blocks( $input ) {
		return $this->update_site_editor_entity_blocks( $input, 'wp_template', 'npcink-abilities-toolkit/update-template-blocks' );
	}

	/**
	 * Creates or updates one block theme template override block tree.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function upsert_template_blocks( $input ) {
		$input              = is_array( $input ) ? $input : array();
		$post_id            = absint( $input['post_id'] ?? 0 );
		$slug               = $this->normalize_template_slug( (string) ( $input['slug'] ?? '' ) );
		$theme              = sanitize_key( (string) ( $input['theme'] ?? '' ) );
		$title              = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$source_template_id = sanitize_text_field( (string) ( $input['source_template_id'] ?? '' ) );
		$mode               = sanitize_key( (string) ( $input['mode'] ?? 'replace' ) );
		$validate_roundtrip = ! array_key_exists( 'validate_roundtrip', $input ) || ! empty( $input['validate_roundtrip'] );
		$raw_blocks         = $input['blocks'] ?? array();
		if ( 'replace' !== $mode ) {
			return new \WP_Error( 'npcink_abilities_toolkit_site_editor_block_mode_invalid', __( 'Site Editor block writes currently support mode=replace only.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to edit block theme templates.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		if ( '' === $slug ) {
			return new \WP_Error( 'npcink_abilities_toolkit_template_slug_required', __( 'Template slug is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( '' === $theme ) {
			$theme = $this->active_theme_stylesheet();
		}
		if ( '' === $title ) {
			$title = ucwords( str_replace( '-', ' ', $slug ) );
		}
		if ( ! is_array( $raw_blocks ) || empty( $raw_blocks ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_required', __( 'Blocks are required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$existing_post = $post_id > 0 ? get_post( $post_id ) : $this->find_template_override_post( $theme, $slug );
		if ( $existing_post && 'wp_template' !== sanitize_key( (string) ( $existing_post->post_type ?? '' ) ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_site_editor_post_type_invalid', __( 'Site Editor entity post type is invalid for this ability.', 'npcink-abilities-toolkit' ), array( 'status' => 400, 'expected_post_type' => 'wp_template' ) );
		}
		$existing_post_id = is_object( $existing_post ) ? absint( $existing_post->ID ?? 0 ) : 0;

		$block_errors  = array();
		$target_blocks = $this->normalize_blocks_input( $raw_blocks, $block_errors, 'blocks' );
		if ( ! empty( $block_errors ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_invalid', __( 'Blocks structure is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400, 'errors' => $block_errors ) );
		}

		$before_content       = is_object( $existing_post ) ? (string) ( $existing_post->post_content ?? '' ) : '';
		$before_parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $before_content ) : array();
		$after_content        = $this->serialize_blocks_native( $target_blocks );
		$after_parsed_blocks  = function_exists( 'parse_blocks' ) ? parse_blocks( $after_content ) : array();
		$roundtrip_checked    = $validate_roundtrip && function_exists( 'parse_blocks' );
		$roundtrip_ok         = true;
		if ( $roundtrip_checked ) {
			$roundtrip_ok = count( $target_blocks ) === count( is_array( $after_parsed_blocks ) ? $after_parsed_blocks : array() );
		}
		$validation = array(
			'valid'                    => empty( $block_errors ) && $roundtrip_ok,
			'roundtrip_checked'        => $roundtrip_checked,
			'roundtrip_ok'             => $roundtrip_ok,
			'parse_available'          => function_exists( 'parse_blocks' ),
			'expected_top_level_count' => count( $target_blocks ),
			'parsed_top_level_count'   => count( is_array( $after_parsed_blocks ) ? $after_parsed_blocks : array() ),
			'errors'                   => $block_errors,
		);
		$created = 0 === $existing_post_id;
		$updated = $created || $before_content !== $after_content;
		$payload = array(
			'post_id'               => $existing_post_id,
			'post_type'             => 'wp_template',
			'slug'                  => $slug,
			'theme'                 => $theme,
			'source_template_id'    => $source_template_id,
			'created'               => $created,
			'updated'               => $updated,
			'status'                => 'dry_run',
			'mode'                  => $mode,
			'edit_link'             => $existing_post_id > 0 ? $this->edit_link( $existing_post_id ) : '',
			'block_count_before'    => $this->count_blocks_recursive( $before_parsed_blocks ),
			'block_count_after'     => $this->count_blocks_recursive( $target_blocks ),
			'content_length_before' => strlen( $before_content ),
			'content_length_after'  => strlen( $after_content ),
			'validation'            => $validation,
			'impact_ranges'         => $this->build_impact_ranges_from_diff( $before_content, $after_content ),
			'diff_preview'          => $this->build_text_diff_preview( $before_content, $after_content ),
			'preview'               => array(
				'action' => 'upsert_template_blocks',
				'mode'   => $mode,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}

		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/upsert-template-blocks', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		if ( $roundtrip_checked && ! $roundtrip_ok ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_roundtrip_invalid', __( 'Blocks roundtrip validation failed; write was blocked.', 'npcink-abilities-toolkit' ), array( 'status' => 400, 'validation' => $validation ) );
		}

		if ( $existing_post_id > 0 ) {
			if ( $updated ) {
				$result = wp_update_post(
					array(
						'ID'           => $existing_post_id,
						'post_title'   => $title,
						'post_content' => $after_content,
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
			$post_id = $existing_post_id;
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'    => 'wp_template',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => $after_content,
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			$post_id = absint( $post_id );
			if ( function_exists( 'taxonomy_exists' ) && taxonomy_exists( 'wp_theme' ) && function_exists( 'wp_set_post_terms' ) ) {
				wp_set_post_terms( $post_id, $theme, 'wp_theme' );
			}
		}

		$payload['post_id']  = $post_id;
		$payload['status']   = $this->post_status( $post_id );
		$payload['edit_link'] = $this->edit_link( $post_id );
		$payload['dry_run']  = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates one block theme template part block tree.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function update_template_part_blocks( $input ) {
		return $this->update_site_editor_entity_blocks( $input, 'wp_template_part', 'npcink-abilities-toolkit/update-template-part-blocks' );
	}

	/**
	 * Updates a Site Editor post content block tree after host approval.
	 *
	 * @param mixed  $input Input args.
	 * @param string $expected_post_type Expected post type.
	 * @param string $ability_id Ability id.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function update_site_editor_entity_blocks( $input, $expected_post_type, $ability_id ) {
		$input               = is_array( $input ) ? $input : array();
		$post_id             = absint( $input['post_id'] ?? 0 );
		$mode                = sanitize_key( (string) ( $input['mode'] ?? 'replace' ) );
		$validate_roundtrip  = ! array_key_exists( 'validate_roundtrip', $input ) || ! empty( $input['validate_roundtrip'] );
		$raw_blocks          = $input['blocks'] ?? array();
		$expected_post_type  = sanitize_key( (string) $expected_post_type );
		if ( 'replace' !== $mode ) {
			return new \WP_Error( 'npcink_abilities_toolkit_site_editor_block_mode_invalid', __( 'Site Editor block writes currently support mode=replace only.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to edit block theme templates.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$post = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		if ( $expected_post_type !== sanitize_key( (string) ( $post->post_type ?? '' ) ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_site_editor_post_type_invalid', __( 'Site Editor entity post type is invalid for this ability.', 'npcink-abilities-toolkit' ), array( 'status' => 400, 'expected_post_type' => $expected_post_type ) );
		}
		if ( ! is_array( $raw_blocks ) || empty( $raw_blocks ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_required', __( 'Blocks are required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$block_errors  = array();
		$target_blocks = $this->normalize_blocks_input( $raw_blocks, $block_errors, 'blocks' );
		if ( ! empty( $block_errors ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_invalid', __( 'Blocks structure is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400, 'errors' => $block_errors ) );
		}

		$before_content       = (string) ( $post->post_content ?? '' );
		$before_parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $before_content ) : array();
		$after_content        = $this->serialize_blocks_native( $target_blocks );
		$after_parsed_blocks  = function_exists( 'parse_blocks' ) ? parse_blocks( $after_content ) : array();
		$roundtrip_checked    = $validate_roundtrip && function_exists( 'parse_blocks' );
		$roundtrip_ok         = true;
		if ( $roundtrip_checked ) {
			$roundtrip_ok = count( $target_blocks ) === count( is_array( $after_parsed_blocks ) ? $after_parsed_blocks : array() );
		}
		$validation = array(
			'valid'                    => empty( $block_errors ) && $roundtrip_ok,
			'roundtrip_checked'        => $roundtrip_checked,
			'roundtrip_ok'             => $roundtrip_ok,
			'parse_available'          => function_exists( 'parse_blocks' ),
			'expected_top_level_count' => count( $target_blocks ),
			'parsed_top_level_count'   => count( is_array( $after_parsed_blocks ) ? $after_parsed_blocks : array() ),
			'errors'                   => $block_errors,
		);
		$updated = $before_content !== $after_content;
		$payload = array(
			'post_id'               => $post_id,
			'post_type'             => $expected_post_type,
			'slug'                  => sanitize_key( (string) ( $post->post_name ?? '' ) ),
			'updated'               => $updated,
			'status'                => 'dry_run',
			'mode'                  => $mode,
			'edit_link'             => $this->edit_link( $post_id ),
			'block_count_before'    => $this->count_blocks_recursive( $before_parsed_blocks ),
			'block_count_after'     => $this->count_blocks_recursive( $target_blocks ),
			'content_length_before' => strlen( $before_content ),
			'content_length_after'  => strlen( $after_content ),
			'validation'            => $validation,
			'impact_ranges'         => $this->build_impact_ranges_from_diff( $before_content, $after_content ),
			'diff_preview'          => $this->build_text_diff_preview( $before_content, $after_content ),
			'preview'               => array(
				'action'    => 'update_site_editor_entity_blocks',
				'post_type' => $expected_post_type,
				'mode'      => $mode,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}

		$allowed = $this->assert_commit_allowed( $ability_id, $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		if ( $roundtrip_checked && ! $roundtrip_ok ) {
			return new \WP_Error( 'npcink_abilities_toolkit_blocks_roundtrip_invalid', __( 'Blocks roundtrip validation failed; write was blocked.', 'npcink-abilities-toolkit' ), array( 'status' => 400, 'validation' => $validation ) );
		}

		if ( $updated ) {
			$result = wp_update_post( array( 'ID' => $post_id, 'post_content' => $after_content ), true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$payload['status']  = $this->post_status( $post_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates one post slug.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function set_post_slug( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$requested_slug = sanitize_title( (string) ( $input['slug'] ?? '' ) );
		if ( '' === $requested_slug ) {
			return new \WP_Error( 'npcink_abilities_toolkit_slug_required', __( 'Slug is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$before_slug = sanitize_title( (string) ( $post->post_name ?? '' ) );
		$next_slug   = function_exists( 'wp_unique_post_slug' )
			? wp_unique_post_slug( $requested_slug, $post_id, sanitize_key( (string) ( $post->post_status ?? '' ) ), sanitize_key( (string) ( $post->post_type ?? '' ) ), absint( $post->post_parent ?? 0 ) )
			: $requested_slug;
		$next_slug   = sanitize_title( (string) $next_slug );

		$payload = array(
			'post_id'     => $post_id,
			'updated'     => false,
			'status'      => 'dry_run',
			'slug'        => $next_slug,
			'before_slug' => $before_slug,
			'edit_link'   => $this->edit_link( $post_id ),
			'preview'     => array(
				'action'    => 'set_post_slug',
				'from_slug' => $before_slug,
				'to_slug'   => $next_slug,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/set-post-slug', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$updated = wp_update_post( array( 'ID' => $post_id, 'post_name' => $next_slug ), true );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$payload['updated'] = true;
		$payload['status']  = $this->post_status( $post_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates one post author.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function set_post_author( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$author_id = absint( $input['author_id'] ?? 0 );
		if ( $author_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_author_invalid', __( 'Author ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( function_exists( 'get_userdata' ) && ! get_userdata( $author_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_author_invalid', __( 'Author ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$before_author_id = absint( $post->post_author ?? 0 );
		$payload = array(
			'post_id'          => $post_id,
			'updated'          => false,
			'author_id'        => $author_id,
			'before_author_id' => $before_author_id,
			'edit_link'        => $this->edit_link( $post_id ),
			'preview'          => array(
				'action'         => 'set_post_author',
				'from_author_id' => $before_author_id,
				'to_author_id'   => $author_id,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/set-post-author', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$updated = wp_update_post( array( 'ID' => $post_id, 'post_author' => $author_id ), true );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$payload['updated'] = $author_id !== $before_author_id;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates one post template.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function set_post_template( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$template = sanitize_text_field( (string) ( $input['template'] ?? '' ) );
		if ( '' === $template ) {
			return new \WP_Error( 'npcink_abilities_toolkit_template_required', __( 'Template is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$before_template = function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $post_id, '_wp_page_template', true ) ) : '';
		$before_template = '' !== $before_template ? $before_template : 'default';
		$payload = array(
			'post_id'         => $post_id,
			'updated'         => false,
			'template'        => $template,
			'before_template' => $before_template,
			'edit_link'       => $this->edit_link( $post_id ),
			'preview'         => array(
				'action'        => 'set_post_template',
				'from_template' => $before_template,
				'to_template'   => $template,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/set-post-template', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		update_post_meta( $post_id, '_wp_page_template', $template );
		$payload['updated'] = $template !== $before_template;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates one post format.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function set_post_format( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$format = sanitize_key( (string) ( $input['format'] ?? '' ) );
		$allowed_formats = array( 'standard', 'aside', 'image', 'video', 'quote', 'link', 'gallery', 'audio', 'chat', 'status' );
		if ( ! in_array( $format, $allowed_formats, true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_format_invalid', __( 'Post format is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$before_format = function_exists( 'get_post_format' ) ? sanitize_key( (string) get_post_format( $post_id ) ) : '';
		$before_format = '' !== $before_format ? $before_format : 'standard';
		$payload = array(
			'post_id'       => $post_id,
			'updated'       => false,
			'format'        => $format,
			'before_format' => $before_format,
			'edit_link'     => $this->edit_link( $post_id ),
			'preview'       => array(
				'action'      => 'set_post_format',
				'from_format' => $before_format,
				'to_format'   => $format,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/set-post-format', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		set_post_format( $post_id, 'standard' === $format ? false : $format );
		$payload['updated'] = $format !== $before_format;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Creates one taxonomy term.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function create_term( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$taxonomy = $this->valid_taxonomy( $input['taxonomy'] ?? 'category' );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}
		$cap_check = $this->check_taxonomy_capability( $taxonomy, 'manage_terms' );
		if ( is_wp_error( $cap_check ) ) {
			return $cap_check;
		}
		$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		if ( '' === $name ) {
			return new \WP_Error( 'npcink_abilities_toolkit_term_name_required', __( 'Term name is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$slug        = sanitize_title( (string) ( $input['slug'] ?? '' ) );
		$description = sanitize_textarea_field( (string) ( $input['description'] ?? '' ) );
		$parent      = absint( $input['parent'] ?? 0 );
		if ( $parent > 0 && is_wp_error( $this->get_term_object( $parent, $taxonomy ) ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_parent_term_not_found', __( 'Parent term was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}

		$payload = array(
			'taxonomy' => $taxonomy,
			'term_id'  => 0,
			'created'  => false,
			'name'     => $name,
			'slug'     => '' !== $slug ? $slug : sanitize_title( $name ),
			'preview'  => array(
				'action'   => 'create_term',
				'taxonomy' => $taxonomy,
				'name'     => $name,
				'parent'   => $parent,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/create-term', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug, 'description' => $description, 'parent' => $parent ) );
		if ( is_wp_error( $created ) ) {
			return $created;
		}

		$payload['term_id'] = absint( $created['term_id'] ?? 0 );
		$payload['created'] = true;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates one taxonomy term.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function update_term( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$taxonomy = $this->valid_taxonomy( $input['taxonomy'] ?? 'category' );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}
		$term_id = absint( $input['term_id'] ?? 0 );
		$term    = $this->get_term_object( $term_id, $taxonomy );
		if ( is_wp_error( $term ) ) {
			return $term;
		}
		$cap_check = $this->check_taxonomy_capability( $taxonomy, 'edit_terms' );
		if ( is_wp_error( $cap_check ) ) {
			return $cap_check;
		}

		$update_args = array();
		$changes     = array();
		foreach ( array( 'name', 'slug', 'description' ) as $field ) {
			if ( ! array_key_exists( $field, $input ) ) {
				continue;
			}
			$value = 'slug' === $field ? sanitize_title( (string) $input[ $field ] ) : sanitize_text_field( (string) $input[ $field ] );
			if ( 'description' === $field ) {
				$value = sanitize_textarea_field( (string) $input[ $field ] );
			}
			$update_args[ $field ] = $value;
			$changes[ $field ] = array( 'before' => sanitize_text_field( (string) ( $term->{$field} ?? '' ) ), 'after' => $value );
		}
		if ( array_key_exists( 'parent', $input ) ) {
			$parent = absint( $input['parent'] );
			if ( $parent > 0 && is_wp_error( $this->get_term_object( $parent, $taxonomy ) ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_parent_term_not_found', __( 'Parent term was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
			}
			$update_args['parent'] = $parent;
			$changes['parent'] = array( 'before' => absint( $term->parent ?? 0 ), 'after' => $parent );
		}
		if ( empty( $update_args ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_no_changes', __( 'No update fields were provided.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$payload = array(
			'taxonomy' => $taxonomy,
			'term_id'  => $term_id,
			'updated'  => false,
			'changes'  => $changes,
			'preview'  => array(
				'action'         => 'update_term',
				'taxonomy'       => $taxonomy,
				'term_id'        => $term_id,
				'changed_fields' => array_keys( $changes ),
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/update-term', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$updated = wp_update_term( $term_id, $taxonomy, $update_args );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$payload['updated'] = true;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates post taxonomy terms.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function set_post_terms( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$taxonomy = $this->valid_taxonomy( $input['taxonomy'] ?? 'post_tag' );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}
		$cap_check = $this->check_taxonomy_capability( $taxonomy, 'assign_terms' );
		if ( is_wp_error( $cap_check ) ) {
			return $cap_check;
		}

		$mode = sanitize_key( (string) ( $input['mode'] ?? 'replace' ) );
		if ( ! in_array( $mode, array( 'replace', 'append', 'remove' ), true ) ) {
			$mode = 'replace';
		}

		$current_term_ids = $this->object_term_ids( $post_id, $taxonomy );
		if ( is_wp_error( $current_term_ids ) ) {
			return $current_term_ids;
		}
		$resolved = $this->resolve_term_ids_for_assignment(
			$taxonomy,
			is_array( $input['term_ids'] ?? null ) ? $input['term_ids'] : array(),
			is_array( $input['terms'] ?? null ) ? $input['terms'] : array(),
			! empty( $input['create_missing'] ),
			$this->should_dry_run( $input )
		);
		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}
		$resolved_term_ids = is_array( $resolved['term_ids'] ?? null ) ? $resolved['term_ids'] : array();
		$missing_terms     = is_array( $resolved['missing'] ?? null ) ? $resolved['missing'] : array();

		$next_term_ids = $this->compute_term_update( $current_term_ids, $resolved_term_ids, $mode );
		$added_term_ids = array_values( array_diff( $next_term_ids, $current_term_ids ) );
		$removed_term_ids = array_values( array_diff( $current_term_ids, $next_term_ids ) );
		$before = array(
			'count'    => count( $current_term_ids ),
			'term_ids' => $current_term_ids,
			'terms'    => $this->collect_term_rows( $taxonomy, $current_term_ids ),
		);
		$after = array(
			'count'    => count( $next_term_ids ),
			'term_ids' => $next_term_ids,
			'terms'    => $this->collect_term_rows( $taxonomy, $next_term_ids ),
		);

		$payload = array(
			'post_id'          => $post_id,
			'taxonomy'         => $taxonomy,
			'mode'             => $mode,
			'updated'          => false,
			'before'           => $before,
			'after'            => $after,
			'added_term_ids'   => $added_term_ids,
			'removed_term_ids' => $removed_term_ids,
			'preview'          => array(
				'action'        => 'set_post_terms',
				'post_status'   => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'taxonomy'      => $taxonomy,
				'mode'          => $mode,
				'missing_terms' => $missing_terms,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/set-post-terms', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		if ( ! empty( $missing_terms ) ) {
			$resolved = $this->resolve_term_ids_for_assignment(
				$taxonomy,
				is_array( $input['term_ids'] ?? null ) ? $input['term_ids'] : array(),
				is_array( $input['terms'] ?? null ) ? $input['terms'] : array(),
				! empty( $input['create_missing'] ),
				false
			);
			if ( is_wp_error( $resolved ) ) {
				return $resolved;
			}
			$resolved_term_ids = is_array( $resolved['term_ids'] ?? null ) ? $resolved['term_ids'] : array();
			$next_term_ids     = $this->compute_term_update( $current_term_ids, $resolved_term_ids, $mode );
		}

		$set_result = wp_set_post_terms( $post_id, $next_term_ids, $taxonomy, false );
		if ( is_wp_error( $set_result ) ) {
			return $set_result;
		}
		$applied_term_ids = $this->object_term_ids( $post_id, $taxonomy );
		if ( is_wp_error( $applied_term_ids ) ) {
			return $applied_term_ids;
		}

		$payload['after'] = array(
			'count'    => count( $applied_term_ids ),
			'term_ids' => $applied_term_ids,
			'terms'    => $this->collect_term_rows( $taxonomy, $applied_term_ids ),
		);
		$payload['added_term_ids']   = array_values( array_diff( $applied_term_ids, $current_term_ids ) );
		$payload['removed_term_ids'] = array_values( array_diff( $current_term_ids, $applied_term_ids ) );
		$payload['updated']          = ! empty( $payload['added_term_ids'] ) || ! empty( $payload['removed_term_ids'] );
		$payload['dry_run']          = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Updates media attachment details.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function update_media_details( $input ) {
		$input         = is_array( $input ) ? $input : array();
		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment    = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to edit this attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$current = $this->media_snapshot( $attachment );
		$next    = $current;
		$changes = array();
		foreach ( array( 'title', 'alt', 'caption', 'description', 'source_type', 'source_page_url', 'photographer_name', 'attribution_text', 'copyright_notice' ) as $field ) {
			if ( ! array_key_exists( $field, $input ) ) {
				continue;
			}
			$value = 'source_page_url' === $field ? esc_url_raw( (string) $input[ $field ] ) : sanitize_textarea_field( (string) $input[ $field ] );
			if ( in_array( $field, array( 'title', 'alt', 'photographer_name' ), true ) ) {
				$value = sanitize_text_field( (string) $input[ $field ] );
			}
			if ( 'source_type' === $field ) {
				$value = $this->normalize_media_source_type( $input[ $field ] ?? '' );
			}
			$next[ $field ]    = $value;
			$changes[ $field ] = array( 'before' => (string) ( $current[ $field ] ?? '' ), 'after' => $value );
		}
		if ( empty( $changes ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_no_changes', __( 'No update fields were provided.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$payload = array_merge(
			$next,
			array(
				'attachment_id' => $attachment_id,
				'updated'       => false,
				'edit_link'     => $this->edit_link( $attachment_id ),
				'changes'       => $changes,
				'preview'       => array(
					'action'         => 'update_media_details',
					'attachment_id'  => $attachment_id,
					'changed_fields' => array_keys( $changes ),
				),
			)
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/update-media-details', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$update_args = array( 'ID' => $attachment_id );
		if ( array_key_exists( 'title', $changes ) ) {
			$update_args['post_title'] = $next['title'];
		}
		if ( array_key_exists( 'caption', $changes ) ) {
			$update_args['post_excerpt'] = $next['caption'];
		}
		if ( array_key_exists( 'description', $changes ) ) {
			$update_args['post_content'] = $next['description'];
		}
		if ( count( $update_args ) > 1 ) {
			$updated = wp_update_post( $update_args, true );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
		}
		if ( array_key_exists( 'alt', $changes ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $next['alt'] );
		}
		$meta_keys = array(
			'source_type'       => '_npcink_ai_media_source_type',
			'source_page_url'   => '_npcink_ai_media_source_page_url',
			'photographer_name' => '_npcink_ai_media_photographer_name',
			'attribution_text'  => '_npcink_ai_media_attribution_text',
			'copyright_notice'  => '_npcink_ai_media_copyright_notice',
		);
		foreach ( $meta_keys as $field => $meta_key ) {
			if ( ! array_key_exists( $field, $changes ) ) {
				continue;
			}
			if ( '' === (string) $next[ $field ] ) {
				delete_post_meta( $attachment_id, $meta_key );
			} else {
				update_post_meta( $attachment_id, $meta_key, $next[ $field ] );
			}
		}

		$payload['updated'] = true;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Uploads one remote media asset into the media library.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function upload_media_from_url( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to upload media.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$url = esc_url_raw( (string) ( $input['url'] ?? '' ) );
		if ( '' === $url ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_url_required', __( 'Media URL is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$attach_to_post_id = absint( $input['attach_to_post_id'] ?? 0 );
		if ( $attach_to_post_id > 0 && ! current_user_can( 'edit_post', $attach_to_post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to attach media to this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$title       = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$file_name   = $this->sanitize_media_file_name( (string) ( $input['file_name'] ?? '' ) );
		$alt         = sanitize_text_field( (string) ( $input['alt'] ?? '' ) );
		$caption     = sanitize_textarea_field( (string) ( $input['caption'] ?? '' ) );
		$description = sanitize_textarea_field( (string) ( $input['description'] ?? '' ) );
		$source_type = $this->normalize_media_source_type( $input['source_type'] ?? 'external', 'external' );
		$source_page_url = esc_url_raw( (string) ( $input['source_page_url'] ?? $url ) );
		$photographer_name = sanitize_text_field( (string) ( $input['photographer_name'] ?? '' ) );
		$attribution_text = sanitize_textarea_field( (string) ( $input['attribution_text'] ?? '' ) );
		$copyright_notice = sanitize_textarea_field( (string) ( $input['copyright_notice'] ?? '' ) );
		$source_metadata = $this->media_source_metadata_with_defaults(
			array(
				'source_type'       => $source_type,
				'source_page_url'   => $source_page_url,
				'photographer_name' => $photographer_name,
				'attribution_text'  => $attribution_text,
				'copyright_notice'  => $copyright_notice,
			),
			$url
		);
		$payload     = array(
			'attachment_id'     => 0,
			'url'               => $url,
			'file_name'         => $file_name,
			'sizes'             => array(),
			'attach_to_post_id' => $attach_to_post_id,
			'source_type'       => $source_metadata['source_type'],
			'source_page_url'   => $source_metadata['source_page_url'],
			'photographer_name' => $source_metadata['photographer_name'],
			'attribution_text'  => $source_metadata['attribution_text'],
			'copyright_notice'  => $source_metadata['copyright_notice'],
			'preview'           => array(
				'action'            => 'upload_media_from_url',
				'url'               => $url,
				'file_name'         => $file_name,
				'attach_to_post_id' => $attach_to_post_id,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/upload-media-from-url', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$attachment_id = $this->upload_media_asset_from_url( $url, $attach_to_post_id, $title, $file_name );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}
		$attachment_id = absint( $attachment_id );

		$update_args = array( 'ID' => $attachment_id );
		if ( '' !== $title ) {
			$update_args['post_title'] = $title;
		}
		if ( '' !== $caption || '' !== $description ) {
			$update_args['post_excerpt'] = $caption;
			$update_args['post_content'] = $description;
		}
		if ( count( $update_args ) > 1 ) {
			$updated = wp_update_post( $update_args, true );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
		}
		if ( '' !== $alt ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		}
		$this->update_media_source_metadata( $attachment_id, $source_metadata );

		return array(
			'attachment_id'     => $attachment_id,
			'url'               => (string) wp_get_attachment_url( $attachment_id ),
			'file_name'         => function_exists( 'get_attached_file' ) ? $this->sanitize_media_file_name( basename( (string) get_attached_file( $attachment_id ) ) ) : '',
			'sizes'             => $this->attachment_sizes( $attachment_id ),
			'attach_to_post_id' => $attach_to_post_id,
			'source_type'       => $source_metadata['source_type'],
			'source_page_url'   => $source_metadata['source_page_url'],
			'photographer_name' => $source_metadata['photographer_name'],
			'attribution_text'  => $source_metadata['attribution_text'],
			'copyright_notice'  => $source_metadata['copyright_notice'],
			'edit_link'         => $this->edit_link( $attachment_id ),
			'dry_run'           => false,
		);
	}

	/**
	 * Generates one optimized derivative while preserving the original asset.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function optimize_media_asset( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to optimize media.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to optimize this media asset.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$plan = $this->build_media_optimization_plan( $attachment_id, $input );
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}
		$payload = array(
			'attachment_id'      => $attachment_id,
			'optimized'          => false,
			'original_preserved' => true,
			'replace_original'   => false,
			'source'             => $plan['source'],
			'derivative'         => $plan['derivative'],
			'derivatives'        => $this->get_media_optimized_derivatives( $attachment_id ),
			'edit_link'          => $this->edit_link( $attachment_id ),
			'preview'            => array(
				'action'            => 'optimize_media_asset',
				'attachment_id'     => $attachment_id,
				'preferred_format'  => $plan['derivative']['format'],
				'target_max_width'  => $plan['derivative']['width'],
				'preserve_original' => true,
				'replace_original'  => false,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/optimize-media-asset', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = $this->generate_media_optimized_derivative( $attachment_id, $plan );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$derivatives = $this->append_media_optimized_derivative( $attachment_id, $result );

		$payload['optimized'] = true;
		$payload['derivative'] = $result;
		$payload['derivative_url'] = esc_url_raw( (string) ( $result['url'] ?? '' ) );
		$payload['derivative_relative_file'] = sanitize_text_field( (string) ( $result['relative_file'] ?? '' ) );
		$payload['derivatives'] = $derivatives;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Replaces one attachment main file through a recorded local derivative.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function replace_media_file( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to replace media files.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to replace this media file.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$plan = $this->build_media_file_replacement_plan( $attachment_id, $input );
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$payload = array(
			'attachment_id'      => $attachment_id,
			'mode'               => 'replace',
			'replaced'           => false,
			'rolled_back'        => false,
			'original_preserved' => true,
			'replacement_id'     => (string) ( $plan['replacement_id'] ?? '' ),
			'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
			'after'              => is_array( $plan['after'] ?? null ) ? $plan['after'] : array(),
			'backup'             => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
			'content_reference_repairs' => $this->build_media_content_reference_repairs( $attachment_id, $plan, false ),
			'history'            => $this->get_media_file_replacement_history( $attachment_id ),
			'edit_link'          => $this->edit_link( $attachment_id ),
			'preview'            => array(
				'action'          => 'replace_media_file',
				'attachment_id'   => $attachment_id,
				'replacement_id'  => (string) ( $plan['replacement_id'] ?? '' ),
				'backup_created'  => true,
				'rollback_ready'  => false,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/replace-media-file', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = $this->execute_media_file_replacement( $attachment_id, $plan );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$payload['replaced'] = ! empty( $result['replaced'] );
		$payload['rolled_back'] = ! empty( $result['rolled_back'] );
		$payload['after'] = is_array( $result['after'] ?? null ) ? $result['after'] : $payload['after'];
		$payload['backup'] = is_array( $result['backup'] ?? null ) ? $result['backup'] : $payload['backup'];
		$payload['content_reference_repairs'] = is_array( $result['content_reference_repairs'] ?? null ) ? $result['content_reference_repairs'] : $payload['content_reference_repairs'];
		$payload['verification'] = $this->media_file_operation_verification( $attachment_id, $payload['after'], $payload['backup'], $payload['content_reference_repairs'] );
		$payload['history'] = $this->get_media_file_replacement_history( $attachment_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Restores one attachment by copying a recorded backup to its original path.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function restore_media_backup( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to restore media backups.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to restore this media file.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$backup_id = sanitize_text_field( (string) ( $input['backup_id'] ?? '' ) );
		if ( '' === $backup_id ) {
			return new \WP_Error( 'npcink_abilities_toolkit_backup_id_required', __( 'A backup_id is required for media backup restore.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$plan = $this->build_media_backup_restore_plan( $attachment_id, $input );
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$payload = array(
			'attachment_id'      => $attachment_id,
			'backup_id'          => $backup_id,
			'replacement_id'     => (string) ( $plan['replacement_id'] ?? '' ),
			'restored'           => false,
			'rolled_back'        => false,
			'original_preserved' => true,
			'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
			'after'              => is_array( $plan['after'] ?? null ) ? $plan['after'] : array(),
			'backup'             => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
			'current_backup'     => is_array( $plan['current_backup'] ?? null ) ? $plan['current_backup'] : array(),
			'content_reference_repairs' => $this->build_media_content_reference_repairs( $attachment_id, $plan, false ),
			'history'            => $this->get_media_file_replacement_history( $attachment_id ),
			'edit_link'          => $this->edit_link( $attachment_id ),
			'preview'            => array(
				'action'         => 'restore_media_backup',
				'attachment_id'  => $attachment_id,
				'backup_id'      => $backup_id,
				'restore_ready'  => true,
				'target_file'    => (string) ( $plan['_target_relative_file'] ?? '' ),
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/restore-media-backup', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = $this->execute_media_backup_restore( $attachment_id, $plan );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$payload['restored'] = ! empty( $result['rolled_back'] );
		$payload['rolled_back'] = ! empty( $result['rolled_back'] );
		$payload['after'] = is_array( $result['after'] ?? null ) ? $result['after'] : $payload['after'];
		$payload['backup'] = is_array( $result['backup'] ?? null ) ? $result['backup'] : $payload['backup'];
		$payload['current_backup'] = is_array( $result['current_backup'] ?? null ) ? $result['current_backup'] : $payload['current_backup'];
		$payload['content_reference_repairs'] = is_array( $result['content_reference_repairs'] ?? null ) ? $result['content_reference_repairs'] : $payload['content_reference_repairs'];
		$payload['verification'] = $this->media_file_operation_verification( $attachment_id, $payload['after'], $payload['current_backup'], $payload['content_reference_repairs'] );
		$payload['history'] = $this->get_media_file_replacement_history( $attachment_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

		/**
		 * Renames one attachment main file within its current uploads directory.
		 *
		 * @param mixed $input Input args.
		 * @return array<string,mixed>|\WP_Error
		 */
		public function rename_media_file( $input ) {
			$input = is_array( $input ) ? $input : array();
			if ( ! current_user_can( 'upload_files' ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to rename media files.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
			}

			$attachment_id = absint( $input['attachment_id'] ?? 0 );
			$attachment = $this->get_media_attachment( $attachment_id );
			if ( is_wp_error( $attachment ) ) {
				return $attachment;
			}
			if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to rename this media file.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
			}

			$plan = $this->build_media_file_rename_plan( $attachment_id, $input );
			if ( is_wp_error( $plan ) ) {
				return $plan;
			}

			$payload = array(
				'attachment_id'      => $attachment_id,
				'renamed'            => false,
				'original_preserved' => true,
				'rename_id'          => (string) ( $plan['rename_id'] ?? '' ),
				'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
				'after'              => is_array( $plan['after'] ?? null ) ? $plan['after'] : array(),
				'backup'             => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
				'history'            => $this->get_media_file_replacement_history( $attachment_id ),
				'edit_link'          => $this->edit_link( $attachment_id ),
				'preview'            => array(
					'action'         => 'rename_media_file',
					'attachment_id'  => $attachment_id,
					'rename_id'      => (string) ( $plan['rename_id'] ?? '' ),
					'backup_created' => true,
					'conflict_mode'  => sanitize_key( (string) ( $plan['conflict_mode'] ?? 'fail' ) ),
				),
			);
			if ( $this->should_dry_run( $input ) ) {
				return $this->dry_run_payload( $payload );
			}
			$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/rename-media-file', $input );
			if ( is_wp_error( $allowed ) ) {
				return $allowed;
			}

			$result = $this->execute_media_file_rename( $attachment_id, $plan );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$payload['renamed'] = ! empty( $result['renamed'] );
			$payload['after'] = is_array( $result['after'] ?? null ) ? $result['after'] : $payload['after'];
			$payload['backup'] = is_array( $result['backup'] ?? null ) ? $result['backup'] : $payload['backup'];
			$payload['history'] = $this->get_media_file_replacement_history( $attachment_id );
			$payload['dry_run'] = false;
			unset( $payload['preview'] );
			return $payload;
		}

		/**
		 * Adopts one short-lived Cloud derivative artifact as a local media replacement.
		 *
		 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function adopt_cloud_media_derivative( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to adopt media derivatives.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to replace this media file.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$plan = $this->build_cloud_media_derivative_adoption_plan( $attachment_id, $input );
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$payload = array(
			'attachment_id'      => $attachment_id,
			'replaced'           => false,
			'original_preserved' => true,
			'replacement_id'     => (string) ( $plan['replacement_id'] ?? '' ),
			'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
			'after'              => is_array( $plan['after'] ?? null ) ? $plan['after'] : array(),
			'backup'             => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
			'artifact'           => is_array( $plan['artifact'] ?? null ) ? $plan['artifact'] : array(),
			'proposed_filename'  => $this->sanitize_media_file_name( (string) ( $plan['proposed_filename'] ?? '' ) ),
			'filename_policy'    => is_array( $plan['filename_policy'] ?? null ) ? $this->sanitize_payload( $plan['filename_policy'] ) : array(),
			'content_reference_repairs' => $this->build_media_content_reference_repairs( $attachment_id, $plan, false ),
			'history'            => $this->get_media_file_replacement_history( $attachment_id ),
			'edit_link'          => $this->edit_link( $attachment_id ),
			'preview'            => array(
				'action'          => 'adopt_cloud_media_derivative',
				'attachment_id'   => $attachment_id,
				'replacement_id'  => (string) ( $plan['replacement_id'] ?? '' ),
				'artifact_id'     => (string) ( $plan['artifact']['artifact_id'] ?? '' ),
				'backup_created'  => true,
				'cloud_artifact'  => true,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/adopt-cloud-media-derivative', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$expectation_error = $this->validate_media_content_reference_repair_expectations(
			is_array( $payload['content_reference_repairs'] ?? null ) ? $payload['content_reference_repairs'] : array(),
			is_array( $plan['content_reference_repair_expectations'] ?? null ) ? $plan['content_reference_repair_expectations'] : array()
		);
		if ( is_wp_error( $expectation_error ) ) {
			return $expectation_error;
		}
		$materialized = $this->materialize_cloud_media_derivative_artifact( $attachment_id, $plan );
		if ( is_wp_error( $materialized ) ) {
			return $materialized;
		}
		$plan['_derivative'] = $materialized;
		$plan['after'] = $this->media_file_state_from_derivative( $attachment_id, $materialized );
		$this->append_media_optimized_derivative( $attachment_id, $materialized );

		$result = $this->execute_media_file_replacement( $attachment_id, $plan );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$payload['replaced'] = ! empty( $result['replaced'] );
		$payload['after'] = is_array( $result['after'] ?? null ) ? $result['after'] : $payload['after'];
		$payload['backup'] = is_array( $result['backup'] ?? null ) ? $result['backup'] : $payload['backup'];
		$payload['content_reference_repairs'] = is_array( $result['content_reference_repairs'] ?? null ) ? $result['content_reference_repairs'] : $payload['content_reference_repairs'];
		$payload['verification'] = $this->media_file_operation_verification( $attachment_id, $payload['after'], $payload['backup'], $payload['content_reference_repairs'] );
		$payload['history'] = $this->get_media_file_replacement_history( $attachment_id );
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Sets one post featured image.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function set_post_featured_image( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$media_url     = esc_url_raw( (string) ( $input['media_url'] ?? '' ) );
		$media_title   = sanitize_text_field( (string) ( $input['media_title'] ?? '' ) );
		if ( $attachment_id <= 0 && '' === $media_url ) {
			return new \WP_Error( 'npcink_abilities_toolkit_featured_image_required', __( 'Either attachment_id or media_url is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$source                = $attachment_id > 0 ? 'attachment_id' : 'media_url';
		$preview_attachment_id = $attachment_id > 0 ? $attachment_id : -1;
		$payload               = array(
			'post_id'            => $post_id,
			'attachment_id'      => $preview_attachment_id,
			'featured_image_url' => '',
			'updated'            => false,
			'source'             => $source,
			'edit_link'          => $this->edit_link( $post_id ),
			'preview'            => array(
				'action'      => 'set_post_featured_image',
				'post_status' => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'source'      => $source,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/set-post-featured-image', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		if ( $attachment_id <= 0 ) {
			if ( ! current_user_can( 'upload_files' ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to upload media.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
			}
			$uploaded_id = $this->upload_media_asset_from_url( $media_url, $post_id, $media_title );
			if ( is_wp_error( $uploaded_id ) ) {
				return $uploaded_id;
			}
			$attachment_id = absint( $uploaded_id );
			$source        = 'media_url';
		}

		$attachment = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to use this attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		if ( ! function_exists( 'set_post_thumbnail' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_runtime_unavailable', __( 'Featured image runtime is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		set_post_thumbnail( $post_id, $attachment_id );
		$applied_attachment_id = absint( get_post_thumbnail_id( $post_id ) );

		return array(
			'post_id'            => $post_id,
			'attachment_id'      => $attachment_id,
			'featured_image_url' => (string) wp_get_attachment_url( $attachment_id ),
			'updated'            => $applied_attachment_id === $attachment_id,
			'source'             => $source,
			'edit_link'          => $this->edit_link( $post_id ),
			'dry_run'            => false,
		);
	}

	/**
	 * Schedules one post for future publication.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function schedule_post( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_post_for_publish( $post_id, __( 'You do not have permission to schedule this post.', 'npcink-abilities-toolkit' ) );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$publish_at_raw = sanitize_text_field( (string) ( $input['publish_at'] ?? '' ) );
		if ( '' === $publish_at_raw ) {
			return new \WP_Error( 'npcink_abilities_toolkit_publish_at_required', __( 'publish_at is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$timestamp = strtotime( $publish_at_raw );
		if ( false === $timestamp ) {
			return new \WP_Error( 'npcink_abilities_toolkit_publish_at_invalid', __( 'publish_at is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( $timestamp <= time() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_publish_at_past', __( 'publish_at must be in the future.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$timezone_mode = sanitize_key( (string) ( $input['timezone'] ?? 'site' ) );
		if ( ! in_array( $timezone_mode, array( 'site', 'utc' ), true ) ) {
			$timezone_mode = 'site';
		}
		if ( 'utc' === $timezone_mode ) {
			$publish_at_gmt = gmdate( 'Y-m-d H:i:s', $timestamp );
			$publish_at     = function_exists( 'get_date_from_gmt' ) ? get_date_from_gmt( $publish_at_gmt ) : $publish_at_gmt;
		} else {
			$publish_at     = function_exists( 'wp_date' ) ? wp_date( 'Y-m-d H:i:s', $timestamp ) : gmdate( 'Y-m-d H:i:s', $timestamp );
			$publish_at_gmt = function_exists( 'get_gmt_from_date' ) ? get_gmt_from_date( $publish_at ) : gmdate( 'Y-m-d H:i:s', $timestamp );
		}

		$payload = array(
			'post_id'        => $post_id,
			'scheduled'      => false,
			'status'         => 'dry_run',
			'publish_at'     => $publish_at,
			'publish_at_gmt' => $publish_at_gmt,
			'edit_link'      => $this->edit_link( $post_id ),
			'preview'        => array(
				'action'      => 'schedule_post',
				'from_status' => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'to_status'   => 'future',
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/schedule-post', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$updated = wp_update_post(
			array(
				'ID'            => $post_id,
				'post_status'   => 'future',
				'post_date'     => $publish_at,
				'post_date_gmt' => $publish_at_gmt,
			),
			true
		);
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$payload['scheduled'] = true;
		$payload['status']    = $this->post_status( $post_id );
		$payload['dry_run']   = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Publishes one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function publish_post( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_post_for_publish( $post_id, __( 'You do not have permission to publish this post.', 'npcink-abilities-toolkit' ) );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$payload = array(
			'post_id'   => $post_id,
			'published' => false,
			'status'    => 'dry_run',
			'edit_link' => $this->edit_link( $post_id ),
			'preview'   => array(
				'action'      => 'publish_post',
				'from_status' => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'to_status'   => 'publish',
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/publish-post', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$updated = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$payload['published'] = true;
		$payload['status']    = $this->post_status( $post_id );
		$payload['permalink'] = function_exists( 'get_permalink' ) ? (string) get_permalink( $post_id ) : '';
		$payload['dry_run']   = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Restores one trashed post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function restore_post( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_editable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		if ( 'trash' !== sanitize_key( (string) ( $post->post_status ?? '' ) ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_not_trashed', __( 'This post is not in the trash.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$payload = array(
			'post_id'  => $post_id,
			'restored' => false,
			'status'   => 'dry_run',
			'edit_link' => $this->edit_link( $post_id ),
			'preview'  => array(
				'action'      => 'restore_post',
				'from_status' => 'trash',
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/restore-post', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = function_exists( 'wp_untrash_post' ) ? wp_untrash_post( $post_id ) : false;
		if ( ! $result ) {
			return new \WP_Error( 'npcink_abilities_toolkit_restore_failed', __( 'Post restore failed.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$payload['restored'] = true;
		$payload['status']   = $this->post_status( $post_id );
		$payload['dry_run']  = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Approves one comment.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function approve_comment( $input ) {
		$input      = is_array( $input ) ? $input : array();
		$comment    = $this->get_comment_for_write( absint( $input['comment_id'] ?? 0 ) );
		if ( is_wp_error( $comment ) ) {
			return $comment;
		}
		$comment_id = absint( $comment->comment_ID ?? 0 );

		$payload = array(
			'comment_id'     => $comment_id,
			'post_id'        => absint( $comment->comment_post_ID ?? 0 ),
			'updated'        => false,
			'comment_status' => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
			'preview'        => array(
				'action'      => 'approve_comment',
				'from_status' => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'to_status'   => 'approve',
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/approve-comment', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		wp_set_comment_status( $comment_id, 'approve', true );
		$updated = get_comment( $comment_id );

		$payload['post_id']        = absint( $updated->comment_post_ID ?? $comment->comment_post_ID ?? 0 );
		$payload['updated']        = true;
		$payload['comment_status'] = sanitize_key( (string) ( $updated->comment_approved ?? 'approve' ) );
		$payload['dry_run']        = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Replies to one comment.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function reply_comment( $input ) {
		$input      = is_array( $input ) ? $input : array();
		$comment    = $this->get_comment_for_write( absint( $input['comment_id'] ?? 0 ) );
		if ( is_wp_error( $comment ) ) {
			return $comment;
		}
		$parent_id = absint( $comment->comment_ID ?? 0 );
		$post_id   = absint( $comment->comment_post_ID ?? 0 );
		if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to reply to this comment.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$content_payload = $this->normalize_content_input( $input, 'content' );
		$content         = (string) ( $content_payload['content'] ?? '' );
		$stripped        = wp_strip_all_tags( $content );
		if ( '' === trim( (string) $stripped ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_comment_content_required', __( 'Reply content is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$payload = array(
			'comment_id'     => 0,
			'parent_id'      => $parent_id,
			'post_id'        => $post_id,
			'created'        => false,
			'comment_status' => 'preview',
			'content_format' => (string) ( $content_payload['content_format'] ?? 'html' ),
			'edit_link'      => $this->edit_link( $post_id ),
			'preview'        => array(
				'action'         => 'reply_comment',
				'parent_id'      => $parent_id,
				'post_id'        => $post_id,
				'content_format' => (string) ( $content_payload['content_format'] ?? 'html' ),
				'content_length' => strlen( $content ),
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'npcink-abilities-toolkit/reply-comment', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
		$new_comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_parent'       => $parent_id,
				'comment_content'      => $content,
				'user_id'              => absint( $current_user->ID ?? 0 ),
				'comment_author'       => sanitize_text_field( (string) ( $current_user->display_name ?? '' ) ),
				'comment_author_email' => function_exists( 'sanitize_email' ) ? sanitize_email( (string) ( $current_user->user_email ?? '' ) ) : sanitize_text_field( (string) ( $current_user->user_email ?? '' ) ),
				'comment_approved'     => 1,
			)
		);
		$new_comment_id = absint( $new_comment_id );
		if ( $new_comment_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_comment_reply_failed', __( 'Comment reply failed.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$created = get_comment( $new_comment_id );

		$payload['comment_id']     = $new_comment_id;
		$payload['created']        = true;
		$payload['comment_status'] = sanitize_key( (string) ( $created->comment_approved ?? 'approve' ) );
		$payload['dry_run']        = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Adds static agent usage guidance for priority write abilities.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return array<string,mixed>
	 */
	private function with_agent_usage_metadata( $ability_id, array $definition ) {
		$usage = array(
			'npcink-abilities-toolkit/create-draft' => array(
				'when_to_use'     => array( 'Prepare or commit a host-approved draft post or page request.' ),
				'not_for'         => array( 'Do not use this for publishing, scheduling, updating existing posts, or bypassing editorial approval.' ),
				'best_for'        => array( 'Creating a draft only after the caller has a reviewed title and content payload.' ),
				'stopping_points' => array( 'Default to dry_run; final commit requires host approval context and idempotency protection.' ),
			),
			'npcink-abilities-toolkit/set-post-seo-meta' => array(
				'when_to_use'     => array( 'Prepare or commit approved SEO title and description metadata changes for one post.' ),
				'not_for'         => array( 'Do not use this for content rewrites, taxonomy changes, schema generation, or model selection.' ),
				'best_for'        => array( 'Applying field-level SEO metadata after a read helper or product workflow has produced reviewed values.' ),
				'stopping_points' => array( 'Default to dry_run; final metadata writes require host approval context and idempotency protection.' ),
			),
			'npcink-abilities-toolkit/update-media-details' => array(
				'when_to_use'     => array( 'Prepare or commit approved attachment title, alt, caption, description, or attribution metadata changes.' ),
				'not_for'         => array( 'Do not use this to upload files, generate images, delete media, or infer copyright ownership.' ),
				'best_for'        => array( 'Applying reviewed media SEO or attribution improvements to one existing attachment.' ),
				'stopping_points' => array( 'Default to dry_run; final media metadata writes require host approval context and idempotency protection.' ),
			),
			'npcink-abilities-toolkit/optimize-media-asset' => array(
				'when_to_use'     => array( 'Prepare or commit an approved optimized derivative for one existing image attachment.' ),
				'not_for'         => array( 'Do not use this to replace the original attachment file, upload remote media, or delete media.' ),
				'best_for'        => array( 'Generating a smaller derivative after inspect-media-asset reports format, size, or width attention.' ),
				'stopping_points' => array( 'Default to dry_run; final derivative generation requires host approval context and preserves the original file.' ),
			),
				'npcink-abilities-toolkit/adopt-cloud-media-derivative' => array(
					'when_to_use'     => array( 'Prepare or commit approved adoption of one non-expired Cloud derivative artifact as an attachment main file.' ),
					'not_for'         => array( 'Do not use this to preview artifacts, create Cloud derivatives, accept arbitrary replacement URLs, or bypass Core approval.' ),
					'best_for'        => array( 'Replacing an attachment after an operator reviewed the Cloud derivative preview and Core approved local adoption.' ),
					'stopping_points' => array( 'Default to dry_run; final commit requires host approval context, artifact evidence, local backup, and rollback metadata.' ),
				),
				'npcink-abilities-toolkit/rename-media-file' => array(
					'when_to_use'     => array( 'Prepare or commit an approved main attachment file rename within the current uploads directory.' ),
					'not_for'         => array( 'Do not use this to change media content, convert formats, move files between directories, rename generated size files, or bypass Core approval.' ),
					'best_for'        => array( 'Applying a reviewed filename from a host/OpenClaw naming policy after inspecting current file hashes and path evidence.' ),
					'stopping_points' => array( 'Default to dry_run; final commit requires host approval context, optimistic current-file checks, backup, and rollback metadata.' ),
				),
				'npcink-abilities-toolkit/restore-media-backup' => array(
					'when_to_use'     => array( 'Prepare or commit restoring one attachment to its original file path and URL after list-media-backups identifies the backup id.' ),
					'not_for'         => array( 'Do not use this for site-level backup restore, arbitrary file copies, media deletion, or bypassing Core approval.' ),
					'best_for'        => array( 'Copying a recorded media-operation backup back to the original attachment path while preserving the current file as a new backup.' ),
					'stopping_points' => array( 'Default to dry_run; final restore requires host approval context and optimistic current-file checks when available.' ),
				),
				'npcink-abilities-toolkit/approve-comment' => array(
					'when_to_use'     => array( 'Prepare or commit approval of one moderated comment after review.' ),
					'not_for'         => array( 'Do not use this to generate replies, spam comments, trash comments, or moderate without human policy review.' ),
					'best_for'        => array( 'Executing a reviewed approve action that was prepared by comment compliance handoff context.' ),
					'stopping_points' => array( 'Default to dry_run; final comment status changes require host approval context and idempotency protection.' ),
				),
		);

		if ( isset( $usage[ $ability_id ] ) ) {
			$definition['agent_usage'] = $usage[ $ability_id ];
		}

		return $definition;
	}

	/**
	 * Builds shared write metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function write_meta() {
		return array(
			'show_in_rest' => true,
			'mcp'          => array(
				'public' => true,
				'server' => 'npcink-abilities-toolkit-write',
				'risk'   => 'write',
			),
		);
	}

	/**
	 * Builds an object schema.
	 *
	 * @param array<string,mixed> $properties Schema properties.
	 * @param array<int,string>   $required Required keys.
	 * @return array<string,mixed>
	 */
	private function schema( array $properties, array $required = array() ) {
		$schema = array(
			'type'                 => 'object',
			'properties'           => $properties,
			'additionalProperties' => false,
		);
		if ( ! empty( $required ) ) {
			$schema['required'] = $required;
		}
		return $schema;
	}

	/**
	 * Sanitizes a bounded mixed payload for public preview output.
	 *
	 * @param mixed $payload Raw payload.
	 * @return mixed
	 */
	private function sanitize_payload( $payload ) {
		if ( is_array( $payload ) ) {
			$clean = array();
			foreach ( $payload as $key => $value ) {
				$clean_key = is_string( $key ) ? sanitize_key( $key ) : (int) $key;
				$clean[ $clean_key ] = $this->sanitize_payload( $value );
			}
			return $clean;
		}
		if ( is_bool( $payload ) || is_int( $payload ) || is_float( $payload ) || null === $payload ) {
			return $payload;
		}
		return sanitize_text_field( (string) $payload );
	}

	/**
	 * Returns whether the request should only preview changes.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @return bool
	 */
	private function should_dry_run( array $input ) {
		if ( array_key_exists( 'dry_run', $input ) ) {
			return ! empty( $input['dry_run'] );
		}

		return empty( $input['commit'] );
	}

	/**
	 * Normalizes a Site Editor template slug.
	 *
	 * @param string $value Raw slug or template id.
	 * @return string
	 */
	private function normalize_template_slug( $value ) {
		$value = (string) $value;
		if ( false !== strpos( $value, '//' ) ) {
			$parts = explode( '//', $value );
			$value = (string) end( $parts );
		}
		return sanitize_key( $value );
	}

	/**
	 * Returns the active theme stylesheet.
	 *
	 * @return string
	 */
	private function active_theme_stylesheet() {
		if ( function_exists( 'wp_get_theme' ) ) {
			$theme = wp_get_theme();
			if ( is_object( $theme ) && method_exists( $theme, 'get_stylesheet' ) ) {
				return sanitize_key( (string) $theme->get_stylesheet() );
			}
		}
		return sanitize_key( (string) ( $GLOBALS['npcink_abilities_toolkit_unit_active_theme']['stylesheet'] ?? 'active-theme' ) );
	}

	/**
	 * Finds an existing wp_template override post for one theme and slug.
	 *
	 * @param string $theme Theme stylesheet.
	 * @param string $slug Template slug.
	 * @return object|null
	 */
	private function find_template_override_post( $theme, $slug ) {
		$theme = sanitize_key( (string) $theme );
		$slug  = $this->normalize_template_slug( (string) $slug );
		$posts = function_exists( 'get_posts' )
			? get_posts(
				array(
					'post_type'        => 'wp_template',
					'post_status'      => array( 'publish', 'draft', 'auto-draft' ),
					'posts_per_page'   => 50,
					'suppress_filters' => false,
				)
			)
			: array();
		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			if ( ! is_object( $post ) || 'wp_template' !== sanitize_key( (string) ( $post->post_type ?? '' ) ) ) {
				continue;
			}
			$post_slug = $this->normalize_template_slug( (string) ( $post->post_name ?? '' ) );
			$post_name = (string) ( $post->post_name ?? '' );
			if ( $slug !== $post_slug ) {
				continue;
			}
			if ( false !== strpos( $post_name, '//' ) ) {
				if ( 0 === strpos( $post_name, $theme . '//' ) ) {
					return $post;
				}
				continue;
			}
			if ( function_exists( 'taxonomy_exists' ) && taxonomy_exists( 'wp_theme' ) && function_exists( 'wp_get_post_terms' ) ) {
				$terms = wp_get_post_terms( absint( $post->ID ?? 0 ), 'wp_theme', array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $terms ) && ! in_array( $theme, array_map( 'sanitize_key', (array) $terms ), true ) ) {
					continue;
				}
			}
			if ( $slug === $post_slug ) {
				return $post;
			}
		}
		return null;
	}

	/**
	 * Wraps one dry-run payload.
	 *
	 * @param array<string,mixed> $payload Preview payload.
	 * @return array<string,mixed>
	 */
	private function dry_run_payload( array $payload ) {
		$payload['dry_run']       = true;
		$payload['host_governed'] = true;
		$payload['commit_required'] = true;
		return $payload;
	}

	/**
	 * Reads SEO metadata through host adapters, with a small WordPress meta fallback.
	 *
	 * @param int $post_id Post id.
	 * @return array<string,mixed>
	 */
	private function read_post_seo_meta( $post_id ) {
		$filtered = null;
		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'npcink_abilities_toolkit_read_post_seo_meta', null, $post_id );
		}
		if ( is_array( $filtered ) ) {
			return $filtered;
		}

		$get_meta = static function ( $key ) use ( $post_id ) {
			return function_exists( 'get_post_meta' ) ? (string) get_post_meta( $post_id, $key, true ) : '';
		};
		$providers = array(
			'yoast'     => array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc' ),
			'rank_math' => array( 'rank_math_title', 'rank_math_description' ),
			'aioseo'    => array( '_aioseo_title', '_aioseo_description' ),
		);
		foreach ( $providers as $provider => $keys ) {
			$title       = sanitize_text_field( $get_meta( $keys[0] ) );
			$description = sanitize_textarea_field( $get_meta( $keys[1] ) );
			if ( '' !== $title || '' !== $description ) {
				return array(
					'provider'    => $provider,
					'title'       => $title,
					'description' => $description,
				);
			}
		}

		return array(
			'provider'    => 'post_meta',
			'title'       => '',
			'description' => '',
		);
	}

	/**
	 * Writes SEO metadata through host adapters, falling back to Yoast-compatible meta keys.
	 *
	 * @param int                 $post_id Post id.
	 * @param array<string,mixed> $args SEO args.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function write_post_seo_meta( $post_id, array $args ) {
		$title       = sanitize_text_field( (string) ( $args['title'] ?? '' ) );
		$description = sanitize_textarea_field( (string) ( $args['description'] ?? '' ) );
		$filtered    = null;
		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters(
				'npcink_abilities_toolkit_write_post_seo_meta',
				null,
				$post_id,
				array(
					'title'       => $title,
					'description' => $description,
				)
			);
		}
		if ( is_wp_error( $filtered ) || is_array( $filtered ) ) {
			return $filtered;
		}

		if ( ! function_exists( 'update_post_meta' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_seo_adapter_missing', __( 'No SEO metadata writer is available.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		update_post_meta( $post_id, '_yoast_wpseo_title', $title );
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $description );

		return array(
			'provider'    => 'post_meta',
			'title'       => $title,
			'description' => $description,
		);
	}

	/**
	 * Returns whether a host runtime approved the final commit.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input args.
	 * @return true|\WP_Error
	 */
	private function assert_commit_allowed( $ability_id, array $input ) {
		$runtime_context = isset( $GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] ) && is_array( $GLOBALS['npcink_ai_runtime_wp_ability_context']['context'] )
			? $GLOBALS['npcink_ai_runtime_wp_ability_context']['context']
			: array();
		$allowed = ! empty( $runtime_context['approval_commit_authorized'] );
		if ( function_exists( 'apply_filters' ) ) {
			$allowed = (bool) apply_filters( 'npcink_abilities_toolkit_write_commit_allowed', $allowed, $ability_id, $input, $runtime_context );
		}
		if ( $allowed ) {
			return true;
		}

		return new \WP_Error(
			'npcink_abilities_toolkit_host_approval_required',
			__( 'This write ability requires approval from a host runtime before commit.', 'npcink-abilities-toolkit' ),
			array(
				'status'       => 403,
				'ability_id'   => $ability_id,
				'host_governed' => true,
			)
		);
	}

	/**
	 * Returns an editable post object.
	 *
	 * @param int $post_id Post id.
	 * @return object|\WP_Error
	 */
	private function get_editable_post( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_invalid', __( 'Post ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_not_found', __( 'Post was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to edit this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		return $post;
	}

	/**
	 * Returns a post that the current user may publish.
	 *
	 * @param int    $post_id Post id.
	 * @param string $permission_message Permission denial message.
	 * @return object|\WP_Error
	 */
	private function get_post_for_publish( $post_id, $permission_message ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_invalid', __( 'Post ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_not_found', __( 'Post was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'publish_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', $permission_message, array( 'status' => 403 ) );
		}
		return $post;
	}

	/**
	 * Returns an editable attachment object.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return object|\WP_Error
	 */
	private function get_media_attachment( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_attachment_invalid', __( 'Attachment ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== sanitize_key( (string) ( $attachment->post_type ?? '' ) ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_attachment_invalid', __( 'Attachment ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		return $attachment;
	}

	/**
	 * Downloads and sideloads one media URL.
	 *
	 * @param string $url Media URL.
	 * @param int    $attach_to_post_id Optional parent post id.
	 * @param string $title Optional attachment title.
	 * @return int|\WP_Error
	 */
	private function upload_media_asset_from_url( $url, $attach_to_post_id = 0, $title = '', $file_name = '' ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_url_required', __( 'Media URL is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( function_exists( 'wp_http_validate_url' ) && ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_url_blocked', __( 'Media URL is not allowed.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$download_timeout = function_exists( 'apply_filters' )
			? (int) apply_filters( 'npcink_abilities_toolkit_upload_media_from_url_timeout', 15, $url )
			: 15;
		$download_timeout = max( 3, min( 30, $download_timeout ) );
		$max_bytes        = function_exists( 'apply_filters' )
			? (int) apply_filters( 'npcink_abilities_toolkit_upload_media_from_url_max_bytes', 20 * MB_IN_BYTES, $url )
			: 20 * MB_IN_BYTES;
		$max_bytes        = max( MB_IN_BYTES, min( 256 * MB_IN_BYTES, $max_bytes ) );
		$head_content_type = '';

		if ( ! function_exists( 'wp_safe_remote_get' ) || ! function_exists( 'wp_upload_bits' ) || ! function_exists( 'wp_insert_attachment' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_runtime_unavailable', __( 'Media runtime is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		if ( function_exists( 'wp_safe_remote_head' ) ) {
			$head_response = wp_safe_remote_head(
				$url,
				array(
					'timeout'             => $download_timeout,
					'redirection'         => 3,
					'reject_unsafe_urls'  => true,
					'limit_response_size' => 1024,
				)
			);
			if ( ! is_wp_error( $head_response ) ) {
				$head_status = (int) wp_remote_retrieve_response_code( $head_response );
				if ( $head_status >= 400 ) {
					return new \WP_Error( 'npcink_abilities_toolkit_media_download_failed', __( 'Remote media is not reachable.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
				}
				$content_length = (int) wp_remote_retrieve_header( $head_response, 'content-length' );
				if ( $content_length > 0 && $content_length > $max_bytes ) {
					return new \WP_Error( 'npcink_abilities_toolkit_media_too_large', __( 'Remote media exceeds the allowed size.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
				}
				$head_content_type = sanitize_text_field( (string) wp_remote_retrieve_header( $head_response, 'content-type' ) );
			}
		}

		$get_response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => $download_timeout,
				'redirection'         => 3,
				'reject_unsafe_urls'  => true,
				'limit_response_size' => $max_bytes + 1,
			)
		);
		if ( is_wp_error( $get_response ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_download_failed', $get_response->get_error_message(), array( 'status' => 400 ) );
		}
		$get_status = (int) wp_remote_retrieve_response_code( $get_response );
		if ( $get_status >= 400 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_download_failed', __( 'Remote media is not reachable.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$file_contents = (string) wp_remote_retrieve_body( $get_response );
		$file_size     = strlen( $file_contents );
		if ( $file_size <= 0 || $file_size > $max_bytes ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_too_large', __( 'Remote media exceeds the allowed size.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$path     = wp_parse_url( $url, PHP_URL_PATH );
		$path     = is_string( $path ) ? $path : '';
		$filename = $this->sanitize_media_file_name( (string) $file_name );
		if ( '' === $filename ) {
			$filename = sanitize_file_name( basename( $path ) );
		}
		if ( '' === $filename ) {
			$filename = 'remote-media-' . gmdate( 'YmdHis' );
		}
		if ( '' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$content_type = strtolower( trim( preg_replace( '/;.*/', '', $head_content_type ) ) );
			$extension_map = array(
				'image/jpeg' => 'jpg',
				'image/jpg'  => 'jpg',
				'image/png'  => 'png',
				'image/gif'  => 'gif',
				'image/webp' => 'webp',
			);
			if ( isset( $extension_map[ $content_type ] ) ) {
				$filename .= '.' . $extension_map[ $content_type ];
			}
		}

		$upload = wp_upload_bits( $filename, null, $file_contents );
		if ( ! empty( $upload['error'] ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_upload_failed', sanitize_text_field( (string) $upload['error'] ), array( 'status' => 400 ) );
		}
		$file_path = (string) ( $upload['file'] ?? '' );
		$file_url  = esc_url_raw( (string) ( $upload['url'] ?? '' ) );
		if ( '' === $file_path || ! is_readable( $file_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_upload_failed', __( 'Uploaded media file is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$allowed_mimes = function_exists( 'get_allowed_mime_types' ) ? get_allowed_mime_types() : array();
		$filetype      = function_exists( 'wp_check_filetype_and_ext' )
			? wp_check_filetype_and_ext( $file_path, $filename, $allowed_mimes )
			: wp_check_filetype( $filename, $allowed_mimes );
		$detected_type = sanitize_text_field( (string) ( $filetype['type'] ?? '' ) );
		$detected_ext  = sanitize_key( (string) ( $filetype['ext'] ?? '' ) );
		if ( '' === $detected_type || '' === $detected_ext ) {
			wp_delete_file( $file_path );
			return new \WP_Error( 'npcink_abilities_toolkit_media_type_blocked', __( 'Remote media type is not allowed.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$attachment_title = sanitize_text_field( (string) $title );
		if ( '' === $attachment_title ) {
			$attachment_title = preg_replace( '/\.[^.]+$/', '', $filename );
			$attachment_title = sanitize_text_field( (string) $attachment_title );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $detected_type,
				'post_title'     => $attachment_title,
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => $file_url,
			),
			$file_path,
			max( 0, absint( $attach_to_post_id ) ),
			true
		);
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $file_path );
			return $attachment_id;
		}

		$this->generate_attachment_metadata_for_file( absint( $attachment_id ), $file_path );

		return absint( $attachment_id );
	}

	/**
	 * Generates and persists attachment metadata for a media file.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $file_path Absolute media file path.
	 * @return array<string,mixed>
	 */
	private function generate_attachment_metadata_for_file( $attachment_id, $file_path ) {
		$attachment_id = absint( $attachment_id );
		$file_path = (string) $file_path;
		if ( $attachment_id <= 0 || '' === $file_path || ! is_readable( $file_path ) ) {
			return array();
		}

		$metadata = array();
		if ( function_exists( 'wp_generate_attachment_metadata' ) ) {
			$generated = wp_generate_attachment_metadata( $attachment_id, $file_path );
			if ( is_array( $generated ) && ! empty( $generated ) ) {
				$metadata = $generated;
			}
		}

		if ( empty( $metadata ) ) {
			$metadata = $this->minimal_attachment_metadata_for_file( $file_path );
		}
		if ( ! empty( $metadata ) ) {
			if ( function_exists( 'wp_update_attachment_metadata' ) ) {
				wp_update_attachment_metadata( $attachment_id, $metadata );
			} elseif ( function_exists( 'update_post_meta' ) ) {
				update_post_meta( $attachment_id, '_wp_attachment_metadata', $metadata );
			}
		}

		return $metadata;
	}

	/**
	 * Builds minimal WordPress attachment metadata when full image helpers are unavailable.
	 *
	 * @param string $file_path Absolute media file path.
	 * @return array<string,mixed>
	 */
	private function minimal_attachment_metadata_for_file( $file_path ) {
		$file_path = (string) $file_path;
		if ( '' === $file_path || ! is_readable( $file_path ) ) {
			return array();
		}

		$image_size = function_exists( 'wp_getimagesize' ) ? wp_getimagesize( $file_path ) : @getimagesize( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! is_array( $image_size ) || empty( $image_size[0] ) || empty( $image_size[1] ) ) {
			return array();
		}

		return array(
			'file'     => $this->uploads_relative_file_from_path( $file_path ),
			'width'    => absint( $image_size[0] ),
			'height'   => absint( $image_size[1] ),
			'filesize' => is_readable( $file_path ) ? absint( filesize( $file_path ) ) : 0,
			'sizes'    => array(),
		);
	}

	/**
	 * Converts an absolute uploads path to WordPress attachment metadata's relative file.
	 *
	 * @param string $file_path Absolute media file path.
	 * @return string
	 */
	private function uploads_relative_file_from_path( $file_path ) {
		$file_path = str_replace( '\\', '/', (string) $file_path );
		if ( '' === $file_path ) {
			return '';
		}
		if ( function_exists( 'wp_upload_dir' ) ) {
			$upload_dir = wp_upload_dir();
			$basedir = is_array( $upload_dir ) ? str_replace( '\\', '/', (string) ( $upload_dir['basedir'] ?? '' ) ) : '';
			if ( '' !== $basedir ) {
				$basedir = rtrim( $basedir, '/' ) . '/';
				if ( 0 === strpos( $file_path, $basedir ) ) {
					return $this->normalize_media_relative_file( substr( $file_path, strlen( $basedir ) ) );
				}
			}
		}

		return $this->sanitize_media_file_name( basename( $file_path ) );
	}

	/**
	 * Returns normalized generated attachment sizes.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return array<string,array<string,int|string>>
	 */
	private function attachment_sizes( $attachment_id ) {
		$meta       = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( absint( $attachment_id ) ) : array();
		$meta_sizes = is_array( $meta['sizes'] ?? null ) ? $meta['sizes'] : array();
		$sizes      = array();
		foreach ( $meta_sizes as $size_key => $size_data ) {
			$size_key = sanitize_key( (string) $size_key );
			if ( '' === $size_key || ! is_array( $size_data ) ) {
				continue;
			}
			$sizes[ $size_key ] = array(
				'file'   => sanitize_file_name( (string) ( $size_data['file'] ?? '' ) ),
				'width'  => absint( $size_data['width'] ?? 0 ),
				'height' => absint( $size_data['height'] ?? 0 ),
			);
		}
		return $sizes;
	}

	/**
	 * Builds an internal optimization plan plus safe public preview fields.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function build_media_optimization_plan( $attachment_id, array $input ) {
		$attachment_id = absint( $attachment_id );
		$metadata = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( $attachment_id ) : array();
		$metadata = is_array( $metadata ) ? $metadata : array();
		$source_file = function_exists( 'get_attached_file' ) ? (string) get_attached_file( $attachment_id ) : '';
		$source_url = function_exists( 'wp_get_attachment_url' ) ? esc_url_raw( (string) wp_get_attachment_url( $attachment_id ) ) : '';
		$mime_type = function_exists( 'get_post_mime_type' ) ? sanitize_text_field( (string) get_post_mime_type( $attachment_id ) ) : '';
		if ( '' !== $mime_type && 0 !== strpos( $mime_type, 'image/' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_not_image', __( 'Only image attachments can be optimized.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$width = absint( $metadata['width'] ?? 0 );
		$height = absint( $metadata['height'] ?? 0 );
		$target_max_width = max( 320, min( 7680, absint( $input['target_max_width'] ?? 1920 ) ) );
		$target_width = $width > 0 ? min( $width, $target_max_width ) : $target_max_width;
		$target_height = ( $width > 0 && $height > 0 && $target_width < $width )
			? max( 1, (int) round( ( $height / $width ) * $target_width ) )
			: $height;
		$format = sanitize_key( (string) ( $input['preferred_format'] ?? 'webp' ) );
		if ( ! in_array( $format, array( 'webp', 'jpeg', 'png' ), true ) ) {
			$format = 'webp';
		}
		$quality = max( 1, min( 100, absint( $input['quality'] ?? 82 ) ) );
		$suffix = sanitize_key( (string) ( $input['derivative_suffix'] ?? 'optimized' ) );
		if ( '' === $suffix ) {
			$suffix = 'optimized';
		}
		$suffix = substr( $suffix, 0, 48 );
		$extension = $this->media_extension_for_format( $format );
		$target_mime = $this->media_mime_for_format( $format );
		$source_basename = $this->media_source_basename( $metadata, $source_file, $source_url, $attachment_id );
		$base_name = preg_replace( '/\.[^.]+$/', '', $source_basename );
		$base_name = $this->sanitize_media_file_name( '' !== (string) $base_name ? (string) $base_name : 'attachment-' . $attachment_id );
		$derivative_basename = $this->sanitize_media_file_name( $base_name . '-' . $suffix . '.' . $extension );
		$relative_dir = $this->media_relative_dir( $metadata );
		$relative_file = '' !== $relative_dir ? $relative_dir . '/' . $derivative_basename : $derivative_basename;

		return array(
			'_source_file' => $source_file,
			'_relative_dir' => $relative_dir,
			'source'       => array(
				'attachment_id'  => $attachment_id,
				'mime_type'      => $mime_type,
				'url'            => $source_url,
				'file_basename'  => $source_basename,
				'width'          => $width,
				'height'         => $height,
				'filesize_bytes' => ( '' !== $source_file && is_readable( $source_file ) ) ? absint( filesize( $source_file ) ) : absint( $metadata['filesize'] ?? 0 ),
			),
			'derivative'   => array(
				'format'          => $format,
				'mime_type'       => $target_mime,
				'file_basename'   => $derivative_basename,
				'relative_file'   => $relative_file,
				'url'             => $this->media_url_for_relative_file( $relative_file ),
				'width'           => $target_width,
				'height'          => $target_height,
				'quality'         => $quality,
				'filesize_bytes'  => 0,
				'generated_at_gmt' => '',
			),
		);
	}

	/**
	 * Generates an optimized derivative from a prepared plan.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Optimization plan.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function generate_media_optimized_derivative( $attachment_id, array $plan ) {
		$attachment_id = absint( $attachment_id );
		$source_file = (string) ( $plan['_source_file'] ?? '' );
		if ( '' === $source_file || ! is_readable( $source_file ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_source_file_unavailable', __( 'The source attachment file is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_image_editor_unavailable', __( 'WordPress image editor is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$editor = wp_get_image_editor( $source_file );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}
		$derivative = is_array( $plan['derivative'] ?? null ) ? $plan['derivative'] : array();
		$source = is_array( $plan['source'] ?? null ) ? $plan['source'] : array();
		$target_width = absint( $derivative['width'] ?? 0 );
		$source_width = absint( $source['width'] ?? 0 );
		if ( $target_width > 0 && $source_width > 0 && $target_width < $source_width ) {
			$resized = $editor->resize( $target_width, null, false );
			if ( is_wp_error( $resized ) ) {
				return $resized;
			}
		}
		if ( method_exists( $editor, 'set_quality' ) ) {
			$editor->set_quality( absint( $derivative['quality'] ?? 82 ) );
		}

		$destination_dir = dirname( $source_file );
		if ( ! is_dir( $destination_dir ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_directory_unavailable', __( 'The attachment directory is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$basename = $this->sanitize_media_file_name( (string) ( $derivative['file_basename'] ?? 'attachment-' . $attachment_id . '-optimized.webp' ) );
		if ( function_exists( 'wp_unique_filename' ) ) {
			$basename = wp_unique_filename( $destination_dir, $basename );
		}
		$destination = trailingslashit( $destination_dir ) . $basename;
		$saved = $editor->save( $destination, (string) ( $derivative['mime_type'] ?? 'image/webp' ) );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		if ( ! is_array( $saved ) || empty( $saved['path'] ) || ! is_readable( (string) $saved['path'] ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_derivative_failed', __( 'Optimized derivative file was not created.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$relative_dir = sanitize_text_field( (string) ( $plan['_relative_dir'] ?? '' ) );
		$saved_basename = $this->sanitize_media_file_name( basename( (string) $saved['path'] ) );
		$relative_file = '' !== $relative_dir ? $relative_dir . '/' . $saved_basename : $saved_basename;
		return array(
			'format'           => sanitize_key( (string) ( $derivative['format'] ?? '' ) ),
			'mime_type'        => sanitize_text_field( (string) ( $saved['mime-type'] ?? $derivative['mime_type'] ?? '' ) ),
			'file_basename'    => $saved_basename,
			'relative_file'    => $relative_file,
			'url'              => $this->media_url_for_relative_file( $relative_file ),
			'width'            => absint( $saved['width'] ?? $derivative['width'] ?? 0 ),
			'height'           => absint( $saved['height'] ?? $derivative['height'] ?? 0 ),
			'quality'          => absint( $derivative['quality'] ?? 82 ),
			'filesize_bytes'   => absint( filesize( (string) $saved['path'] ) ),
			'generated_at_gmt' => gmdate( 'c' ),
		);
	}

	/**
	 * Returns recorded optimized derivatives.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_media_optimized_derivatives( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( isset( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_optimized_derivatives'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_optimized_derivatives'] ) ) {
			return array_values( array_filter( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_optimized_derivatives'], 'is_array' ) );
		}
		$derivatives = function_exists( 'get_post_meta' ) ? get_post_meta( absint( $attachment_id ), '_npcink_ai_media_optimized_derivatives', true ) : array();
		return is_array( $derivatives ) ? array_values( array_filter( $derivatives, 'is_array' ) ) : array();
	}

	/**
	 * Appends one optimized derivative record to attachment metadata.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $derivative Derivative record.
	 * @return array<int,array<string,mixed>>
	 */
	private function append_media_optimized_derivative( $attachment_id, array $derivative ) {
		$derivatives = $this->get_media_optimized_derivatives( $attachment_id );
		$derivatives[] = $derivative;
		$derivatives = array_slice( $derivatives, -20 );
		if ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( absint( $attachment_id ), '_npcink_ai_media_optimized_derivatives', $derivatives );
			update_post_meta( absint( $attachment_id ), '_npcink_ai_media_latest_optimized_derivative', $derivative );
		}

		return $derivatives;
	}

	/**
	 * Builds a local adoption plan for one Cloud derivative artifact.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function build_cloud_media_derivative_adoption_plan( $attachment_id, array $input ) {
		$attachment_id = absint( $attachment_id );
		$current = $this->current_media_file_state( $attachment_id );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$expected_error = $this->validate_media_expected_state( $current, $input );
		if ( is_wp_error( $expected_error ) ) {
			return $expected_error;
		}

		$artifact = $this->normalize_cloud_media_derivative_artifact( is_array( $input['derivative_artifact'] ?? null ) ? $input['derivative_artifact'] : array() );
		if ( is_wp_error( $artifact ) ) {
			return $artifact;
		}
		$expected_derivative_mime = sanitize_text_field( (string) ( $input['expected_derivative_mime_type'] ?? '' ) );
		if ( '' !== $expected_derivative_mime && $expected_derivative_mime !== (string) ( $artifact['mime_type'] ?? '' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_derivative_mime_mismatch', __( 'The derivative MIME type did not match the expected value.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$replacement_id = 'cloud_media_replace_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . (string) $artifact['artifact_id'] . '|' . microtime( true ) ), 0, 8 );
		$backup_suffix = sanitize_key( (string) ( $input['backup_suffix'] ?? 'npcink-abilities-toolkit-cloud-backup' ) );
		$backup_suffix = '' !== $backup_suffix ? substr( $backup_suffix, 0, 48 ) : 'npcink-abilities-toolkit-cloud-backup';
		$backup_relative = $this->backup_relative_file_for_current_media( $current, $replacement_id, $backup_suffix );
		$reviewed_file_name = $this->sanitize_media_file_name( (string) ( $input['file_name'] ?? '' ) );
		if ( '' === $reviewed_file_name ) {
			$reviewed_file_name = $this->sanitize_media_file_name( (string) ( $artifact['suggested_filename'] ?? '' ) );
		}
		$derivative = $this->cloud_artifact_derivative_state( $attachment_id, $current, $artifact, $reviewed_file_name );
		$after = $this->media_file_state_from_derivative( $attachment_id, $derivative );

		return array(
			'replacement_id' => $replacement_id,
			'before'         => $this->public_media_file_state( $current ),
			'after'          => $after,
			'backup'         => array(
				'relative_file' => $backup_relative,
				'url'           => $this->media_url_for_relative_file( $backup_relative ),
				'mime_type'     => (string) ( $current['mime_type'] ?? '' ),
				'width'         => absint( $current['width'] ?? 0 ),
				'height'        => absint( $current['height'] ?? 0 ),
			),
			'artifact'       => $artifact,
			'proposed_filename' => $reviewed_file_name,
			'filename_policy' => array(
				'owner'                          => 'wordpress_write_ability_final',
				'proposed_filename'              => $reviewed_file_name,
				'final_sanitize_unique_required' => true,
				'preserve_attachment_metadata'   => true,
				'source'                         => '' !== (string) ( $input['file_name'] ?? '' ) ? 'reviewed_input' : 'cloud_artifact_suggestion',
			),
			'content_reference_repair_expectations' => $this->normalize_media_content_reference_repair_expectations( $input ),
			'_current'       => $current,
			'_derivative'    => $derivative,
			'_backup_relative_file' => $backup_relative,
		);
	}

	/**
	 * Validates a bounded Cloud derivative artifact descriptor.
	 *
	 * @param array<string,mixed> $artifact Artifact descriptor.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function normalize_cloud_media_derivative_artifact( array $artifact ) {
		$artifact_id = sanitize_text_field( (string) ( $artifact['artifact_id'] ?? $artifact['id'] ?? '' ) );
		if ( '' === $artifact_id ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_id_required', __( 'A derivative artifact_id is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$mime_type = sanitize_text_field( (string) ( $artifact['mime_type'] ?? '' ) );
		if ( ! in_array( $mime_type, array( 'image/webp', 'image/avif', 'image/jpeg', 'image/png' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_mime_invalid', __( 'The derivative artifact MIME type is not supported for media replacement.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$expires_at = sanitize_text_field( (string) ( $artifact['expires_at'] ?? '' ) );
		$expires_ts = absint( $artifact['expires_ts'] ?? 0 );
		if ( $expires_ts <= 0 && '' !== $expires_at ) {
			$parsed = strtotime( $expires_at );
			$expires_ts = false !== $parsed ? absint( $parsed ) : 0;
		}
		if ( $expires_ts <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_expiry_required', __( 'The derivative artifact expiry is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( $expires_ts <= time() ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_expired', __( 'The derivative artifact has expired. Generate a new preview before adoption.', 'npcink-abilities-toolkit' ), array( 'status' => 410 ) );
		}
		$format = sanitize_key( (string) ( $artifact['format'] ?? '' ) );
		if ( '' === $format || 'original' === $format ) {
			$format = $this->media_format_for_mime( $mime_type );
		}
		if ( ! in_array( $format, array( 'webp', 'avif', 'jpeg', 'png' ), true ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_format_invalid', __( 'The derivative artifact format is not supported for media replacement.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		return array(
			'artifact_id'         => $artifact_id,
			'expires_at'          => '' !== $expires_at ? $expires_at : gmdate( 'c', $expires_ts ),
			'expires_ts'          => $expires_ts,
			'mime_type'           => $mime_type,
			'format'              => $format,
			'width'               => absint( $artifact['width'] ?? 0 ),
			'height'              => absint( $artifact['height'] ?? 0 ),
			'filesize_bytes'      => absint( $artifact['filesize_bytes'] ?? 0 ),
			'checksum'            => sanitize_text_field( (string) ( $artifact['checksum'] ?? $artifact['sha256'] ?? '' ) ),
			'sha256'              => $this->normalize_media_sha256( (string) ( $artifact['sha256'] ?? $artifact['checksum'] ?? '' ) ),
			'suggested_filename'  => $this->sanitize_media_file_name( (string) ( $artifact['suggested_filename'] ?? '' ) ),
			'filename_basis'      => is_array( $artifact['filename_basis'] ?? null ) ? $this->sanitize_payload( $artifact['filename_basis'] ) : array(),
			'processing_warnings' => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $artifact['processing_warnings'] ?? array() ) ) ) ),
		);
	}

	/**
	 * Builds the future local derivative file state from a Cloud artifact.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $current Current media state.
	 * @param array<string,mixed> $artifact Normalized artifact.
	 * @return array<string,mixed>
	 */
	private function cloud_artifact_derivative_state( $attachment_id, array $current, array $artifact, $file_name = '' ) {
		$current_relative = $this->normalize_media_relative_file( (string) ( $current['relative_file'] ?? '' ) );
		$dir = dirname( $current_relative );
		$dir = '.' !== $dir ? trim( $dir, '/' ) : '';
		$basename = $this->sanitize_media_file_name( basename( $current_relative ) );
		$custom_basename = $this->sanitize_media_file_name( (string) $file_name );
		$stem = '' !== $custom_basename ? preg_replace( '/\.[^.]+$/', '', $custom_basename ) : preg_replace( '/\.[^.]+$/', '', $basename );
		$stem = '' !== (string) $stem ? $stem : 'attachment-' . absint( $attachment_id );
		$artifact_key = substr( sanitize_key( (string) ( $artifact['artifact_id'] ?? '' ) ), 0, 16 );
		$extension = $this->media_extension_for_mime( (string) ( $artifact['mime_type'] ?? '' ) );
		$file_basename = '' !== $custom_basename
			? $this->sanitize_media_file_name( (string) $stem . '.' . $extension )
			: $this->sanitize_media_file_name( (string) $stem . '-npcink-abilities-toolkit-cloud-' . $artifact_key . '.' . $extension );
		$relative_file = '' !== $dir ? $dir . '/' . $file_basename : $file_basename;

		return array(
			'format'          => sanitize_key( (string) ( $artifact['format'] ?? '' ) ),
			'mime_type'       => sanitize_text_field( (string) ( $artifact['mime_type'] ?? '' ) ),
			'file_basename'   => $file_basename,
			'relative_file'   => $relative_file,
			'url'             => $this->media_url_for_relative_file( $relative_file ),
			'width'           => absint( $artifact['width'] ?? 0 ),
			'height'          => absint( $artifact['height'] ?? 0 ),
			'filesize_bytes'  => absint( $artifact['filesize_bytes'] ?? 0 ),
			'generated_at_gmt' => gmdate( 'c' ),
			'source'          => 'cloud_derivative_artifact',
			'artifact_id'     => sanitize_text_field( (string) ( $artifact['artifact_id'] ?? '' ) ),
			'artifact_checksum' => $this->normalize_media_sha256( (string) ( $artifact['sha256'] ?? $artifact['checksum'] ?? '' ) ),
		);
	}

	/**
	 * Downloads and stores a Cloud artifact as a bounded local derivative file.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Adoption plan.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function materialize_cloud_media_derivative_artifact( $attachment_id, array $plan ) {
		$artifact = is_array( $plan['artifact'] ?? null ) ? $plan['artifact'] : array();
		$download = apply_filters(
			'npcink_abilities_toolkit_cloud_media_derivative_artifact_download',
			null,
			$artifact,
			(string) ( $plan['replacement_id'] ?? '' ),
			absint( $attachment_id )
		);
		if ( null === $download ) {
			if ( ! function_exists( 'npcink_cloud_addon_download_media_derivative_artifact' ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_cloud_addon_unavailable', __( 'Cloud Addon artifact download is unavailable on this site.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			$download = npcink_cloud_addon_download_media_derivative_artifact( $artifact, (string) ( $plan['replacement_id'] ?? '' ) );
		}
		if ( is_wp_error( $download ) ) {
			return $download;
		}
		$contents = is_array( $download ) ? (string) ( $download['contents'] ?? '' ) : '';
		if ( '' === $contents ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_empty', __( 'The downloaded derivative artifact was empty.', 'npcink-abilities-toolkit' ), array( 'status' => 502 ) );
		}
		$sha256 = hash( 'sha256', $contents );
		$expected_sha256 = $this->normalize_media_sha256( (string) ( $artifact['sha256'] ?? $artifact['checksum'] ?? '' ) );
		if ( '' !== $expected_sha256 && $expected_sha256 !== $sha256 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_artifact_checksum_mismatch', __( 'The downloaded derivative checksum did not match the proposal evidence.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$derivative = is_array( $plan['_derivative'] ?? null ) ? $plan['_derivative'] : array();
		$relative_file = $this->normalize_media_relative_file( (string) ( $derivative['relative_file'] ?? '' ) );
		$destination = $this->media_uploads_path_for_relative_file( $relative_file );
		if ( '' === $destination ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_path_invalid', __( 'The local derivative path is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$destination_dir = dirname( $destination );
		if ( ! $this->ensure_media_directory( $destination_dir ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_directory_unavailable', __( 'The local derivative directory could not be created.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}
		$basename = $this->sanitize_media_file_name( basename( $destination ) );
		if ( function_exists( 'wp_unique_filename' ) ) {
			$basename = wp_unique_filename( $destination_dir, $basename );
			$destination = $this->trailingslashit_value( $destination_dir ) . $basename;
			$relative_dir = dirname( $relative_file );
			$relative_dir = '.' !== $relative_dir ? trim( $relative_dir, '/' ) : '';
			$relative_file = '' !== $relative_dir ? $relative_dir . '/' . $basename : $basename;
		} elseif ( file_exists( $destination ) ) {
			$basename = $this->unique_media_basename( $destination_dir, $basename );
			$destination = $this->trailingslashit_value( $destination_dir ) . $basename;
			$relative_dir = dirname( $relative_file );
			$relative_dir = '.' !== $relative_dir ? trim( $relative_dir, '/' ) : '';
			$relative_file = '' !== $relative_dir ? $relative_dir . '/' . $basename : $basename;
		}
		if (
			false === $this->write_media_file(
				$destination,
				$contents,
				array(
					'operation'     => 'adopt_cloud_media_derivative',
					'step'          => 'write_derivative',
					'attachment_id' => $attachment_id,
					'relative_file' => $relative_file,
				)
			)
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_cloud_derivative_write_failed', __( 'The local derivative file could not be written.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$derivative['file_basename'] = $basename;
		$derivative['relative_file'] = $relative_file;
		$derivative['url'] = $this->media_url_for_relative_file( $relative_file );
		$derivative['filesize_bytes'] = absint( filesize( $destination ) );
		$derivative['artifact_checksum'] = $sha256;
		$derivative['generated_at_gmt'] = gmdate( 'c' );

		return $derivative;
	}

	/**
	 * Builds a replacement plan from a recorded optimized derivative.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
		private function build_media_file_replacement_plan( $attachment_id, array $input ) {
			$attachment_id = absint( $attachment_id );
			$current = $this->current_media_file_state( $attachment_id );
			if ( is_wp_error( $current ) ) {
				return $current;
		}
		$expected_error = $this->validate_media_expected_state( $current, $input );
		if ( is_wp_error( $expected_error ) ) {
			return $expected_error;
		}

		$derivative_relative = $this->normalize_media_relative_file( (string) ( $input['derivative_relative_file'] ?? '' ) );
		if ( '' === $derivative_relative ) {
			return new \WP_Error( 'npcink_abilities_toolkit_derivative_required', __( 'A derivative_relative_file is required for media file replacement.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$derivative = $this->find_media_optimized_derivative( $attachment_id, $derivative_relative );
		if ( empty( $derivative ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_derivative_not_recorded', __( 'The requested derivative is not recorded for this attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$expected_derivative_mime = sanitize_text_field( (string) ( $input['expected_derivative_mime_type'] ?? '' ) );
		if ( '' !== $expected_derivative_mime && $expected_derivative_mime !== (string) ( $derivative['mime_type'] ?? '' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_derivative_mime_mismatch', __( 'The derivative MIME type did not match the expected value.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		$replacement_id = 'media_replace_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . $derivative_relative . '|' . microtime( true ) ), 0, 8 );
		$backup_suffix = sanitize_key( (string) ( $input['backup_suffix'] ?? 'npcink-abilities-toolkit-backup' ) );
		$backup_suffix = '' !== $backup_suffix ? substr( $backup_suffix, 0, 48 ) : 'npcink-abilities-toolkit-backup';
		$backup_relative = $this->backup_relative_file_for_current_media( $current, $replacement_id, $backup_suffix );
		$after = $this->media_file_state_from_derivative( $attachment_id, $derivative );

		return array(
			'replacement_id' => $replacement_id,
			'before'         => $this->public_media_file_state( $current ),
			'after'          => $after,
			'backup'         => array(
				'relative_file' => $backup_relative,
				'url'           => $this->media_url_for_relative_file( $backup_relative ),
				'mime_type'     => (string) ( $current['mime_type'] ?? '' ),
				'width'         => absint( $current['width'] ?? 0 ),
				'height'        => absint( $current['height'] ?? 0 ),
			),
			'_current'       => $current,
			'_derivative'    => $derivative,
				'_backup_relative_file' => $backup_relative,
			);
		}

		/**
		 * Builds a governed rename plan for the current attachment main file.
		 *
		 * @param int                 $attachment_id Attachment id.
		 * @param array<string,mixed> $input Input args.
		 * @return array<string,mixed>|\WP_Error
		 */
		private function build_media_file_rename_plan( $attachment_id, array $input ) {
			$attachment_id = absint( $attachment_id );
			$current = $this->current_media_file_state( $attachment_id );
			if ( is_wp_error( $current ) ) {
				return $current;
			}
			$expected_error = $this->validate_media_expected_state( $current, $input );
			if ( is_wp_error( $expected_error ) ) {
				return $expected_error;
			}
			$hash_error = $this->validate_media_expected_hashes( $current, $input );
			if ( is_wp_error( $hash_error ) ) {
				return $hash_error;
			}

			$current_relative = $this->normalize_media_relative_file( (string) ( $current['relative_file'] ?? '' ) );
			$current_path = (string) ( $current['file_path'] ?? '' );
			if ( '' === $current_relative || '' === $current_path ) {
				return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'Current attachment file metadata is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}

			$raw_target = trim( (string) ( $input['target_file_name'] ?? '' ) );
			if ( '' === $raw_target || basename( str_replace( '\\', '/', $raw_target ) ) !== $raw_target ) {
				return new \WP_Error( 'npcink_abilities_toolkit_target_file_name_invalid', __( 'Target file name must be a file basename, not a path.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			$target_basename = $this->sanitize_media_file_name( $raw_target );
			if ( '' === $target_basename ) {
				return new \WP_Error( 'npcink_abilities_toolkit_target_file_name_invalid', __( 'Target file name is invalid after sanitization.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}

			$current_basename = $this->sanitize_media_file_name( basename( $current_relative ) );
			$current_extension = strtolower( pathinfo( $current_basename, PATHINFO_EXTENSION ) );
			$target_extension = strtolower( pathinfo( $target_basename, PATHINFO_EXTENSION ) );
			if ( '' === $target_extension && '' !== $current_extension ) {
				$target_basename .= '.' . $current_extension;
				$target_extension = $current_extension;
			}
			if ( '' !== $current_extension && $target_extension !== $current_extension ) {
				return new \WP_Error( 'npcink_abilities_toolkit_target_extension_mismatch', __( 'Target file extension must match the current media file extension.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			if ( $target_basename === $current_basename ) {
				return new \WP_Error( 'npcink_abilities_toolkit_no_changes', __( 'Target file name matches the current file name.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}

			$dir = dirname( $current_relative );
			$dir = '.' !== $dir ? trim( $dir, '/' ) : '';
			$target_dir = dirname( $current_path );
			$conflict_mode = sanitize_key( (string) ( $input['conflict_mode'] ?? 'fail' ) );
			$conflict_mode = in_array( $conflict_mode, array( 'fail', 'unique' ), true ) ? $conflict_mode : 'fail';
			$target_path = $this->trailingslashit_value( $target_dir ) . $target_basename;
			if ( file_exists( $target_path ) ) {
				if ( 'unique' !== $conflict_mode ) {
					return new \WP_Error( 'npcink_abilities_toolkit_target_file_exists', __( 'Target media file already exists.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
				}
				$target_basename = $this->unique_media_basename( $target_dir, $target_basename );
			}

			$target_relative = '' !== $dir ? $dir . '/' . $target_basename : $target_basename;
			$target_path = $this->media_uploads_path_for_relative_file( $target_relative );
			$rename_id = 'media_rename_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . $current_relative . '|' . $target_relative . '|' . microtime( true ) ), 0, 8 );
			$backup_suffix = sanitize_key( (string) ( $input['backup_suffix'] ?? 'npcink-abilities-toolkit-rename-backup' ) );
			$backup_suffix = '' !== $backup_suffix ? substr( $backup_suffix, 0, 48 ) : 'npcink-abilities-toolkit-rename-backup';
			$backup_relative = $this->backup_relative_file_for_current_media( $current, $rename_id, $backup_suffix );
			$hashes = $this->media_content_hashes_for_state( $current );
			$before = $this->public_media_file_state( $current );
			$before['content_hashes'] = $hashes;
			$after = $before;
			$after['relative_file'] = $target_relative;
			$after['url'] = $this->media_url_for_relative_file( $target_relative );
			$after['file_basename'] = $target_basename;

			return array(
				'rename_id'      => $rename_id,
				'conflict_mode'  => $conflict_mode,
				'before'         => $before,
				'after'          => $after,
				'backup'         => array(
					'relative_file' => $backup_relative,
					'url'           => $this->media_url_for_relative_file( $backup_relative ),
					'mime_type'     => (string) ( $current['mime_type'] ?? '' ),
					'width'         => absint( $current['width'] ?? 0 ),
					'height'        => absint( $current['height'] ?? 0 ),
					'content_hashes' => $hashes,
				),
				'_current'       => $current,
				'_target_relative_file' => $target_relative,
				'_target_path'   => $target_path,
				'_backup_relative_file' => $backup_relative,
			);
		}

		/**
		 * Builds a restore plan that copies a recorded backup back to its original path.
		 *
		 * @param int                 $attachment_id Attachment id.
		 * @param array<string,mixed> $input Input args.
		 * @return array<string,mixed>|\WP_Error
		 */
		private function build_media_backup_restore_plan( $attachment_id, array $input ) {
			$attachment_id = absint( $attachment_id );
			$current = $this->current_media_file_state( $attachment_id );
			if ( is_wp_error( $current ) ) {
				return $current;
			}
			$expected_error = $this->validate_media_expected_state( $current, $input );
			if ( is_wp_error( $expected_error ) ) {
				return $expected_error;
			}

			$backup_id = sanitize_text_field( (string) ( $input['backup_id'] ?? '' ) );
			if ( '' === $backup_id ) {
				return new \WP_Error( 'npcink_abilities_toolkit_backup_id_required', __( 'A backup_id is required for media backup restore.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			$history = $this->find_media_file_replacement_history( $attachment_id, $backup_id );
			if ( empty( $history ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_replacement_not_found', __( 'Replacement history was not found for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
			}

			$backup = is_array( $history['backup'] ?? null ) ? $history['backup'] : array();
			$backup_relative = $this->normalize_media_relative_file( (string) ( $backup['relative_file'] ?? '' ) );
			$backup_path = $this->media_uploads_path_for_relative_file( $backup_relative );
			if ( '' === $backup_relative || '' === $backup_path || ! is_readable( $backup_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_backup_file_unavailable', __( 'The backup file is unavailable for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}

			$original = is_array( $history['before'] ?? null ) ? $history['before'] : array();
			$target_relative = $this->normalize_media_relative_file( (string) ( $original['relative_file'] ?? '' ) );
			$target_path = $this->media_uploads_path_for_relative_file( $target_relative );
			if ( '' === $target_relative || '' === $target_path ) {
				return new \WP_Error( 'npcink_abilities_toolkit_restore_target_unavailable', __( 'The original media file path is unavailable for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}

			$conflict_mode = sanitize_key( (string) ( $input['target_conflict_mode'] ?? 'fail' ) );
			$conflict_mode = in_array( $conflict_mode, array( 'fail', 'overwrite' ), true ) ? $conflict_mode : 'fail';
			if ( file_exists( $target_path ) && 'overwrite' !== $conflict_mode && md5_file( $target_path ) !== md5_file( $backup_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_restore_target_exists', __( 'The original media file path already exists with different content.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}

			$restore_id = 'media_restore_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( $attachment_id . '|' . $backup_id . '|' . $target_relative . '|' . microtime( true ) ), 0, 8 );
			$current_backup_relative = $this->backup_relative_file_for_current_media( $current, $restore_id, 'npcink-abilities-toolkit-restore-backup' );
			$after = array(
				'relative_file'  => $target_relative,
				'url'            => $this->media_url_for_relative_file( $target_relative ),
				'file_basename'  => $this->sanitize_media_file_name( basename( $target_relative ) ),
				'mime_type'      => sanitize_text_field( (string) ( $backup['mime_type'] ?? $original['mime_type'] ?? '' ) ),
				'width'          => absint( $backup['width'] ?? $original['width'] ?? 0 ),
				'height'         => absint( $backup['height'] ?? $original['height'] ?? 0 ),
				'filesize_bytes' => absint( filesize( $backup_path ) ),
			);

			return array(
				'replacement_id' => $backup_id,
				'restore_id'     => $restore_id,
				'conflict_mode'  => $conflict_mode,
				'before'         => $this->public_media_file_state( $current ),
				'after'          => $after,
				'backup'         => $backup,
				'current_backup' => array(
					'relative_file' => $current_backup_relative,
					'url'           => $this->media_url_for_relative_file( $current_backup_relative ),
					'mime_type'     => (string) ( $current['mime_type'] ?? '' ),
					'width'         => absint( $current['width'] ?? 0 ),
					'height'        => absint( $current['height'] ?? 0 ),
				),
				'_current'       => $current,
				'_history'       => $history,
				'_backup_relative_file' => $backup_relative,
				'_backup_path'   => $backup_path,
				'_target_relative_file' => $target_relative,
				'_target_path'   => $target_path,
				'_current_backup_relative_file' => $current_backup_relative,
			);
		}

		/**
		 * Executes a file replacement by switching the attachment pointer.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Replacement plan.
	 * @return array<string,mixed>|\WP_Error
	 */
		private function execute_media_file_replacement( $attachment_id, array $plan ) {
			$attachment_id = absint( $attachment_id );
			$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
			$derivative = is_array( $plan['_derivative'] ?? null ) ? $plan['_derivative'] : array();
			$current_path = (string) ( $current['file_path'] ?? '' );
			$content_reference_repairs = $this->build_media_content_reference_repairs( $attachment_id, $plan, false );
			$expectation_error = $this->validate_media_content_reference_repair_expectations(
				$content_reference_repairs,
				is_array( $plan['content_reference_repair_expectations'] ?? null ) ? $plan['content_reference_repair_expectations'] : array()
			);
			if ( is_wp_error( $expectation_error ) ) {
				return $expectation_error;
			}
			$permission_error = $this->validate_media_content_reference_repair_permissions( $content_reference_repairs );
			if ( is_wp_error( $permission_error ) ) {
				return $permission_error;
			}
		$backup_relative = $this->normalize_media_relative_file( (string) ( $plan['_backup_relative_file'] ?? '' ) );
		$backup_path = $this->media_uploads_path_for_relative_file( $backup_relative );
		$derivative_relative = $this->normalize_media_relative_file( (string) ( $derivative['relative_file'] ?? '' ) );
		$derivative_path = $this->media_uploads_path_for_relative_file( $derivative_relative );
		if ( '' === $current_path || ! is_readable( $current_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'The current attachment file is unavailable for backup.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		if ( '' === $derivative_path || ! is_readable( $derivative_path ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_derivative_file_unavailable', __( 'The derivative file is unavailable for replacement.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}
		$backup_dir_ready = '' !== $backup_path && $this->ensure_media_directory( dirname( $backup_path ) );
		if (
			'' === $backup_path ||
			! $backup_dir_ready ||
			! $this->copy_media_file(
				$current_path,
				$backup_path,
				array(
					'operation'     => 'replace_media_file',
					'step'          => 'backup_current',
					'attachment_id' => $attachment_id,
					'relative_file' => $backup_relative,
				)
			)
		) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
		}

		$after = is_array( $plan['after'] ?? null ) ? $plan['after'] : array();
		$after['filesize_bytes'] = absint( filesize( $derivative_path ) );
		$backup = is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array();
		$backup['filesize_bytes'] = absint( filesize( $backup_path ) );
		$updated = $this->update_media_file_pointer( $attachment_id, $derivative_relative, (string) ( $after['mime_type'] ?? '' ), $after );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		$content_reference_repairs = $this->apply_media_content_reference_repairs( $content_reference_repairs );
		if ( is_wp_error( $content_reference_repairs ) ) {
			return $content_reference_repairs;
		}
		$this->append_media_file_replacement_history(
			$attachment_id,
			array(
				'replacement_id'     => (string) ( $plan['replacement_id'] ?? '' ),
				'status'             => 'active',
				'replaced_at_gmt'    => gmdate( 'c' ),
				'rolled_back_at_gmt' => '',
				'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
				'after'              => $after,
				'backup'             => $backup,
			)
		);

		return array(
			'replaced' => true,
			'rolled_back' => false,
			'after'    => $after,
				'backup'   => $backup,
				'content_reference_repairs' => $content_reference_repairs,
			);
		}

		/**
		 * Executes an approved main media file rename.
		 *
		 * @param int                 $attachment_id Attachment id.
		 * @param array<string,mixed> $plan Rename plan.
		 * @return array<string,mixed>|\WP_Error
		 */
		private function execute_media_file_rename( $attachment_id, array $plan ) {
			$attachment_id = absint( $attachment_id );
			$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
			$current_path = (string) ( $current['file_path'] ?? '' );
			$target_relative = $this->normalize_media_relative_file( (string) ( $plan['_target_relative_file'] ?? '' ) );
			$target_path = (string) ( $plan['_target_path'] ?? '' );
			$backup_relative = $this->normalize_media_relative_file( (string) ( $plan['_backup_relative_file'] ?? '' ) );
			$backup_path = $this->media_uploads_path_for_relative_file( $backup_relative );
			if ( '' === $current_path || ! is_readable( $current_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'The current attachment file is unavailable for rename.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if ( '' === $target_relative || '' === $target_path ) {
				return new \WP_Error( 'npcink_abilities_toolkit_target_file_name_invalid', __( 'Target media file path is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			if ( file_exists( $target_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_target_file_exists', __( 'Target media file already exists.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if (
				'' === $backup_path ||
				! $this->ensure_media_directory( dirname( $backup_path ) ) ||
				! $this->copy_media_file(
					$current_path,
					$backup_path,
					array(
						'operation'     => 'rename_media_file',
						'step'          => 'backup_current',
						'attachment_id' => $attachment_id,
						'relative_file' => $backup_relative,
					)
				)
			) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			}
			if (
				! $this->ensure_media_directory( dirname( $target_path ) ) ||
				! $this->move_media_file(
					$current_path,
					$target_path,
					array(
						'operation'     => 'rename_media_file',
						'step'          => 'move_current',
						'attachment_id' => $attachment_id,
						'relative_file' => $target_relative,
					)
				)
			) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_rename_failed', __( 'The current attachment file could not be renamed.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			}

			$after = is_array( $plan['after'] ?? null ) ? $plan['after'] : array();
			$after['filesize_bytes'] = absint( filesize( $target_path ) );
			$backup = is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array();
			$backup['filesize_bytes'] = absint( filesize( $backup_path ) );
			$pointer_state = $after;
			$pointer_state['_metadata'] = $this->renamed_media_metadata( is_array( $current['metadata'] ?? null ) ? $current['metadata'] : array(), $target_relative, $after );
			$updated = $this->update_media_file_pointer( $attachment_id, $target_relative, (string) ( $after['mime_type'] ?? '' ), $pointer_state );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
			$this->append_media_file_replacement_history(
				$attachment_id,
				array(
					'replacement_id'     => (string) ( $plan['rename_id'] ?? '' ),
					'operation'          => 'rename_media_file',
					'status'             => 'active',
					'replaced_at_gmt'    => gmdate( 'c' ),
					'rolled_back_at_gmt' => '',
					'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
					'after'              => $after,
					'backup'             => $backup,
				)
			);

			return array(
				'renamed' => true,
				'after'   => $after,
				'backup'  => $backup,
			);
		}

		/**
		 * Executes an approved restore by copying a backup to its original path.
		 *
		 * @param int                 $attachment_id Attachment id.
		 * @param array<string,mixed> $plan Restore plan.
		 * @return array<string,mixed>|\WP_Error
		 */
		private function execute_media_backup_restore( $attachment_id, array $plan ) {
			$attachment_id = absint( $attachment_id );
			$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
			$current_path = (string) ( $current['file_path'] ?? '' );
			$backup_path = (string) ( $plan['_backup_path'] ?? '' );
			$target_relative = $this->normalize_media_relative_file( (string) ( $plan['_target_relative_file'] ?? '' ) );
			$target_path = (string) ( $plan['_target_path'] ?? '' );
			$current_backup_relative = $this->normalize_media_relative_file( (string) ( $plan['_current_backup_relative_file'] ?? '' ) );
			$current_backup_path = $this->media_uploads_path_for_relative_file( $current_backup_relative );
			$content_reference_repairs = $this->build_media_content_reference_repairs( $attachment_id, $plan, false );
			$permission_error = $this->validate_media_content_reference_repair_permissions( $content_reference_repairs );
			if ( is_wp_error( $permission_error ) ) {
				return $permission_error;
			}
			if ( '' === $current_path || ! is_readable( $current_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'The current attachment file is unavailable for restore backup.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if ( '' === $backup_path || ! is_readable( $backup_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_backup_file_unavailable', __( 'The backup file is unavailable for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if ( '' === $target_relative || '' === $target_path ) {
				return new \WP_Error( 'npcink_abilities_toolkit_restore_target_unavailable', __( 'The original media file path is unavailable for restore.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if ( file_exists( $target_path ) && 'overwrite' !== (string) ( $plan['conflict_mode'] ?? 'fail' ) && md5_file( $target_path ) !== md5_file( $backup_path ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_restore_target_exists', __( 'The original media file path already exists with different content.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if (
				'' === $current_backup_path ||
				! $this->ensure_media_directory( dirname( $current_backup_path ) ) ||
				! $this->copy_media_file(
					$current_path,
					$current_backup_path,
					array(
						'operation'     => 'restore_media_backup',
						'step'          => 'backup_current',
						'attachment_id' => $attachment_id,
						'relative_file' => $current_backup_relative,
					)
				)
			) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_backup_failed', __( 'The current attachment file could not be backed up before restore.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			}
			if (
				! $this->ensure_media_directory( dirname( $target_path ) ) ||
				! $this->copy_media_file(
					$backup_path,
					$target_path,
					array(
						'operation'     => 'restore_media_backup',
						'step'          => 'restore_backup',
						'attachment_id' => $attachment_id,
						'relative_file' => $target_relative,
					)
				)
			) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_restore_failed', __( 'The backup file could not be restored to the original path.', 'npcink-abilities-toolkit' ), array( 'status' => 500 ) );
			}

			$after = is_array( $plan['after'] ?? null ) ? $plan['after'] : array();
			$after['filesize_bytes'] = absint( filesize( $target_path ) );
			$current_backup = is_array( $plan['current_backup'] ?? null ) ? $plan['current_backup'] : array();
			$current_backup['filesize_bytes'] = absint( filesize( $current_backup_path ) );
			$updated = $this->update_media_file_pointer( $attachment_id, $target_relative, (string) ( $after['mime_type'] ?? '' ), $after );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
			$content_reference_repairs = $this->apply_media_content_reference_repairs( $content_reference_repairs );
			if ( is_wp_error( $content_reference_repairs ) ) {
				return $content_reference_repairs;
			}
			$this->mark_media_file_replacement_rolled_back( $attachment_id, (string) ( $plan['replacement_id'] ?? '' ) );
			$this->append_media_file_replacement_history(
				$attachment_id,
				array(
					'replacement_id'     => (string) ( $plan['restore_id'] ?? '' ),
					'operation'          => 'restore_media_backup',
					'status'             => 'active',
					'replaced_at_gmt'    => gmdate( 'c' ),
					'rolled_back_at_gmt' => '',
					'restored_from'      => (string) ( $plan['replacement_id'] ?? '' ),
					'before'             => is_array( $plan['before'] ?? null ) ? $plan['before'] : array(),
					'after'              => $after,
					'backup'             => $current_backup,
				)
			);

			return array(
				'restored'       => true,
				'rolled_back'    => true,
				'after'          => $after,
				'backup'         => is_array( $plan['backup'] ?? null ) ? $plan['backup'] : array(),
				'current_backup' => $current_backup,
				'content_reference_repairs' => $content_reference_repairs,
			);
		}

	/**
	 * Returns current attachment file state with internal path for commit checks.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function current_media_file_state( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		$metadata = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( $attachment_id ) : array();
		$metadata = is_array( $metadata ) ? $metadata : array();
		$relative_file = $this->normalize_media_relative_file( function_exists( 'get_post_meta' ) ? (string) get_post_meta( $attachment_id, '_wp_attached_file', true ) : '' );
		if ( '' === $relative_file ) {
			$relative_file = $this->normalize_media_relative_file( (string) ( $metadata['file'] ?? '' ) );
		}
		$file_path = function_exists( 'get_attached_file' ) ? (string) get_attached_file( $attachment_id ) : '';
		if ( '' !== $file_path && '' !== $relative_file && ! is_readable( $file_path ) ) {
			$file_path = $this->media_uploads_path_for_relative_file( $relative_file );
		}
		if ( '' === $file_path && '' !== $relative_file ) {
			$file_path = $this->media_uploads_path_for_relative_file( $relative_file );
		}
		$mime_type = function_exists( 'get_post_mime_type' ) ? sanitize_text_field( (string) get_post_mime_type( $attachment_id ) ) : '';
		if ( '' === $relative_file && '' === $file_path ) {
			return new \WP_Error( 'npcink_abilities_toolkit_current_media_file_unavailable', __( 'Current attachment file metadata is unavailable.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

		return array(
			'relative_file'   => $relative_file,
			'url'             => '' !== $relative_file ? $this->media_url_for_relative_file( $relative_file ) : ( function_exists( 'wp_get_attachment_url' ) ? esc_url_raw( (string) wp_get_attachment_url( $attachment_id ) ) : '' ),
			'file_basename'   => $this->sanitize_media_file_name( basename( '' !== $relative_file ? $relative_file : $file_path ) ),
			'file_path'       => $file_path,
			'mime_type'       => $mime_type,
			'width'           => absint( $metadata['width'] ?? 0 ),
			'height'          => absint( $metadata['height'] ?? 0 ),
			'filesize_bytes'  => ( '' !== $file_path && is_readable( $file_path ) ) ? absint( filesize( $file_path ) ) : absint( $metadata['filesize'] ?? 0 ),
			'metadata'        => $metadata,
		);
	}

	/**
	 * Validates optional optimistic preflight fields.
	 *
	 * @param array<string,mixed> $current Current state.
	 * @param array<string,mixed> $input Input args.
	 * @return true|\WP_Error
	 */
		private function validate_media_expected_state( array $current, array $input ) {
			$expected_relative = $this->normalize_media_relative_file( (string) ( $input['expected_current_relative_file'] ?? '' ) );
			if ( '' !== $expected_relative && $expected_relative !== (string) ( $current['relative_file'] ?? '' ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_current_file_mismatch', __( 'The current media file did not match the expected value.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
		$expected_mime = sanitize_text_field( (string) ( $input['expected_current_mime_type'] ?? '' ) );
		if ( '' !== $expected_mime && $expected_mime !== (string) ( $current['mime_type'] ?? '' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_current_mime_mismatch', __( 'The current media MIME type did not match the expected value.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
		}

			return true;
		}

		/**
		 * Validates optional expected content hashes for optimistic media writes.
		 *
		 * @param array<string,mixed> $current Current state.
		 * @param array<string,mixed> $input Input args.
		 * @return true|\WP_Error
		 */
		private function validate_media_expected_hashes( array $current, array $input ) {
			$raw_expected_md5 = trim( (string) ( $input['expected_current_md5'] ?? '' ) );
			$raw_expected_sha256 = trim( (string) ( $input['expected_current_sha256'] ?? '' ) );
			$expected_md5 = $this->normalize_media_md5( $raw_expected_md5 );
			$expected_sha256 = $this->normalize_media_sha256( $raw_expected_sha256 );
			if ( '' !== $raw_expected_md5 && '' === $expected_md5 ) {
				return new \WP_Error( 'npcink_abilities_toolkit_expected_md5_invalid', __( 'The expected current MD5 value is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			if ( '' !== $raw_expected_sha256 && '' === $expected_sha256 ) {
				return new \WP_Error( 'npcink_abilities_toolkit_expected_sha256_invalid', __( 'The expected current SHA-256 value is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			if ( '' === $expected_md5 && '' === $expected_sha256 ) {
				return true;
			}
			$hashes = $this->media_content_hashes_for_state( $current );
			if ( '' !== $expected_md5 && $expected_md5 !== (string) ( $hashes['md5'] ?? '' ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_current_md5_mismatch', __( 'The current media file MD5 did not match the expected value.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}
			if ( '' !== $expected_sha256 && $expected_sha256 !== (string) ( $hashes['sha256'] ?? '' ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_current_sha256_mismatch', __( 'The current media file SHA-256 did not match the expected value.', 'npcink-abilities-toolkit' ), array( 'status' => 409 ) );
			}

			return true;
		}

		/**
		 * Normalizes optional post-content reference repair drift guards.
		 *
		 * @param array<string,mixed> $input Input args.
		 * @return array<string,mixed>
		 */
		private function normalize_media_content_reference_repair_expectations( array $input ) {
			$expectations = array();
			if ( array_key_exists( 'expected_content_reference_post_ids', $input ) ) {
				$post_ids = is_array( $input['expected_content_reference_post_ids'] )
					? array_map( 'absint', $input['expected_content_reference_post_ids'] )
					: array();
				$post_ids = array_slice( array_values( array_unique( array_filter( $post_ids ) ) ), 0, 50 );
				sort( $post_ids );
				$expectations['post_ids'] = $post_ids;
			}
			if ( array_key_exists( 'expected_content_reference_post_count', $input ) ) {
				$expectations['post_count'] = absint( $input['expected_content_reference_post_count'] );
			}
			if ( array_key_exists( 'expected_content_reference_replacement_count', $input ) ) {
				$expectations['replacement_count'] = absint( $input['expected_content_reference_replacement_count'] );
			}
			return $expectations;
		}

		/**
		 * Builds content hashes for an internal media file state.
		 *
		 * @param array<string,mixed> $state Internal state.
		 * @return array<string,mixed>
		 */
		private function media_content_hashes_for_state( array $state ) {
			$file_path = (string) ( $state['file_path'] ?? '' );
			if ( '' === $file_path || ! is_readable( $file_path ) ) {
				return array(
					'available' => false,
					'md5'       => '',
					'sha256'    => '',
				);
			}

			return array(
				'available' => true,
				'md5'       => (string) md5_file( $file_path ),
				'sha256'    => (string) hash_file( 'sha256', $file_path ),
			);
		}

		/**
		 * Builds safe public state from an internal media file state.
		 *
		 * @param array<string,mixed> $state Internal state.
	 * @return array<string,mixed>
	 */
	private function public_media_file_state( array $state ) {
		return array(
			'relative_file'  => $this->normalize_media_relative_file( (string) ( $state['relative_file'] ?? '' ) ),
			'url'            => esc_url_raw( (string) ( $state['url'] ?? '' ) ),
			'file_basename'  => $this->sanitize_media_file_name( (string) ( $state['file_basename'] ?? '' ) ),
			'mime_type'      => sanitize_text_field( (string) ( $state['mime_type'] ?? '' ) ),
			'width'          => absint( $state['width'] ?? 0 ),
			'height'         => absint( $state['height'] ?? 0 ),
			'filesize_bytes' => absint( $state['filesize_bytes'] ?? 0 ),
		);
	}

	/**
	 * Builds target state from a recorded derivative.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $derivative Derivative row.
	 * @return array<string,mixed>
	 */
	private function media_file_state_from_derivative( $attachment_id, array $derivative ) {
		$relative_file = $this->normalize_media_relative_file( (string) ( $derivative['relative_file'] ?? '' ) );
		return array(
			'relative_file'  => $relative_file,
			'url'            => $this->media_url_for_relative_file( $relative_file ),
			'file_basename'  => $this->sanitize_media_file_name( basename( $relative_file ) ),
			'mime_type'      => sanitize_text_field( (string) ( $derivative['mime_type'] ?? '' ) ),
			'width'          => absint( $derivative['width'] ?? 0 ),
			'height'         => absint( $derivative['height'] ?? 0 ),
			'filesize_bytes' => absint( $derivative['filesize_bytes'] ?? 0 ),
			'attachment_id'  => absint( $attachment_id ),
		);
	}

	/**
	 * Finds one recorded optimized derivative by relative file.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $relative_file Relative file.
	 * @return array<string,mixed>
	 */
	private function find_media_optimized_derivative( $attachment_id, $relative_file ) {
		$relative_file = $this->normalize_media_relative_file( $relative_file );
		foreach ( $this->get_media_optimized_derivatives( $attachment_id ) as $derivative ) {
			if ( $relative_file === $this->normalize_media_relative_file( (string) ( $derivative['relative_file'] ?? '' ) ) ) {
				return $derivative;
			}
		}

		return array();
	}

	/**
	 * Returns replacement history records.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_media_file_replacement_history( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( isset( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_file_replacement_history'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_file_replacement_history'] ) ) {
			return array_values( array_filter( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $attachment_id ]['_npcink_ai_media_file_replacement_history'], 'is_array' ) );
		}
		$history = function_exists( 'get_post_meta' ) ? get_post_meta( $attachment_id, '_npcink_ai_media_file_replacement_history', true ) : array();
		return is_array( $history ) ? array_values( array_filter( $history, 'is_array' ) ) : array();
	}

	/**
	 * Finds one replacement history record.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $replacement_id Replacement id.
	 * @return array<string,mixed>
	 */
	private function find_media_file_replacement_history( $attachment_id, $replacement_id ) {
		$replacement_id = sanitize_text_field( (string) $replacement_id );
		foreach ( $this->get_media_file_replacement_history( $attachment_id ) as $record ) {
			if ( $replacement_id === (string) ( $record['replacement_id'] ?? '' ) ) {
				return $record;
			}
		}

		return array();
	}

	/**
	 * Appends one replacement history record.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $record History record.
	 * @return void
	 */
	private function append_media_file_replacement_history( $attachment_id, array $record ) {
		$history = $this->get_media_file_replacement_history( $attachment_id );
		$history[] = $record;
		$history = array_slice( $history, -20 );
		if ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( absint( $attachment_id ), '_npcink_ai_media_file_replacement_history', $history );
			update_post_meta( absint( $attachment_id ), '_npcink_ai_media_latest_file_replacement', $record );
		}
	}

	/**
	 * Marks a replacement history record rolled back.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $replacement_id Replacement id.
	 * @return void
	 */
	private function mark_media_file_replacement_rolled_back( $attachment_id, $replacement_id ) {
		$replacement_id = sanitize_text_field( (string) $replacement_id );
		$history = $this->get_media_file_replacement_history( $attachment_id );
		foreach ( $history as &$record ) {
			if ( $replacement_id === (string) ( $record['replacement_id'] ?? '' ) ) {
				$record['status'] = 'rolled_back';
				$record['rolled_back_at_gmt'] = gmdate( 'c' );
			}
		}
		unset( $record );
		if ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( absint( $attachment_id ), '_npcink_ai_media_file_replacement_history', $history );
		}
	}

	/**
	 * Updates attachment file pointer and metadata.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param string              $relative_file Uploads-relative file.
	 * @param string              $mime_type MIME type.
	 * @param array<string,mixed> $state Public file state.
	 * @return true|\WP_Error
	 */
	private function update_media_file_pointer( $attachment_id, $relative_file, $mime_type, array $state ) {
		$attachment_id = absint( $attachment_id );
		$relative_file = $this->normalize_media_relative_file( $relative_file );
		$file_path = $this->media_uploads_path_for_relative_file( $relative_file );
		if ( '' === $relative_file || '' === $file_path ) {
			return new \WP_Error( 'npcink_abilities_toolkit_replacement_path_invalid', __( 'Replacement file path is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		if ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( $attachment_id, '_wp_attached_file', $relative_file );
		}
		$update_args = array(
			'ID'             => $attachment_id,
			'post_mime_type' => sanitize_text_field( (string) $mime_type ),
		);
		$updated = wp_update_post( $update_args, true );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
			$metadata = array(
				'file'     => $relative_file,
				'width'    => absint( $state['width'] ?? 0 ),
				'height'   => absint( $state['height'] ?? 0 ),
				'filesize' => absint( $state['filesize_bytes'] ?? 0 ),
				'sizes'    => array(),
			);
			if ( is_array( $state['_metadata'] ?? null ) ) {
				$metadata = $state['_metadata'];
			} elseif ( is_readable( $file_path ) ) {
				$generated = $this->generate_attachment_metadata_for_file( $attachment_id, $file_path );
				if ( ! empty( $generated ) ) {
					$metadata = $generated;
				}
			}
		if ( function_exists( 'update_post_meta' ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $metadata );
		}

			return true;
		}

		/**
		 * Preserves attachment metadata when only the main file basename changes.
		 *
		 * @param array<string,mixed> $metadata Existing metadata.
		 * @param string              $relative_file New uploads-relative main file.
		 * @param array<string,mixed> $state Public file state.
		 * @return array<string,mixed>
		 */
		private function renamed_media_metadata( array $metadata, $relative_file, array $state ) {
			$metadata['file'] = $this->normalize_media_relative_file( $relative_file );
			$metadata['width'] = absint( $metadata['width'] ?? $state['width'] ?? 0 );
			$metadata['height'] = absint( $metadata['height'] ?? $state['height'] ?? 0 );
			$metadata['filesize'] = absint( $state['filesize_bytes'] ?? $metadata['filesize'] ?? 0 );
			if ( ! isset( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
				$metadata['sizes'] = array();
			}

			return $metadata;
		}

		/**
		 * Builds a backup relative file in the dedicated uploads backup directory.
		 *
		 * @param array<string,mixed> $current Current media state.
	 * @param string              $replacement_id Replacement id.
	 * @param string              $backup_suffix Backup suffix.
	 * @return string
	 */
	private function backup_relative_file_for_current_media( array $current, $replacement_id, $backup_suffix ) {
		$current_relative = $this->normalize_media_relative_file( (string) ( $current['relative_file'] ?? '' ) );
		$current_dir = dirname( $current_relative );
		$current_dir = '.' !== $current_dir ? trim( $current_dir, '/' ) : '';
		$backup_dir = 'npcink-abilities-toolkit-backups' . ( '' !== $current_dir ? '/' . $current_dir : '' );
		$basename = $this->sanitize_media_file_name( basename( $current_relative ) );
		$stem = preg_replace( '/\.[^.]+$/', '', $basename );
		$extension = pathinfo( $basename, PATHINFO_EXTENSION );
		$backup_name = $this->sanitize_media_file_name( (string) $stem . '-' . sanitize_key( (string) $backup_suffix ) . '-' . sanitize_key( (string) $replacement_id ) . ( '' !== $extension ? '.' . $extension : '' ) );
		return $backup_dir . '/' . $backup_name;
	}

	/**
	 * Normalizes an uploads-relative media file.
	 *
	 * @param string $relative_file Relative file.
	 * @return string
	 */
	private function normalize_media_relative_file( $relative_file ) {
		$relative_file = ltrim( str_replace( '\\', '/', sanitize_text_field( (string) $relative_file ) ), '/' );
		if ( '' === $relative_file || false !== strpos( $relative_file, '../' ) || '..' === $relative_file || 0 === strpos( $relative_file, '/' ) ) {
			return '';
		}
		return $relative_file;
	}

	/**
	 * Resolves an uploads-relative file to an absolute path.
	 *
	 * @param string $relative_file Relative file.
	 * @return string
	 */
	private function media_uploads_path_for_relative_file( $relative_file ) {
		$relative_file = $this->normalize_media_relative_file( $relative_file );
		if ( '' === $relative_file ) {
			return '';
		}
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return '';
		}
		$upload_dir = wp_upload_dir();
		$basedir = is_array( $upload_dir ) ? (string) ( $upload_dir['basedir'] ?? '' ) : '';
		if ( '' === $basedir ) {
			return '';
		}
		return $this->trailingslashit_value( $basedir ) . $relative_file;
	}

	/**
	 * Returns a source basename without exposing absolute paths.
	 *
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @param string              $source_file Source file path.
	 * @param string              $source_url Source URL.
	 * @param int                 $attachment_id Attachment id.
	 * @return string
	 */
	private function media_source_basename( array $metadata, $source_file, $source_url, $attachment_id ) {
		$file = sanitize_text_field( (string) ( $metadata['file'] ?? '' ) );
		if ( '' === $file && '' !== (string) $source_file ) {
			$file = (string) $source_file;
		}
		if ( '' === $file && '' !== (string) $source_url ) {
			$file = (string) wp_parse_url( (string) $source_url, PHP_URL_PATH );
		}
		$basename = $this->sanitize_media_file_name( basename( $file ) );
		return '' !== $basename ? $basename : 'attachment-' . absint( $attachment_id );
	}

	/**
	 * Returns the metadata-relative upload directory.
	 *
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @return string
	 */
	private function media_relative_dir( array $metadata ) {
		$file = sanitize_text_field( (string) ( $metadata['file'] ?? '' ) );
		$dir = '' !== $file ? dirname( str_replace( '\\', '/', $file ) ) : '';
		return '.' !== $dir ? trim( $dir, '/' ) : '';
	}

	/**
	 * Builds a public URL for one uploads-relative derivative file.
	 *
	 * @param string $relative_file Uploads-relative file.
	 * @return string
	 */
	private function media_url_for_relative_file( $relative_file ) {
		$relative_file = ltrim( str_replace( '\\', '/', sanitize_text_field( (string) $relative_file ) ), '/' );
		if ( '' === $relative_file || ! function_exists( 'wp_upload_dir' ) ) {
			return '';
		}
		$upload_dir = wp_upload_dir();
		$baseurl = is_array( $upload_dir ) ? esc_url_raw( (string) ( $upload_dir['baseurl'] ?? '' ) ) : '';
		return '' !== $baseurl ? $this->trailingslashit_value( $baseurl ) . $relative_file : '';
	}

	/**
	 * Adds a trailing slash without requiring the WordPress helper in unit tests.
	 *
	 * @param string $value Path or URL.
	 * @return string
	 */
	private function trailingslashit_value( $value ) {
		return rtrim( (string) $value, "/\\" ) . '/';
	}

	/**
	 * Ensures a media directory exists using WordPress helpers when available.
	 *
	 * @param string $directory Directory path.
	 * @return bool
	 */
	private function ensure_media_directory( $directory ) {
		$directory = (string) $directory;
		if ( '' === $directory ) {
			return false;
		}
		if ( is_dir( $directory ) ) {
			return true;
		}
		return function_exists( 'wp_mkdir_p' ) && wp_mkdir_p( $directory );
	}

	/**
	 * Writes a media file with a bounded failure-injection hook for verification.
	 *
	 * @param string              $target_path Target path.
	 * @param string              $contents File contents.
	 * @param array<string,mixed> $context Operation context.
	 * @return bool
	 */
	private function write_media_file( $target_path, $contents, array $context = array() ) {
		$target_path = (string) $target_path;
		$contents    = (string) $contents;
		$blocked     = apply_filters( 'npcink_abilities_toolkit_media_file_write_blocked', false, $target_path, strlen( $contents ), $context );
		if ( true === $blocked ) {
			return false;
		}
		return false !== file_put_contents( $target_path, $contents );
	}

	/**
	 * Copies a media file with a bounded failure-injection hook for verification.
	 *
	 * @param string              $source_path Source path.
	 * @param string              $target_path Target path.
	 * @param array<string,mixed> $context Operation context.
	 * @return bool
	 */
	private function copy_media_file( $source_path, $target_path, array $context = array() ) {
		$source_path = (string) $source_path;
		$target_path = (string) $target_path;
		$blocked     = apply_filters( 'npcink_abilities_toolkit_media_file_copy_blocked', false, $source_path, $target_path, $context );
		if ( true === $blocked ) {
			return false;
		}
		return copy( $source_path, $target_path );
	}

	/**
	 * Moves a media file using WordPress filesystem-safe deletion semantics.
	 *
	 * @param string $source_path Source path.
	 * @param string $target_path Target path.
	 * @param array<string,mixed> $context Operation context.
	 * @return bool
	 */
	private function move_media_file( $source_path, $target_path, array $context = array() ) {
		$source_path = (string) $source_path;
		$target_path = (string) $target_path;
		if ( '' === $source_path || '' === $target_path || ! is_readable( $source_path ) || file_exists( $target_path ) ) {
			return false;
		}
		if ( ! $this->copy_media_file( $source_path, $target_path, $context ) ) {
			return false;
		}
		if ( function_exists( 'wp_delete_file' ) && wp_delete_file( $source_path ) ) {
			return ! file_exists( $source_path ) && is_readable( $target_path );
		}
		wp_delete_file( $target_path );
		return false;
	}

	/**
	 * Returns a MIME type for an optimized format.
	 *
	 * @param string $format Format.
	 * @return string
	 */
	private function media_mime_for_format( $format ) {
		$format = sanitize_key( (string) $format );
		if ( 'avif' === $format ) {
			return 'image/avif';
		}
		if ( 'jpeg' === $format ) {
			return 'image/jpeg';
		}
		if ( 'png' === $format ) {
			return 'image/png';
		}
		return 'image/webp';
	}

	/**
	 * Returns a file extension for an optimized format.
	 *
	 * @param string $format Format.
	 * @return string
	 */
	private function media_extension_for_format( $format ) {
		$format = sanitize_key( (string) $format );
		return 'jpeg' === $format ? 'jpg' : ( in_array( $format, array( 'webp', 'avif', 'png' ), true ) ? $format : 'webp' );
	}

	/**
	 * Returns a derivative format for a MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return string
	 */
	private function media_format_for_mime( $mime_type ) {
		$mime_type = sanitize_text_field( (string) $mime_type );
		if ( 'image/avif' === $mime_type ) {
			return 'avif';
		}
		if ( 'image/jpeg' === $mime_type ) {
			return 'jpeg';
		}
		if ( 'image/png' === $mime_type ) {
			return 'png';
		}
		return 'webp';
	}

	/**
	 * Returns a safe extension for a MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return string
	 */
	private function media_extension_for_mime( $mime_type ) {
		return $this->media_extension_for_format( $this->media_format_for_mime( $mime_type ) );
	}

	/**
	 * Normalizes SHA-256 values from Cloud artifact descriptors.
	 *
	 * @param string $value Raw checksum value.
	 * @return string
	 */
		private function normalize_media_sha256( $value ) {
			$value = strtolower( trim( sanitize_text_field( (string) $value ) ) );
			if ( 0 === strpos( $value, 'sha256:' ) ) {
				$value = substr( $value, 7 );
			}
			return 1 === preg_match( '/^[a-f0-9]{64}$/', $value ) ? $value : '';
		}

		/**
		 * Normalizes MD5 values from caller optimistic checks.
		 *
		 * @param string $value Raw checksum value.
		 * @return string
		 */
		private function normalize_media_md5( $value ) {
			$value = strtolower( trim( sanitize_text_field( (string) $value ) ) );
			if ( 0 === strpos( $value, 'md5:' ) ) {
				$value = substr( $value, 4 );
			}
			return 1 === preg_match( '/^[a-f0-9]{32}$/', $value ) ? $value : '';
		}

		/**
		 * Returns a unique file basename without requiring WordPress helpers in tests.
		 *
	 * @param string $directory Directory path.
	 * @param string $basename Desired basename.
	 * @return string
	 */
	private function unique_media_basename( $directory, $basename ) {
		$basename = $this->sanitize_media_file_name( $basename );
		$stem = preg_replace( '/\.[^.]+$/', '', $basename );
		$extension = pathinfo( $basename, PATHINFO_EXTENSION );
		$candidate = $basename;
		$index = 1;
		while ( file_exists( $this->trailingslashit_value( $directory ) . $candidate ) ) {
			$candidate = $this->sanitize_media_file_name( (string) $stem . '-' . $index . ( '' !== $extension ? '.' . $extension : '' ) );
			++$index;
		}
		return $candidate;
	}

	/**
	 * Sanitizes file names in WordPress and isolated test runtimes.
	 *
	 * @param string $file_name Raw file name.
	 * @return string
	 */
	private function sanitize_media_file_name( $file_name ) {
		if ( function_exists( 'sanitize_file_name' ) ) {
			return sanitize_file_name( (string) $file_name );
		}

		return preg_replace( '/[^A-Za-z0-9._-]/', '', basename( (string) $file_name ) );
	}

	/**
	 * Builds one media details snapshot.
	 *
	 * @param object $attachment Attachment object.
	 * @return array<string,mixed>
	 */
	private function media_snapshot( $attachment ) {
		$attachment_id = absint( $attachment->ID ?? 0 );
		return array(
			'title'             => sanitize_text_field( (string) ( $attachment->post_title ?? '' ) ),
			'alt'               => function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) : '',
			'caption'           => sanitize_textarea_field( (string) ( $attachment->post_excerpt ?? '' ) ),
			'description'       => sanitize_textarea_field( (string) ( $attachment->post_content ?? '' ) ),
			'source_type'       => function_exists( 'get_post_meta' ) ? $this->normalize_media_source_type( get_post_meta( $attachment_id, '_npcink_ai_media_source_type', true ) ) : '',
			'source_page_url'   => function_exists( 'get_post_meta' ) ? esc_url_raw( (string) get_post_meta( $attachment_id, '_npcink_ai_media_source_page_url', true ) ) : '',
			'photographer_name' => function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $attachment_id, '_npcink_ai_media_photographer_name', true ) ) : '',
			'attribution_text'  => function_exists( 'get_post_meta' ) ? sanitize_textarea_field( (string) get_post_meta( $attachment_id, '_npcink_ai_media_attribution_text', true ) ) : '',
			'copyright_notice'  => function_exists( 'get_post_meta' ) ? sanitize_textarea_field( (string) get_post_meta( $attachment_id, '_npcink_ai_media_copyright_notice', true ) ) : '',
		);
	}

	/**
	 * Normalizes media source type.
	 *
	 * @param mixed  $value Source type value.
	 * @param string $fallback Fallback source type.
	 * @return string
	 */
	private function normalize_media_source_type( $value, $fallback = '' ) {
		$value    = sanitize_key( (string) $value );
		$fallback = sanitize_key( (string) $fallback );
		$allowed  = array( 'owned', 'ai_generated', 'stock', 'external', 'test' );
		if ( in_array( $value, $allowed, true ) ) {
			return $value;
		}
		return in_array( $fallback, $allowed, true ) ? $fallback : '';
	}

	/**
	 * Adds conservative source defaults for uploads where the operator supplied
	 * no richer attribution text.
	 *
	 * @param array<string,mixed> $metadata Source metadata.
	 * @param string              $fallback_url Fallback source URL.
	 * @return array<string,string>
	 */
	private function media_source_metadata_with_defaults( array $metadata, $fallback_url ) {
		$source_type = $this->normalize_media_source_type( $metadata['source_type'] ?? '', 'external' );
		$source_page_url = esc_url_raw( (string) ( $metadata['source_page_url'] ?? $fallback_url ) );
		$photographer_name = sanitize_text_field( (string) ( $metadata['photographer_name'] ?? '' ) );
		$attribution_text = sanitize_textarea_field( (string) ( $metadata['attribution_text'] ?? '' ) );
		$copyright_notice = sanitize_textarea_field( (string) ( $metadata['copyright_notice'] ?? '' ) );

		if ( 'ai_generated' === $source_type ) {
			if ( '' === $attribution_text ) {
				$attribution_text = 'AI-generated by site operator';
			}
			if ( '' === $copyright_notice ) {
				$copyright_notice = 'Generated asset for this site';
			}
		} elseif ( 'stock' === $source_type || 'external' === $source_type ) {
			if ( '' === $attribution_text && '' !== $source_page_url ) {
				$attribution_text = 'Source: ' . $source_page_url;
			}
		} elseif ( 'owned' === $source_type && '' === $copyright_notice ) {
			$copyright_notice = 'Owned asset for this site';
		} elseif ( 'test' === $source_type && '' === $copyright_notice ) {
			$copyright_notice = 'Test media asset for this site';
		}

		return array(
			'source_type'       => $source_type,
			'source_page_url'   => $source_page_url,
			'photographer_name' => $photographer_name,
			'attribution_text'  => $attribution_text,
			'copyright_notice'  => $copyright_notice,
		);
	}

	/**
	 * Persists media source metadata.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $metadata Source metadata.
	 * @return void
	 */
	private function update_media_source_metadata( $attachment_id, array $metadata ) {
		$attachment_id = absint( $attachment_id );
		$meta_keys = array(
			'source_type'       => '_npcink_ai_media_source_type',
			'source_page_url'   => '_npcink_ai_media_source_page_url',
			'photographer_name' => '_npcink_ai_media_photographer_name',
			'attribution_text'  => '_npcink_ai_media_attribution_text',
			'copyright_notice'  => '_npcink_ai_media_copyright_notice',
		);
		foreach ( $meta_keys as $field => $meta_key ) {
			$value = (string) ( $metadata[ $field ] ?? '' );
			if ( '' === $value ) {
				delete_post_meta( $attachment_id, $meta_key );
			} else {
				update_post_meta( $attachment_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Resolves one comment for write operations.
	 *
	 * @param int $comment_id Comment id.
	 * @return object|\WP_Error
	 */
	private function get_comment_for_write( $comment_id ) {
		$comment_id = absint( $comment_id );
		if ( $comment_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_comment_invalid', __( 'Comment ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return new \WP_Error( 'npcink_abilities_toolkit_comment_not_found', __( 'Comment was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'moderate_comments' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to moderate comments.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		return $comment;
	}

	/**
	 * Normalizes write content into safe HTML.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @param string              $field Input field.
	 * @param string              $title Optional title used to remove duplicate first headings.
	 * @return array{content:string,content_format:string}
	 */
	private function normalize_content_input( array $input, $field = 'content', $title = '' ) {
		$field          = is_string( $field ) && '' !== $field ? $field : 'content';
		$content        = array_key_exists( $field, $input ) ? (string) $input[ $field ] : '';
		$content_format = $this->resolve_content_format( $content, (string) ( $input['content_format'] ?? '' ) );
		if ( 'markdown' === $content_format ) {
			$content = $this->markdown_to_html( $content );
		} elseif ( 'plain' === $content_format ) {
			$content = $this->plain_text_to_html( $content );
		}

		$content = $this->strip_duplicate_title_heading( $content, $title );

		return array(
			'content'        => function_exists( 'wp_kses_post' ) ? wp_kses_post( $content ) : $content,
			'content_format' => $content_format,
		);
	}

	/**
	 * Resolves input content format with a lightweight markdown fallback.
	 *
	 * @param string $content Raw content.
	 * @param string $declared_format Declared format.
	 * @return string
	 */
	private function resolve_content_format( $content, $declared_format = '' ) {
		$declared_format = sanitize_key( (string) $declared_format );
		if ( in_array( $declared_format, array( 'html', 'markdown', 'plain' ), true ) ) {
			return $declared_format;
		}
		$content = (string) $content;
		if ( '' === trim( $content ) || preg_match( '/<\\/?[a-z][^>]*>/i', $content ) ) {
			return 'html';
		}
		if (
			preg_match( '/^#{1,6}\\s+/m', $content )
			|| preg_match( '/^(?:[-*+]\\s+|\\d+\\.\\s+)/m', $content )
			|| preg_match( '/\\*\\*[^\\n]+\\*\\*/', $content )
			|| preg_match( '/\\[[^\\]]+\\]\\((https?:\\/\\/[^\\s)]+)\\)/', $content )
			|| preg_match( '/`[^`]+`/', $content )
		) {
			return 'markdown';
		}
		return 'plain';
	}

	/**
	 * Converts a small markdown subset into safe HTML.
	 *
	 * @param string $markdown Markdown source.
	 * @return string
	 */
	private function markdown_to_html( $markdown ) {
		$markdown = str_replace( array( "\r\n", "\r" ), "\n", trim( (string) $markdown ) );
		if ( '' === $markdown ) {
			return '';
		}
		$lines           = explode( "\n", $markdown );
		$html            = array();
		$list_items      = array();
		$list_type       = '';
		$paragraph_lines = array();

		$flush_list = function () use ( &$html, &$list_items, &$list_type ) {
			if ( empty( $list_items ) || '' === $list_type ) {
				$list_items = array();
				$list_type  = '';
				return;
			}
			$html[]     = '<' . $list_type . '><li>' . implode( '</li><li>', $list_items ) . '</li></' . $list_type . '>';
			$list_items = array();
			$list_type  = '';
		};
		$flush_paragraph = function () use ( &$html, &$paragraph_lines ) {
			if ( empty( $paragraph_lines ) ) {
				return;
			}
			$html[]          = '<p>' . implode( "<br />\n", $paragraph_lines ) . '</p>';
			$paragraph_lines = array();
		};

		foreach ( $lines as $raw_line ) {
			$line = trim( (string) $raw_line );
			if ( '' === $line ) {
				$flush_list();
				$flush_paragraph();
				continue;
			}
			if ( preg_match( '/^(#{1,6})\\s+(.+)$/', $line, $matches ) ) {
				$flush_list();
				$flush_paragraph();
				$level  = min( 6, max( 1, strlen( (string) $matches[1] ) ) );
				$html[] = '<h' . $level . '>' . $this->render_inline_markdown_html( (string) $matches[2] ) . '</h' . $level . '>';
				continue;
			}
			if ( preg_match( '/^[-*+]\\s+(.+)$/', $line, $matches ) ) {
				$flush_paragraph();
				if ( '' !== $list_type && 'ul' !== $list_type ) {
					$flush_list();
				}
				$list_type    = 'ul';
				$list_items[] = $this->render_inline_markdown_html( (string) $matches[1] );
				continue;
			}
			if ( preg_match( '/^\\d+\\.\\s+(.+)$/', $line, $matches ) ) {
				$flush_paragraph();
				if ( '' !== $list_type && 'ol' !== $list_type ) {
					$flush_list();
				}
				$list_type    = 'ol';
				$list_items[] = $this->render_inline_markdown_html( (string) $matches[1] );
				continue;
			}
			$flush_list();
			$paragraph_lines[] = $this->render_inline_markdown_html( $line );
		}
		$flush_list();
		$flush_paragraph();

		return implode( "\n\n", $html );
	}

	/**
	 * Renders a small markdown subset for inline spans.
	 *
	 * @param string $content Raw inline markdown.
	 * @return string
	 */
	private function render_inline_markdown_html( $content ) {
		$content = (string) $content;
		if ( '' === $content ) {
			return '';
		}
		$tokens = array();
		$content = preg_replace_callback(
			'/`([^`]+)`/',
			function ( $matches ) use ( &$tokens ) {
				$token             = '__MAA_CODE_' . count( $tokens ) . '__';
				$tokens[ $token ] = '<code>' . $this->escape_html( (string) ( $matches[1] ?? '' ) ) . '</code>';
				return $token;
			},
			$content
		);
		$content = preg_replace_callback(
			'/!\\[([^\\]]*)\\]\\((https?:\\/\\/[^\\s)]+)\\)/',
			function ( $matches ) use ( &$tokens ) {
				$token             = '__MAA_IMAGE_' . count( $tokens ) . '__';
				$alt               = $this->escape_html( (string) ( $matches[1] ?? '' ) );
				$src               = esc_url_raw( (string) ( $matches[2] ?? '' ) );
				$tokens[ $token ] = '<img src="' . $src . '" alt="' . $alt . '" />';
				return $token;
			},
			$content
		);
		$content = $this->escape_html( (string) $content );
		$content = preg_replace( '/\\[([^\\]]+)\\]\\((https?:\\/\\/[^\\s)]+)\\)/', '<a href="$2">$1</a>', $content );
		$content = preg_replace( '/\\*\\*([^*]+)\\*\\*/', '<strong>$1</strong>', $content );
		$content = preg_replace( '/\\*([^*]+)\\*/', '<em>$1</em>', $content );

		return ! empty( $tokens ) ? strtr( (string) $content, $tokens ) : (string) $content;
	}

	/**
	 * Converts plain text into paragraph HTML.
	 *
	 * @param string $content Plain text source.
	 * @return string
	 */
	private function plain_text_to_html( $content ) {
		$content = trim( (string) $content );
		if ( '' === $content ) {
			return '';
		}
		$content = $this->escape_html( $content );
		if ( function_exists( 'wpautop' ) ) {
			return (string) wpautop( $content );
		}
		$paragraphs = preg_split( "/\\n\\s*\\n/", str_replace( array( "\r\n", "\r" ), "\n", $content ) );
		$paragraphs = is_array( $paragraphs ) ? $paragraphs : array( $content );
		$paragraphs = array_map(
			static function ( $paragraph ) {
				return '<p>' . nl2br( trim( (string) $paragraph ) ) . '</p>';
			},
			array_filter(
				$paragraphs,
				static function ( $paragraph ) {
					return '' !== trim( (string) $paragraph );
				}
			)
		);
		return implode( "\n\n", $paragraphs );
	}

	/**
	 * Escapes text for HTML output.
	 *
	 * @param string $content Raw text.
	 * @return string
	 */
	private function escape_html( $content ) {
		return function_exists( 'esc_html' )
			? (string) esc_html( (string) $content )
			: htmlspecialchars( (string) $content, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Removes a first HTML heading when it only repeats the post title.
	 *
	 * @param string $content Normalized HTML content.
	 * @param string $title Post title.
	 * @return string
	 */
	private function strip_duplicate_title_heading( $content, $title ) {
		$strip_tags = static function ( $value ) {
			return wp_strip_all_tags( (string) $value );
		};
		$title = trim( $strip_tags( (string) $title ) );
		if ( '' === $title ) {
			return (string) $content;
		}

		$pattern = '/^\\s*<h([1-6])[^>]*>(.*?)<\\/h\\1>\\s*/is';
		if ( ! preg_match( $pattern, (string) $content, $matches ) ) {
			return (string) $content;
		}
		$heading_text = trim( $strip_tags( html_entity_decode( (string) ( $matches[2] ?? '' ), ENT_QUOTES, 'UTF-8' ) ) );
		if ( strtolower( $heading_text ) !== strtolower( $title ) ) {
			return (string) $content;
		}

		return (string) preg_replace( $pattern, '', (string) $content, 1 );
	}

	/**
	 * Truncates one preview fragment.
	 *
	 * @param string $text Raw text.
	 * @param int    $max_len Max length.
	 * @return string
	 */
	private function truncate_fragment( $text, $max_len = 400 ) {
		$text    = (string) $text;
		$max_len = max( 32, absint( $max_len ) );
		if ( strlen( $text ) <= $max_len ) {
			return $text;
		}
		return substr( $text, 0, $max_len ) . '...';
	}

	/**
	 * Builds a minimal diff preview for audit output.
	 *
	 * @param string $before Before content.
	 * @param string $after After content.
	 * @return array<string,mixed>
	 */
	private function build_text_diff_preview( $before, $after ) {
		$before     = (string) $before;
		$after      = (string) $after;
		$before_len = strlen( $before );
		$after_len  = strlen( $after );
		$prefix     = 0;
		$limit      = min( $before_len, $after_len );
		while ( $prefix < $limit && $before[ $prefix ] === $after[ $prefix ] ) {
			$prefix++;
		}

		$suffix = 0;
		while (
			$suffix < ( $before_len - $prefix ) &&
			$suffix < ( $after_len - $prefix ) &&
			substr( $before, $before_len - 1 - $suffix, 1 ) === substr( $after, $after_len - 1 - $suffix, 1 )
		) {
			$suffix++;
		}

		$before_changed_len = max( 0, $before_len - $prefix - $suffix );
		$after_changed_len  = max( 0, $after_len - $prefix - $suffix );
		$prefix_start       = max( 0, $prefix - 120 );

		return array(
			'changed'               => $before !== $after,
			'before_hash'           => md5( $before ),
			'after_hash'            => md5( $after ),
			'prefix_length'         => $prefix,
			'suffix_length'         => $suffix,
			'before_changed_length' => $before_changed_len,
			'after_changed_length'  => $after_changed_len,
			'prefix_context'        => $this->truncate_fragment( substr( $before, $prefix_start, $prefix - $prefix_start ), 240 ),
			'before_fragment'       => $this->truncate_fragment( substr( $before, $prefix, $before_changed_len ), 600 ),
			'after_fragment'        => $this->truncate_fragment( substr( $after, $prefix, $after_changed_len ), 600 ),
			'suffix_context'        => $this->truncate_fragment( $suffix > 0 ? substr( $after, $after_len - min( 120, $suffix ) ) : '', 240 ),
		);
	}

	/**
	 * Builds a minimal changed range from a before/after diff.
	 *
	 * @param string $before Before content.
	 * @param string $after After content.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_impact_ranges_from_diff( $before, $after ) {
		$preview = $this->build_text_diff_preview( $before, $after );
		if ( empty( $preview['changed'] ) ) {
			return array();
		}
		$start                 = max( 0, absint( $preview['prefix_length'] ?? 0 ) );
		$before_changed_length = max( 0, absint( $preview['before_changed_length'] ?? 0 ) );
		$after_changed_length  = max( 0, absint( $preview['after_changed_length'] ?? 0 ) );
		return array(
			array(
				'op'            => 'replace',
				'start'         => $start,
				'end'           => $start + $before_changed_length,
				'before_length' => $before_changed_length,
				'after_length'  => $after_changed_length,
			),
		);
	}

	/**
	 * Applies patch operations to post content.
	 *
	 * @param string       $content Original content.
	 * @param array<mixed> $operations Patch operations.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function apply_patch_operations( $content, array $operations ) {
		$content    = (string) $content;
		$operations = array_values( $operations );
		if ( empty( $operations ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_patch_operations_required', __( 'Operations are required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$impact_ranges = array();
		$patch_preview = array();
		foreach ( $operations as $index => $operation ) {
			$operation = is_array( $operation ) ? $operation : array();
			$op        = sanitize_key( (string) ( $operation['op'] ?? '' ) );
			if ( ! in_array( $op, array( 'replace', 'delete', 'insert_before', 'insert_after' ), true ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_patch_operation_invalid', __( 'Patch operation is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			$find = (string) ( $operation['find'] ?? '' );
			if ( '' === $find ) {
				return new \WP_Error( 'npcink_abilities_toolkit_patch_find_required', __( 'Patch find text is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			$replace       = 'delete' === $op ? '' : (string) ( $operation['replace'] ?? '' );
			$limit         = max( 1, min( 20, absint( $operation['limit'] ?? 1 ) ) );
			$case_sensitive = ! array_key_exists( 'case_sensitive', $operation ) || ! empty( $operation['case_sensitive'] );
			$needle_len    = strlen( $find );
			$offset        = 0;
			$applied       = 0;

			while ( $applied < $limit ) {
				$position = $case_sensitive ? strpos( $content, $find, $offset ) : stripos( $content, $find, $offset );
				if ( false === $position ) {
					break;
				}
				$position       = (int) $position;
				$before_segment = substr( $content, $position, $needle_len );
				if ( 'replace' === $op ) {
					$after_segment = $replace;
				} elseif ( 'insert_before' === $op ) {
					$after_segment = $replace . $before_segment;
				} elseif ( 'insert_after' === $op ) {
					$after_segment = $before_segment . $replace;
				} else {
					$after_segment = '';
				}

				$content         = substr( $content, 0, $position ) . $after_segment . substr( $content, $position + $needle_len );
				$impact_ranges[] = array(
					'operation_index' => $index,
					'op'              => $op,
					'start'           => $position,
					'end'             => $position + $needle_len,
					'before_length'   => $needle_len,
					'after_length'    => strlen( $after_segment ),
					'before_preview'  => $this->truncate_fragment( $before_segment, 160 ),
					'after_preview'   => $this->truncate_fragment( $after_segment, 160 ),
				);
				$applied++;
				$offset = $position + strlen( $after_segment );
			}

			$patch_preview[] = array(
				'operation_index' => $index,
				'op'              => $op,
				'find'            => $this->truncate_fragment( $find, 160 ),
				'replace'         => $this->truncate_fragment( $replace, 160 ),
				'applied'         => $applied,
				'limit'           => $limit,
				'case_sensitive'  => $case_sensitive,
			);
		}

		return array(
			'content'       => $content,
			'impact_ranges' => $impact_ranges,
			'patch_preview' => $patch_preview,
		);
	}

	/**
	 * Builds exact post-content repairs for media replacement references.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $plan Replacement plan.
	 * @param bool                $applied Whether repairs have been committed.
	 * @return array<string,mixed>
	 */
	private function build_media_content_reference_repairs( $attachment_id, array $plan, $applied ) {
		$attachment_id = absint( $attachment_id );
		$pairs = $this->media_content_reference_pairs_for_plan( $plan );
		$needles = $this->media_content_reference_needles( $attachment_id, $plan, $pairs );
		$max_posts = 50;
		$repairs = array();
		$scanned_count = 0;
		$replacement_count = 0;
		$replacement_rule_count = 0;
		$actual_replacement_count = 0;
		$unmatched_rules = array();

		foreach ( $this->media_content_reference_candidate_posts( $attachment_id, $needles, $max_posts * 3 ) as $post ) {
			if ( count( $repairs ) >= $max_posts ) {
				break;
			}
			if ( ! is_object( $post ) || 'attachment' === sanitize_key( (string) ( $post->post_type ?? '' ) ) ) {
				continue;
			}
			if ( ! in_array( sanitize_key( (string) ( $post->post_status ?? '' ) ), array( 'publish', 'future', 'draft', 'pending', 'private' ), true ) ) {
				continue;
			}
			++$scanned_count;
			$post_id = absint( $post->ID ?? 0 );
			$content = (string) ( $post->post_content ?? '' );
			if ( $post_id <= 0 || '' === $content ) {
				continue;
			}
			$post_pairs = $this->merge_media_content_reference_pairs( $pairs, $this->media_content_reference_dynamic_sized_pairs( $content, $plan ) );
			$operations = array();
			foreach ( $post_pairs as $pair ) {
				$old = (string) ( $pair['old'] ?? '' );
				$new = (string) ( $pair['new'] ?? '' );
				if ( '' === $old || '' === $new || $old === $new || false === strpos( $content, $old ) ) {
					continue;
				}
				$count = substr_count( $content, $old );
				if ( $count <= 0 ) {
					continue;
				}
				$operations[] = array(
					'op'      => 'replace',
					'find'    => $old,
					'replace' => $new,
					'limit'   => min( 20, $count ),
				);
				$replacement_count += $count;
			}
			if ( empty( $operations ) ) {
				continue;
			}
			$preview = $this->apply_patch_operations( $content, $operations );
			$patch_preview = is_wp_error( $preview ) ? array() : (array) ( $preview['patch_preview'] ?? array() );
			$repair_counts = $this->media_reference_repair_count_summary( $post_id, $operations, $patch_preview );
			$replacement_rule_count += absint( $repair_counts['replacement_rule_count'] ?? 0 );
			$actual_replacement_count += absint( $repair_counts['actual_replacement_count'] ?? 0 );
			foreach ( (array) ( $repair_counts['unmatched_rules'] ?? array() ) as $unmatched_rule ) {
				if ( is_array( $unmatched_rule ) ) {
					$unmatched_rules[] = $unmatched_rule;
				}
			}
			$repairs[] = array(
				'post_id'               => $post_id,
				'post_type'             => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'post_status'           => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'title'                 => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'operation_count'       => count( $operations ),
				'replacement_rule_count' => absint( $repair_counts['replacement_rule_count'] ?? 0 ),
				'actual_replacement_count' => absint( $repair_counts['actual_replacement_count'] ?? 0 ),
				'unmatched_rules'       => (array) ( $repair_counts['unmatched_rules'] ?? array() ),
				'operations'            => $operations,
				'content_length_before' => strlen( $content ),
				'content_length_after'  => is_wp_error( $preview ) ? strlen( $content ) : strlen( (string) ( $preview['content'] ?? $content ) ),
				'patch_preview'         => $patch_preview,
				'updated'               => false,
			);
		}

		return array(
			'attachment_id'      => $attachment_id,
			'applied'            => (bool) $applied,
			'scanned_count'      => $scanned_count,
			'post_count'         => count( $repairs ),
			'replacement_count'  => $replacement_count,
			'replacement_rule_count' => $replacement_rule_count,
			'actual_replacement_count' => $actual_replacement_count,
			'unmatched_rules'    => $unmatched_rules,
			'repairs'            => $repairs,
			'reference_strategy' => 'replace_old_main_and_sized_upload_urls_with_new_main_file_url',
		);
	}

	/**
	 * Validates that the current user can edit every post that will be repaired.
	 *
	 * @param array<string,mixed> $repairs Repair plan.
	 * @return true|\WP_Error
	 */
	private function validate_media_content_reference_repair_permissions( array $repairs ) {
		foreach ( (array) ( $repairs['repairs'] ?? array() ) as $repair ) {
			$post_id = absint( is_array( $repair ) ? ( $repair['post_id'] ?? 0 ) : 0 );
			if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_reference_repair_permission_denied', __( 'You do not have permission to update a post that references this media item.', 'npcink-abilities-toolkit' ), array( 'status' => 403, 'post_id' => $post_id ) );
			}
		}
		return true;
	}

	/**
	 * Validates reviewed post-content reference repair expectations.
	 *
	 * @param array<string,mixed> $repairs Repair plan.
	 * @param array<string,mixed> $expectations Reviewed expectations.
	 * @return true|\WP_Error
	 */
	private function validate_media_content_reference_repair_expectations( array $repairs, array $expectations ) {
		if ( empty( $expectations ) ) {
			return true;
		}

		$actual_post_ids = array();
		foreach ( (array) ( $repairs['repairs'] ?? array() ) as $repair ) {
			$post_id = absint( is_array( $repair ) ? ( $repair['post_id'] ?? 0 ) : 0 );
			if ( $post_id > 0 ) {
				$actual_post_ids[] = $post_id;
			}
		}
		$actual_post_ids = array_values( array_unique( $actual_post_ids ) );
		sort( $actual_post_ids );
		$actual_post_count = absint( $repairs['post_count'] ?? count( $actual_post_ids ) );
		$actual_replacement_count = absint( $repairs['replacement_count'] ?? 0 );

		if ( array_key_exists( 'post_ids', $expectations ) ) {
			$expected_post_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $expectations['post_ids'] ) ) ) );
			sort( $expected_post_ids );
			if ( $expected_post_ids !== $actual_post_ids ) {
				return new \WP_Error(
					'npcink_abilities_toolkit_media_reference_repair_expectation_mismatch',
					__( 'The post-content media reference repair targets changed after review.', 'npcink-abilities-toolkit' ),
					array(
						'status'   => 409,
						'expected' => array( 'post_ids' => $expected_post_ids ),
						'actual'   => array( 'post_ids' => $actual_post_ids ),
					)
				);
			}
		}
		if ( array_key_exists( 'post_count', $expectations ) && absint( $expectations['post_count'] ) !== $actual_post_count ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_media_reference_repair_expectation_mismatch',
				__( 'The post-content media reference repair count changed after review.', 'npcink-abilities-toolkit' ),
				array(
					'status'   => 409,
					'expected' => array( 'post_count' => absint( $expectations['post_count'] ) ),
					'actual'   => array( 'post_count' => $actual_post_count ),
				)
			);
		}
		if ( array_key_exists( 'replacement_count', $expectations ) && absint( $expectations['replacement_count'] ) !== $actual_replacement_count ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_media_reference_repair_expectation_mismatch',
				__( 'The post-content media reference replacement count changed after review.', 'npcink-abilities-toolkit' ),
				array(
					'status'   => 409,
					'expected' => array( 'replacement_count' => absint( $expectations['replacement_count'] ) ),
					'actual'   => array( 'replacement_count' => $actual_replacement_count ),
				)
			);
		}

		return true;
	}

	/**
	 * Applies planned post-content media reference repairs.
	 *
	 * @param array<string,mixed> $repairs Repair plan.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function apply_media_content_reference_repairs( array $repairs ) {
		$updated_count = 0;
		foreach ( (array) ( $repairs['repairs'] ?? array() ) as $index => $repair ) {
			$repair = is_array( $repair ) ? $repair : array();
			$post_id = absint( $repair['post_id'] ?? 0 );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_media_reference_repair_post_missing', __( 'A post that references this media item was not found during repair.', 'npcink-abilities-toolkit' ), array( 'status' => 404, 'post_id' => $post_id ) );
			}
			$patch = $this->apply_patch_operations( (string) ( $post->post_content ?? '' ), (array) ( $repair['operations'] ?? array() ) );
			if ( is_wp_error( $patch ) ) {
				return $patch;
			}
			$after_content = (string) ( $patch['content'] ?? ( $post->post_content ?? '' ) );
			$changed = $after_content !== (string) ( $post->post_content ?? '' );
			if ( $changed ) {
				$result = wp_update_post( array( 'ID' => $post_id, 'post_content' => $after_content ), true );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				++$updated_count;
			}
			$repairs['repairs'][ $index ]['updated'] = $changed;
			$repairs['repairs'][ $index ]['content_length_after'] = strlen( $after_content );
			$repairs['repairs'][ $index ]['patch_preview'] = (array) ( $patch['patch_preview'] ?? array() );
			$repair_counts = $this->media_reference_repair_count_summary( $post_id, (array) ( $repair['operations'] ?? array() ), (array) ( $patch['patch_preview'] ?? array() ) );
			$repairs['repairs'][ $index ]['replacement_rule_count'] = absint( $repair_counts['replacement_rule_count'] ?? 0 );
			$repairs['repairs'][ $index ]['actual_replacement_count'] = absint( $repair_counts['actual_replacement_count'] ?? 0 );
			$repairs['repairs'][ $index ]['unmatched_rules'] = (array) ( $repair_counts['unmatched_rules'] ?? array() );
		}
		$repairs['applied'] = true;
		$repairs['updated_count'] = $updated_count;
		$repairs = $this->normalize_media_reference_repair_counts( $repairs );
		return $repairs;
	}

	/**
	 * Summarizes repair rule and actual replacement counts for one post.
	 *
	 * @param int                  $post_id Post id.
	 * @param array<int,mixed>     $operations Patch operations.
	 * @param array<int,mixed>     $patch_preview Patch preview rows.
	 * @return array<string,mixed>
	 */
	private function media_reference_repair_count_summary( $post_id, array $operations, array $patch_preview ) {
		$post_id = absint( $post_id );
		$actual_replacement_count = 0;
		$unmatched_rules = array();
		foreach ( $patch_preview as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$applied = absint( $row['applied'] ?? 0 );
			$actual_replacement_count += $applied;
			if ( 0 === $applied ) {
				$operation = is_array( $operations[ $index ] ?? null ) ? $operations[ $index ] : array();
				$unmatched_rules[] = array(
					'post_id'         => $post_id,
					'operation_index' => absint( $index ),
					'find'            => (string) ( $operation['find'] ?? ( $row['find'] ?? '' ) ),
				);
			}
		}

		return array(
			'replacement_rule_count'   => count( $operations ),
			'actual_replacement_count' => $actual_replacement_count,
			'unmatched_rules'          => $unmatched_rules,
		);
	}

	/**
	 * Recomputes top-level repair count fields from repair rows.
	 *
	 * @param array<string,mixed> $repairs Repair plan/result.
	 * @return array<string,mixed>
	 */
	private function normalize_media_reference_repair_counts( array $repairs ) {
		$replacement_rule_count = 0;
		$actual_replacement_count = 0;
		$unmatched_rules = array();
		foreach ( (array) ( $repairs['repairs'] ?? array() ) as $repair ) {
			if ( ! is_array( $repair ) ) {
				continue;
			}
			$replacement_rule_count += absint( $repair['replacement_rule_count'] ?? ( $repair['operation_count'] ?? 0 ) );
			$actual_replacement_count += absint( $repair['actual_replacement_count'] ?? 0 );
			foreach ( (array) ( $repair['unmatched_rules'] ?? array() ) as $unmatched_rule ) {
				if ( is_array( $unmatched_rule ) ) {
					$unmatched_rules[] = $unmatched_rule;
				}
			}
		}
		$repairs['replacement_rule_count'] = $replacement_rule_count;
		$repairs['actual_replacement_count'] = $actual_replacement_count;
		$repairs['unmatched_rules'] = $unmatched_rules;

		return $repairs;
	}

	/**
	 * Builds a compact verification summary after a media file operation.
	 *
	 * @param int                 $attachment_id Attachment id.
	 * @param array<string,mixed> $after Expected after state.
	 * @param array<string,mixed> $rollback_backup Backup available for rollback.
	 * @param array<string,mixed> $content_reference_repairs Applied reference repair result.
	 * @return array<string,mixed>
	 */
	private function media_file_operation_verification( $attachment_id, array $after, array $rollback_backup, array $content_reference_repairs = array() ) {
		$attachment_id = absint( $attachment_id );
		$current = $this->current_media_file_state( $attachment_id );
		$current = is_wp_error( $current ) ? array() : $current;
		$backup_relative = $this->normalize_media_relative_file( (string) ( $rollback_backup['relative_file'] ?? '' ) );
		$backup_path = '' !== $backup_relative ? $this->media_uploads_path_for_relative_file( $backup_relative ) : '';
		$post_references = array();
		foreach ( (array) ( $content_reference_repairs['repairs'] ?? array() ) as $repair ) {
			if ( ! is_array( $repair ) ) {
				continue;
			}
			$post_id = absint( $repair['post_id'] ?? 0 );
			$post_content = '';
			if ( $post_id > 0 && function_exists( 'get_post' ) ) {
				$post = get_post( $post_id );
				$post_content = is_object( $post ) ? (string) ( $post->post_content ?? '' ) : '';
			}
			$old_url_absent = true;
			$new_url_present = true;
			foreach ( (array) ( $repair['operations'] ?? array() ) as $operation ) {
				if ( ! is_array( $operation ) ) {
					continue;
				}
				$find = (string) ( $operation['find'] ?? '' );
				$replace = (string) ( $operation['replace'] ?? '' );
				if ( '' !== $find && false !== strpos( $post_content, $find ) ) {
					$old_url_absent = false;
				}
				if ( '' !== $replace && false === strpos( $post_content, $replace ) ) {
					$new_url_present = false;
				}
			}
			$post_references[] = array(
				'post_id'                  => $post_id,
				'updated'                  => (bool) ( $repair['updated'] ?? false ),
				'old_url_absent'           => $old_url_absent,
				'new_url_present'          => $new_url_present,
				'replacement_rule_count'   => absint( $repair['replacement_rule_count'] ?? ( $repair['operation_count'] ?? 0 ) ),
				'actual_replacement_count' => absint( $repair['actual_replacement_count'] ?? 0 ),
				'unmatched_rules'          => (array) ( $repair['unmatched_rules'] ?? array() ),
			);
		}

		return array(
			'attachment_id'              => $attachment_id,
			'media_current_file'         => (string) ( $current['relative_file'] ?? '' ),
			'media_expected_file'        => (string) ( $after['relative_file'] ?? '' ),
			'media_file_matches_expected' => (string) ( $current['relative_file'] ?? '' ) === (string) ( $after['relative_file'] ?? '' ),
			'media_mime_type'            => (string) ( $current['mime_type'] ?? '' ),
			'expected_mime_type'         => (string) ( $after['mime_type'] ?? '' ),
			'media_mime_type_matches_expected' => (string) ( $current['mime_type'] ?? '' ) === (string) ( $after['mime_type'] ?? '' ),
			'post_references_verified'   => $post_references,
			'content_reference_repair_applied' => (bool) ( $content_reference_repairs['applied'] ?? false ),
			'content_reference_updated_count' => absint( $content_reference_repairs['updated_count'] ?? 0 ),
			'content_reference_actual_replacement_count' => absint( $content_reference_repairs['actual_replacement_count'] ?? 0 ),
			'backup_available'           => '' !== $backup_path && is_readable( $backup_path ),
			'rollback_available'         => '' !== $backup_path && is_readable( $backup_path ),
		);
	}

	/**
	 * Builds exact old->new reference pairs for a replacement plan.
	 *
	 * @param array<string,mixed> $plan Replacement plan.
	 * @return array<int,array{old:string,new:string}>
	 */
	private function media_content_reference_pairs_for_plan( array $plan ) {
		$before = is_array( $plan['before'] ?? null ) ? $plan['before'] : array();
		$after = is_array( $plan['after'] ?? null ) ? $plan['after'] : array();
		$old_relative = $this->normalize_media_relative_file( (string) ( $before['relative_file'] ?? '' ) );
		$new_relative = $this->normalize_media_relative_file( (string) ( $after['relative_file'] ?? '' ) );
		$old_url = esc_url_raw( (string) ( $before['url'] ?? '' ) );
		$new_url = esc_url_raw( (string) ( $after['url'] ?? '' ) );
		$old_path = $this->media_content_reference_url_path( $old_url );
		$new_path = $this->media_content_reference_url_path( $new_url );
		$pairs = array(
			array( 'old' => $old_url, 'new' => $new_url ),
			array( 'old' => $old_path, 'new' => $new_path ),
			array( 'old' => $old_relative, 'new' => $new_relative ),
		);

		$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
		$metadata = is_array( $current['metadata'] ?? null ) ? $current['metadata'] : array();
		foreach ( $this->media_content_reference_source_relative_files( $metadata, $old_relative ) as $source_relative ) {
			$source_url = $this->media_url_for_relative_file( $source_relative );
			$source_path = $this->media_content_reference_url_path( $source_url );
			$pairs[] = array( 'old' => $source_url, 'new' => $new_url );
			$pairs[] = array( 'old' => $source_path, 'new' => $new_path );
			$pairs[] = array( 'old' => $source_relative, 'new' => $new_relative );
		}
		$sizes = is_array( $metadata['sizes'] ?? null ) ? $metadata['sizes'] : array();
		$old_dir = dirname( $old_relative );
		$old_dir = '.' !== $old_dir ? trim( $old_dir, '/' ) : '';
		foreach ( $sizes as $size ) {
			$size = is_array( $size ) ? $size : array();
			$file = $this->sanitize_media_file_name( (string) ( $size['file'] ?? '' ) );
			if ( '' === $file ) {
				continue;
			}
			$size_relative = '' !== $old_dir ? $old_dir . '/' . $file : $file;
			$size_url = $this->media_url_for_relative_file( $size_relative );
			$size_path = $this->media_content_reference_url_path( $size_url );
			$pairs[] = array( 'old' => $size_url, 'new' => $new_url );
			$pairs[] = array( 'old' => $size_path, 'new' => $new_path );
			$pairs[] = array( 'old' => $size_relative, 'new' => $new_relative );
		}

		return $this->merge_media_content_reference_pairs( $pairs, array() );
	}

	/**
	 * Adds old sized variant references found in content even when not in metadata.
	 *
	 * @param string              $content Post content.
	 * @param array<string,mixed> $plan Replacement plan.
	 * @return array<int,array{old:string,new:string}>
	 */
	private function media_content_reference_dynamic_sized_pairs( $content, array $plan ) {
		$before = is_array( $plan['before'] ?? null ) ? $plan['before'] : array();
		$after = is_array( $plan['after'] ?? null ) ? $plan['after'] : array();
		$old_relative = $this->normalize_media_relative_file( (string) ( $before['relative_file'] ?? '' ) );
		$new_relative = $this->normalize_media_relative_file( (string) ( $after['relative_file'] ?? '' ) );
		$new_url = esc_url_raw( (string) ( $after['url'] ?? '' ) );
		$new_path = $this->media_content_reference_url_path( $new_url );
		$current = is_array( $plan['_current'] ?? null ) ? $plan['_current'] : array();
		$metadata = is_array( $current['metadata'] ?? null ) ? $current['metadata'] : array();
		if ( '' === $new_url ) {
			return array();
		}
		$pairs = array();
		foreach ( $this->media_content_reference_source_relative_files( $metadata, $old_relative ) as $source_relative ) {
			$old_basename = basename( $source_relative );
			$stem = preg_replace( '/\.[^.]+$/', '', $old_basename );
			$extension = pathinfo( $old_basename, PATHINFO_EXTENSION );
			if ( '' === (string) $stem || '' === (string) $extension ) {
				continue;
			}
			$pattern = '/' . preg_quote( (string) $stem, '/' ) . '-[0-9]{2,5}x[0-9]{2,5}\.' . preg_quote( (string) $extension, '/' ) . '/u';
			if ( ! preg_match_all( $pattern, (string) $content, $matches ) ) {
				continue;
			}
			$old_dir = dirname( $source_relative );
			$old_dir = '.' !== $old_dir ? trim( $old_dir, '/' ) : '';
			foreach ( array_unique( (array) ( $matches[0] ?? array() ) ) as $sized_basename ) {
				$sized_basename = $this->sanitize_media_file_name( (string) $sized_basename );
				if ( '' === $sized_basename ) {
					continue;
				}
				$size_relative = '' !== $old_dir ? $old_dir . '/' . $sized_basename : $sized_basename;
				$size_url = $this->media_url_for_relative_file( $size_relative );
				$size_path = $this->media_content_reference_url_path( $size_url );
				$pairs[] = array( 'old' => $size_url, 'new' => $new_url );
				$pairs[] = array( 'old' => $size_path, 'new' => $new_path );
				$pairs[] = array( 'old' => $size_relative, 'new' => $new_relative );
			}
		}
		return $pairs;
	}

	/**
	 * Returns original/current uploads-relative files that may appear in post content.
	 *
	 * @param array<string,mixed> $metadata Attachment metadata.
	 * @param string              $current_relative Current uploads-relative file.
	 * @return array<int,string>
	 */
	private function media_content_reference_source_relative_files( array $metadata, $current_relative ) {
		$current_relative = $this->normalize_media_relative_file( $current_relative );
		$base_dir = '' !== $current_relative ? dirname( $current_relative ) : '';
		$base_dir = '.' !== $base_dir ? trim( $base_dir, '/' ) : '';
		$candidates = array(
			$current_relative,
			(string) ( $metadata['file'] ?? '' ),
		);
		$original_image = $this->sanitize_media_file_name( (string) ( $metadata['original_image'] ?? '' ) );
		if ( '' !== $original_image ) {
			$candidates[] = false === strpos( $original_image, '/' ) && '' !== $base_dir ? $base_dir . '/' . $original_image : $original_image;
		}
		foreach ( $candidates as $candidate ) {
			$variant = $this->media_content_reference_without_unique_suffix( $candidate );
			if ( '' !== $variant ) {
				$candidates[] = $variant;
			}
		}

		$files = array();
		foreach ( $candidates as $candidate ) {
			$file = $this->normalize_media_relative_file( (string) $candidate );
			if ( '' !== $file ) {
				$files[ $file ] = $file;
			}
		}
		return array_values( $files );
	}

	/**
	 * Infers the pre-unique-suffix relative file when WordPress stored "-1".
	 *
	 * @param string $relative_file Uploads-relative file.
	 * @return string
	 */
	private function media_content_reference_without_unique_suffix( $relative_file ) {
		$relative_file = $this->normalize_media_relative_file( $relative_file );
		$basename = basename( $relative_file );
		if ( ! preg_match( '/^(.+)-[0-9]+(\.[^.]+)$/', $basename, $matches ) ) {
			return '';
		}
		$dir = dirname( $relative_file );
		$dir = '.' !== $dir ? trim( $dir, '/' ) : '';
		$file = $this->sanitize_media_file_name( (string) $matches[1] . (string) $matches[2] );
		if ( '' === $file ) {
			return '';
		}
		return '' !== $dir ? $dir . '/' . $file : $file;
	}

	/**
	 * Merges old->new reference pairs.
	 *
	 * @param array<int,array<string,string>> $primary Primary pairs.
	 * @param array<int,array<string,string>> $secondary Secondary pairs.
	 * @return array<int,array{old:string,new:string}>
	 */
	private function merge_media_content_reference_pairs( array $primary, array $secondary ) {
		$merged = array();
		foreach ( array_merge( $primary, $secondary ) as $pair ) {
			$old = trim( (string) ( is_array( $pair ) ? ( $pair['old'] ?? '' ) : '' ) );
			$new = trim( (string) ( is_array( $pair ) ? ( $pair['new'] ?? '' ) : '' ) );
			if ( '' === $old || '' === $new || $old === $new ) {
				continue;
			}
			$key = $old . "\n" . $new;
			$merged[ $key ] = array(
				'old' => $old,
				'new' => $new,
			);
		}
		return array_values( $merged );
	}

	/**
	 * Returns bounded candidate posts likely to contain old media references.
	 *
	 * @param int           $attachment_id Attachment id.
	 * @param array<string> $needles Search strings.
	 * @param int           $limit Candidate limit.
	 * @return array<int,object>
	 */
	private function media_content_reference_candidate_posts( $attachment_id, array $needles, $limit ) {
		$attachment_id = absint( $attachment_id );
		$limit = max( 1, min( 150, absint( $limit ) ) );
		if ( isset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ) ) {
			return get_posts( array( 'posts_per_page' => $limit ) );
		}

		$candidates = array();
		foreach ( array_slice( array_values( array_filter( array_unique( $needles ) ) ), 0, 25 ) as $needle ) {
			$needle = trim( (string) $needle );
			if ( '' === $needle ) {
				continue;
			}
			foreach (
				get_posts(
					array(
						'post_type'      => 'any',
						'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
						'posts_per_page' => $limit,
						'orderby'        => 'ID',
						'order'          => 'DESC',
						's'              => $needle,
					)
				) as $post
			) {
				if ( is_object( $post ) && 'attachment' !== (string) ( $post->post_type ?? '' ) ) {
					$candidates[ absint( $post->ID ?? 0 ) ] = $post;
				}
				if ( count( $candidates ) >= $limit ) {
					break 2;
				}
			}
		}
		if ( ! empty( $candidates ) ) {
			return array_values( $candidates );
		}

		return get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page' => $limit,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				's'              => 'wp-image-' . $attachment_id,
			)
		);
	}

	/**
	 * Builds bounded search needles for candidate post lookup.
	 *
	 * @param int                                      $attachment_id Attachment id.
	 * @param array<string,mixed>                      $plan Replacement plan.
	 * @param array<int,array{old:string,new:string}> $pairs Reference pairs.
	 * @return array<int,string>
	 */
	private function media_content_reference_needles( $attachment_id, array $plan, array $pairs ) {
		$needles = array( 'wp-image-' . absint( $attachment_id ), '"id":' . absint( $attachment_id ), 'data-id="' . absint( $attachment_id ) . '"' );
		foreach ( $pairs as $pair ) {
			$needles[] = (string) ( $pair['old'] ?? '' );
		}
		$before = is_array( $plan['before'] ?? null ) ? $plan['before'] : array();
		$old_relative = $this->normalize_media_relative_file( (string) ( $before['relative_file'] ?? '' ) );
		$old_stem = preg_replace( '/\.[^.]+$/', '', basename( $old_relative ) );
		if ( strlen( (string) $old_stem ) >= 4 ) {
			$needles[] = (string) $old_stem;
		}
		return array_values( array_filter( array_unique( array_map( 'strval', $needles ) ) ) );
	}

	/**
	 * Returns the path component of a URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function media_content_reference_url_path( $url ) {
		$path = wp_parse_url( (string) $url, PHP_URL_PATH );
		return is_string( $path ) ? $path : '';
	}

	/**
	 * Applies exact patch operations recursively to one setting value.
	 *
	 * @param mixed        $value Original value.
	 * @param array<mixed> $operations Patch operations.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function apply_patch_operations_to_value( $value, array $operations ) {
		if ( is_string( $value ) && $this->looks_serialized_setting_string( $value ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_serialized_setting_patch_blocked', __( 'Raw serialized setting strings require manual review.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$operations = array_values( $operations );
		if ( empty( $operations ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_patch_operations_required', __( 'Operations are required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		foreach ( $operations as $operation ) {
			$op = sanitize_key( (string) ( is_array( $operation ) ? ( $operation['op'] ?? '' ) : '' ) );
			if ( 'replace' !== $op ) {
				return new \WP_Error( 'npcink_abilities_toolkit_setting_patch_operation_invalid', __( 'Setting patch operations must be exact replacements.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
		}

		$impact_ranges = array();
		$patch_preview = array();
		$after = $this->replace_in_setting_value( $value, $operations, '', $impact_ranges, $patch_preview );
		if ( is_wp_error( $after ) ) {
			return $after;
		}
		return array(
			'value'         => $after,
			'impact_ranges' => $impact_ranges,
			'patch_preview' => $patch_preview,
		);
	}

	/**
	 * Replaces strings recursively in one setting value.
	 *
	 * @param mixed                $value Value.
	 * @param array<int,mixed>     $operations Operations.
	 * @param string               $path Value path.
	 * @param array<int,mixed>     $impact_ranges Impact rows.
	 * @param array<int,mixed>     $patch_preview Preview rows.
	 * @return mixed|\WP_Error
	 */
	private function replace_in_setting_value( $value, array $operations, $path, array &$impact_ranges, array &$patch_preview ) {
		if ( is_string( $value ) ) {
			if ( $this->looks_serialized_setting_string( $value ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_serialized_setting_patch_blocked', __( 'Raw serialized setting strings require manual review.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
			$result = $this->apply_patch_operations( $value, $operations );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			foreach ( (array) ( $result['impact_ranges'] ?? array() ) as $row ) {
				$row = is_array( $row ) ? $row : array();
				$row['path'] = $path;
				$impact_ranges[] = $row;
			}
			foreach ( (array) ( $result['patch_preview'] ?? array() ) as $row ) {
				$row = is_array( $row ) ? $row : array();
				$row['path'] = $path;
				$patch_preview[] = $row;
			}
			return $result['content'] ?? $value;
		}
		if ( is_array( $value ) ) {
			$next = array();
			foreach ( $value as $key => $child ) {
				$child_path = '' === $path ? (string) $key : $path . '.' . (string) $key;
				$next_child = $this->replace_in_setting_value( $child, $operations, $child_path, $impact_ranges, $patch_preview );
				if ( is_wp_error( $next_child ) ) {
					return $next_child;
				}
				$next[ $key ] = $next_child;
			}
			return $next;
		}
		if ( is_object( $value ) ) {
			$next = clone $value;
			foreach ( get_object_vars( $value ) as $key => $child ) {
				$child_path = '' === $path ? (string) $key : $path . '.' . (string) $key;
				$next_child = $this->replace_in_setting_value( $child, $operations, $child_path, $impact_ranges, $patch_preview );
				if ( is_wp_error( $next_child ) ) {
					return $next_child;
				}
				$next->{$key} = $next_child;
			}
			return $next;
		}
		return $value;
	}

	/**
	 * Reads one patchable setting value.
	 *
	 * @param string $target_type option|theme_mod.
	 * @param string $target_name Setting name.
	 * @return mixed
	 */
	private function get_patchable_setting_value( $target_type, $target_name ) {
		if ( 'theme_mod' === $target_type ) {
			return function_exists( 'get_theme_mod' ) ? get_theme_mod( $target_name, null ) : ( $GLOBALS['npcink_abilities_toolkit_unit_theme_mods'][ $target_name ] ?? null );
		}
		return function_exists( 'get_option' ) ? get_option( $target_name, null ) : ( $GLOBALS['npcink_abilities_toolkit_unit_options'][ $target_name ] ?? null );
	}

	/**
	 * Builds stable preview text for a setting value.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function setting_value_preview_text( $value ) {
		if ( is_string( $value ) || is_numeric( $value ) || is_bool( $value ) ) {
			return (string) $value;
		}
		if ( is_array( $value ) || is_object( $value ) ) {
			$encoded = wp_json_encode( $value, defined( 'JSON_UNESCAPED_SLASHES' ) ? JSON_UNESCAPED_SLASHES : 0 );
			return is_string( $encoded ) ? $encoded : '';
		}
		return '';
	}

	/**
	 * Returns a setting value type label.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function setting_value_type( $value ) {
		if ( is_array( $value ) ) {
			return 'array';
		}
		if ( is_object( $value ) ) {
			return 'object';
		}
		return gettype( $value );
	}

	/**
	 * Detects raw serialized strings.
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function looks_serialized_setting_string( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return false;
		}
		if ( function_exists( 'is_serialized' ) && is_serialized( $value ) ) {
			return true;
		}
		return (bool) preg_match( '/^(a|O|s|i|b|d):[0-9]+[:;]/', $value );
	}

	/**
	 * Sanitizes one Gutenberg attribute key without destroying camelCase names.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	private function sanitize_block_attr_key( $key ) {
		$key = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $key );
		return is_string( $key ) ? $key : '';
	}

	/**
	 * Sanitizes block attrs recursively.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $depth Recursion depth.
	 * @return mixed
	 */
	private function sanitize_block_attrs( $value, $depth = 0 ) {
		$depth = absint( $depth );
		if ( $depth >= 5 ) {
			return null;
		}
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}
		if ( is_array( $value ) ) {
			$normalized = array();
			foreach ( $value as $key => $item ) {
				$normalized_key = is_int( $key ) ? $key : $this->sanitize_block_attr_key( (string) $key );
				if ( '' === $normalized_key ) {
					continue;
				}
				$normalized[ $normalized_key ] = $this->sanitize_block_attrs( $item, $depth + 1 );
			}
			return $normalized;
		}
		if ( is_object( $value ) ) {
			return $this->sanitize_block_attrs( (array) $value, $depth + 1 );
		}
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Normalizes one block list payload recursively.
	 *
	 * @param mixed        $blocks Raw block list.
	 * @param array<mixed> $errors Validation error rows.
	 * @param string       $path Path hint.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_blocks_input( $blocks, &$errors, $path = 'root' ) {
		$blocks     = is_array( $blocks ) ? array_values( $blocks ) : array();
		$errors     = is_array( $errors ) ? $errors : array();
		$normalized = array();
		foreach ( $blocks as $index => $block ) {
			$current_path = $path . '.' . $index;
			if ( ! is_array( $block ) ) {
				$errors[] = array( 'path' => $current_path, 'error' => 'block_must_be_object' );
				continue;
			}
			$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? $block['block_name'] ?? '' ) );
			if ( '' === $block_name ) {
				$errors[] = array( 'path' => $current_path . '.blockName', 'error' => 'block_name_required' );
				continue;
			}
			$inner_blocks_errors = array();
			$inner_blocks        = $this->normalize_blocks_input( $block['innerBlocks'] ?? array(), $inner_blocks_errors, $current_path . '.innerBlocks' );
			if ( ! empty( $inner_blocks_errors ) ) {
				$errors = array_merge( $errors, $inner_blocks_errors );
			}
			$normalized[] = array(
				'blockName'    => $block_name,
				'attrs'        => is_array( $block['attrs'] ?? null ) ? $this->sanitize_block_attrs( $block['attrs'], 0 ) : array(),
				'innerHTML'    => (string) ( $block['innerHTML'] ?? $block['inner_html'] ?? '' ),
				'innerBlocks'  => $inner_blocks,
				'innerContent' => $this->normalize_block_inner_content( $block, count( $inner_blocks ) ),
			);
		}
		return $normalized;
	}

	/**
	 * Counts a block tree recursively.
	 *
	 * @param mixed $blocks Block list.
	 * @return int
	 */
	private function count_blocks_recursive( $blocks ) {
		$blocks = is_array( $blocks ) ? $blocks : array();
		$total  = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$total++;
			$total += $this->count_blocks_recursive( $block['innerBlocks'] ?? array() );
		}
		return $total;
	}

	/**
	 * Serializes blocks using WordPress' native parsed-block format.
	 *
	 * @param array<mixed> $blocks Normalized blocks.
	 * @return string
	 */
	private function serialize_blocks_native( array $blocks ) {
		if ( function_exists( 'serialize_blocks' ) ) {
			return serialize_blocks( $blocks );
		}

		$serialized = '';
		foreach ( $blocks as $block ) {
			$serialized .= is_array( $block ) ? $this->serialize_block_fallback( $block ) : '';
		}
		return $serialized;
	}

	/**
	 * Normalizes parsed-block innerContent chunks.
	 *
	 * @param array<string,mixed> $block Raw block.
	 * @param int                 $inner_block_count Number of normalized inner blocks.
	 * @return array<int,string|null>
	 */
	private function normalize_block_inner_content( array $block, $inner_block_count ) {
		$raw_inner_content = $block['innerContent'] ?? ( $block['inner_content'] ?? null );
		if ( is_array( $raw_inner_content ) ) {
			$inner_content = array();
			foreach ( $raw_inner_content as $chunk ) {
				$inner_content[] = null === $chunk ? null : (string) $chunk;
			}
			return $inner_content;
		}

		$inner_html = (string) ( $block['innerHTML'] ?? ( $block['inner_html'] ?? '' ) );
		if ( $inner_block_count <= 0 ) {
			return array( $inner_html );
		}

		return array_merge( array( $inner_html ), array_fill( 0, absint( $inner_block_count ), null ) );
	}

	/**
	 * Fallback for environments without WordPress' serialize_block().
	 *
	 * @param array<string,mixed> $block Normalized parsed block.
	 * @return string
	 */
	private function serialize_block_fallback( array $block ) {
		$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
		$block_name = preg_replace( '/[^A-Za-z0-9_\/-]/', '', $block_name );
		$block_name = is_string( $block_name ) ? $block_name : '';
		$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? array_values( $block['innerBlocks'] ) : array();
		$inner_content = is_array( $block['innerContent'] ?? null ) ? array_values( $block['innerContent'] ) : array( (string) ( $block['innerHTML'] ?? '' ) );
		$content = '';
		$inner_block_index = 0;
		foreach ( $inner_content as $chunk ) {
			if ( is_string( $chunk ) ) {
				$content .= $chunk;
				continue;
			}
			if ( isset( $inner_blocks[ $inner_block_index ] ) && is_array( $inner_blocks[ $inner_block_index ] ) ) {
				$content .= $this->serialize_block_fallback( $inner_blocks[ $inner_block_index ] );
			}
			++$inner_block_index;
		}

		if ( '' === $block_name || 'core/freeform' === $block_name ) {
			return $content;
		}

		$comment_block_name = 0 === strpos( $block_name, 'core/' ) ? substr( $block_name, 5 ) : $block_name;
		$attrs              = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		$attrs_json         = '';
		if ( ! empty( $attrs ) ) {
			$encoded = wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( is_string( $encoded ) && '' !== $encoded && '{}' !== $encoded ) {
				$attrs_json = ' ' . $encoded;
			}
		}
		return '<!-- wp:' . $comment_block_name . $attrs_json . ' -->' . $content . '<!-- /wp:' . $comment_block_name . ' -->';
	}

	/**
	 * Returns normalized term IDs attached to an object.
	 *
	 * @param int    $object_id Object id.
	 * @param string $taxonomy Taxonomy id.
	 * @return array<int,int>|\WP_Error
	 */
	private function object_term_ids( $object_id, $taxonomy ) {
		$term_ids = wp_get_object_terms(
			absint( $object_id ),
			$taxonomy,
			array( 'fields' => 'ids' )
		);
		if ( is_wp_error( $term_ids ) ) {
			return $term_ids;
		}
		return array_values( array_unique( array_filter( array_map( 'absint', (array) $term_ids ) ) ) );
	}

	/**
	 * Collects normalized term rows for summary payloads.
	 *
	 * @param string     $taxonomy Taxonomy id.
	 * @param array<int> $term_ids Term ids.
	 * @return array<int,array<string,mixed>>
	 */
	private function collect_term_rows( $taxonomy, array $term_ids ) {
		$rows = array();
		foreach ( $term_ids as $term_id ) {
			$term_id = absint( $term_id );
			if ( $term_id <= 0 ) {
				continue;
			}
			$term = get_term( $term_id, $taxonomy );
			if ( ! $term || is_wp_error( $term ) || ! is_object( $term ) || empty( $term->term_id ) ) {
				continue;
			}
			$rows[] = array(
				'term_id' => absint( $term->term_id ),
				'slug'    => sanitize_title( (string) ( $term->slug ?? '' ) ),
				'name'    => sanitize_text_field( (string) ( $term->name ?? '' ) ),
			);
		}
		return $rows;
	}

	/**
	 * Resolves term IDs from explicit IDs and labels.
	 *
	 * @param string       $taxonomy Taxonomy id.
	 * @param array<mixed> $term_ids Explicit term ids.
	 * @param array<mixed> $terms Term labels.
	 * @param bool         $create_missing Whether missing labels may be created during commit.
	 * @param bool         $dry_run Whether this is a dry-run.
	 * @return array{term_ids:array<int,int>,missing:array<int,string>}|\WP_Error
	 */
	private function resolve_term_ids_for_assignment( $taxonomy, array $term_ids, array $terms, $create_missing, $dry_run ) {
		$resolved = array();
		$missing  = array();
		foreach ( $term_ids as $term_id ) {
			$term_id = absint( $term_id );
			if ( $term_id <= 0 ) {
				continue;
			}
			$term = get_term( $term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) && is_object( $term ) && ! empty( $term->term_id ) ) {
				$resolved[] = absint( $term->term_id );
			}
		}

		foreach ( $terms as $raw_term ) {
			$term_label = sanitize_text_field( (string) $raw_term );
			if ( '' === $term_label ) {
				continue;
			}
			$term_slug = sanitize_title( $term_label );
			$term      = get_term_by( 'slug', $term_slug, $taxonomy );
			if ( ! is_object( $term ) || empty( $term->term_id ) ) {
				$term = get_term_by( 'name', $term_label, $taxonomy );
			}
			if ( is_object( $term ) && ! empty( $term->term_id ) ) {
				$resolved[] = absint( $term->term_id );
				continue;
			}
			if ( ! $create_missing ) {
				continue;
			}
			$missing[] = $term_label;
			if ( $dry_run ) {
				continue;
			}
			$cap_check = $this->check_taxonomy_capability( $taxonomy, 'manage_terms' );
			if ( is_wp_error( $cap_check ) ) {
				return $cap_check;
			}
			$created = wp_insert_term( $term_label, $taxonomy, array( 'slug' => $term_slug ) );
			if ( is_wp_error( $created ) ) {
				return $created;
			}
			$created_term_id = absint( $created['term_id'] ?? 0 );
			if ( $created_term_id > 0 ) {
				$resolved[] = $created_term_id;
			}
		}

		return array(
			'term_ids' => array_values( array_unique( array_filter( array_map( 'absint', $resolved ) ) ) ),
			'missing'  => array_values( array_unique( $missing ) ),
		);
	}

	/**
	 * Computes the next term IDs for a taxonomy assignment operation.
	 *
	 * @param array<int> $current_term_ids Current term ids.
	 * @param array<int> $resolved_term_ids Target term ids.
	 * @param string     $mode replace|append|remove.
	 * @return array<int,int>
	 */
	private function compute_term_update( array $current_term_ids, array $resolved_term_ids, $mode ) {
		$current_term_ids  = array_values( array_unique( array_filter( array_map( 'absint', $current_term_ids ) ) ) );
		$resolved_term_ids = array_values( array_unique( array_filter( array_map( 'absint', $resolved_term_ids ) ) ) );
		if ( 'append' === $mode ) {
			return array_values( array_unique( array_merge( $current_term_ids, $resolved_term_ids ) ) );
		}
		if ( 'remove' === $mode ) {
			return array_values( array_diff( $current_term_ids, $resolved_term_ids ) );
		}
		return $resolved_term_ids;
	}

	/**
	 * Returns a valid taxonomy id.
	 *
	 * @param mixed $taxonomy Raw taxonomy.
	 * @return string|\WP_Error
	 */
	private function valid_taxonomy( $taxonomy ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_invalid', __( 'Taxonomy is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		return $taxonomy;
	}

	/**
	 * Checks a taxonomy capability.
	 *
	 * @param string $taxonomy Taxonomy id.
	 * @param string $cap_key Capability key on taxonomy object.
	 * @return true|\WP_Error
	 */
	private function check_taxonomy_capability( $taxonomy, $cap_key ) {
		$taxonomy_object = get_taxonomy( $taxonomy );
		$capability = is_object( $taxonomy_object ) && ! empty( $taxonomy_object->cap->{$cap_key} )
			? sanitize_key( (string) $taxonomy_object->cap->{$cap_key} )
			: 'manage_categories';
		if ( '' !== $capability && ! current_user_can( $capability ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission for this taxonomy operation.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Returns one term object.
	 *
	 * @param int    $term_id Term id.
	 * @param string $taxonomy Taxonomy id.
	 * @return object|\WP_Error
	 */
	private function get_term_object( $term_id, $taxonomy ) {
		$term_id = absint( $term_id );
		if ( $term_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_term_invalid', __( 'Term ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_term_not_found', __( 'Term was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		return $term;
	}

	/**
	 * Returns post edit link.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function edit_link( $post_id ) {
		return function_exists( 'get_edit_post_link' ) ? (string) get_edit_post_link( $post_id, 'raw' ) : '';
	}

	/**
	 * Returns post status.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function post_status( $post_id ) {
		return function_exists( 'get_post_status' ) ? sanitize_key( (string) get_post_status( $post_id ) ) : '';
	}
}
