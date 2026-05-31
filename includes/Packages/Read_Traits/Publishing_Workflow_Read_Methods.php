<?php
/**
 * Publishing workflow read methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides publishing checklist, risk, preflight, and calendar callbacks.
 */
trait Publishing_Workflow_Read_Methods {
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
}
