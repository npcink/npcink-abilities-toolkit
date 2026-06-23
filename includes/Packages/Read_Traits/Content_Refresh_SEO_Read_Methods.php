<?php
/**
 * Content refresh and SEO read methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides content refresh, SEO/GEO readiness, and internal-link callbacks.
 */
trait Content_Refresh_SEO_Read_Methods {
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
			return new \WP_Error( 'npcink_abilities_toolkit_post_invalid', __( 'post_id is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_not_found', __( 'Post was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
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
				'placement_hint' => __( 'Place one contextual internal link after the first paragraph that introduces this concept.', 'npcink-abilities-toolkit' ),
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
	 * Builds content refresh opportunities.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_content_refresh_opportunities( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read content refresh opportunities.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
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
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read article refresh context.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
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
				'recipe'                    => 'npcink-abilities-toolkit/recipes/old-article-refresh-discovery',
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
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read internal link graph health.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
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
					'reason'         => __( 'Posts share topic terms and may support a contextual internal link.', 'npcink-abilities-toolkit' ),
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
				'reason'   => __( 'Refresh scan found repeated SEO/GEO readiness gaps.', 'npcink-abilities-toolkit' ),
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
	 * Builds one deterministic SEO/GEO readiness snapshot for a post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_seo_geo_readiness( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_invalid', __( 'post_id is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_not_found', __( 'Post was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
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

		$this->append_readiness_check( $checks, $recommendations, 'focus_keyword', '' !== $focus_keyword && $this->contains_text_ci( $plain_text . ' ' . $title, $focus_keyword ), 'high', __( 'Focus keyword appears in title or content.', 'npcink-abilities-toolkit' ), __( 'Add or confirm a focus keyword and place it naturally in the title or lead section.', 'npcink-abilities-toolkit' ) );
		$this->append_readiness_check( $checks, $recommendations, 'seo_title', '' !== $seo_title, 'medium', __( 'SEO title metadata is present.', 'npcink-abilities-toolkit' ), __( 'Add SEO title metadata before publication or optimization handoff.', 'npcink-abilities-toolkit' ) );
		$this->append_readiness_check( $checks, $recommendations, 'seo_description', '' !== $seo_description, 'medium', __( 'SEO description metadata is present.', 'npcink-abilities-toolkit' ), __( 'Add SEO description metadata for search and share snippets.', 'npcink-abilities-toolkit' ) );
		$this->append_readiness_check( $checks, $recommendations, 'content_depth', $this->strlen_value( $plain_text ) >= 320, 'medium', __( 'Content has enough depth for baseline SEO review.', 'npcink-abilities-toolkit' ), __( 'Expand the content with clearer context, steps, evidence, or examples.', 'npcink-abilities-toolkit' ) );
		$this->append_readiness_check( $checks, $recommendations, 'answerability', ! empty( $question_candidates ), 'medium', __( 'Content has answer-oriented question candidates.', 'npcink-abilities-toolkit' ), __( 'Add direct answer sections or FAQ-style questions for GEO readiness.', 'npcink-abilities-toolkit' ) );
		$this->append_readiness_check( $checks, $recommendations, 'featured_media', $featured_media_id > 0, 'low', __( 'Featured media is assigned.', 'npcink-abilities-toolkit' ), __( 'Add featured media when the channel or theme expects one.', 'npcink-abilities-toolkit' ) );

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
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to read topic coverage.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
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
					'reason' => __( 'Only one scanned post covers this topic; consider adding supporting content or internal links.', 'npcink-abilities-toolkit' ),
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
						/* translators: %s: Related term. */
						'reason'          => sprintf( __( 'Existing content is related to "%s" and can support the source post with contextual reading.', 'npcink-abilities-toolkit' ), $term ),
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
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Bounded candidate search must exclude the source post from its own internal-link targets.
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
}
