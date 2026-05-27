<?php
/**
 * Core WordPress host-governed write ability package.
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
 * Registers low-risk WordPress write abilities migrated from the Magick AI plugin.
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
			'magick-ai-write',
			array(
				'label'       => __( 'WordPress Write Abilities', 'magick-ai-abilities' ),
				'description' => __( 'Host-governed WordPress write abilities with dry-run previews and external approval.', 'magick-ai-abilities' ),
			)
		);

		foreach ( $this->definitions() as $ability_id => $definition ) {
			$definition['source']                    = 'official';
			$definition['project_to_magick_catalog'] = true;
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
			'magick-ai/set-post-slug'      => array(
				'label'           => __( 'Set Post Slug', 'magick-ai-abilities' ),
				'description'     => __( 'Updates a post slug after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/set-post-author'    => array(
				'label'           => __( 'Set Post Author', 'magick-ai-abilities' ),
				'description'     => __( 'Updates a post author after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/set-post-template'  => array(
				'label'           => __( 'Set Post Template', 'magick-ai-abilities' ),
				'description'     => __( 'Updates a post template after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/set-post-format'    => array(
				'label'           => __( 'Set Post Format', 'magick-ai-abilities' ),
				'description'     => __( 'Updates a post format after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/create-term'        => array(
				'label'           => __( 'Create Term', 'magick-ai-abilities' ),
				'description'     => __( 'Creates a taxonomy term after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/update-term'        => array(
				'label'           => __( 'Update Term', 'magick-ai-abilities' ),
				'description'     => __( 'Updates a taxonomy term after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/set-post-terms'     => array(
				'label'           => __( 'Set Post Terms', 'magick-ai-abilities' ),
				'description'     => __( 'Updates a post taxonomy terms in replace, append, or remove mode after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/update-media-details' => array(
				'label'           => __( 'Update Media Details', 'magick-ai-abilities' ),
				'description'     => __( 'Updates attachment title, alt, caption, description, and attribution fields after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/approve-comment'      => array(
				'label'           => __( 'Approve Comment', 'magick-ai-abilities' ),
				'description'     => __( 'Approves one comment after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			'magick-ai/reply-comment'        => array(
				'label'           => __( 'Reply Comment', 'magick-ai-abilities' ),
				'description'     => __( 'Replies to one comment as the current user after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
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
			return new \WP_Error( 'magick_ai_abilities_slug_required', __( 'Slug is required.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/set-post-slug', $input );
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
			return new \WP_Error( 'magick_ai_abilities_author_invalid', __( 'Author ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		if ( function_exists( 'get_userdata' ) && ! get_userdata( $author_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_author_invalid', __( 'Author ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/set-post-author', $input );
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
			return new \WP_Error( 'magick_ai_abilities_template_required', __( 'Template is required.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/set-post-template', $input );
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
			return new \WP_Error( 'magick_ai_abilities_post_format_invalid', __( 'Post format is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/set-post-format', $input );
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
			return new \WP_Error( 'magick_ai_abilities_term_name_required', __( 'Term name is required.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$slug        = sanitize_title( (string) ( $input['slug'] ?? '' ) );
		$description = sanitize_textarea_field( (string) ( $input['description'] ?? '' ) );
		$parent      = absint( $input['parent'] ?? 0 );
		if ( $parent > 0 && is_wp_error( $this->get_term_object( $parent, $taxonomy ) ) ) {
			return new \WP_Error( 'magick_ai_abilities_parent_term_not_found', __( 'Parent term was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/create-term', $input );
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
				return new \WP_Error( 'magick_ai_abilities_parent_term_not_found', __( 'Parent term was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
			}
			$update_args['parent'] = $parent;
			$changes['parent'] = array( 'before' => absint( $term->parent ?? 0 ), 'after' => $parent );
		}
		if ( empty( $update_args ) ) {
			return new \WP_Error( 'magick_ai_abilities_no_changes', __( 'No update fields were provided.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/update-term', $input );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/set-post-terms', $input );
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
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to edit this attachment.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$current = $this->media_snapshot( $attachment );
		$next    = $current;
		$changes = array();
		foreach ( array( 'title', 'alt', 'caption', 'description', 'source_page_url', 'photographer_name', 'attribution_text', 'copyright_notice' ) as $field ) {
			if ( ! array_key_exists( $field, $input ) ) {
				continue;
			}
			$value = 'source_page_url' === $field ? esc_url_raw( (string) $input[ $field ] ) : sanitize_textarea_field( (string) $input[ $field ] );
			if ( in_array( $field, array( 'title', 'alt', 'photographer_name' ), true ) ) {
				$value = sanitize_text_field( (string) $input[ $field ] );
			}
			$next[ $field ]    = $value;
			$changes[ $field ] = array( 'before' => (string) ( $current[ $field ] ?? '' ), 'after' => $value );
		}
		if ( empty( $changes ) ) {
			return new \WP_Error( 'magick_ai_abilities_no_changes', __( 'No update fields were provided.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/update-media-details', $input );
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
			'source_page_url'   => '_magick_ai_media_source_page_url',
			'photographer_name' => '_magick_ai_media_photographer_name',
			'attribution_text'  => '_magick_ai_media_attribution_text',
			'copyright_notice'  => '_magick_ai_media_copyright_notice',
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/approve-comment', $input );
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
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to reply to this comment.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$content_payload = $this->normalize_content_input( $input, 'content' );
		$content         = (string) ( $content_payload['content'] ?? '' );
		$stripped        = function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $content ) : strip_tags( $content );
		if ( '' === trim( (string) $stripped ) ) {
			return new \WP_Error( 'magick_ai_abilities_comment_content_required', __( 'Reply content is required.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
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
		$allowed = $this->assert_commit_allowed( 'magick-ai/reply-comment', $input );
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
			return new \WP_Error( 'magick_ai_abilities_comment_reply_failed', __( 'Comment reply failed.', 'magick-ai-abilities' ), array( 'status' => 500 ) );
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
	 * Builds shared write metadata.
	 *
	 * @return array<string,mixed>
	 */
	private function write_meta() {
		return array(
			'show_in_rest' => true,
			'mcp'          => array(
				'public' => true,
				'server' => 'magick-ai-write',
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
	 * Returns whether a host runtime approved the final commit.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input args.
	 * @return true|\WP_Error
	 */
	private function assert_commit_allowed( $ability_id, array $input ) {
		$runtime_context = isset( $GLOBALS['magick_ai_runtime_wp_ability_context']['context'] ) && is_array( $GLOBALS['magick_ai_runtime_wp_ability_context']['context'] )
			? $GLOBALS['magick_ai_runtime_wp_ability_context']['context']
			: array();
		$allowed = ! empty( $runtime_context['approval_commit_authorized'] );
		if ( function_exists( 'apply_filters' ) ) {
			$allowed = (bool) apply_filters( 'magick_ai_abilities_write_commit_allowed', $allowed, $ability_id, $input, $runtime_context );
		}
		if ( $allowed ) {
			return true;
		}

		return new \WP_Error(
			'magick_ai_abilities_host_approval_required',
			__( 'This write ability requires approval from a host runtime before commit.', 'magick-ai-abilities' ),
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
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to edit this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
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
			return new \WP_Error( 'magick_ai_abilities_attachment_invalid', __( 'Attachment ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== sanitize_key( (string) ( $attachment->post_type ?? '' ) ) ) {
			return new \WP_Error( 'magick_ai_abilities_attachment_invalid', __( 'Attachment ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		return $attachment;
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
			'source_page_url'   => function_exists( 'get_post_meta' ) ? esc_url_raw( (string) get_post_meta( $attachment_id, '_magick_ai_media_source_page_url', true ) ) : '',
			'photographer_name' => function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $attachment_id, '_magick_ai_media_photographer_name', true ) ) : '',
			'attribution_text'  => function_exists( 'get_post_meta' ) ? sanitize_textarea_field( (string) get_post_meta( $attachment_id, '_magick_ai_media_attribution_text', true ) ) : '',
			'copyright_notice'  => function_exists( 'get_post_meta' ) ? sanitize_textarea_field( (string) get_post_meta( $attachment_id, '_magick_ai_media_copyright_notice', true ) ) : '',
		);
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
			return new \WP_Error( 'magick_ai_abilities_comment_invalid', __( 'Comment ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return new \WP_Error( 'magick_ai_abilities_comment_not_found', __( 'Comment was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'moderate_comments' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to moderate comments.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}
		return $comment;
	}

	/**
	 * Normalizes write content into safe HTML.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @param string              $field Input field.
	 * @return array{content:string,content_format:string}
	 */
	private function normalize_content_input( array $input, $field = 'content' ) {
		$field          = is_string( $field ) && '' !== $field ? $field : 'content';
		$content        = array_key_exists( $field, $input ) ? (string) $input[ $field ] : '';
		$content_format = $this->resolve_content_format( $content, (string) ( $input['content_format'] ?? '' ) );
		if ( 'markdown' === $content_format ) {
			$content = $this->markdown_to_html( $content );
		} elseif ( 'plain' === $content_format ) {
			$content = $this->plain_text_to_html( $content );
		}

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
			return new \WP_Error( 'magick_ai_abilities_taxonomy_invalid', __( 'Taxonomy is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
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
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission for this taxonomy operation.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
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
			return new \WP_Error( 'magick_ai_abilities_term_invalid', __( 'Term ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_Error( 'magick_ai_abilities_term_not_found', __( 'Term was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
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
