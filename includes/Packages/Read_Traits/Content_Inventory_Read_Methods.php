<?php
/**
 * Content inventory read methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides content inventory health, test-content inventory, and fix-plan callbacks.
 */
trait Content_Inventory_Read_Methods {
	/**
	 * Builds an inventory health report for a bounded content set.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_content_inventory_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read content inventory.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		if ( '' === $post_type ) {
			$post_type = 'post';
		}
		if ( function_exists( 'post_type_exists' ) && ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_type_invalid', __( 'Post type does not exist.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
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
	 * Detects smoke, fixture, and test content without mutating anything.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_test_content_inventory( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read test content inventory.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$patterns = $this->normalize_test_content_patterns( $input['patterns'] ?? array() );
		$post_types = $this->normalize_test_content_post_types( $input['post_types'] ?? array( 'post', 'page' ) );
		$statuses = $this->normalize_test_content_statuses( $input['statuses'] ?? array( 'publish', 'draft', 'pending', 'future', 'private' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$include_posts = ! array_key_exists( 'include_posts', $input ) || ! empty( $input['include_posts'] );
		$include_terms = ! array_key_exists( 'include_terms', $input ) || ! empty( $input['include_terms'] );
		$include_comments = ! array_key_exists( 'include_comments', $input ) || ! empty( $input['include_comments'] );

		$posts = $include_posts ? $this->detect_test_content_posts( $patterns, $post_types, $statuses, $per_page ) : array( 'total' => 0, 'items' => array() );
		$terms = $include_terms ? $this->detect_test_content_terms( $patterns, $per_page ) : array( 'total' => 0, 'items' => array() );
		$comments = $include_comments ? $this->detect_test_content_comments( $patterns, $per_page ) : array( 'total' => 0, 'items' => array() );

		$total = (int) ( $posts['total'] ?? 0 ) + (int) ( $terms['total'] ?? 0 ) + (int) ( $comments['total'] ?? 0 );

		return $this->build_analysis_success_response(
			array(
				'included' => true,
				'detected' => $total > 0,
				'patterns' => $patterns,
				'posts'    => $posts,
				'terms'    => $terms,
				'comments' => $comments,
				'summary'  => array(
					'total_detected' => $total,
					'post_count'     => (int) ( $posts['total'] ?? 0 ),
					'term_count'     => (int) ( $terms['total'] ?? 0 ),
					'comment_count'  => (int) ( $comments['total'] ?? 0 ),
				),
			),
			array(
				'source'         => 'local_test_content_inventory',
				'execution_mode' => 'deterministic',
				'bounded'        => true,
			),
			'Test content inventory built.'
		);
	}

	/**
	 * Builds a cleanup plan for detected test content without executing writes.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_test_content_cleanup_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		$inventory = $this->get_test_content_inventory(
			array(
				'patterns'         => $input['patterns'] ?? array(),
				'post_types'       => $input['post_types'] ?? array( 'post', 'page' ),
				'statuses'         => $input['statuses'] ?? array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'include_posts'    => ! array_key_exists( 'include_posts', $input ) || ! empty( $input['include_posts'] ),
				'include_terms'    => ! array_key_exists( 'include_terms', $input ) || ! empty( $input['include_terms'] ),
				'include_comments' => ! array_key_exists( 'include_comments', $input ) || ! empty( $input['include_comments'] ),
				'per_page'         => $input['per_page'] ?? 50,
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $inventory ) ) {
			return $inventory;
		}

		$data = is_array( $inventory['data'] ?? null ) ? $inventory['data'] : array();
		$max_actions = max( 1, min( 200, $this->absint_value( $input['max_actions'] ?? 50 ) ) );
		$actions = array();
		$preview = array(
			'posts'    => array(),
			'terms'    => array(),
			'comments' => array(),
		);

		foreach ( (array) ( $data['posts']['items'] ?? array() ) as $post ) {
			if ( count( $actions ) >= $max_actions || ! is_array( $post ) ) {
				break;
			}
			$post_id = $this->absint_value( $post['post_id'] ?? 0 );
			if ( $post_id <= 0 ) {
				continue;
			}
			$actions[] = $this->build_plan_action(
				'trash_test_post_' . $post_id,
				'magick-ai/trash-post',
				array( 'post_id' => $post_id ),
				array( 'post.delete' ),
				'medium',
				'Move detected test post to trash after approval.'
			);
			$preview['posts'][] = $post;
		}

		foreach ( (array) ( $data['terms']['items'] ?? array() ) as $term ) {
			if ( count( $actions ) >= $max_actions || ! is_array( $term ) ) {
				break;
			}
			$term_id = $this->absint_value( $term['term_id'] ?? 0 );
			$taxonomy = sanitize_key( (string) ( $term['taxonomy'] ?? '' ) );
			if ( $term_id <= 0 || '' === $taxonomy || (int) ( $term['count'] ?? 0 ) > 0 ) {
				continue;
			}
			$actions[] = $this->build_plan_action(
				'delete_unused_test_term_' . $term_id,
				'magick-ai/delete-term',
				array(
					'taxonomy' => $taxonomy,
					'term_id'  => $term_id,
				),
				array( 'taxonomy.manage' ),
				'high',
				'Delete unused detected test term after approval.'
			);
			$preview['terms'][] = $term;
		}

		foreach ( (array) ( $data['comments']['items'] ?? array() ) as $comment ) {
			if ( count( $actions ) >= $max_actions || ! is_array( $comment ) ) {
				break;
			}
			$comment_id = $this->absint_value( $comment['comment_id'] ?? 0 );
			if ( $comment_id <= 0 ) {
				continue;
			}
			$actions[] = $this->build_plan_action(
				'trash_test_comment_' . $comment_id,
				'magick-ai/trash-comment',
				array( 'comment_id' => $comment_id ),
				array( 'comments.manage' ),
				'medium',
				'Move detected test comment to trash after approval.'
			);
			$preview['comments'][] = $comment;
		}

		return $this->build_analysis_success_response(
			array(
				'batch_id'          => 'test_content_cleanup_' . gmdate( 'Ymd_His' ),
				'requires_approval' => true,
				'commit_execution'  => false,
				'dry_run'           => true,
				'proposal_mode'     => 'batch',
				'batch_approval'    => true,
				'action_count'      => count( $actions ),
				'write_actions'     => $actions,
				'preview'           => $preview,
				'risk'              => array(
					'level'  => empty( $preview['terms'] ) ? 'medium' : 'high',
					'reason' => 'Posts and comments are trashable; term deletion is irreversible and only proposed for unused terms.',
				),
				'inventory_summary' => is_array( $data['summary'] ?? null ) ? $data['summary'] : array(),
			),
			array(
				'source'         => 'local_test_content_cleanup_plan',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
			),
			'Test content cleanup plan built.'
		);
	}

	/**
	 * Builds a read-only fix plan for content inventory issues.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_content_inventory_fix_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to build content inventory fix plans.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$post_ids = is_array( $input['post_ids'] ?? null ) ? array_values( array_filter( array_map( array( $this, 'absint_value' ), $input['post_ids'] ) ) ) : array();
		$post_ids = array_slice( array_values( array_unique( $post_ids ) ), 0, 50 );
		if ( empty( $post_ids ) ) {
			$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
			$status = sanitize_key( (string) ( $input['status'] ?? 'any' ) );
			$per_page = max( 1, min( 50, $this->absint_value( $input['per_page'] ?? 20 ) ) );
			$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
			$query_result = $this->query_inventory_posts( $post_type, $status, $per_page, $page );
			$post_ids = is_array( $query_result['post_ids'] ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $query_result['post_ids'] ) ) : array();
		}

		$issue_types = $this->normalize_content_fix_issue_types( $input['issue_types'] ?? array() );
		$target_status = sanitize_key( (string) ( $input['target_status'] ?? 'publish' ) );
		if ( ! in_array( $target_status, array( 'publish', 'future', 'draft' ), true ) ) {
			$target_status = 'publish';
		}
		$max_actions = max( 1, min( 100, $this->absint_value( $input['max_actions'] ?? 50 ) ) );
		$actions = array();
		$preview = array();
		$issue_counts = array();

		foreach ( $post_ids as $post_id ) {
			if ( count( $actions ) >= $max_actions ) {
				break;
			}
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$post_plan = $this->build_post_inventory_fix_plan_rows( $post_id, $post, $issue_types, $target_status, $max_actions - count( $actions ) );
			foreach ( (array) ( $post_plan['issues'] ?? array() ) as $issue ) {
				$issue = sanitize_key( (string) $issue );
				if ( '' !== $issue ) {
					$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
				}
			}
			$actions = array_merge( $actions, (array) ( $post_plan['actions'] ?? array() ) );
			if ( ! empty( $post_plan['preview'] ) ) {
				$preview[] = $post_plan['preview'];
			}
		}

		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'batch_id'          => 'content_inventory_fix_' . gmdate( 'Ymd_His' ),
				'issue_types'       => $issue_types,
				'post_ids'          => $post_ids,
				'requires_approval' => true,
				'commit_execution'  => false,
				'dry_run'           => true,
				'action_count'      => count( $actions ),
				'issue_counts'      => $issue_counts,
				'write_actions'     => $actions,
				'preview'           => $preview,
				'risk'              => array(
					'level'  => 'medium',
					'reason' => 'Plan maps issues to existing write abilities only; final writes require Core approval and host execution.',
				),
			),
			array(
				'source'         => 'local_content_inventory_fix_plan',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
			),
			'Content inventory fix plan built.'
		);
	}

	/**
	 * Returns default test-content detection patterns.
	 *
	 * @return string[]
	 */
	private function default_test_content_patterns() {
		return array(
			'smoke',
			'runtime smoke',
			'core governance',
			'taxonomy terms smoke',
			'本地文章生产 smoke',
			'core smoke candidate topic',
			'core smoke current topic',
			'core plan bridge content candidate',
			'core plan bridge test cleanup candidate',
			'core governance comment smoke',
			'taxonomy terms smoke parent post',
			'core plan bridge media candidate',
			'playwright native media alt',
			'playwright native media apply',
			'content assistant test image',
		);
	}

	/**
	 * Normalizes test-content patterns.
	 *
	 * @param mixed $patterns Raw patterns.
	 * @return string[]
	 */
	private function normalize_test_content_patterns( $patterns ) {
		$patterns = is_array( $patterns ) ? $patterns : array();
		if ( empty( $patterns ) ) {
			$patterns = $this->default_test_content_patterns();
		}

		$normalized = array();
		foreach ( $patterns as $pattern ) {
			$pattern = $this->sanitize_metadata_text( (string) $pattern );
			if ( '' !== $pattern ) {
				$normalized[] = $pattern;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Normalizes test-content post types.
	 *
	 * @param mixed $post_types Raw post types.
	 * @return string[]
	 */
	private function normalize_test_content_post_types( $post_types ) {
		$post_types = is_array( $post_types ) ? $post_types : array( $post_types );
		$normalized = array();
		foreach ( $post_types as $post_type ) {
			$post_type = sanitize_key( (string) $post_type );
			if ( '' !== $post_type && ( ! function_exists( 'post_type_exists' ) || post_type_exists( $post_type ) ) ) {
				$normalized[] = $post_type;
			}
		}

		return ! empty( $normalized ) ? array_values( array_unique( $normalized ) ) : array( 'post', 'page' );
	}

	/**
	 * Normalizes test-content post statuses.
	 *
	 * @param mixed $statuses Raw statuses.
	 * @return string[]
	 */
	private function normalize_test_content_statuses( $statuses ) {
		$statuses = is_array( $statuses ) ? $statuses : array( $statuses );
		$normalized = array();
		foreach ( $statuses as $status ) {
			$status = sanitize_key( (string) $status );
			if ( '' !== $status ) {
				$normalized[] = $status;
			}
		}

		return ! empty( $normalized ) ? array_values( array_unique( $normalized ) ) : array( 'publish', 'draft', 'pending', 'future', 'private' );
	}

	/**
	 * Detects matching test posts.
	 *
	 * @param string[] $patterns Patterns.
	 * @param string[] $post_types Post types.
	 * @param string[] $statuses Post statuses.
	 * @param int      $limit Max rows.
	 * @return array<string,mixed>
	 */
	private function detect_test_content_posts( array $patterns, array $post_types, array $statuses, $limit ) {
		$limit = max( 1, min( 100, $this->absint_value( $limit ) ) );
		$candidates = array();
		if ( class_exists( '\WP_Query' ) ) {
			foreach ( $patterns as $pattern ) {
				$query = new \WP_Query(
					array(
						'post_type'      => $post_types,
						'post_status'    => $statuses,
						'posts_per_page' => $limit,
						'orderby'        => 'modified',
						'order'          => 'DESC',
						'fields'         => 'ids',
						's'              => $pattern,
					)
				);
				$candidates = array_merge( $candidates, is_array( $query->posts ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $query->posts ) ) : array() );
			}
		} elseif ( function_exists( 'get_posts' ) ) {
			foreach ( (array) get_posts(
				array(
					'post_type'      => $post_types,
					'post_status'    => $statuses,
					'posts_per_page' => $limit * max( 1, count( $patterns ) ),
					'orderby'        => 'modified',
					'order'          => 'DESC',
				)
			) as $post ) {
				$candidates[] = is_object( $post ) ? $this->absint_value( $post->ID ?? 0 ) : $this->absint_value( $post );
			}
		}

		$items = array();
		foreach ( array_values( array_unique( array_filter( $candidates ) ) ) as $post_id ) {
			if ( count( $items ) >= $limit ) {
				break;
			}
			$post = get_post( $post_id );
			if ( ! is_object( $post ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$haystack = implode(
				' ',
				array(
					(string) ( $post->post_title ?? '' ),
					(string) ( $post->post_name ?? '' ),
					(string) ( $post->post_excerpt ?? '' ),
					$this->trim_words_value( $this->strip_all_tags_value( (string) ( $post->post_content ?? '' ) ), 80 ),
				)
			);
			$matched = $this->match_test_content_pattern( $haystack, $patterns );
			if ( '' === $matched ) {
				continue;
			}
			$items[] = array(
				'post_id'         => $post_id,
				'title'           => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'post_type'       => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'status'          => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'matched_pattern' => $matched,
				'modified'        => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
				'edit_link'       => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
			);
		}

		return array(
			'total' => count( $items ),
			'items' => $items,
		);
	}

	/**
	 * Detects matching test terms.
	 *
	 * @param string[] $patterns Patterns.
	 * @param int      $limit Max rows.
	 * @return array<string,mixed>
	 */
	private function detect_test_content_terms( array $patterns, $limit ) {
		if ( ! function_exists( 'get_terms' ) ) {
			return array( 'total' => 0, 'items' => array() );
		}

		$limit = max( 1, min( 100, $this->absint_value( $limit ) ) );
		$terms = get_terms(
			array(
				'taxonomy'   => function_exists( 'get_taxonomies' ) ? array_values( (array) get_taxonomies( array(), 'names' ) ) : array( 'category', 'post_tag' ),
				'hide_empty' => false,
				'number'     => $limit * max( 1, count( $patterns ) ),
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) {
			return array( 'total' => 0, 'items' => array() );
		}

		$items = array();
		foreach ( (array) $terms as $term ) {
			if ( count( $items ) >= $limit || ! is_object( $term ) ) {
				break;
			}
			$matched = $this->match_test_content_pattern( (string) ( $term->name ?? '' ) . ' ' . (string) ( $term->slug ?? '' ), $patterns );
			if ( '' === $matched ) {
				continue;
			}
			$items[] = array(
				'term_id'         => $this->absint_value( $term->term_id ?? 0 ),
				'taxonomy'        => sanitize_key( (string) ( $term->taxonomy ?? '' ) ),
				'name'            => sanitize_text_field( (string) ( $term->name ?? '' ) ),
				'slug'            => $this->sanitize_metadata_slug( (string) ( $term->slug ?? '' ) ),
				'count'           => $this->absint_value( $term->count ?? 0 ),
				'matched_pattern' => $matched,
			);
		}

		return array(
			'total' => count( $items ),
			'items' => $items,
		);
	}

	/**
	 * Detects matching test comments.
	 *
	 * @param string[] $patterns Patterns.
	 * @param int      $limit Max rows.
	 * @return array<string,mixed>
	 */
	private function detect_test_content_comments( array $patterns, $limit ) {
		if ( ! function_exists( 'get_comments' ) ) {
			return array( 'total' => 0, 'items' => array() );
		}

		$limit = max( 1, min( 100, $this->absint_value( $limit ) ) );
		$comments = get_comments(
			array(
				'number' => $limit * max( 1, count( $patterns ) ),
				'status' => 'all',
			)
		);
		$items = array();
		foreach ( (array) $comments as $comment ) {
			if ( count( $items ) >= $limit || ! is_object( $comment ) ) {
				break;
			}
			$matched = $this->match_test_content_pattern(
				(string) ( $comment->comment_author ?? '' ) . ' ' . (string) ( $comment->comment_content ?? '' ),
				$patterns
			);
			if ( '' === $matched ) {
				continue;
			}
			$items[] = array(
				'comment_id'      => $this->absint_value( $comment->comment_ID ?? 0 ),
				'post_id'         => $this->absint_value( $comment->comment_post_ID ?? 0 ),
				'author'          => sanitize_text_field( (string) ( $comment->comment_author ?? '' ) ),
				'status'          => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'date'            => sanitize_text_field( (string) ( $comment->comment_date ?? '' ) ),
				'matched_pattern' => $matched,
				'excerpt'         => $this->trim_words_value( $this->strip_all_tags_value( (string) ( $comment->comment_content ?? '' ) ), 20 ),
			);
		}

		return array(
			'total' => count( $items ),
			'items' => $items,
		);
	}

	/**
	 * Finds the first matching test-content pattern.
	 *
	 * @param string   $text Text to inspect.
	 * @param string[] $patterns Patterns.
	 * @return string
	 */
	private function match_test_content_pattern( $text, array $patterns ) {
		$text = strtolower( (string) $text );
		foreach ( $patterns as $pattern ) {
			$pattern = (string) $pattern;
			if ( '' !== $pattern && false !== strpos( $text, strtolower( $pattern ) ) ) {
				return $pattern;
			}
		}

		return '';
	}

	/**
	 * Normalizes requested content fix issue types.
	 *
	 * @param mixed $issue_types Raw issue types.
	 * @return string[]
	 */
	private function normalize_content_fix_issue_types( $issue_types ) {
		$allowed = array( 'title', 'content', 'slug', 'excerpt', 'featured_media', 'seo_title', 'seo_description' );
		$issue_types = is_array( $issue_types ) ? $issue_types : array();
		if ( empty( $issue_types ) ) {
			$issue_types = array( 'seo_title', 'seo_description', 'slug', 'excerpt', 'featured_media', 'content' );
		}
		$normalized = array();
		foreach ( $issue_types as $issue ) {
			$issue = sanitize_key( (string) $issue );
			if ( in_array( $issue, $allowed, true ) ) {
				$normalized[] = $issue;
			}
		}

		return ! empty( $normalized ) ? array_values( array_unique( $normalized ) ) : array( 'seo_title', 'seo_description' );
	}

	/**
	 * Builds action rows for one post inventory fix plan.
	 *
	 * @param int      $post_id Post ID.
	 * @param object   $post Post object.
	 * @param string[] $issue_types Requested issue types.
	 * @param string   $target_status Target status.
	 * @param int      $remaining_slots Remaining action slots.
	 * @return array<string,mixed>
	 */
	private function build_post_inventory_fix_plan_rows( $post_id, $post, array $issue_types, $target_status, $remaining_slots ) {
		$post_id = $this->absint_value( $post_id );
		$remaining_slots = max( 0, (int) $remaining_slots );
		$checklist = $this->get_content_publishing_checklist(
			array(
				'post_id'       => $post_id,
				'target_status' => $target_status,
			)
		);
		$check_data = is_array( $checklist ) && is_array( $checklist['data'] ?? null ) ? $checklist['data'] : array();
		$issues = array_values( array_unique( array_merge( (array) ( $check_data['missing'] ?? array() ), (array) ( $check_data['warnings'] ?? array() ) ) ) );
		$issues = array_values(
			array_filter(
				array_map( 'sanitize_key', $issues ),
				static function ( $issue ) use ( $issue_types ) {
					return in_array( $issue, $issue_types, true );
				}
			)
		);

		$content = (string) ( $post->post_content ?? '' );
		$plain_text = $this->strip_all_tags_value( $content );
		$title = sanitize_text_field( (string) get_the_title( $post_id ) );
		$excerpt = $this->sanitize_metadata_text( (string) ( $post->post_excerpt ?? '' ) );
		$slug = $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) );
		$seo_provider = $this->detect_seo_provider();
		$seo_keys = $this->seo_meta_keys( $seo_provider );
		$current_seo_title = $this->get_first_post_meta_text( $post_id, $seo_keys['title'] ?? '' );
		$current_seo_description = $this->sanitize_metadata_text( $this->get_first_post_meta_text( $post_id, $seo_keys['description'] ?? '' ) );
		$featured_media_id = function_exists( 'get_post_thumbnail_id' ) ? $this->absint_value( get_post_thumbnail_id( $post_id ) ) : 0;
		$actions = array();
		$after = array();

		$seo_input = array( 'post_id' => $post_id );
		if ( in_array( 'seo_title', $issues, true ) ) {
			$seo_input['seo_title'] = $this->truncate_text( '' !== $title ? $title : 'Post ' . $post_id, 70 );
			$after['seo_title'] = $seo_input['seo_title'];
		}
		if ( in_array( 'seo_description', $issues, true ) ) {
			$description_source = '' !== $excerpt ? $excerpt : $this->trim_words_value( $plain_text, 28 );
			$seo_input['seo_description'] = $this->truncate_text( $description_source, 155 );
			$after['seo_description'] = $seo_input['seo_description'];
		}
		if ( count( $seo_input ) > 1 && count( $actions ) < $remaining_slots ) {
			$actions[] = $this->build_plan_action( 'set_seo_meta_' . $post_id, 'magick-ai/set-post-seo-meta', $seo_input, array( 'post.write' ), 'medium', 'Fill missing SEO metadata.' );
		}

		if ( in_array( 'title', $issues, true ) && count( $actions ) < $remaining_slots ) {
			$actions[] = $this->build_plan_action( 'set_title_' . $post_id, 'magick-ai/update-post', array( 'post_id' => $post_id ), array( 'post.write' ), 'medium', 'Provide a human-reviewed title before execution.', array( 'title' ) );
		}

		if ( in_array( 'slug', $issues, true ) && count( $actions ) < $remaining_slots ) {
			$suggested_slug = $this->sanitize_metadata_slug( '' !== $title ? $title : 'post-' . $post_id );
			if ( '' !== $suggested_slug ) {
				$actions[] = $this->build_plan_action( 'set_slug_' . $post_id, 'magick-ai/set-post-slug', array( 'post_id' => $post_id, 'slug' => $suggested_slug ), array( 'post.write' ), 'medium', 'Set stable post slug.' );
				$after['slug'] = $suggested_slug;
			}
		}

		if ( in_array( 'excerpt', $issues, true ) && count( $actions ) < $remaining_slots ) {
			$suggested_excerpt = $this->truncate_text( $this->trim_words_value( $plain_text, 34 ), 220 );
			if ( '' !== $suggested_excerpt ) {
				$actions[] = $this->build_plan_action( 'set_excerpt_' . $post_id, 'magick-ai/update-post', array( 'post_id' => $post_id, 'excerpt' => $suggested_excerpt ), array( 'post.write' ), 'medium', 'Fill missing post excerpt.' );
				$after['excerpt'] = $suggested_excerpt;
			}
		}

		if ( in_array( 'featured_media', $issues, true ) && count( $actions ) < $remaining_slots ) {
			$actions[] = $this->build_plan_action( 'set_featured_media_' . $post_id, 'magick-ai/set-post-featured-image', array( 'post_id' => $post_id ), array( 'media.write' ), 'medium', 'Select an approved attachment or media URL before execution.', array( 'attachment_id_or_media_url' ) );
		}

		if ( in_array( 'content', $issues, true ) && count( $actions ) < $remaining_slots ) {
			$actions[] = $this->build_plan_action( 'expand_content_' . $post_id, 'magick-ai/update-post', array( 'post_id' => $post_id ), array( 'post.write' ), 'medium', 'Generate or provide expanded content before execution.', array( 'content' ) );
		}

		return array(
			'issues'  => $issues,
			'actions' => $actions,
			'preview' => empty( $actions ) ? array() : array(
				'post_id' => $post_id,
				'title'   => $title,
				'before'  => array(
					'title'             => $title,
					'seo_title'         => $current_seo_title,
					'seo_description'   => $current_seo_description,
					'slug'              => $slug,
					'excerpt'           => $excerpt,
					'featured_media_id' => $featured_media_id,
					'plain_text_length' => $this->strlen_value( $plain_text ),
				),
				'after_suggestion' => $after,
				'issues'           => $issues,
			),
		);
	}
}
