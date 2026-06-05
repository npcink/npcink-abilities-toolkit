<?php
/**
 * Taxonomy read methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides taxonomy inventory, listing, and proposal read callbacks.
 */
trait Taxonomy_Read_Methods {
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
			return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_invalid', __( 'Taxonomy does not exist.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$term = null;
		if ( $term_id > 0 ) {
			$term = get_term( $term_id, $taxonomy );
		} elseif ( '' !== $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
		}

		if ( ! $term || is_wp_error( $term ) || ! is_object( $term ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_term_not_found', __( 'Term was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
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
	 * Builds taxonomy consolidation suggestions.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_taxonomy_consolidation_suggestions( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'manage_categories' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read taxonomy consolidation suggestions.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? 'post_tag' ) );
		if ( '' === $taxonomy ) {
			$taxonomy = 'post_tag';
		}
		if ( function_exists( 'taxonomy_exists' ) && ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_invalid', __( 'Taxonomy does not exist.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
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
					'reason'    => __( 'Term has no associated content in the current scan.', 'npcink-abilities-toolkit' ),
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
				'reason'   => __( 'Terms normalize to the same consolidation key.', 'npcink-abilities-toolkit' ),
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
	 * Builds a post taxonomy assignment proposal from existing terms.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function propose_post_taxonomy_terms( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_required', __( 'A valid post_id is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}
		$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_not_found', __( 'Post not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to inspect taxonomy proposals for this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? 'post_tag' ) );
		if ( '' === $taxonomy ) {
			$taxonomy = 'post_tag';
		}
		if ( function_exists( 'taxonomy_exists' ) && ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_invalid', __( 'Taxonomy does not exist.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$mode = sanitize_key( (string) ( $input['mode'] ?? 'append' ) );
		if ( ! in_array( $mode, array( 'replace', 'append', 'remove' ), true ) ) {
			$mode = 'append';
		}

		$candidate_term_ids = $this->normalize_limited_positive_ids( $input['candidate_term_ids'] ?? array(), 20 );
		$candidate_terms    = $this->normalize_limited_text_list( $input['candidate_terms'] ?? array(), 20 );
		if ( empty( $candidate_term_ids ) && empty( $candidate_terms ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_candidates_required', __( 'At least one candidate term id or term name is required.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$query_result  = $this->query_taxonomy_inventory_terms( $taxonomy, false, 100, 1 );
		$catalog_terms = $this->normalize_taxonomy_proposal_catalog( is_array( $query_result['terms'] ?? null ) ? $query_result['terms'] : array() );
		$current_terms = $this->current_post_taxonomy_terms_for_proposal( $post_id, $taxonomy );
		$current_ids   = array_values( array_map( 'intval', array_column( $current_terms, 'term_id' ) ) );
		$matched_terms = array();
		$unmatched     = array();

		foreach ( $candidate_term_ids as $candidate_id ) {
			$row = $this->resolve_taxonomy_proposal_row_by_id( $taxonomy, $catalog_terms, $candidate_id );
			if ( ! empty( $row ) ) {
				$matched_terms[ (int) $row['term_id'] ] = $row;
				continue;
			}
			$unmatched[] = array(
				'type'  => 'term_id',
				'value' => (int) $candidate_id,
			);
		}

		foreach ( $candidate_terms as $candidate_name ) {
			$row = $this->resolve_taxonomy_proposal_row_by_name( $taxonomy, $catalog_terms, $candidate_name );
			if ( ! empty( $row ) ) {
				$matched_terms[ (int) $row['term_id'] ] = $row;
				continue;
			}
			$unmatched[] = array(
				'type'  => 'term_name',
				'value' => $candidate_name,
			);
		}

		$matched_terms = array_values( $matched_terms );
		$matched_ids   = array_values( array_map( 'intval', array_column( $matched_terms, 'term_id' ) ) );
		if ( empty( $matched_ids ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_no_matches', __( 'No candidate terms matched existing taxonomy terms.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		if ( 'replace' === $mode ) {
			$proposed_ids = $matched_ids;
		} elseif ( 'remove' === $mode ) {
			$proposed_ids = array_values( array_diff( $current_ids, $matched_ids ) );
		} else {
			$proposed_ids = array_values( array_unique( array_merge( $current_ids, $matched_ids ) ) );
		}
		sort( $proposed_ids );
		$added_ids   = array_values( array_diff( $proposed_ids, $current_ids ) );
		$removed_ids = array_values( array_diff( $current_ids, $proposed_ids ) );

		return $this->build_analysis_success_response(
			array(
				'post'              => array(
					'post_id' => $post_id,
					'status'  => sanitize_key( (string) ( $post->post_status ?? '' ) ),
					'title'   => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				),
				'taxonomy'          => $taxonomy,
				'mode'              => $mode,
				'current_terms'     => $current_terms,
				'matched_terms'     => $matched_terms,
				'unmatched_terms'   => $unmatched,
				'proposed_term_ids' => $proposed_ids,
				'added_term_ids'    => $added_ids,
				'removed_term_ids'  => $removed_ids,
				'proposal'          => array(
					'target_ability_id' => 'npcink-abilities-toolkit/set-post-terms',
					'input'             => array(
						'post_id'        => $post_id,
						'taxonomy'       => $taxonomy,
						'mode'           => $mode,
						'term_ids'       => $matched_ids,
						'create_missing' => false,
						'dry_run'        => true,
						'commit'         => false,
					),
					'dry_run'           => true,
					'commit'            => false,
					'commit_execution'  => false,
					'next_actions'      => array( 'review_in_host', 'run_set_post_terms_dry_run', 'request_host_approval' ),
				),
			),
			array(
				'source'         => 'local_post_taxonomy_terms_proposal',
				'execution_mode' => 'deterministic',
			),
			'Post taxonomy term proposal built.'
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
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read taxonomy inventory.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? 'category' ) );
		if ( '' === $taxonomy ) {
			$taxonomy = 'category';
		}
		if ( function_exists( 'taxonomy_exists' ) && ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_invalid', __( 'Taxonomy does not exist.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
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

		$terms = isset( $GLOBALS['npcink_abilities_toolkit_unit_terms'][ $taxonomy ] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_terms'][ $taxonomy ] )
			? array_values( $GLOBALS['npcink_abilities_toolkit_unit_terms'][ $taxonomy ] )
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
	 * Normalizes a bounded list of positive ids.
	 *
	 * @param mixed $value Raw list.
	 * @param int   $limit Max list length.
	 * @return array<int,int>
	 */
	private function normalize_limited_positive_ids( $value, $limit ) {
		$items = is_array( $value ) ? $value : array();
		$ids   = array();
		foreach ( array_slice( $items, 0, max( 1, (int) $limit ) ) as $item ) {
			$item = $this->absint_value( $item );
			if ( $item > 0 ) {
				$ids[] = $item;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Normalizes a bounded list of text values.
	 *
	 * @param mixed $value Raw list.
	 * @param int   $limit Max list length.
	 * @return array<int,string>
	 */
	private function normalize_limited_text_list( $value, $limit ) {
		$items = is_array( $value ) ? $value : array();
		$texts = array();
		foreach ( array_slice( $items, 0, max( 1, (int) $limit ) ) as $item ) {
			$item = sanitize_text_field( (string) $item );
			if ( '' !== $item ) {
				$texts[] = $item;
			}
		}
		return array_values( array_unique( $texts ) );
	}

	/**
	 * Normalizes taxonomy term objects for proposal matching.
	 *
	 * @param array<int,mixed> $terms Term objects.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_taxonomy_proposal_catalog( array $terms ) {
		$rows = array();
		foreach ( $terms as $term ) {
			$row = $this->normalize_taxonomy_proposal_term_object( $term );
			if ( empty( $row ) ) {
				continue;
			}
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * Normalizes one taxonomy term object for proposal matching.
	 *
	 * @param mixed $term Term object.
	 * @return array<string,mixed>
	 */
	private function normalize_taxonomy_proposal_term_object( $term ) {
		if ( ! is_object( $term ) || ( function_exists( 'is_wp_error' ) && is_wp_error( $term ) ) ) {
			return array();
		}

		$term_id = $this->absint_value( $term->term_id ?? 0 );
		$name    = sanitize_text_field( (string) ( $term->name ?? '' ) );
		$slug    = sanitize_title( (string) ( $term->slug ?? $name ) );
		if ( $term_id <= 0 || '' === $name ) {
			return array();
		}

		return array(
			'term_id' => $term_id,
			'name'    => $name,
			'slug'    => $slug,
			'count'   => $this->absint_value( $term->count ?? 0 ),
		);
	}

	/**
	 * Resolves one taxonomy proposal row by id.
	 *
	 * @param string                        $taxonomy Taxonomy name.
	 * @param array<int,array<string,mixed>> $catalog Catalog rows.
	 * @param int                           $term_id Term id.
	 * @return array<string,mixed>
	 */
	private function resolve_taxonomy_proposal_row_by_id( $taxonomy, array $catalog, $term_id ) {
		$term_id = $this->absint_value( $term_id );
		if ( $term_id > 0 && function_exists( 'get_term' ) ) {
			$row = $this->normalize_taxonomy_proposal_term_object( get_term( $term_id, sanitize_key( (string) $taxonomy ) ) );
			if ( ! empty( $row ) ) {
				return $row;
			}
		}

		return $this->find_taxonomy_proposal_catalog_row_by_id( $catalog, $term_id );
	}

	/**
	 * Resolves one taxonomy proposal row by name or slug.
	 *
	 * @param string                        $taxonomy Taxonomy name.
	 * @param array<int,array<string,mixed>> $catalog Catalog rows.
	 * @param string                        $name Candidate name.
	 * @return array<string,mixed>
	 */
	private function resolve_taxonomy_proposal_row_by_name( $taxonomy, array $catalog, $name ) {
		$name = sanitize_text_field( (string) $name );
		if ( '' !== $name && function_exists( 'get_term_by' ) ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			$row      = $this->normalize_taxonomy_proposal_term_object( get_term_by( 'name', $name, $taxonomy ) );
			if ( ! empty( $row ) ) {
				return $row;
			}
			$row = $this->normalize_taxonomy_proposal_term_object( get_term_by( 'slug', sanitize_title( $name ), $taxonomy ) );
			if ( ! empty( $row ) ) {
				return $row;
			}
		}

		return $this->find_taxonomy_proposal_catalog_row_by_name( $catalog, $name );
	}

	/**
	 * Finds one taxonomy proposal row by id.
	 *
	 * @param array<int,array<string,mixed>> $catalog Catalog rows.
	 * @param int                           $term_id Term id.
	 * @return array<string,mixed>
	 */
	private function find_taxonomy_proposal_catalog_row_by_id( array $catalog, $term_id ) {
		$term_id = $this->absint_value( $term_id );
		foreach ( $catalog as $row ) {
			if ( $term_id === $this->absint_value( $row['term_id'] ?? 0 ) ) {
				return $row;
			}
		}
		return array();
	}

	/**
	 * Finds one taxonomy proposal row by normalized name or slug.
	 *
	 * @param array<int,array<string,mixed>> $catalog Catalog rows.
	 * @param string                        $name Candidate name.
	 * @return array<string,mixed>
	 */
	private function find_taxonomy_proposal_catalog_row_by_name( array $catalog, $name ) {
		$name     = sanitize_text_field( (string) $name );
		$name_key = $this->normalize_taxonomy_consolidation_key( $name );
		$slug     = sanitize_title( $name );
		foreach ( $catalog as $row ) {
			$row_name_key = $this->normalize_taxonomy_consolidation_key( (string) ( $row['name'] ?? '' ) );
			$row_slug     = sanitize_title( (string) ( $row['slug'] ?? '' ) );
			if ( '' !== $name_key && $name_key === $row_name_key ) {
				return $row;
			}
			if ( '' !== $slug && $slug === $row_slug ) {
				return $row;
			}
		}
		return array();
	}

	/**
	 * Returns current post terms for taxonomy proposal previews.
	 *
	 * @param int    $post_id Post id.
	 * @param string $taxonomy Taxonomy.
	 * @return array<int,array<string,mixed>>
	 */
	private function current_post_taxonomy_terms_for_proposal( $post_id, $taxonomy ) {
		$terms = array();
		if ( function_exists( 'get_the_terms' ) ) {
			$terms = get_the_terms( $this->absint_value( $post_id ), sanitize_key( (string) $taxonomy ) );
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) {
				$terms = array();
			}
		} elseif ( isset( $GLOBALS['npcink_abilities_toolkit_unit_post_terms'][ (int) $post_id ][ (string) $taxonomy ] ) ) {
			$terms = $GLOBALS['npcink_abilities_toolkit_unit_post_terms'][ (int) $post_id ][ (string) $taxonomy ];
		}

		return $this->normalize_taxonomy_proposal_catalog( is_array( $terms ) ? $terms : array() );
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
		$include_sample_posts = ! empty( $input['include_sample_posts'] );
		$sample_post_limit = max( 1, min( 5, absint( $input['sample_post_limit'] ?? 3 ) ) );
		$per_page_max = $include_taxonomy ? 100 : 50;
		$per_page_default = $include_taxonomy ? 20 : 10;
		$per_page = max( 1, min( $per_page_max, absint( $input['per_page'] ?? $per_page_default ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$offset = ( $page - 1 ) * $per_page;

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_invalid', __( 'Taxonomy does not exist.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
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
			if ( $include_sample_posts ) {
				$row['sample_posts'] = $this->build_term_sample_posts( $taxonomy, absint( $term->term_id ?? 0 ), $sample_post_limit );
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
}
