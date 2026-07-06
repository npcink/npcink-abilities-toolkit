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
	 * Suggests existing post taxonomy terms from supplied editorial context.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function suggest_post_taxonomy_terms( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read taxonomy suggestions.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$taxonomy_mode = sanitize_key( (string) ( $input['taxonomy'] ?? 'both' ) );
		if ( ! in_array( $taxonomy_mode, array( 'both', 'category', 'post_tag' ), true ) ) {
			$taxonomy_mode = 'both';
		}
		$taxonomies = 'both' === $taxonomy_mode ? array( 'category', 'post_tag' ) : array( $taxonomy_mode );
		foreach ( $taxonomies as $taxonomy ) {
			if ( function_exists( 'taxonomy_exists' ) && ! taxonomy_exists( $taxonomy ) ) {
				return new \WP_Error( 'npcink_abilities_toolkit_taxonomy_invalid', __( 'Taxonomy does not exist.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
			}
		}

		$context = $this->normalize_taxonomy_suggestion_context( $input );
		$query   = $this->sanitize_metadata_text( (string) ( $input['query'] ?? '' ) );
		$related_term_evidence = $this->normalize_taxonomy_suggestion_related_evidence( $input['related_term_evidence'] ?? array() );
		$category_limit  = max( 1, min( 10, $this->absint_value( $input['category_limit'] ?? 5 ) ) );
		$tag_limit       = max( 1, min( 20, $this->absint_value( $input['tag_limit'] ?? 8 ) ) );
		$candidate_limit = max( 1, min( 30, $this->absint_value( $input['candidate_limit'] ?? 10 ) ) );
		$candidates = array();

		foreach ( $taxonomies as $taxonomy ) {
			$query_result = $this->query_taxonomy_inventory_terms( $taxonomy, false, 60, 1 );
			$terms = is_array( $query_result['terms'] ?? null ) ? $query_result['terms'] : array();
			foreach ( $terms as $term ) {
				if ( ! is_object( $term ) ) {
					continue;
				}
				$term_id = $this->absint_value( $term->term_id ?? 0 );
				if ( $term_id <= 0 ) {
					continue;
				}

				$term_key         = sanitize_key( $taxonomy ) . ':' . $term_id;
				$term_text        = (string) ( $term->name ?? '' ) . ' ' . (string) ( $term->slug ?? '' ) . ' ' . (string) ( $term->description ?? '' );
				$related_evidence = is_array( $related_term_evidence[ $term_key ] ?? null ) ? $related_term_evidence[ $term_key ] : array();
				$draft_score      = $this->taxonomy_suggestion_contextual_match_score( $term_text, $context, $query );
				$matched_tokens   = $this->taxonomy_suggestion_evidence_tokens( $this->taxonomy_suggestion_contextual_match_tokens( $term_text, $context, $query ) );
				if ( $draft_score > 0 && array() === $matched_tokens ) {
					$draft_score = 0;
				}

				$match_profile  = $this->taxonomy_suggestion_match_profile( $term, $context, $query, $draft_score, $matched_tokens );
				$draft_score    = (int) $match_profile['score'];
				$matched_tokens = is_array( $match_profile['matched_tokens'] ?? null ) ? $match_profile['matched_tokens'] : $matched_tokens;
				$related_score  = $this->taxonomy_suggestion_related_term_score( $related_evidence );
				$score          = $draft_score + $related_score;
				if ( $score <= 0 ) {
					continue;
				}

				$match_signals = array( 'existing_taxonomy_vocabulary' );
				if ( $draft_score > 0 ) {
					$match_signals[] = 'draft_query_overlap';
					$match_signals[] = 'current_draft_match';
				}
				if ( $related_score > 0 ) {
					$match_signals[] = 'related_site_knowledge_term';
				}
				$match_signals = array_merge( $match_signals, is_array( $match_profile['signals'] ?? null ) ? $match_profile['signals'] : array() );

				$candidates[] = array(
					'term_id'                      => $term_id,
					'taxonomy'                     => sanitize_key( $taxonomy ),
					'name'                         => sanitize_text_field( (string) ( $term->name ?? '' ) ),
					'slug'                         => sanitize_title( (string) ( $term->slug ?? '' ) ),
					'score'                        => $score,
					'status'                       => 'existing_term',
					'controlled_vocabulary_status' => 'existing_wordpress_term',
					'normalization_key'            => sanitize_title( (string) ( $term->name ?? '' ) ),
					'matched_tokens'               => $matched_tokens,
					'match_signals'                => array_values( array_unique( $match_signals ) ),
					'related_context'              => $this->taxonomy_suggestion_related_term_context_summary( $related_evidence ),
					'evidence_refs'                => is_array( $related_evidence['source_refs'] ?? null ) ? array_values( array_unique( array_map( 'sanitize_text_field', $related_evidence['source_refs'] ) ) ) : array(),
					'reason'                       => $this->taxonomy_suggestion_candidate_reason( $matched_tokens, $related_evidence ),
				);
			}
		}

		usort(
			$candidates,
			static function ( array $left, array $right ): int {
				return (int) $right['score'] <=> (int) $left['score'];
			}
		);

		$category_candidates = array_values(
			array_filter(
				$candidates,
				static function ( array $item ): bool {
					return 'category' === (string) ( $item['taxonomy'] ?? '' );
				}
			)
		);
		$tag_candidates = array_values(
			array_filter(
				$candidates,
				static function ( array $item ): bool {
					return 'post_tag' === (string) ( $item['taxonomy'] ?? '' );
				}
			)
		);
		$taxonomy_terms = array(
			'candidate_type'         => 'taxonomy_tag_candidates',
			'write_posture'          => 'suggestion_only',
			'direct_wordpress_write' => false,
			'ranking_context'        => array(
				'draft_query_overlap'          => '' !== trim( implode( ' ', $context ) ) || '' !== trim( $query ),
				'related_content_terms'        => array() !== $related_term_evidence,
				'related_term_evidence_count' => count( $related_term_evidence ),
				'related_term_policy'         => 'ranking_evidence_only_no_term_creation_or_assignment',
			),
			'items'                  => array_slice( $candidates, 0, $candidate_limit ),
		);
		$data = array(
			'artifact_type'              => 'article_taxonomy_suggestions.v1',
			'composition_role'           => 'taxonomy_candidates_only',
			'candidate_type'             => 'taxonomy_suggestions',
			'candidate_contract'         => 'recommendation_candidate.v1',
			'write_posture'              => 'suggestion_only',
			'final_write_path'           => 'core_proposal_required',
			'direct_wordpress_write'     => false,
			'category_candidates'        => array_slice( $category_candidates, 0, $category_limit ),
			'tag_candidates'             => array_slice( $tag_candidates, 0, $tag_limit ),
			'proposed_new_terms'         => $this->taxonomy_suggestion_empty_new_terms_review(),
			'taxonomy_terms'             => $taxonomy_terms,
			'recommendation_candidates'  => array_merge(
				$this->taxonomy_suggestion_recommendation_candidates( 'category_suggestions', $category_candidates, array() ),
				$this->taxonomy_suggestion_recommendation_candidates( 'tag_suggestions', array(), $tag_candidates )
			),
			'quality_gate'               => array(
				'name'           => 'runtime_taxonomy_candidate_rerank',
				'policy'         => 'existing_terms_first_current_draft_match_then_related_history',
				'candidate_sort' => 'score_desc_then_existing_term_order',
			),
			'selection_policy'           => array(
				'prefer_existing_terms'     => true,
				'new_terms_deferred'        => true,
				'no_toolbox_term_creation'  => true,
				'accepted_write_path'       => 'core_proposal_required',
			),
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_taxonomy_suggestion_ranker',
				'execution_mode' => 'deterministic',
			),
			'Taxonomy suggestions built.'
		);
	}

	/**
	 * Builds a review-only taxonomy/tag candidate set.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_taxonomy_tag_review_set( $input ) {
		$input = is_array( $input ) ? $input : array();
		$review_set_limit = max( 1, min( 20, $this->absint_value( $input['review_set_limit'] ?? 8 ) ) );
		$suggestions      = $this->suggest_post_taxonomy_terms( $input );
		if ( is_wp_error( $suggestions ) ) {
			return $suggestions;
		}

		$suggestion_data = is_array( $suggestions['data'] ?? null ) ? $suggestions['data'] : array();
		$taxonomy_terms  = is_array( $suggestion_data['taxonomy_terms'] ?? null ) ? $suggestion_data['taxonomy_terms'] : array();
		$candidates      = is_array( $taxonomy_terms['items'] ?? null ) ? $taxonomy_terms['items'] : array();
		$selected        = array();
		$blocked         = array();

		foreach ( $candidates as $candidate ) {
			if ( ! is_array( $candidate ) ) {
				continue;
			}
			$item    = $this->taxonomy_review_set_item_from_candidate( $candidate );
			$quality = is_array( $item['quality'] ?? null ) ? $item['quality'] : array();
			if ( '' === (string) ( $item['name'] ?? '' ) || $this->absint_value( $item['term_id'] ?? 0 ) <= 0 ) {
				$item['blocked_reason'] = 'invalid_existing_term_candidate';
				$blocked[] = $item;
				continue;
			}

			if ( 'weak' === (string) ( $quality['status'] ?? '' ) ) {
				$item['blocked_reason'] = 'weak_taxonomy_evidence';
				$blocked[] = $item;
				continue;
			}

			if ( count( $selected ) >= $review_set_limit ) {
				$item['blocked_reason'] = 'review_set_limit_reached';
				$blocked[] = $item;
				continue;
			}

			$item['review_status'] = 'good' === (string) ( $quality['status'] ?? '' ) ? 'ready_for_review' : 'review_recommended';
			$selected[] = $item;
		}

		$taxonomy_counts = array(
			'category' => 0,
			'post_tag' => 0,
		);
		foreach ( $selected as $item ) {
			$taxonomy = sanitize_key( (string) ( $item['taxonomy'] ?? '' ) );
			if ( isset( $taxonomy_counts[ $taxonomy ] ) ) {
				++$taxonomy_counts[ $taxonomy ];
			}
		}

		$data = array(
			'contract_version'       => 'taxonomy_tag_review_set.v1',
			'artifact_type'          => 'taxonomy_tag_review_set',
			'mode'                   => 'governed_review_set',
			'write_posture'          => 'suggestion_only',
			'final_write_path'       => 'core_proposal_required',
			'direct_wordpress_write' => false,
			'proposal_created'       => false,
			'execution_created'      => false,
			'commit_execution'       => false,
			'source_ability_id'      => 'npcink-abilities-toolkit/build-taxonomy-tag-review-set',
			'source_candidate_ability_id' => 'npcink-abilities-toolkit/suggest-post-taxonomy-terms',
			'runtime_owner'          => 'npcink-abilities-toolkit',
			'review_set_limit'       => $review_set_limit,
			'eligibility_summary'    => array(
				'scanned'           => count( $candidates ),
				'selected'          => count( $selected ),
				'blocked'           => count( $blocked ),
				'selected_category' => $taxonomy_counts['category'],
				'selected_tag'      => $taxonomy_counts['post_tag'],
			),
			'selected_items'         => $selected,
			'blocked_items'          => $blocked,
			'safety'                 => array(
				'term_creation_allowed'     => false,
				'term_assignment_allowed'   => false,
				'proposal_created'          => false,
				'direct_wordpress_write'    => false,
				'provider_runtime_used'     => false,
				'cloud_runtime_dependency'  => false,
			),
			'handoff'                => array(
				'accepted_selection_target' => 'npcink-abilities-toolkit/build-content-metadata-apply-plan',
				'term_assignment_target'    => 'npcink-abilities-toolkit/set-post-terms',
				'final_write_path'          => 'core_proposal_required',
				'operator_review_required'  => true,
			),
		);

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'         => 'local_taxonomy_tag_review_set',
				'execution_mode' => 'deterministic',
				'readonly'       => true,
			),
			'Taxonomy/tag review set built.'
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
	 * Converts one ranked taxonomy candidate into a review-set row.
	 *
	 * @param array<string,mixed> $candidate Candidate row.
	 * @return array<string,mixed>
	 */
	private function taxonomy_review_set_item_from_candidate( array $candidate ): array {
		$taxonomy = sanitize_key( (string) ( $candidate['taxonomy'] ?? '' ) );
		$term_id  = $this->absint_value( $candidate['term_id'] ?? 0 );
		$name     = sanitize_text_field( (string) ( $candidate['name'] ?? '' ) );
		$quality  = $this->taxonomy_suggestion_candidate_quality( $candidate );

		return array(
			'candidate_id'                => ( 'category' === $taxonomy ? 'category_' : 'tag_' ) . $term_id,
			'candidate_contract'          => 'taxonomy_tag_review_candidate.v1',
			'taxonomy'                    => $taxonomy,
			'term_id'                     => $term_id,
			'name'                        => $name,
			'slug'                        => sanitize_title( (string) ( $candidate['slug'] ?? '' ) ),
			'score'                       => is_numeric( $candidate['score'] ?? null ) ? (float) $candidate['score'] : 0.0,
			'quality'                     => $quality,
			'matched_tokens'              => is_array( $candidate['matched_tokens'] ?? null ) ? array_values( array_map( 'sanitize_text_field', $candidate['matched_tokens'] ) ) : array(),
			'match_signals'               => is_array( $candidate['match_signals'] ?? null ) ? array_values( array_map( 'sanitize_key', $candidate['match_signals'] ) ) : array(),
			'related_context'             => is_array( $candidate['related_context'] ?? null ) ? $this->taxonomy_review_set_sanitize_payload( $candidate['related_context'] ) : array(),
			'evidence_refs'               => is_array( $candidate['evidence_refs'] ?? null ) ? array_values( array_map( 'sanitize_text_field', $candidate['evidence_refs'] ) ) : array(),
			'reason'                      => sanitize_text_field( (string) ( $candidate['reason'] ?? '' ) ),
			'proposed_action'             => 'append_existing_term',
			'needs_operator_review'       => true,
			'direct_wordpress_write'      => false,
			'term_creation_allowed'       => false,
			'term_assignment_authorized'  => false,
		);
	}

	/**
	 * Sanitizes nested review-set evidence payloads.
	 *
	 * @param mixed $payload Raw payload.
	 * @param int   $depth Recursion depth.
	 * @return mixed
	 */
	private function taxonomy_review_set_sanitize_payload( $payload, $depth = 0 ) {
		if ( $depth > 4 ) {
			return is_array( $payload ) ? array() : $this->sanitize_metadata_text( (string) $payload );
		}
		if ( is_array( $payload ) ) {
			$clean = array();
			foreach ( $payload as $key => $value ) {
				$clean[ is_string( $key ) ? sanitize_key( $key ) : $key ] = $this->taxonomy_review_set_sanitize_payload( $value, $depth + 1 );
			}
			return $clean;
		}
		if ( is_bool( $payload ) || is_int( $payload ) || is_float( $payload ) || null === $payload ) {
			return $payload;
		}

		return $this->sanitize_metadata_text( (string) $payload );
	}

	/**
	 * Normalizes editorial context for taxonomy suggestion ranking.
	 *
	 * @param array<string,mixed> $input Raw input.
	 * @return array<string,string>
	 */
	private function normalize_taxonomy_suggestion_context( array $input ): array {
		$context = array();
		foreach ( array( 'title', 'excerpt', 'content_text', 'selected_text', 'selected_block_text', 'user_instruction' ) as $field ) {
			$context[ $field ] = $this->sanitize_metadata_text( (string) ( $input[ $field ] ?? '' ) );
		}

		return $context;
	}

	/**
	 * Normalizes related term evidence into taxonomy:term_id keyed rows.
	 *
	 * @param mixed $evidence Raw evidence.
	 * @return array<string,array<string,mixed>>
	 */
	private function normalize_taxonomy_suggestion_related_evidence( $evidence ): array {
		$rows       = is_array( $evidence ) ? $evidence : array();
		$normalized = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$term_id  = $this->absint_value( $row['term_id'] ?? 0 );
			$taxonomy = sanitize_key( (string) ( $row['taxonomy'] ?? '' ) );
			if ( $term_id <= 0 || ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
				continue;
			}

			$key                = $taxonomy . ':' . $term_id;
			$normalized[ $key ] = array(
				'term_id'         => $term_id,
				'taxonomy'        => $taxonomy,
				'name'            => sanitize_text_field( (string) ( $row['name'] ?? '' ) ),
				'source_count'    => $this->absint_value( $row['source_count'] ?? 0 ),
				'source_post_ids' => $this->normalize_limited_positive_ids( $row['source_post_ids'] ?? array(), 20 ),
				'source_titles'   => $this->normalize_limited_text_list( $row['source_titles'] ?? array(), 10 ),
				'source_refs'     => $this->normalize_limited_text_list( $row['source_refs'] ?? array(), 20 ),
				'max_similarity'  => is_numeric( $row['max_similarity'] ?? null ) ? (float) $row['max_similarity'] : 0.0,
			);
			if ( 0 === (int) $normalized[ $key ]['source_count'] ) {
				$normalized[ $key ]['source_count'] = count( $normalized[ $key ]['source_post_ids'] );
			}
		}

		return $normalized;
	}

	/**
	 * Calculates weighted context overlap for a taxonomy candidate.
	 *
	 * @param string               $candidate_text Term text.
	 * @param array<string,string> $context Editorial context.
	 * @param string               $query Query text.
	 * @return int
	 */
	private function taxonomy_suggestion_contextual_match_score( string $candidate_text, array $context, string $query ): int {
		$weighted_score  = 0;
		$weighted_fields = array(
			'title'               => 4,
			'excerpt'             => 3,
			'selected_text'       => 3,
			'selected_block_text' => 3,
			'content_text'        => 1,
			'user_instruction'    => 2,
		);

		foreach ( $weighted_fields as $field => $weight ) {
			$value = trim( (string) ( $context[ $field ] ?? '' ) );
			if ( '' === $value ) {
				continue;
			}
			$weighted_score += count( $this->taxonomy_suggestion_match_tokens( $candidate_text, $value ) ) * $weight;
		}

		if ( $weighted_score <= 0 && '' !== trim( $query ) ) {
			$weighted_score = count( $this->taxonomy_suggestion_match_tokens( $candidate_text, $query ) );
		}

		return max( 0, min( 30, $weighted_score ) );
	}

	/**
	 * Returns matched context tokens for a taxonomy candidate.
	 *
	 * @param string               $candidate_text Term text.
	 * @param array<string,string> $context Editorial context.
	 * @param string               $query Query text.
	 * @return array<int,string>
	 */
	private function taxonomy_suggestion_contextual_match_tokens( string $candidate_text, array $context, string $query ): array {
		$tokens = array();
		foreach ( array( 'title', 'excerpt', 'selected_text', 'selected_block_text', 'content_text', 'user_instruction' ) as $field ) {
			$value = trim( (string) ( $context[ $field ] ?? '' ) );
			if ( '' === $value ) {
				continue;
			}
			$tokens = array_merge( $tokens, $this->taxonomy_suggestion_match_tokens( $candidate_text, $value ) );
		}
		if ( array() === $tokens && '' !== trim( $query ) ) {
			$tokens = $this->taxonomy_suggestion_match_tokens( $candidate_text, $query );
		}

		return array_values( array_unique( array_filter( $tokens ) ) );
	}

	/**
	 * Refines candidate score with taxonomy name, slug, and specificity signals.
	 *
	 * @param object               $term Term object.
	 * @param array<string,string> $context Editorial context.
	 * @param string               $query Query text.
	 * @param int                  $draft_score Draft score.
	 * @param array<int,string>    $matched_tokens Matched tokens.
	 * @return array<string,mixed>
	 */
	private function taxonomy_suggestion_match_profile( object $term, array $context, string $query, int $draft_score, array $matched_tokens ): array {
		$name               = sanitize_text_field( (string) ( $term->name ?? '' ) );
		$slug               = sanitize_title( (string) ( $term->slug ?? '' ) );
		$description        = sanitize_text_field( (string) ( $term->description ?? '' ) );
		$name_tokens        = $this->taxonomy_suggestion_tokens( $name );
		$slug_tokens        = $this->taxonomy_suggestion_tokens( str_replace( '-', ' ', $slug ) );
		$description_tokens = $this->taxonomy_suggestion_tokens( $description );
		$name_slug_tokens   = array_values( array_unique( array_merge( $name_tokens, $slug_tokens ) ) );
		$signals            = array();
		$score              = max( 0, $draft_score );

		if ( array() !== $name_tokens && '' !== trim( (string) ( $context['title'] ?? '' ) ) && $this->taxonomy_suggestion_text_contains_phrase( (string) $context['title'], $name ) ) {
			$score    += 6;
			$signals[] = 'title_term_name_match';
		}
		foreach ( array( 'excerpt', 'selected_text', 'selected_block_text' ) as $field ) {
			$value = trim( (string) ( $context[ $field ] ?? '' ) );
			if ( array() !== $name_tokens && '' !== $value && $this->taxonomy_suggestion_text_contains_phrase( $value, $name ) ) {
				$score    += 4;
				$signals[] = $field . '_term_name_match';
				break;
			}
		}
		if ( array() !== $name_tokens && '' !== trim( (string) ( $context['content_text'] ?? '' ) ) && $this->taxonomy_suggestion_text_contains_phrase( (string) $context['content_text'], $name ) ) {
			$score    += 2;
			$signals[] = 'body_term_name_match';
		}

		$context_tokens = $this->taxonomy_suggestion_tokens(
			implode(
				' ',
				array(
					(string) ( $context['title'] ?? '' ),
					(string) ( $context['excerpt'] ?? '' ),
					(string) ( $context['selected_text'] ?? '' ),
					(string) ( $context['selected_block_text'] ?? '' ),
					'' !== trim( $query ) ? $query : '',
				)
			)
		);
		$slug_overlap          = array_intersect( $slug_tokens, $context_tokens );
		$required_slug_overlap = count( $slug_tokens ) > 1 ? 2 : 1;
		if ( count( $slug_overlap ) >= $required_slug_overlap ) {
			$score    += 2;
			$signals[] = 'slug_alias_match';
		}

		if ( $score > 0 && array() !== $matched_tokens && array() === array_intersect( $matched_tokens, $name_slug_tokens ) && array() !== array_intersect( $matched_tokens, $description_tokens ) ) {
			$score    = min( $score, 2 );
			$signals[] = 'description_only_match';
		}
		$has_exact_name_signal = (bool) array_filter(
			$signals,
			static function ( string $signal ): bool {
				return false !== strpos( $signal, '_term_name_match' );
			}
		);
		if ( $score > 0 && 1 === count( $matched_tokens ) && ! $has_exact_name_signal && ! in_array( 'slug_alias_match', $signals, true ) ) {
			$score    = min( $score, 2 );
			$signals[] = 'low_specificity_match';
		}

		return array(
			'score'          => max( 0, min( 40, $score ) ),
			'matched_tokens' => array_values( array_unique( $matched_tokens ) ),
			'signals'        => array_values( array_unique( $signals ) ),
		);
	}

	/**
	 * Builds recommendation candidate rows for ranked taxonomy terms.
	 *
	 * @param string                         $candidate_type Candidate type.
	 * @param array<int,array<string,mixed>> $categories Category candidates.
	 * @param array<int,array<string,mixed>> $tags Tag candidates.
	 * @return array<int,array<string,mixed>>
	 */
	private function taxonomy_suggestion_recommendation_candidates( string $candidate_type, array $categories, array $tags ): array {
		$items  = 'category_suggestions' === $candidate_type ? array_slice( $categories, 0, 5 ) : array_slice( $tags, 0, 8 );
		$result = array();
		foreach ( $items as $item ) {
			$taxonomy = sanitize_key( (string) ( $item['taxonomy'] ?? '' ) );
			$term_id  = $this->absint_value( $item['term_id'] ?? 0 );
			$name     = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
			if ( '' === $name || $term_id <= 0 ) {
				continue;
			}
			$quality  = $this->taxonomy_suggestion_candidate_quality( $item );
			$result[] = array(
				'candidate_contract' => 'recommendation_candidate.v1',
				'id'                 => ( 'category' === $taxonomy ? 'category_' : 'tag_' ) . $term_id,
				'kind'               => 'category' === $taxonomy ? 'category' : 'tag',
				'label'              => 'category' === $taxonomy ? __( 'Existing category', 'npcink-abilities-toolkit' ) : __( 'Existing tag', 'npcink-abilities-toolkit' ),
				'value'              => $name,
				'reason'             => sanitize_text_field( (string) ( $item['reason'] ?? '' ) ),
				'confidence'         => $quality['confidence'],
				'target_field'       => 'category' === $taxonomy ? 'category' : 'post_tag',
				'action_policy'      => 'core_proposal_required',
				'quality_status'     => $quality['status'],
				'quality_score'      => $quality['score'],
				'quality_issues'     => $quality['issues'],
				'evidence_refs'      => is_array( $item['evidence_refs'] ?? null ) ? $item['evidence_refs'] : array(),
			);
		}

		return $result;
	}

	/**
	 * Scores taxonomy candidate quality for review surfaces.
	 *
	 * @param array<string,mixed> $item Candidate row.
	 * @return array<string,mixed>
	 */
	private function taxonomy_suggestion_candidate_quality( array $item ): array {
		$score           = is_numeric( $item['score'] ?? null ) ? (float) $item['score'] : 0.0;
		$match_signals   = is_array( $item['match_signals'] ?? null ) ? $item['match_signals'] : array();
		$related_context = is_array( $item['related_context'] ?? null ) ? $item['related_context'] : array();
		$quality_score   = max( 0, min( 100, 45 + (int) round( $score * 10 ) ) );
		$quality_issues  = array();
		if ( in_array( 'current_draft_match', $match_signals, true ) ) {
			$quality_issues[] = 'Matches the current draft title, excerpt, or body.';
		}
		if ( in_array( 'title_term_name_match', $match_signals, true ) ) {
			$quality_issues[] = 'The term name appears in the title.';
		}
		if ( in_array( 'slug_alias_match', $match_signals, true ) ) {
			$quality_issues[] = 'The term slug or alias matches the editing context.';
		}
		if ( in_array( 'related_site_knowledge_term', $match_signals, true ) ) {
			$quality_issues[] = 'Related site content has used this taxonomy term.';
		}
		if ( in_array( 'description_only_match', $match_signals, true ) ) {
			$quality_score   -= 20;
			$quality_issues[] = 'Only the term description matched; review before applying.';
		}
		if ( in_array( 'low_specificity_match', $match_signals, true ) ) {
			$quality_score   -= 15;
			$quality_issues[] = 'Only one weak token matched; review specificity.';
		}
		if ( empty( $quality_issues ) ) {
			$quality_issues[] = 'Existing WordPress term candidate for manual review.';
		}
		if ( 0 === $this->absint_value( $related_context['source_count'] ?? 0 ) && ! in_array( 'current_draft_match', $match_signals, true ) ) {
			$quality_score   -= 15;
			$quality_issues[] = 'Missing strong draft or related-content evidence.';
		}
		$status = 'good';
		if ( $quality_score < 70 ) {
			$status = 'review';
		}
		if ( $quality_score < 55 ) {
			$status = 'weak';
		}

		return array(
			'score'      => max( 0, min( 100, $quality_score ) ),
			'status'     => $status,
			'confidence' => max( 0.0, min( 1.0, $score / 5 ) ),
			'issues'     => array_values( array_unique( $quality_issues ) ),
		);
	}

	/**
	 * Returns the no-new-terms policy block.
	 *
	 * @return array<string,mixed>
	 */
	private function taxonomy_suggestion_empty_new_terms_review(): array {
		return array(
			'candidate_type'         => 'new_taxonomy_terms',
			'write_posture'          => 'suggestion_only',
			'direct_wordpress_write' => false,
			'items'                  => array(),
			'review_policy'          => 'new_terms_deferred_existing_terms_first',
			'creation_policy'        => 'core_proposal_required',
		);
	}

	/**
	 * Returns related-evidence score.
	 *
	 * @param array<string,mixed> $related_evidence Related evidence.
	 * @return int
	 */
	private function taxonomy_suggestion_related_term_score( array $related_evidence ): int {
		if ( array() === $related_evidence ) {
			return 0;
		}
		$source_count   = $this->absint_value( $related_evidence['source_count'] ?? 0 );
		$max_similarity = is_numeric( $related_evidence['max_similarity'] ?? null ) ? (float) $related_evidence['max_similarity'] : 0.0;

		return max( 1, min( 5, $source_count + (int) ceil( max( 0.0, $max_similarity ) * 2 ) ) );
	}

	/**
	 * Builds a human-readable reason for a taxonomy suggestion.
	 *
	 * @param array<int,string>   $matched_tokens Matched tokens.
	 * @param array<string,mixed> $related_evidence Related evidence.
	 * @return string
	 */
	private function taxonomy_suggestion_candidate_reason( array $matched_tokens, array $related_evidence ): string {
		$has_related = array() !== $related_evidence;
		if ( $has_related && array() !== $matched_tokens ) {
			return sprintf( 'Existing term matched the draft and appears on related Site Knowledge posts. Matched tokens: %s.', implode( ', ', array_slice( $matched_tokens, 0, 6 ) ) );
		}
		if ( $has_related ) {
			return 'Existing term appears on related Site Knowledge posts and should be reviewed as a proven site taxonomy pattern.';
		}
		if ( array() === $matched_tokens ) {
			return 'Existing term has local taxonomy evidence but no concise matched token could be displayed. Review it against the current draft before applying.';
		}

		return sprintf( 'Existing term matched against the current title, excerpt, or draft body. Matched tokens: %s.', implode( ', ', array_slice( $matched_tokens, 0, 6 ) ) );
	}

	/**
	 * Summarizes related term evidence.
	 *
	 * @param array<string,mixed> $related_evidence Related evidence.
	 * @return array<string,mixed>
	 */
	private function taxonomy_suggestion_related_term_context_summary( array $related_evidence ): array {
		if ( array() === $related_evidence ) {
			return array();
		}

		return array(
			'source_count'    => $this->absint_value( $related_evidence['source_count'] ?? 0 ),
			'source_post_ids' => $this->normalize_limited_positive_ids( $related_evidence['source_post_ids'] ?? array(), 20 ),
			'source_titles'   => $this->normalize_limited_text_list( $related_evidence['source_titles'] ?? array(), 5 ),
			'max_similarity'  => is_numeric( $related_evidence['max_similarity'] ?? null ) ? (float) $related_evidence['max_similarity'] : null,
			'policy'          => 'related_content_ranking_evidence_only',
		);
	}

	/**
	 * Returns non-generic evidence tokens.
	 *
	 * @param array<int,string> $tokens Tokens.
	 * @return array<int,string>
	 */
	private function taxonomy_suggestion_evidence_tokens( array $tokens ): array {
		return array_values(
			array_filter(
				array_unique( array_map( 'strtolower', array_map( 'sanitize_text_field', $tokens ) ) ),
				function ( string $token ): bool {
					return ! $this->taxonomy_suggestion_is_generic_match_token( $token );
				}
			)
		);
	}

	/**
	 * Returns overlap tokens between term text and query text.
	 *
	 * @param string $term_text Term text.
	 * @param string $query Query.
	 * @return array<int,string>
	 */
	private function taxonomy_suggestion_match_tokens( string $term_text, string $query ): array {
		$term_tokens  = $this->taxonomy_suggestion_tokens( $term_text );
		$query_tokens = $this->taxonomy_suggestion_tokens( $query );
		if ( array() === $term_tokens || array() === $query_tokens ) {
			return array();
		}

		return array_values( array_intersect( $term_tokens, $query_tokens ) );
	}

	/**
	 * Tokenizes plain text for taxonomy suggestion ranking.
	 *
	 * @param string $text Text.
	 * @return array<int,string>
	 */
	private function taxonomy_suggestion_tokens( string $text ): array {
		$tokens = preg_split( '/[^\p{L}\p{N}]+/u', strtolower( $text ) );
		if ( ! is_array( $tokens ) ) {
			return array();
		}
		$stopwords = array(
			'a'    => true,
			'an'   => true,
			'and'  => true,
			'are'  => true,
			'as'   => true,
			'at'   => true,
			'be'   => true,
			'by'   => true,
			'for'  => true,
			'from' => true,
			'has'  => true,
			'have' => true,
			'in'   => true,
			'into' => true,
			'is'   => true,
			'it'   => true,
			'of'   => true,
			'on'   => true,
			'or'   => true,
			'that' => true,
			'the'  => true,
			'this' => true,
			'to'   => true,
			'with' => true,
		);

		return array_values(
			array_unique(
				array_filter(
					$tokens,
					static function ( string $token ) use ( $stopwords ): bool {
						return strlen( $token ) >= 2 && empty( $stopwords[ $token ] );
					}
				)
			)
		);
	}

	/**
	 * Checks whether a phrase appears in text.
	 *
	 * @param string $haystack Text to inspect.
	 * @param string $needle Phrase.
	 * @return bool
	 */
	private function taxonomy_suggestion_text_contains_phrase( string $haystack, string $needle ): bool {
		$needle = trim( $needle );
		if ( '' === $needle ) {
			return false;
		}

		return false !== strpos( strtolower( $haystack ), strtolower( $needle ) );
	}

	/**
	 * Checks generic taxonomy matching tokens.
	 *
	 * @param string $token Token.
	 * @return bool
	 */
	private function taxonomy_suggestion_is_generic_match_token( string $token ): bool {
		$generic_tokens = array(
			'post'    => true,
			'posts'   => true,
			'page'    => true,
			'pages'   => true,
			'format'  => true,
			'formats' => true,
			'type'    => true,
			'types'   => true,
		);

		return ! empty( $generic_tokens[ strtolower( trim( $token ) ) ] );
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
