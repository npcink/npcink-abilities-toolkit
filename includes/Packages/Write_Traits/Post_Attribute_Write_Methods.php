<?php
/**
 * Post attribute write methods for Core_Write_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides host-governed post slug, author, template, and format writes.
 */
trait Post_Attribute_Write_Methods {
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
}
