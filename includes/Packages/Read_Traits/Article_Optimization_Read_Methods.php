<?php
/**
 * Article optimization read methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides article optimization reports, suggestions, and apply-plan callbacks.
 */
trait Article_Optimization_Read_Methods {
	/**
	 * Composes article optimization report sections into one deterministic envelope.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_optimization_report( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post = is_array( $input['post'] ?? null ) ? $input['post'] : array();
		$seo = is_array( $input['seo'] ?? null ) ? $input['seo'] : array();
		$geo = is_array( $input['geo'] ?? null ) ? $input['geo'] : array();
		$internal_links = is_array( $input['internal_links'] ?? null ) ? $input['internal_links'] : array();
		$media = is_array( $input['media'] ?? null ) ? $input['media'] : array();

		$recommendations = array();
		foreach ( array( 'seo' => $seo, 'geo' => $geo ) as $section_key => $section ) {
			$section_recommendations = is_array( $section['recommendations'] ?? null ) ? $section['recommendations'] : array();
			foreach ( $section_recommendations as $item ) {
				$item = is_array( $item ) ? $item : array();
				$item['section'] = $section_key;
				$recommendations[] = $item;
			}
		}
		foreach ( array_slice( is_array( $internal_links['placement_plan'] ?? null ) ? $internal_links['placement_plan'] : array(), 0, 3 ) as $placement ) {
			$placement = is_array( $placement ) ? $placement : array();
			$recommendations[] = array(
				'section'  => 'internal_links',
				'type'     => 'placement',
				'priority' => 'medium',
				'title'    => sanitize_text_field( (string) ( $placement['anchor_text'] ?? '建议内链' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $placement['reason'] ?? '' ) ),
			);
		}
		foreach ( array_slice( is_array( $media['assets'] ?? null ) ? $media['assets'] : array(), 0, 3 ) as $asset ) {
			$asset = is_array( $asset ) ? $asset : array();
			$issues = is_array( $asset['issues'] ?? null ) ? $asset['issues'] : array();
			if ( empty( $issues ) ) {
				continue;
			}
			$recommendations[] = array(
				'section'  => 'media',
				'type'     => 'metadata',
				'priority' => 'medium',
				'title'    => '补齐媒体元数据',
				'detail'   => implode( ' ', array_map( array( $this, 'sanitize_text_value' ), $issues ) ),
			);
		}

		$high_priority_count = count(
			array_filter(
				$recommendations,
				static function ( array $recommendation ) {
					return 'high' === sanitize_key( (string) ( $recommendation['priority'] ?? '' ) );
				}
			)
		);

		return $this->build_analysis_success_response(
			array(
				'post'            => array(
					'post_id'   => $this->absint_value( $post['id'] ?? $post['post_id'] ?? 0 ),
					'title'     => sanitize_text_field( (string) ( $post['title'] ?? '' ) ),
					'status'    => sanitize_key( (string) ( $post['status'] ?? '' ) ),
					'edit_link' => $this->esc_url_value( (string) ( $post['edit_link'] ?? '' ) ),
				),
				'seo'             => $seo,
				'geo'             => $geo,
				'internal_links'  => $internal_links,
				'media'           => $media,
				'summary'         => array(
					'status'                => $high_priority_count > 0 ? 'needs_attention' : 'ready_for_review',
					'high_priority_count'   => $high_priority_count,
					'total_recommendations' => count( $recommendations ),
					'sections'              => array( 'seo', 'geo', 'internal_links', 'media' ),
				),
				'recommendations' => $recommendations,
			),
			array(
				'source'         => 'local_article_optimization_report',
				'execution_mode' => 'deterministic',
			),
			'Article optimization report built.'
		);
	}

	/**
	 * Builds lightweight SEO report context for article optimization workflows.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_seo_report_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		$content = $this->normalize_plain_text( $input['input'] ?? '' );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$issues = array();
		$recommendations = array();
		$score = 88;

		if ( '' !== $focus_keyword && ! $this->contains_text_ci( $content, $focus_keyword ) ) {
			$issues[] = array(
				'id'       => 'focus_keyword_missing',
				'severity' => 'high',
				'title'    => '焦点关键词未在正文中明显出现。',
				'detail'   => '建议在导语或第一个小标题附近自然补齐焦点关键词。',
			);
			$recommendations[] = array(
				'type'     => 'keyword',
				'priority' => 'high',
				'title'    => '补齐焦点关键词',
				'detail'   => '优先在首屏摘要和一个小标题中自然出现焦点关键词。',
			);
			$score -= 18;
		}

		if ( $this->measure_style_text_length( $content ) < 320 ) {
			$issues[] = array(
				'id'       => 'content_depth_thin',
				'severity' => 'medium',
				'title'    => '正文长度偏短，SEO 覆盖面不足。',
				'detail'   => '建议增加 1 到 2 段解释型内容，补齐背景或步骤。',
			);
			$recommendations[] = array(
				'type'     => 'depth',
				'priority' => 'medium',
				'title'    => '补内容深度',
				'detail'   => '增加对核心问题的背景解释、步骤或注意事项。',
			);
			$score -= 10;
		}

		return $this->build_analysis_success_response(
			array(
				'score'           => max( 0, $score ),
				'issues'          => $issues,
				'recommendations' => $recommendations,
				'summary'         => array(
					'high_priority_count' => count(
						array_filter(
							$issues,
							static function ( array $issue ) {
								return 'high' === sanitize_key( (string) ( $issue['severity'] ?? '' ) );
							}
						)
					),
					'focus_keyword'        => $focus_keyword,
				),
			),
			array(
				'source'         => 'local_seo_report_context',
				'execution_mode' => 'deterministic',
			),
			'SEO report context built.'
		);
	}

	/**
	 * Reads one post snapshot for internal optimization workflows.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function read_post_optimization_context( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_invalid', __( 'post_id is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 400 ) );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_post_missing', __( 'Post was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You are not allowed to read this post.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$categories = function_exists( 'get_the_terms' ) ? get_the_terms( $post_id, 'category' ) : array();
		$tags = function_exists( 'get_the_terms' ) ? get_the_terms( $post_id, 'post_tag' ) : array();
		$template = '';
		if ( function_exists( 'get_page_template_slug' ) ) {
			$template = sanitize_text_field( (string) get_page_template_slug( $post_id ) );
		}
		if ( '' === $template ) {
			$template = function_exists( 'get_post_meta' )
				? sanitize_text_field( (string) get_post_meta( $post_id, '_wp_page_template', true ) )
				: '';
		}
		$format = function_exists( 'get_post_format' ) ? sanitize_key( (string) get_post_format( $post_id ) ) : '';
		if ( '' === $format ) {
			$format = 'standard';
		}

		$seo_provider = $this->detect_seo_provider();
		$seo_meta_keys = $this->seo_meta_keys( $seo_provider );

		return $this->build_analysis_success_response(
			array(
				'id'         => $post_id,
				'title'      => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'status'     => sanitize_key( (string) $post->post_status ),
				'post_type'  => sanitize_key( (string) $post->post_type ),
				'excerpt'    => function_exists( 'has_excerpt' ) && has_excerpt( $post_id ) && function_exists( 'get_the_excerpt' ) ? $this->sanitize_metadata_text( (string) get_the_excerpt( $post_id ) ) : '',
				'content'    => (string) $post->post_content,
				'slug'       => $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) ),
				'edit_link'  => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
				'categories' => $this->format_term_name_list( $categories ),
				'tags'       => $this->format_term_name_list( $tags ),
				'seo'        => array(
					'provider'    => $seo_provider,
					'title'       => function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $post_id, (string) ( $seo_meta_keys['title'] ?? '' ), true ) ) : '',
					'description' => function_exists( 'get_post_meta' ) ? $this->sanitize_metadata_text( (string) get_post_meta( $post_id, (string) ( $seo_meta_keys['description'] ?? '' ), true ) ) : '',
				),
				'template'   => $template,
				'format'     => $format,
			),
			array(
				'source'         => 'local_post_optimization_context',
				'execution_mode' => 'deterministic',
			),
			'Post optimization context loaded.'
		);
	}

	/**
	 * Builds canonical single-article suggest envelope.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_single_optimization_suggest( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post = is_array( $input['post'] ?? null ) ? $input['post'] : array();
		$generated_excerpt = is_array( $input['generated_excerpt'] ?? null ) ? $input['generated_excerpt'] : array();
		$generated_seo = is_array( $input['generated_seo'] ?? null ) ? $input['generated_seo'] : array();
		$seo_analysis = is_array( $input['seo_analysis'] ?? null ) ? $input['seo_analysis'] : array();
		$geo_analysis = is_array( $input['geo_analysis'] ?? null ) ? $input['geo_analysis'] : array();
		$taxonomy_context = is_array( $input['taxonomy_context'] ?? null ) ? $input['taxonomy_context'] : array();
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$keyword_input = is_array( $input['keywords'] ?? null ) ? $input['keywords'] : array();
		$existing_content = $this->sanitize_metadata_text( (string) ( $post['content'] ?? '' ) );

		$current_title = sanitize_text_field( (string) ( $post['title'] ?? '' ) );
		$current_excerpt = $this->sanitize_metadata_text( (string) ( $post['excerpt'] ?? '' ) );
		$current_slug = $this->sanitize_metadata_slug( (string) ( $post['slug'] ?? '' ) );
		$current_seo = is_array( $post['seo'] ?? null ) ? $post['seo'] : array();

		$title_suggested = $current_title;
		$title_reason = '当前标题已可继续使用。';
		if ( '' !== $focus_keyword && ! $this->contains_text_ci( $current_title, $focus_keyword ) ) {
			$title_suggested = trim( $current_title . '｜' . $focus_keyword );
			$title_reason = '标题未覆盖焦点关键词，建议补齐到标题层。';
		}
		$title_suggestion_rows = $this->build_title_suggestions_from_context( $title_suggested, $existing_content, 3, $title_reason );

		$excerpt_suggested = $this->sanitize_metadata_text(
			(string) (
				$generated_excerpt['proposal_text']
				?? $generated_excerpt['excerpt']
				?? $generated_excerpt['text']
				?? ''
			)
		);
		if ( '' === $excerpt_suggested ) {
			$excerpt_suggested = $current_excerpt;
		}

		$seo_title_suggested = sanitize_text_field( (string) ( $generated_seo['meta_title'] ?? $generated_seo['title'] ?? '' ) );
		if ( '' === $seo_title_suggested ) {
			$seo_title_suggested = $title_suggested;
		}

		$seo_description_suggested = $this->sanitize_metadata_text( (string) ( $generated_seo['meta_description'] ?? $generated_seo['description'] ?? '' ) );
		if ( '' === $seo_description_suggested ) {
			$seo_description_suggested = $excerpt_suggested;
		}

		$slug_source = '' !== $focus_keyword ? $focus_keyword : $title_suggested;
		$slug_suggested = $this->sanitize_metadata_slug( $slug_source );
		if ( '' === $slug_suggested ) {
			$slug_suggested = $current_slug;
		}

		$keyword_candidates = $this->collect_article_suggest_keywords(
			array_merge(
				array( $focus_keyword, $title_suggested, $seo_title_suggested ),
				$keyword_input,
				is_array( $generated_seo['keywords'] ?? null ) ? $generated_seo['keywords'] : array()
			)
		);

		$category_candidates = is_array( $taxonomy_context['categories'] ?? null ) && ! empty( $taxonomy_context['categories'] )
			? array_values( $taxonomy_context['categories'] )
			: array_slice( $keyword_candidates, 0, 3 );
		$tag_candidates = is_array( $taxonomy_context['tags'] ?? null ) && ! empty( $taxonomy_context['tags'] )
			? array_values( $taxonomy_context['tags'] )
			: array_slice( $keyword_candidates, 0, 5 );

		$suggestions = array(
			'title'            => $this->build_article_suggest_field_row(
				'title',
				'标题',
				$current_title,
				$title_suggested,
				false,
				$title_reason
			),
			'title_suggestions' => $title_suggestion_rows,
			'categories'       => $this->build_article_suggest_taxonomy_row(
				'categories',
				'分类',
				is_array( $post['categories'] ?? null ) ? $post['categories'] : array(),
				$category_candidates,
				$this->get_article_suggest_taxonomy_catalog( 'category' )
			),
			'tags'             => $this->build_article_suggest_taxonomy_row(
				'tags',
				'标签',
				is_array( $post['tags'] ?? null ) ? $post['tags'] : array(),
				$tag_candidates,
				$this->get_article_suggest_taxonomy_catalog( 'post_tag' )
			),
			'excerpt'          => $this->build_article_suggest_field_row(
				'excerpt',
				'摘要',
				$current_excerpt,
				$excerpt_suggested,
				true,
				'摘要属于第一阶段低风险字段，可先预览再应用。'
			),
			'slug'             => $this->build_article_suggest_field_row(
				'slug',
				'别名 slug',
				$current_slug,
				$slug_suggested,
				true,
				'建议使用更短、更稳定、可读的 slug。'
			),
			'seo_title'        => $this->build_article_suggest_field_row(
				'seo_title',
				'SEO 标题',
				sanitize_text_field( (string) ( $current_seo['title'] ?? '' ) ),
				$seo_title_suggested,
				true,
				'建议让 SEO 标题与主题和焦点关键词对齐。'
			),
			'seo_description'  => $this->build_article_suggest_field_row(
				'seo_description',
				'SEO 描述',
				$this->sanitize_metadata_text( (string) ( $current_seo['description'] ?? '' ) ),
				$seo_description_suggested,
				true,
				'建议让 SEO 描述覆盖摘要主旨与核心关键词。'
			),
			'keywords'         => array(
				'field' => 'keywords',
				'label' => '关键词',
				'items' => $keyword_candidates,
			),
		);

		$content_improvements = array(
			array(
				'type'     => 'patch_proposal',
				'priority' => 'medium',
				'field'    => 'content',
				'section'  => 'article_body',
				'issue'    => '当前正文修改仍需停在 section-level proposal，不能直接走整篇自动改写。',
				'action'   => '先输出 patch plan、补充点和改写方向，再由人工决定如何修改正文。',
				'reason'   => '保持文章域默认写回边界不变，避免把 suggest 主线升级成整篇自动重写入口。',
				'title'    => '正文只停在 patch / section proposal',
				'detail'   => '当前主线只输出旧文章内容补强建议，不默认整篇自动重写。',
			),
		);
		if ( '' !== $focus_keyword && ! $this->contains_text_ci( $current_title, $focus_keyword ) ) {
			$content_improvements[] = array(
				'type'     => 'section_proposal',
				'priority' => 'medium',
				'field'    => 'content',
				'section'  => 'opening',
				'issue'    => '首屏还没有自然回答主题并覆盖焦点关键词。',
				'action'   => '在导语或第一个小标题附近补 1 段 answer-first 内容，先定义问题、给结论，再自然带入关键词。',
				'reason'   => '先补首屏定义和结论，能同时改善可回答性、关键词覆盖和读者进入正文的理解成本。',
				'title'    => '在导语或第一个小标题附近补齐焦点词',
				'detail'   => '建议在正文首屏增加 1 段直接回答型内容，并自然覆盖焦点关键词。',
			);
		}

		$seo_improvements = $this->build_analysis_improvement_rows( $seo_analysis, 'seo', 'seo_recommendation' );
		if ( empty( $seo_improvements ) ) {
			$seo_improvements[] = array(
				'type'     => 'seo_alignment',
				'priority' => 'medium',
				'field'    => 'seo',
				'title'    => '继续对齐标题、摘要与 SEO 元字段',
				'detail'   => 'SEO 改进块当前应继续围绕 title、seo_title、seo_description、slug 与 excerpt 协同收口。',
			);
		}

		$geo_improvements = $this->build_analysis_improvement_rows( $geo_analysis, 'content', 'geo_recommendation' );
		if ( empty( $geo_improvements ) ) {
			$geo_improvements[] = array(
				'type'     => 'answerability',
				'priority' => 'medium',
				'field'    => 'content',
				'title'    => '补一段 answer-first 摘要或 FAQ',
				'detail'   => 'GEO 改进块当前应继续围绕 answer-first summary、FAQ 和实体一致性给出建议。',
			);
		}

		$recommendations = $this->build_article_single_suggest_recommendations( $suggestions, $content_improvements, $seo_improvements, $geo_improvements );
		$high_priority_count = count(
			array_filter(
				$recommendations,
				static function ( array $row ) {
					return 'high' === sanitize_key( (string) ( $row['priority'] ?? '' ) );
				}
			)
		);
		$ai_risk_review = $this->build_article_ai_risk_review( $existing_content, 'generic', array(), 'medium', array(), array() );
		foreach ( array_slice( (array) ( $ai_risk_review['items'] ?? array() ), 0, 2 ) as $risk_item ) {
			$risk_item = is_array( $risk_item ) ? $risk_item : array();
			$recommendations[] = array(
				'priority' => sanitize_key( (string) ( $risk_item['severity'] ?? 'medium' ) ),
				'section'  => 'review',
				'title'    => sanitize_text_field( (string) ( $risk_item['title'] ?? 'AI 风险复核' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $risk_item['detail'] ?? '' ) ),
			);
		}

		return $this->build_analysis_success_response(
			array(
				'post'                 => array(
					'post_id'    => $this->absint_value( $post['id'] ?? $post['post_id'] ?? 0 ),
					'title'      => $current_title,
					'status'     => sanitize_key( (string) ( $post['status'] ?? '' ) ),
					'post_type'  => sanitize_key( (string) ( $post['post_type'] ?? '' ) ),
					'excerpt'    => $current_excerpt,
					'slug'       => $current_slug,
					'edit_link'  => $this->esc_url_value( (string) ( $post['edit_link'] ?? '' ) ),
					'categories' => is_array( $post['categories'] ?? null ) ? array_values( $post['categories'] ) : array(),
					'tags'       => is_array( $post['tags'] ?? null ) ? array_values( $post['tags'] ) : array(),
					'seo'        => array(
						'provider'    => sanitize_key( (string) ( $current_seo['provider'] ?? '' ) ),
						'title'       => sanitize_text_field( (string) ( $current_seo['title'] ?? '' ) ),
						'description' => $this->sanitize_metadata_text( (string) ( $current_seo['description'] ?? '' ) ),
					),
				),
				'suggestions'          => $suggestions,
				'content_improvements' => $content_improvements,
				'seo_improvements'     => $seo_improvements,
				'geo_improvements'     => $geo_improvements,
				'summary'              => array(
					'status'                => $high_priority_count > 0 ? 'needs_attention' : 'ready_for_review',
					'focus_keyword'         => $focus_keyword,
					'high_priority_count'   => $high_priority_count,
					'total_recommendations' => count( $recommendations ),
					'safe_apply_fields'     => array( 'excerpt', 'seo_title', 'seo_description', 'slug' ),
					'taxonomy_fields'       => array( 'categories', 'tags' ),
					'improvement_sections'  => array( 'content_improvements', 'seo_improvements', 'geo_improvements' ),
				),
				'review'               => array(
					'ai_risk_review' => $ai_risk_review,
				),
				'recommendations'      => $recommendations,
			),
			array(
				'source'         => 'local_article_single_optimization_suggest',
				'execution_mode' => 'deterministic',
			),
			'Article single optimization suggest built.'
		);
	}

	/**
	 * Builds one conservative apply plan from the read-only article optimization report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_optimization_apply_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post = is_array( $input['post'] ?? null ) ? $input['post'] : array();
		$report = is_array( $input['report'] ?? null ) ? $input['report'] : array();
		$optimization_plan = is_array( $input['optimization_plan'] ?? null ) ? $input['optimization_plan'] : array();
		$generated_excerpt = is_array( $input['generated_excerpt'] ?? null ) ? $input['generated_excerpt'] : array();
		$seo_meta = is_array( $input['seo_meta'] ?? null ) ? $input['seo_meta'] : array();

		$excerpt_mode = $this->read_plan_mode( $optimization_plan, 'excerpt_mode', 'keep' );
		$title_mode = $this->read_plan_mode( $optimization_plan, 'title_mode', 'keep' );
		$seo_mode = $this->read_plan_mode( $optimization_plan, 'seo_mode', 'suggest' );
		$geo_mode = $this->read_plan_mode( $optimization_plan, 'geo_mode', 'suggest' );
		$internal_link_mode = $this->read_plan_mode( $optimization_plan, 'internal_link_mode', 'suggest' );
		$faq_mode = $this->read_plan_mode( $optimization_plan, 'faq_mode', 'suggest' );
		$heading_mode = $this->read_plan_mode( $optimization_plan, 'heading_mode', 'keep' );
		$schema_hint_mode = $this->read_plan_mode( $optimization_plan, 'schema_hint_mode', 'suggest' );
		$risk_mode = $this->read_plan_mode( $optimization_plan, 'risk_mode', 'safe' );

		$regenerated_excerpt = $this->sanitize_metadata_text( (string) ( $generated_excerpt['proposal_text'] ?? '' ) );
		$current_excerpt = $this->sanitize_metadata_text( (string) ( $post['excerpt'] ?? '' ) );
		$excerpt_apply_generate = in_array( $excerpt_mode, array( 'regenerate', 'apply' ), true ) && '' !== $regenerated_excerpt;
		$recommendations = is_array( $report['recommendations'] ?? null ) ? $report['recommendations'] : array();
		$geo_summary = is_array( $report['geo']['summary'] ?? null ) ? $report['geo']['summary'] : array();
		$internal_summary = is_array( $report['internal_links']['summary'] ?? null ) ? $report['internal_links']['summary'] : array();
		$media_summary = is_array( $report['media']['summary'] ?? null ) ? $report['media']['summary'] : array();

		$actions = array(
			'excerpt'        => array(
				'mode'             => $excerpt_mode,
				'apply_generate'   => $excerpt_apply_generate,
				'current_excerpt'  => $current_excerpt,
				'proposed_excerpt' => $regenerated_excerpt,
				'write_supported'  => true,
			),
			'seo_meta'       => array(
				'mode'            => $seo_mode,
				'write_supported' => false,
				'proposal'        => $seo_meta,
			),
			'title'          => array(
				'mode'            => $title_mode,
				'write_supported' => false,
			),
			'geo'            => array(
				'mode'                => $geo_mode,
				'write_supported'     => false,
				'faq_candidate_count' => $this->absint_value( $geo_summary['faq_candidate_count'] ?? 0 ),
			),
			'internal_links' => array(
				'mode'            => $internal_link_mode,
				'write_supported' => false,
				'placement_count' => $this->absint_value( $internal_summary['placement_count'] ?? 0 ),
			),
			'faq'            => array(
				'mode'                => $faq_mode,
				'write_supported'     => false,
				'candidate_count'     => $this->absint_value( $geo_summary['faq_candidate_count'] ?? 0 ),
			),
			'headings'       => array(
				'mode'                 => $heading_mode,
				'write_supported'      => false,
				'recommendation_count' => $this->count_recommendation_section( $recommendations, 'seo' ) + $this->count_recommendation_section( $recommendations, 'geo' ),
			),
			'schema_hints'  => array(
				'mode'                => $schema_hint_mode,
				'write_supported'     => false,
				'faq_candidate_count' => $this->absint_value( $geo_summary['faq_candidate_count'] ?? 0 ),
			),
			'media'          => array(
				'mode'            => 'suggest',
				'write_supported' => false,
				'asset_count'     => $this->absint_value( $media_summary['asset_count'] ?? 0 ),
			),
		);

		$safe_write_actions = array();
		$write_actions = array();
		if ( $excerpt_apply_generate ) {
			$safe_write_actions[] = 'update_excerpt';
			$write_actions[] = array(
				'action_id'         => 'update_excerpt',
				'target_ability_id' => 'npcink-abilities-toolkit/update-post',
				'recipe_step'       => 'host_governed_update_excerpt',
				'input'             => array(
					'post_id' => $this->absint_value( $post['post_id'] ?? $post['id'] ?? 0 ),
					'excerpt' => $regenerated_excerpt,
					'dry_run' => true,
					'commit'  => false,
				),
				'risk'              => 'medium',
				'required_scopes'   => array( 'post.write' ),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
				'reason'            => __( 'Apply the reviewed article optimization excerpt through host governance.', 'npcink-abilities-toolkit' ),
			);
		}

		$advisory_sections = array();
		foreach ( array( 'seo_meta', 'title', 'geo', 'internal_links', 'faq', 'headings', 'schema_hints', 'media' ) as $section_key ) {
			$mode = sanitize_key( (string) ( $actions[ $section_key ]['mode'] ?? 'off' ) );
			if ( in_array( $mode, array( 'suggest', 'apply', 'replace', 'normalize', 'rewrite' ), true ) ) {
				$advisory_sections[] = $section_key;
			}
		}

		$summary = array(
			'post_id'               => $this->absint_value( $post['post_id'] ?? $post['id'] ?? 0 ),
			'safe_apply_supported'  => $safe_write_actions,
			'advisory_sections'     => array_values( array_unique( $advisory_sections ) ),
			'report_status'         => sanitize_key( (string) ( $report['summary']['status'] ?? 'ready_for_review' ) ),
			'high_priority_count'   => $this->absint_value( $report['summary']['high_priority_count'] ?? 0 ),
			'total_recommendations' => $this->absint_value( $report['summary']['total_recommendations'] ?? 0 ),
			'risk_mode'             => $risk_mode,
			'next_action'           => ! empty( $safe_write_actions ) ? 'safe_apply_available' : 'review_before_apply',
		);

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'article_optimization_apply_plan',
				'composition_role'       => 'core_article_optimization_apply_plan',
				'version'                => 1,
				'source_recipe_id'       => 'article_optimization_v1',
				'source_recipe_ref'      => 'npcink-abilities-toolkit/recipes/article-optimization',
				'source_recipe_provider' => 'npcink-abilities-toolkit',
				'recipe_execution'       => 'host_orchestration',
				'write_posture'          => 'core_proposal_handoff',
				'direct_wordpress_write' => false,
				'requires_approval'      => true,
				'dry_run'                => true,
				'commit_execution'       => false,
				'proposal_mode'          => 'single',
				'batch_id'               => 'article_optimization_' . substr( md5( wp_json_encode( array( $summary['post_id'], $regenerated_excerpt, $safe_write_actions ) ) ?: '' ), 0, 12 ),
				'post'                   => array(
					'post_id' => $this->absint_value( $post['post_id'] ?? $post['id'] ?? 0 ),
					'title'   => sanitize_text_field( (string) ( $post['title'] ?? '' ) ),
					'status'  => sanitize_key( (string) ( $post['status'] ?? '' ) ),
				),
				'actions'                => $actions,
				'write_actions'          => $write_actions,
				'summary'                => $summary,
				'handoff'                => array(
					'plan_ability_id'        => 'npcink-abilities-toolkit/build-article-optimization-apply-plan',
					'recipe_id'              => 'article_optimization_v1',
					'recipe_ref'             => 'npcink-abilities-toolkit/recipes/article-optimization',
					'core_route'             => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
					'final_write_path'       => 'core_proposal_required',
					'direct_wordpress_write' => false,
				),
			),
			array(
				'source'         => 'local_article_optimization_apply_plan',
				'execution_mode' => 'deterministic',
			),
			'Article optimization apply plan built.'
		);
	}

	/**
	 * Builds a Core-ready content metadata apply plan from reviewed choices.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_content_metadata_apply_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = $this->absint_value( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_content_metadata_post_required',
				__( 'A post_id is required to build a content metadata apply plan.', 'npcink-abilities-toolkit' ),
				array( 'status' => 400 )
			);
		}

		$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
		if ( ! $post ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_content_metadata_post_not_found',
				__( 'The requested post was not found.', 'npcink-abilities-toolkit' ),
				array( 'status' => 404 )
			);
		}

		$excerpt = trim(
			$this->content_metadata_bounded_text(
				(string) ( $input['excerpt'] ?? ( $input['selected_excerpt'] ?? ( $input['summary'] ?? '' ) ) ),
				500
			)
		);
		$category_ids = $this->content_metadata_term_ids( $input['category_ids'] ?? ( $input['categories'] ?? array() ), 'category' );
		if ( is_wp_error( $category_ids ) ) {
			return $category_ids;
		}
		$tag_ids = $this->content_metadata_term_ids( $input['tag_ids'] ?? ( $input['tags'] ?? array() ), 'post_tag' );
		if ( is_wp_error( $tag_ids ) ) {
			return $tag_ids;
		}

		if ( '' === $excerpt && empty( $category_ids ) && empty( $tag_ids ) ) {
			return new \WP_Error(
				'npcink_abilities_toolkit_content_metadata_selection_required',
				__( 'At least one reviewed excerpt, category id, or tag id is required to build a content metadata apply plan.', 'npcink-abilities-toolkit' ),
				array( 'status' => 400 )
			);
		}

		$category_mode = sanitize_key( (string) ( $input['category_mode'] ?? ( $input['mode'] ?? 'append' ) ) );
		$category_mode = in_array( $category_mode, array( 'append', 'replace' ), true ) ? $category_mode : 'append';
		$tag_mode = sanitize_key( (string) ( $input['tag_mode'] ?? ( $input['mode'] ?? 'append' ) ) );
		$tag_mode = in_array( $tag_mode, array( 'append', 'replace' ), true ) ? $tag_mode : 'append';

		$new_term_candidates = $this->content_metadata_new_term_candidates_from_input( $input );
		$evidence_refs = is_array( $input['evidence_refs'] ?? null ) ? array_values( $input['evidence_refs'] ) : array();
		$source_delta = is_array( $input['content_metadata_delta'] ?? null )
			? $input['content_metadata_delta']
			: ( is_array( $input['source_delta'] ?? null ) ? $input['source_delta'] : array() );
		$current_categories = $this->content_metadata_current_term_ids( $post_id, 'category' );
		$current_tags = $this->content_metadata_current_term_ids( $post_id, 'post_tag' );
		$hash_basis = wp_json_encode(
			array(
				'post_id'       => $post_id,
				'excerpt'       => $excerpt,
				'category_ids'  => $category_ids,
				'tag_ids'       => $tag_ids,
				'category_mode' => $category_mode,
				'tag_mode'      => $tag_mode,
			)
		);
		$hash_basis = is_string( $hash_basis ) ? $hash_basis : (string) $post_id;
		$batch_suffix = substr( md5( $hash_basis ), 0, 12 );
		$write_actions = array();
		$accepted_choices = array(
			'excerpt_selected'         => '' !== $excerpt,
			'category_ids'             => $category_ids,
			'category_mode'            => $category_mode,
			'tag_ids'                  => $tag_ids,
			'tag_mode'                 => $tag_mode,
			'new_term_candidate_count' => count( $new_term_candidates ),
			'new_term_policy'          => 'manual_review_only_no_create_term_action',
		);

		if ( '' !== $excerpt ) {
			$write_actions[] = array(
				'action_id'         => 'apply_selected_excerpt',
				'target_ability_id' => 'npcink-abilities-toolkit/update-post',
				'recipe_step'       => 'host_governed_update_excerpt',
				'input'             => array(
					'post_id'         => $post_id,
					'excerpt'         => $excerpt,
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'content-metadata-excerpt-' . $post_id . '-' . $batch_suffix,
				),
				'risk'              => 'low',
				'required_scopes'   => array( 'post.write' ),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
				'reason'            => __( 'Apply the reviewed excerpt through Core-governed update-post.', 'npcink-abilities-toolkit' ),
			);
		}

		if ( ! empty( $category_ids ) ) {
			$write_actions[] = array(
				'action_id'         => 'assign_existing_categories',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-terms',
				'recipe_step'       => 'host_governed_assign_existing_categories',
				'input'             => array(
					'post_id'         => $post_id,
					'taxonomy'        => 'category',
					'mode'            => $category_mode,
					'term_ids'        => $category_ids,
					'create_missing'  => false,
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'content-metadata-categories-' . $post_id . '-' . $batch_suffix,
				),
				'risk'              => 'medium',
				'required_scopes'   => array( 'taxonomy.manage' ),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
				'reason'            => __( 'Assign reviewed existing categories through Core-governed set-post-terms; this planner does not create or assign terms directly.', 'npcink-abilities-toolkit' ),
			);
		}

		if ( ! empty( $tag_ids ) ) {
			$write_actions[] = array(
				'action_id'         => 'assign_existing_tags',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-terms',
				'recipe_step'       => 'host_governed_assign_existing_tags',
				'input'             => array(
					'post_id'         => $post_id,
					'taxonomy'        => 'post_tag',
					'mode'            => $tag_mode,
					'term_ids'        => $tag_ids,
					'create_missing'  => false,
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'content-metadata-tags-' . $post_id . '-' . $batch_suffix,
				),
				'risk'              => 'low',
				'required_scopes'   => array( 'taxonomy.manage' ),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
				'reason'            => __( 'Assign reviewed existing tags through Core-governed set-post-terms; this planner does not create or assign terms directly.', 'npcink-abilities-toolkit' ),
			);
		}

		$manual_review = array();
		if ( ! empty( $new_term_candidates ) ) {
			$manual_review[] = array(
				'code'       => 'new_term_candidates_not_applied',
				'fields'     => array( 'new_term_candidates' ),
				'item_count' => count( $new_term_candidates ),
				'reason'     => __( 'Proposed new terms are preserved as vocabulary-gap review notes only. This plan never creates missing taxonomy terms.', 'npcink-abilities-toolkit' ),
			);
		}

		$post_type = is_object( $post ) ? (string) ( $post->post_type ?? '' ) : '';
		$post_status = function_exists( 'get_post_status' ) ? get_post_status( $post_id ) : ( is_object( $post ) ? (string) ( $post->post_status ?? '' ) : '' );
		$title = function_exists( 'get_the_title' ) ? get_the_title( $post ) : ( is_object( $post ) ? (string) ( $post->post_title ?? '' ) : '' );
		$current_excerpt = is_object( $post ) ? (string) ( $post->post_excerpt ?? '' ) : '';

		return $this->build_analysis_success_response(
			array(
				'artifact_type'          => 'content_metadata_apply_plan',
				'composition_role'       => 'core_content_metadata_apply_plan',
				'version'                => 1,
				'source_recipe_id'       => 'content_metadata_delta_v1',
				'source_recipe_ref'      => 'workflow/content_metadata_delta',
				'source_recipe_provider' => 'npcink-abilities-toolkit',
				'recipe_execution'       => 'host_orchestration',
				'write_posture'          => 'core_proposal_handoff',
				'direct_wordpress_write' => false,
				'batch_id'               => 'content_metadata_apply_' . $batch_suffix,
				'requires_approval'      => true,
				'dry_run'                => true,
				'commit_execution'       => false,
				'proposal_mode'          => 'batch',
				'batch_approval'         => true,
				'post'                   => array(
					'post_id'     => $post_id,
					'post_type'   => sanitize_key( $post_type ),
					'post_status' => sanitize_key( $post_status ),
					'title'       => sanitize_text_field( $title ),
				),
				'accepted_choices'       => $accepted_choices,
				'authorization'          => array(
					'classification'    => 'core_proposal_required',
					'requires_proposal' => true,
					'requires_approval' => true,
					'decision_envelope' => array(
						'post_id'             => $post_id,
						'action_count'        => count( $write_actions ),
						'accepted_choices'    => $accepted_choices,
						'direct_write_denied' => true,
					),
					'reasons'           => array(
						'excerpt_or_taxonomy_mutation',
						'existing_terms_only',
						'core_proposal_required',
					),
				),
				'evidence_refs'          => $this->content_metadata_sanitize_payload( $evidence_refs ),
				'source_delta'           => $this->content_metadata_sanitize_payload( $source_delta ),
				'new_term_candidates'    => $this->content_metadata_sanitize_payload( $new_term_candidates ),
				'preview'                => array(
					array(
						'action_id' => 'content_metadata_apply',
						'post_id'   => $post_id,
						'before'    => array(
							'excerpt'      => sanitize_textarea_field( $current_excerpt ),
							'category_ids' => $current_categories,
							'tag_ids'      => $current_tags,
						),
						'after_suggestion' => array(
							'excerpt'       => $excerpt,
							'category_ids'  => $category_ids,
							'category_mode' => $category_mode,
							'tag_ids'       => $tag_ids,
							'tag_mode'      => $tag_mode,
						),
					),
				),
				'manual_review'          => $manual_review,
				'write_actions'          => $write_actions,
				'risk'                   => array(
					'level'   => ! empty( $category_ids ) ? 'medium' : 'low',
					'reasons' => array(
						'excerpt_update_only_if_selected',
						'existing_terms_only',
						'no_create_missing_terms',
						'core_proposal_required',
					),
				),
				'handoff'                => array(
					'plan_ability_id'        => 'npcink-abilities-toolkit/build-content-metadata-apply-plan',
					'recipe_id'              => 'content_metadata_delta_v1',
					'recipe_ref'             => 'workflow/content_metadata_delta',
					'core_route'             => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
					'final_write_path'       => 'core_proposal_required',
					'direct_wordpress_write' => false,
					'proposal_ready'         => true,
				),
			),
			array(
				'source'         => 'local_content_metadata_apply_plan',
				'execution_mode' => 'deterministic',
			),
			'Content metadata apply plan built.'
		);
	}

	/**
	 * Normalizes existing term ids for content metadata apply plans.
	 *
	 * @param mixed  $raw Raw term id input.
	 * @param string $taxonomy Taxonomy name.
	 * @return array<int,int>|\WP_Error
	 */
	private function content_metadata_term_ids( $raw, $taxonomy ) {
		$raw = is_array( $raw ) ? $raw : array();
		$ids = array();
		foreach ( $raw as $value ) {
			$term_id = $this->absint_value( $value );
			if ( $term_id <= 0 ) {
				continue;
			}
			if ( function_exists( 'get_term' ) ) {
				$term = get_term( $term_id, $taxonomy );
				if ( ! $term || is_wp_error( $term ) ) {
					return new \WP_Error(
						'npcink_abilities_toolkit_content_metadata_term_not_found',
						__( 'One or more selected taxonomy terms were not found.', 'npcink-abilities-toolkit' ),
						array(
							'status'   => 400,
							'taxonomy' => $taxonomy,
							'term_id'  => $term_id,
						)
					);
				}
			}
			$ids[] = $term_id;
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Reads current post term ids when WordPress taxonomy APIs are available.
	 *
	 * @param int    $post_id Post id.
	 * @param string $taxonomy Taxonomy name.
	 * @return array<int,int>
	 */
	private function content_metadata_current_term_ids( $post_id, $taxonomy ) {
		if ( ! function_exists( 'wp_get_object_terms' ) ) {
			return array();
		}

		$ids = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $ids ) || ! is_array( $ids ) ) {
			return array();
		}

		return array_values( array_map( array( $this, 'absint_value' ), $ids ) );
	}

	/**
	 * Extracts deferred new-term review notes from common input fields.
	 *
	 * @param array<string,mixed> $input Input payload.
	 * @return array<int,mixed>
	 */
	private function content_metadata_new_term_candidates_from_input( array $input ) {
		$candidates = array();
		foreach ( array( 'new_term_candidates', 'proposed_new_terms', 'new_terms' ) as $field ) {
			if ( is_array( $input[ $field ] ?? null ) ) {
				$candidates = array_merge( $candidates, array_values( $input[ $field ] ) );
			}
		}

		return array_slice( $candidates, 0, 20 );
	}

	/**
	 * Sanitizes nested payloads while preserving review evidence shape.
	 *
	 * @param mixed $payload Raw payload.
	 * @return mixed
	 */
	private function content_metadata_sanitize_payload( $payload ) {
		if ( is_array( $payload ) ) {
			$clean = array();
			foreach ( $payload as $key => $value ) {
				$clean[ is_string( $key ) ? sanitize_key( $key ) : $key ] = $this->content_metadata_sanitize_payload( $value );
			}
			return $clean;
		}

		if ( is_bool( $payload ) || is_int( $payload ) || is_float( $payload ) || null === $payload ) {
			return $payload;
		}

		return $this->sanitize_metadata_text( (string) $payload );
	}

	/**
	 * Sanitizes and bounds content metadata text.
	 *
	 * @param string $value Raw text.
	 * @param int    $max_length Maximum byte length.
	 * @return string
	 */
	private function content_metadata_bounded_text( $value, $max_length ) {
		$value = $this->sanitize_metadata_text( (string) $value );
		if ( strlen( $value ) <= $max_length ) {
			return $value;
		}

		return substr( $value, 0, $max_length );
	}

	/**
	 * Composes one conservative optimization apply result envelope.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function compose_article_optimization_apply_result( $input ) {
		$input = is_array( $input ) ? $input : array();
		$report = is_array( $input['report'] ?? null ) ? $input['report'] : array();
		$apply_plan = is_array( $input['apply_plan'] ?? null ) ? $input['apply_plan'] : array();
		$apply_excerpt = is_array( $input['apply_excerpt'] ?? null ) ? $input['apply_excerpt'] : array();

		$plan_summary = is_array( $apply_plan['summary'] ?? null ) ? $apply_plan['summary'] : array();
		$actions = is_array( $apply_plan['actions'] ?? null ) ? $apply_plan['actions'] : array();
		$safe_apply_supported = is_array( $plan_summary['safe_apply_supported'] ?? null ) ? $plan_summary['safe_apply_supported'] : array();
		$advisory_sections = is_array( $plan_summary['advisory_sections'] ?? null ) ? $plan_summary['advisory_sections'] : array();
		$applied_changes = array();
		if ( true === (bool) ( $apply_excerpt['updated'] ?? false ) ) {
			$applied_changes[] = array(
				'type'    => 'excerpt',
				'post_id' => $this->absint_value( $apply_excerpt['post_id'] ?? 0 ),
				'changes' => is_array( $apply_excerpt['changes'] ?? null ) ? $apply_excerpt['changes'] : array(),
			);
		}

		$result_mode = empty( $applied_changes ) ? 'plan_only' : 'partial_apply';
		$summary_parts = array(
			'结果：' . ( 'partial_apply' === $result_mode ? '已安全应用部分优化' : '已生成应用计划' ),
			'安全可写项：' . ( ! empty( $safe_apply_supported ) ? implode( '、', array_map( array( $this, 'sanitize_text_value' ), $safe_apply_supported ) ) : '无' ),
		);
		if ( ! empty( $advisory_sections ) ) {
			$summary_parts[] = '仍停在建议层：' . implode( '、', array_map( array( $this, 'sanitize_text_value' ), $advisory_sections ) );
		}
		if ( ! empty( $applied_changes ) ) {
			$summary_parts[] = '已应用：' . implode(
				'、',
				array_map(
					static function ( array $row ): string {
						return sanitize_text_field( (string) ( $row['type'] ?? '' ) );
					},
					$applied_changes
				)
			);
		}

		return $this->build_analysis_success_response(
			array(
				'report'          => $report,
				'apply_plan'      => $apply_plan,
				'applied_changes' => $applied_changes,
				'summary'         => array(
					'result_mode'          => $result_mode,
					'next_action'          => ! empty( $advisory_sections ) ? 'review_remaining_changes' : 'safe_apply_complete',
					'safe_apply_supported' => $safe_apply_supported,
					'advisory_sections'    => $advisory_sections,
					'applied_count'        => count( $applied_changes ),
				),
				'summary_text'    => implode( '；', array_filter( $summary_parts ) ),
				'actions'         => $actions,
			),
			array(
				'source'         => 'local_article_optimization_apply_result',
				'execution_mode' => 'deterministic',
			),
			'Article optimization apply result built.'
		);
	}

	/**
	 * Counts recommendations in one section.
	 *
	 * @param array<int,mixed> $rows Recommendation rows.
	 * @param string           $section Section key.
	 * @return int
	 */
	private function count_recommendation_section( array $rows, $section ) {
		$count = 0;
		foreach ( $rows as $row ) {
			$row = is_array( $row ) ? $row : array();
			if ( $section === sanitize_key( (string) ( $row['section'] ?? '' ) ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Builds deterministic title suggestion rows from context.
	 *
	 * @param string $title Candidate title.
	 * @param string $content Content context.
	 * @param int    $limit Max suggestions.
	 * @param string $fallback_reason Reason fallback.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_title_suggestions_from_context( $title, $content, $limit, $fallback_reason ) {
		if ( function_exists( 'npcink_ai_build_title_suggestions_from_context' ) ) {
			$suggestions = npcink_ai_build_title_suggestions_from_context( (string) $title, (string) $content, (int) $limit );
			if ( is_array( $suggestions ) ) {
				return $suggestions;
			}
		}

		return array(
			array(
				'title'      => sanitize_text_field( (string) $title ),
				'reason'     => sanitize_text_field( (string) $fallback_reason ),
				'confidence' => 0.72,
			),
		);
	}

	/**
	 * Collects de-duplicated keyword candidates for one article suggest run.
	 *
	 * @param array<int|string,mixed> $parts Raw text parts.
	 * @return array<int,string>
	 */
	private function collect_article_suggest_keywords( array $parts ) {
		$keywords = array();
		foreach ( $parts as $part ) {
			$text = trim( sanitize_text_field( (string) $part ) );
			if ( '' === $text ) {
				continue;
			}

			$keywords[] = $text;
			$tokens = preg_split( '/[\s,，、\/|;；:：]+/u', $text );
			foreach ( is_array( $tokens ) ? $tokens : array() as $token ) {
				$token = trim( sanitize_text_field( (string) $token ) );
				$length = function_exists( 'mb_strlen' ) ? mb_strlen( $token, 'UTF-8' ) : strlen( $token );
				if ( $length >= 2 ) {
					$keywords[] = $token;
				}
			}
		}

		return array_values(
			array_unique(
				array_filter(
					$keywords,
					static function ( $value ) {
						return '' !== (string) $value;
					}
				)
			)
		);
	}

	/**
	 * Returns lightweight term catalog for one taxonomy.
	 *
	 * @param string $taxonomy Taxonomy id.
	 * @return array<int,array<string,string>>
	 */
	private function get_article_suggest_taxonomy_catalog( $taxonomy ) {
		if ( ! function_exists( 'get_terms' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => sanitize_key( (string) $taxonomy ),
				'hide_empty' => false,
				'number'     => 50,
			)
		);
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) {
			return array();
		}
		if ( ! is_array( $terms ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					array( $this, 'format_article_suggest_term' ),
					$terms
				)
			)
		);
	}

	/**
	 * Formats one term object for article suggestions.
	 *
	 * @param mixed $term Term object.
	 * @return array<string,string>
	 */
	private function format_article_suggest_term( $term ) {
		if ( ! is_object( $term ) ) {
			return array();
		}

		return array(
			'name' => sanitize_text_field( (string) ( $term->name ?? '' ) ),
			'slug' => $this->sanitize_metadata_slug( (string) ( $term->slug ?? '' ) ),
		);
	}

	/**
	 * Matches keyword candidates against site taxonomy catalog.
	 *
	 * @param array<int,string>               $keywords Keyword candidates.
	 * @param array<int,array<string,string>> $catalog Site term catalog.
	 * @return array<int,array<string,string>>
	 */
	private function match_article_suggest_taxonomy_terms( array $keywords, array $catalog ) {
		$matches = array();
		foreach ( $catalog as $term ) {
			$name = sanitize_text_field( (string) ( $term['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}

			foreach ( $keywords as $keyword ) {
				$keyword = sanitize_text_field( (string) $keyword );
				if ( '' === $keyword ) {
					continue;
				}
				if ( $this->contains_text_ci( $name, $keyword ) || $this->contains_text_ci( $keyword, $name ) ) {
					$matches[] = array(
						'name' => $name,
						'slug' => $this->sanitize_metadata_slug( (string) ( $term['slug'] ?? '' ) ),
					);
					break;
				}
			}
		}

		$unique = array();
		foreach ( $matches as $term ) {
			$key = sanitize_key( $this->sanitize_metadata_slug( (string) ( $term['slug'] ?? $term['name'] ?? '' ) ) );
			if ( '' !== $key ) {
				$unique[ $key ] = $term;
			}
		}

		return array_values( $unique );
	}

	/**
	 * Builds one scalar field suggestion row.
	 *
	 * @param string $field Field id.
	 * @param string $label UI label.
	 * @param string $current Current value.
	 * @param string $suggested Suggested value.
	 * @param bool   $safe_apply Whether field is low-risk writable.
	 * @param string $reason Suggestion reason.
	 * @return array<string,mixed>
	 */
	private function build_article_suggest_field_row( $field, $label, $current, $suggested, $safe_apply, $reason ) {
		$field = sanitize_key( (string) $field );
		$current = in_array( $field, array( 'excerpt', 'seo_description' ), true )
			? $this->sanitize_metadata_text( (string) $current )
			: sanitize_text_field( (string) $current );
		$suggested = in_array( $field, array( 'excerpt', 'seo_description' ), true )
			? $this->sanitize_metadata_text( (string) $suggested )
			: sanitize_text_field( (string) $suggested );

		return array(
			'field'            => $field,
			'label'            => sanitize_text_field( (string) $label ),
			'current_value'    => $current,
			'suggested_value'  => $suggested,
			'recommend_apply'  => '' !== $suggested && $suggested !== $current,
			'safe_apply'       => (bool) $safe_apply,
			'reason'           => $this->sanitize_metadata_text( (string) $reason ),
		);
	}

	/**
	 * Builds one taxonomy suggestion row with site matching.
	 *
	 * @param string                          $field Field id.
	 * @param string                          $label UI label.
	 * @param array<int,mixed>                $current_items Current term names.
	 * @param array<int,mixed>                $suggested_items Suggested term candidates.
	 * @param array<int,array<string,string>> $catalog Site term catalog.
	 * @return array<string,mixed>
	 */
	private function build_article_suggest_taxonomy_row( $field, $label, array $current_items, array $suggested_items, array $catalog ) {
		$current_items = array_values(
			array_filter(
				array_map( 'sanitize_text_field', $current_items )
			)
		);
		$suggested_items = array_values(
			array_filter(
				array_map( 'sanitize_text_field', $suggested_items )
			)
		);
		$matched_terms = $this->match_article_suggest_taxonomy_terms( $suggested_items, $catalog );
		$matched_names = array_values(
			array_filter(
				array_map(
					static function ( array $term ) {
						return sanitize_text_field( (string) ( $term['name'] ?? '' ) );
					},
					$matched_terms
				)
			)
		);

		return array(
			'field'           => sanitize_key( (string) $field ),
			'label'           => sanitize_text_field( (string) $label ),
			'current_items'   => $current_items,
			'suggested_items' => array_slice( $suggested_items, 0, 5 ),
			'matched_items'   => $matched_names,
			'recommend_apply' => ! empty( $matched_names ),
			'safe_apply'      => false,
			'risk_note'       => empty( $matched_names )
				? '未匹配到站点现有词库，当前只建议人工判断，不默认新建。'
				: '已匹配到站点现有词库，第一阶段仍建议人工确认后再应用。',
		);
	}

	/**
	 * Builds normalized improvement rows from analysis recommendations.
	 *
	 * @param array<string,mixed> $analysis Analysis result.
	 * @param string              $field Field id.
	 * @param string              $fallback_type Fallback type.
	 * @return array<int,array<string,string>>
	 */
	private function build_analysis_improvement_rows( array $analysis, $field, $fallback_type ) {
		$rows = array();
		foreach ( (array) ( $analysis['recommendations'] ?? array() ) as $item ) {
			$item = is_array( $item ) ? $item : array();
			$rows[] = array(
				'type'     => sanitize_key( (string) ( $item['type'] ?? $fallback_type ) ),
				'priority' => sanitize_key( (string) ( $item['priority'] ?? 'medium' ) ),
				'field'    => sanitize_key( (string) $field ),
				'title'    => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $item['detail'] ?? '' ) ),
			);
		}

		return $rows;
	}

	/**
	 * Builds the summary recommendation list for single-article suggestions.
	 *
	 * @param array<string,mixed> $suggestions Suggestion map.
	 * @param array<int,mixed>    $content_improvements Content improvements.
	 * @param array<int,mixed>    $seo_improvements SEO improvements.
	 * @param array<int,mixed>    $geo_improvements GEO improvements.
	 * @return array<int,array<string,string>>
	 */
	private function build_article_single_suggest_recommendations( array $suggestions, array $content_improvements, array $seo_improvements, array $geo_improvements ) {
		$recommendations = array();
		foreach ( array( 'title', 'excerpt', 'slug', 'seo_title', 'seo_description' ) as $field_key ) {
			$field = is_array( $suggestions[ $field_key ] ?? null ) ? $suggestions[ $field_key ] : array();
			if ( empty( $field['recommend_apply'] ) ) {
				continue;
			}
			$recommendations[] = array(
				'priority' => in_array( $field_key, array( 'title', 'seo_title', 'seo_description' ), true ) ? 'high' : 'medium',
				'section'  => $field_key,
				'title'    => '建议更新' . sanitize_text_field( (string) ( $field['label'] ?? $field_key ) ),
				'detail'   => $this->sanitize_metadata_text( (string) ( $field['suggested_value'] ?? '' ) ),
			);
		}
		foreach ( array( 'categories', 'tags' ) as $field_key ) {
			$field = is_array( $suggestions[ $field_key ] ?? null ) ? $suggestions[ $field_key ] : array();
			$items = (array) ( $field['matched_items'] ?? $field['suggested_items'] ?? array() );
			$detail = implode( '、', array_map( 'sanitize_text_field', $items ) );
			if ( '' === $detail ) {
				continue;
			}
			$recommendations[] = array(
				'priority' => 'medium',
				'section'  => $field_key,
				'title'    => '建议检查' . sanitize_text_field( (string) ( $field['label'] ?? $field_key ) ),
				'detail'   => $detail,
			);
		}
		foreach ( array( 'content_improvements' => $content_improvements, 'seo_improvements' => $seo_improvements, 'geo_improvements' => $geo_improvements ) as $section_key => $items ) {
			foreach ( array_slice( $items, 0, 2 ) as $item ) {
				$item = is_array( $item ) ? $item : array();
				$recommendations[] = array(
					'priority' => sanitize_key( (string) ( $item['priority'] ?? 'medium' ) ),
					'section'  => sanitize_key( (string) $section_key ),
					'title'    => sanitize_text_field( (string) ( $item['title'] ?? $section_key ) ),
					'detail'   => $this->sanitize_metadata_text( (string) ( $item['detail'] ?? '' ) ),
				);
			}
		}

		return $recommendations;
	}
}
