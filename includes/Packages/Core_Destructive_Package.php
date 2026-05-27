<?php
/**
 * Core WordPress host-governed destructive ability package.
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
 * Registers destructive WordPress abilities migrated from the Magick AI plugin.
 */
final class Core_Destructive_Package {
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
				'description' => __( 'Host-governed WordPress write and destructive abilities with dry-run previews and external approval.', 'magick-ai-abilities' ),
			)
		);

		foreach ( $this->definitions() as $ability_id => $definition ) {
			$definition['source']                    = 'official';
			$definition['project_to_magick_catalog'] = true;
			$this->abilities->add_destructive_host_governed( $ability_id, $definition );
		}
	}

	/**
	 * Returns package ability definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function definitions() {
		$term_id    = array( 'type' => 'integer', 'minimum' => 1 );
		$post_id    = array( 'type' => 'integer', 'minimum' => 1 );
		$comment_id = array( 'type' => 'integer', 'minimum' => 1 );

		return array(
			'magick-ai/delete-term'              => array(
				'label'           => __( 'Delete Term', 'magick-ai-abilities' ),
				'description'     => __( 'Deletes a taxonomy term after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
				'capability'      => 'manage_categories',
				'required_scopes' => array( 'taxonomy.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta(),
				'input_schema'    => $this->schema(
					array(
						'taxonomy' => array( 'type' => 'string', 'default' => 'category' ),
						'term_id'  => $term_id,
					),
					array( 'taxonomy', 'term_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'taxonomy' => array( 'type' => 'string' ),
						'term_id'  => array( 'type' => 'integer' ),
						'deleted'  => array( 'type' => 'boolean' ),
						'dry_run'  => array( 'type' => 'boolean' ),
					),
					array( 'taxonomy', 'term_id', 'deleted', 'dry_run' )
				),
				'execute_callback' => array( $this, 'delete_term' ),
			),
			'magick-ai/merge-terms'              => array(
				'label'           => __( 'Merge Terms', 'magick-ai-abilities' ),
				'description'     => __( 'Moves objects from source terms to a target term, then deletes source terms after host approval.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
				'capability'      => 'manage_categories',
				'required_scopes' => array( 'taxonomy.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta(),
				'input_schema'    => $this->schema(
					array(
						'taxonomy'        => array( 'type' => 'string', 'default' => 'category' ),
						'target_term_id'  => $term_id,
						'source_term_ids' => array(
							'type'     => 'array',
							'items'    => $term_id,
							'minItems' => 1,
						),
					),
					array( 'taxonomy', 'target_term_id', 'source_term_ids' )
				),
				'output_schema'   => $this->schema(
					array(
						'taxonomy'         => array( 'type' => 'string' ),
						'target_term_id'   => array( 'type' => 'integer' ),
						'merged'           => array( 'type' => 'boolean' ),
						'merged_count'     => array( 'type' => 'integer' ),
						'removed_term_ids' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'dry_run'          => array( 'type' => 'boolean' ),
					),
					array( 'taxonomy', 'target_term_id', 'merged', 'merged_count', 'dry_run' )
				),
				'execute_callback' => array( $this, 'merge_terms' ),
			),
			'magick-ai/bulk-update-post-terms'   => array(
				'label'           => __( 'Bulk Update Post Terms', 'magick-ai-abilities' ),
				'description'     => __( 'Bulk updates post or page taxonomy terms after host approval, with computed dry-run previews and per-post before/after audit.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
				'capability'      => 'edit_posts',
				'required_scopes' => array( 'taxonomy.manage', 'post.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_type'      => array(
							'type'    => 'string',
							'enum'    => array( 'post', 'page' ),
							'default' => 'post',
						),
						'post_status'    => array(
							'type'    => 'string',
							'enum'    => array( 'publish', 'draft', 'pending', 'future', 'any' ),
							'default' => 'publish',
						),
						'taxonomy'       => array(
							'type'    => 'string',
							'enum'    => array( 'category', 'post_tag' ),
							'default' => 'post_tag',
						),
						'operation'      => array(
							'type'    => 'string',
							'enum'    => array( 'add', 'replace', 'remove' ),
							'default' => 'add',
						),
						'terms'          => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'term_ids'       => array(
							'type'  => 'array',
							'items' => $term_id,
						),
						'create_missing' => array( 'type' => 'boolean', 'default' => false ),
						'filters'        => array(
							'type'                 => 'object',
							'properties'           => array(
								'include_term_ids' => array(
									'type'  => 'array',
									'items' => $term_id,
								),
								'exclude_term_ids' => array(
									'type'  => 'array',
									'items' => $term_id,
								),
								'search'           => array( 'type' => 'string' ),
								'date_after'       => array( 'type' => 'string' ),
								'date_before'      => array( 'type' => 'string' ),
							),
							'additionalProperties' => false,
						),
						'limit'          => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 200,
							'default' => 50,
						),
						'sample_limit'   => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 50,
							'default' => 20,
						),
					)
				),
				'output_schema'   => $this->schema(
					array(
						'matched_count'     => array( 'type' => 'integer' ),
						'affected_count'    => array( 'type' => 'integer' ),
						'sample'            => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'skipped'           => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'warnings'          => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'approval_required' => array( 'type' => 'boolean' ),
						'processed_count'   => array( 'type' => 'integer' ),
						'updated_count'     => array( 'type' => 'integer' ),
						'skipped_count'     => array( 'type' => 'integer' ),
						'failed_count'      => array( 'type' => 'integer' ),
						'items'             => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'dry_run'           => array( 'type' => 'boolean' ),
					)
				),
				'execute_callback' => array( $this, 'bulk_update_post_terms' ),
			),
			'magick-ai/spam-comment'             => array(
				'label'           => __( 'Spam Comment', 'magick-ai-abilities' ),
				'description'     => __( 'Marks one comment as spam after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
				'capability'      => 'moderate_comments',
				'required_scopes' => array( 'comments.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta(),
				'input_schema'    => $this->schema(
					array(
						'comment_id' => $comment_id,
					),
					array( 'comment_id' )
				),
				'output_schema'   => $this->comment_output_schema( true ),
				'execute_callback' => array( $this, 'spam_comment' ),
			),
			'magick-ai/trash-comment'            => array(
				'label'           => __( 'Trash Comment', 'magick-ai-abilities' ),
				'description'     => __( 'Moves one comment to trash after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
				'capability'      => 'moderate_comments',
				'required_scopes' => array( 'comments.manage' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta(),
				'input_schema'    => $this->schema(
					array(
						'comment_id' => $comment_id,
					),
					array( 'comment_id' )
				),
				'output_schema'   => $this->comment_output_schema( false ),
				'execute_callback' => array( $this, 'trash_comment' ),
			),
			'magick-ai/delete-media-permanently' => array(
				'label'           => __( 'Delete Media Permanently', 'magick-ai-abilities' ),
				'description'     => __( 'Permanently deletes one media attachment after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
				'capability'      => 'delete_posts',
				'required_scopes' => array( 'media.write' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta( false ),
				'input_schema'    => $this->schema(
					array(
						'attachment_id' => $post_id,
					),
					array( 'attachment_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'attachment_id'  => array( 'type' => 'integer' ),
						'deleted'        => array( 'type' => 'boolean' ),
						'parent_post_id' => array( 'type' => 'integer' ),
						'dry_run'        => array( 'type' => 'boolean' ),
					),
					array( 'attachment_id', 'deleted', 'parent_post_id', 'dry_run' )
				),
				'execute_callback' => array( $this, 'delete_media_permanently' ),
			),
			'magick-ai/trash-post'               => array(
				'label'           => __( 'Trash Post', 'magick-ai-abilities' ),
				'description'     => __( 'Moves one post to trash after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
				'capability'      => 'delete_posts',
				'required_scopes' => array( 'post.delete' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta( false ),
				'input_schema'    => $this->schema(
					array(
						'post_id' => $post_id,
					),
					array( 'post_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'trashed' => array( 'type' => 'boolean' ),
						'post_id' => array( 'type' => 'integer' ),
						'dry_run' => array( 'type' => 'boolean' ),
					),
					array( 'trashed', 'post_id', 'dry_run' )
				),
				'execute_callback' => array( $this, 'trash_post' ),
			),
			'magick-ai/delete-post-permanently'  => array(
				'label'           => __( 'Delete Post Permanently', 'magick-ai-abilities' ),
				'description'     => __( 'Permanently deletes one post after host approval, or returns a dry-run preview by default.', 'magick-ai-abilities' ),
				'category'        => 'magick-ai-write',
				'capability'      => 'delete_posts',
				'required_scopes' => array( 'post.delete' ),
				'channels'        => array( 'agent', 'mcp' ),
				'meta'            => $this->destructive_meta(),
				'input_schema'    => $this->schema(
					array(
						'post_id' => $post_id,
					),
					array( 'post_id' )
				),
				'output_schema'   => $this->schema(
					array(
						'post_id' => array( 'type' => 'integer' ),
						'deleted' => array( 'type' => 'boolean' ),
						'dry_run' => array( 'type' => 'boolean' ),
					),
					array( 'post_id', 'deleted', 'dry_run' )
				),
				'execute_callback' => array( $this, 'delete_post_permanently' ),
			),
		);
	}

	/**
	 * Deletes one taxonomy term.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function delete_term( $input ) {
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
		$cap_check = $this->check_taxonomy_capability( $taxonomy, 'delete_terms' );
		if ( is_wp_error( $cap_check ) ) {
			return $cap_check;
		}

		$payload = array(
			'taxonomy' => $taxonomy,
			'term_id'  => $term_id,
			'deleted'  => false,
			'preview'  => array(
				'action'   => 'delete_term',
				'taxonomy' => $taxonomy,
				'term_id'  => $term_id,
				'name'     => sanitize_text_field( (string) ( $term->name ?? '' ) ),
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'magick-ai/delete-term', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$deleted = wp_delete_term( $term_id, $taxonomy );
		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}
		if ( false === $deleted ) {
			return new \WP_Error( 'magick_ai_abilities_term_delete_failed', __( 'Term deletion failed.', 'magick-ai-abilities' ), array( 'status' => 500 ) );
		}

		$payload['deleted'] = true;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Merges source terms into one target term and deletes source terms.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function merge_terms( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$taxonomy = $this->valid_taxonomy( $input['taxonomy'] ?? 'category' );
		if ( is_wp_error( $taxonomy ) ) {
			return $taxonomy;
		}
		$target_term_id = absint( $input['target_term_id'] ?? 0 );
		$target_term    = $this->get_term_object( $target_term_id, $taxonomy );
		if ( is_wp_error( $target_term ) ) {
			return new \WP_Error( 'magick_ai_abilities_target_term_not_found', __( 'Target term was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		$cap_check = $this->check_taxonomy_capability( $taxonomy, 'manage_terms' );
		if ( is_wp_error( $cap_check ) ) {
			return $cap_check;
		}
		$cap_check = $this->check_taxonomy_capability( $taxonomy, 'delete_terms' );
		if ( is_wp_error( $cap_check ) ) {
			return $cap_check;
		}

		$source_term_ids = is_array( $input['source_term_ids'] ?? null ) ? $input['source_term_ids'] : array();
		$source_term_ids = array_values( array_unique( array_filter( array_map( 'absint', $source_term_ids ) ) ) );
		$source_term_ids = array_values(
			array_filter(
				$source_term_ids,
				static function ( $term_id ) use ( $target_term_id ) {
					return (int) $term_id !== (int) $target_term_id;
				}
			)
		);
		if ( empty( $source_term_ids ) ) {
			return new \WP_Error( 'magick_ai_abilities_source_terms_required', __( 'At least one source term is required.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$valid_source_ids = array();
		foreach ( $source_term_ids as $source_term_id ) {
			if ( ! is_wp_error( $this->get_term_object( $source_term_id, $taxonomy ) ) ) {
				$valid_source_ids[] = $source_term_id;
			}
		}
		$valid_source_ids = array_values( array_unique( $valid_source_ids ) );
		if ( empty( $valid_source_ids ) ) {
			return new \WP_Error( 'magick_ai_abilities_source_terms_not_found', __( 'Source terms were not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}

		$payload = array(
			'taxonomy'         => $taxonomy,
			'target_term_id'   => $target_term_id,
			'merged'           => false,
			'merged_count'     => 0,
			'removed_term_ids' => array(),
			'preview'          => array(
				'action'          => 'merge_terms',
				'taxonomy'        => $taxonomy,
				'target_term_id'  => $target_term_id,
				'source_term_ids' => $valid_source_ids,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'magick-ai/merge-terms', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$removed_term_ids = array();
		foreach ( $valid_source_ids as $source_term_id ) {
			$object_ids = get_objects_in_term( $source_term_id, $taxonomy );
			$object_ids = is_array( $object_ids ) ? $object_ids : array();
			foreach ( $object_ids as $object_id ) {
				$object_id = absint( $object_id );
				if ( $object_id <= 0 ) {
					continue;
				}
				$current_term_ids = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );
				if ( is_wp_error( $current_term_ids ) ) {
					continue;
				}
				$current_term_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $current_term_ids ) ) ) );
				$next_term_ids    = array_values( array_unique( array_merge( array_diff( $current_term_ids, array( $source_term_id ) ), array( $target_term_id ) ) ) );
				wp_set_object_terms( $object_id, $next_term_ids, $taxonomy, false );
			}

			$deleted = wp_delete_term( $source_term_id, $taxonomy );
			if ( is_wp_error( $deleted ) || false === $deleted ) {
				continue;
			}
			$removed_term_ids[] = $source_term_id;
		}

		$payload['merged']           = ! empty( $removed_term_ids );
		$payload['merged_count']     = count( $removed_term_ids );
		$payload['removed_term_ids'] = $removed_term_ids;
		$payload['dry_run']          = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Marks one comment as spam.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function spam_comment( $input ) {
		return $this->change_comment_status( 'magick-ai/spam-comment', $input, 'spam', 'spam_comment' );
	}

	/**
	 * Moves one comment to trash.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function trash_comment( $input ) {
		return $this->change_comment_status( 'magick-ai/trash-comment', $input, 'trash', 'trash_comment' );
	}

	/**
	 * Permanently deletes one attachment.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function delete_media_permanently( $input ) {
		$input         = is_array( $input ) ? $input : array();
		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		if ( $attachment_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_attachment_invalid', __( 'Attachment ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$attachment    = $this->get_media_attachment( $attachment_id );
		if ( is_wp_error( $attachment ) && ! $this->should_dry_run( $input ) ) {
			$allowed = $this->assert_commit_allowed( 'magick-ai/delete-media-permanently', $input );
			if ( is_wp_error( $allowed ) ) {
				return $allowed;
			}

			return array(
				'attachment_id'  => $attachment_id,
				'deleted'        => true,
				'parent_post_id' => 0,
				'dry_run'        => false,
			);
		}
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		if ( ! current_user_can( 'delete_post', $attachment_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to delete this attachment.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$payload = array(
			'attachment_id'  => $attachment_id,
			'deleted'        => false,
			'parent_post_id' => absint( $attachment->post_parent ?? 0 ),
			'preview'        => array(
				'action'          => 'delete_media_permanently',
				'attachment_id'   => $attachment_id,
				'parent_post_id'  => absint( $attachment->post_parent ?? 0 ),
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'magick-ai/delete-media-permanently', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$deleted = function_exists( 'wp_delete_attachment' )
			? wp_delete_attachment( $attachment_id, true )
			: wp_delete_post( $attachment_id, true );
		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}
		if ( ! $deleted ) {
			return new \WP_Error( 'magick_ai_abilities_media_delete_failed', __( 'Attachment deletion failed.', 'magick-ai-abilities' ), array( 'status' => 500 ) );
		}

		$payload['deleted'] = true;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Moves one post to trash.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function trash_post( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_deletable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$payload = array(
			'trashed' => false,
			'post_id' => $post_id,
			'preview' => array(
				'action'      => 'trash_post',
				'from_status' => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'to_status'   => 'trash',
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'magick-ai/trash-post', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = wp_trash_post( $post_id );
		if ( ! $result ) {
			return new \WP_Error( 'magick_ai_abilities_trash_failed', __( 'Post trash failed.', 'magick-ai-abilities' ), array( 'status' => 500 ) );
		}

		$payload['trashed'] = true;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Permanently deletes one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function delete_post_permanently( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$post    = $this->get_deletable_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$payload = array(
			'post_id' => $post_id,
			'deleted' => false,
			'preview' => array(
				'action'      => 'delete_post_permanently',
				'from_status' => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'to_status'   => 'deleted',
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( 'magick-ai/delete-post-permanently', $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$result = wp_delete_post( $post_id, true );
		if ( ! $result ) {
			return new \WP_Error( 'magick_ai_abilities_delete_failed', __( 'Post deletion failed.', 'magick-ai-abilities' ), array( 'status' => 500 ) );
		}

		$payload['deleted'] = true;
		$payload['dry_run'] = false;
		unset( $payload['preview'] );
		return $payload;
	}

	/**
	 * Builds shared destructive metadata.
	 *
	 * @param bool $mcp_public Whether the ability is public on the governed MCP write server.
	 * @return array<string,mixed>
	 */
	private function destructive_meta( $mcp_public = true ) {
		return array(
			'show_in_rest' => true,
			'mcp'          => array(
				'public' => $mcp_public,
				'server' => 'magick-ai-write',
				'risk'   => 'destructive',
			),
		);
	}

	/**
	 * Builds comment status output schema.
	 *
	 * @param bool $include_post_id Whether post_id is required in the migrated contract.
	 * @return array<string,mixed>
	 */
	private function comment_output_schema( $include_post_id ) {
		$properties = array(
			'comment_id'     => array( 'type' => 'integer' ),
			'updated'        => array( 'type' => 'boolean' ),
			'comment_status' => array( 'type' => 'string' ),
			'dry_run'        => array( 'type' => 'boolean' ),
		);
		$required = array( 'comment_id', 'updated', 'comment_status', 'dry_run' );
		if ( $include_post_id ) {
			$properties['post_id'] = array( 'type' => 'integer' );
			$required[] = 'post_id';
		}
		return $this->schema( $properties, $required );
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
		$payload['dry_run']         = true;
		$payload['host_governed']   = true;
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
			__( 'This destructive ability requires approval from a host runtime before commit.', 'magick-ai-abilities' ),
			array(
				'status'       => 403,
				'ability_id'   => $ability_id,
				'host_governed' => true,
			)
		);
	}

	/**
	 * Changes one comment status with host-governed commit.
	 *
	 * @param string $ability_id Ability id.
	 * @param mixed  $input Input args.
	 * @param string $status Target status.
	 * @param string $action Preview action.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function change_comment_status( $ability_id, $input, $status, $action ) {
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
				'action'      => $action,
				'from_status' => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'to_status'   => $status,
			),
		);
		if ( $this->should_dry_run( $input ) ) {
			return $this->dry_run_payload( $payload );
		}
		$allowed = $this->assert_commit_allowed( $ability_id, $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		if ( 'spam' === $status ) {
			wp_spam_comment( $comment_id );
		} elseif ( 'trash' === $status ) {
			wp_trash_comment( $comment_id );
		} else {
			wp_set_comment_status( $comment_id, $status, true );
		}
		$updated = get_comment( $comment_id );

		$payload['post_id']        = absint( $updated->comment_post_ID ?? $comment->comment_post_ID ?? 0 );
		$payload['updated']        = true;
		$payload['comment_status'] = sanitize_key( (string) ( $updated->comment_approved ?? $status ) );
		$payload['dry_run']        = false;
		unset( $payload['preview'] );
		return $payload;
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
		$capability      = is_object( $taxonomy_object ) && ! empty( $taxonomy_object->cap->{$cap_key} )
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
	 * Returns one editable attachment object.
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
	 * Returns one deletable post object.
	 *
	 * @param int $post_id Post id.
	 * @return object|\WP_Error
	 */
	private function get_deletable_post( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'delete_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to delete this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}
		return $post;
	}
}
