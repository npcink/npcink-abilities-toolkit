<?php
/**
 * Media read methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides media inventory, metadata, and inline image read callbacks.
 */
trait Media_Read_Methods {
	/**
	 * Lists media attachments.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_media( $input ) {
		$input = is_array( $input ) ? $input : array();
		$mime_type = sanitize_text_field( (string) ( $input['mime_type'] ?? '' ) );
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);
		if ( '' !== $mime_type ) {
			$args['post_mime_type'] = $mime_type;
		}
		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$date_from = sanitize_text_field( (string) ( $input['date_from'] ?? '' ) );
		$date_to = sanitize_text_field( (string) ( $input['date_to'] ?? '' ) );
		if ( '' !== $date_from || '' !== $date_to ) {
			$args['date_query'] = array();
			if ( '' !== $date_from ) {
				$args['date_query']['after'] = $date_from;
			}
			if ( '' !== $date_to ) {
				$args['date_query']['before'] = $date_to;
			}
			}

			if ( ! empty( $input['has_empty_alt'] ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Optional bounded media audit filter for missing attachment alt text.
				$args['meta_query'] = is_array( $args['meta_query'] ?? null ) ? $args['meta_query'] : array();
				$args['meta_query'][] = array(
					'relation' => 'OR',
				array(
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '=',
				),
			);
			}

			if ( ! empty( $input['has_empty_caption'] ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Optional bounded media audit filter for missing attachment metadata.
				$args['meta_query'] = is_array( $args['meta_query'] ?? null ) ? $args['meta_query'] : array();
				$args['meta_query'][] = array(
					'relation' => 'OR',
				array(
					'key'     => '_wp_attachment_metadata',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_wp_attachment_metadata',
					'value'   => '',
					'compare' => '=',
				),
			);
		}

		$query = new \WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$attachment = get_post( $post_id );
			$parent_id = is_object( $attachment ) ? absint( $attachment->post_parent ?? 0 ) : 0;
			$items[] = array(
				'id'        => $post_id,
				'title'     => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'date'      => sanitize_text_field( (string) get_post_field( 'post_date', $post_id ) ),
				'mime_type' => sanitize_text_field( (string) get_post_mime_type( $post_id ) ),
				'url'       => esc_url_raw( (string) wp_get_attachment_url( $post_id ) ),
				'attached_to' => $parent_id > 0 ? array(
					'post_id'    => $parent_id,
					'post_type'  => sanitize_key( (string) get_post_type( $parent_id ) ),
					'post_title' => sanitize_text_field( (string) get_the_title( $parent_id ) ),
				) : null,
				'usage'     => $this->build_media_usage_context( $post_id ),
				'edit_link' => get_edit_post_link( $post_id, 'raw' ),
			);
		}

		return array(
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Builds bounded usage context for one attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>
	 */
	private function build_media_usage_context( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 ) {
			return array();
		}

			$featured_query = new \WP_Query(
				array(
					'post_type'      => 'any',
					'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
					'posts_per_page' => 5,
					'fields'         => 'ids',
					// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Attachment usage context needs a bounded featured-image lookup by _thumbnail_id.
					'meta_key'       => '_thumbnail_id',
					'meta_value'     => $attachment_id,
					// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
		$featured_posts = array();
		foreach ( (array) $featured_query->posts as $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id > 0 && current_user_can( 'edit_post', $post_id ) ) {
				$featured_posts[] = array(
					'post_id'    => $post_id,
					'post_type'  => sanitize_key( (string) get_post_type( $post_id ) ),
					'post_title' => sanitize_text_field( (string) get_the_title( $post_id ) ),
				);
			}
		}

		return array(
			'featured_image_count' => (int) $featured_query->found_posts,
			'featured_image_posts' => $featured_posts,
			'content_reference_scan_run' => false,
		);
	}

	/**
	 * Builds append-ready Gutenberg image blocks from uploaded media rows.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_inline_image_blocks( $input ) {
		$input = is_array( $input ) ? $input : array();
		$upload_rows = is_array( $input['uploaded_inline_media'] ?? null ) ? $input['uploaded_inline_media'] : array();
		$generated_upload_rows = is_array( $input['generated_inline_media'] ?? null ) ? $input['generated_inline_media'] : array();
		$plan_rows = is_array( $input['inline_plan'] ?? null ) ? $input['inline_plan'] : array();
		$blocks = array();

		$max_rows = max( count( $upload_rows ), count( $generated_upload_rows ), count( $plan_rows ) );
		for ( $index = 0; $index < $max_rows; ++$index ) {
			$upload_row = is_array( $upload_rows[ $index ] ?? null ) ? $upload_rows[ $index ] : array();
			if ( $this->absint_value( $upload_row['attachment_id'] ?? 0 ) <= 0 && is_array( $generated_upload_rows[ $index ] ?? null ) ) {
				$upload_row = $generated_upload_rows[ $index ];
			}
			$plan_row = is_array( $plan_rows[ $index ] ?? null ) ? $plan_rows[ $index ] : array();
			$attachment_id = $this->absint_value( $upload_row['attachment_id'] ?? 0 );
			$image_url = $this->esc_url_value( (string) ( $upload_row['url'] ?? '' ) );
			if ( $attachment_id <= 0 || '' === $image_url ) {
				continue;
			}

			$alt = sanitize_text_field( (string) ( $plan_row['alt'] ?? $upload_row['alt'] ?? '' ) );
			$caption = $this->sanitize_metadata_text( (string) ( $plan_row['caption'] ?? $upload_row['caption'] ?? '' ) );
			$placement_key = $this->sanitize_html_class_value( (string) ( $plan_row['placement_key'] ?? 'inline_' . max( 1, (int) $index + 1 ) ) );
			$img_tag = '<img src="' . $this->esc_attr_value( $image_url ) . '" alt="' . $this->esc_attr_value( $alt ) . '" class="wp-image-' . $attachment_id . '" />';
			$figcaption = '' !== $caption ? '<figcaption>' . $this->esc_html_value( $caption ) . '</figcaption>' : '';

			$blocks[] = array(
				'blockName'   => 'core/image',
				'attrs'       => array(
					'id'              => $attachment_id,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
					'alt'             => $alt,
					'className'       => 'magick-ai-inline-image ' . $placement_key,
				),
				'innerHTML'   => '<figure class="wp-block-image size-large magick-ai-inline-image ' . $this->esc_attr_value( $placement_key ) . '">' . $img_tag . $figcaption . '</figure>',
				'innerBlocks' => array(),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'blocks'  => $blocks,
				'summary' => array(
					'count' => count( $blocks ),
					'mode'  => 'append',
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Builds canonical media SEO enrichment asset rows from article media workflow outputs.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_media_seo_assets( $input ) {
		$input = is_array( $input ) ? $input : array();
		$article = is_array( $input['article'] ?? null ) ? $input['article'] : array();
		$resolved = is_array( $input['resolved_image_source'] ?? null ) ? $input['resolved_image_source'] : array();
		$featured = is_array( $resolved['featured'] ?? null ) ? $resolved['featured'] : array();
		$inline_plan = is_array( $resolved['inline'] ?? null ) ? $resolved['inline'] : array();
		$featured_upload = is_array( $input['featured_upload'] ?? null ) ? $input['featured_upload'] : array();
		$generated_featured_upload = is_array( $input['generated_featured_upload'] ?? null ) ? $input['generated_featured_upload'] : array();
		$inline_uploads = is_array( $input['inline_uploads'] ?? null ) ? $input['inline_uploads'] : array();
		$generated_inline_uploads = is_array( $input['generated_inline_uploads'] ?? null ) ? $input['generated_inline_uploads'] : array();
		$vision_fallback_mode = sanitize_key( (string) ( $input['vision_fallback_mode'] ?? 'auto' ) );
		if ( ! in_array( $vision_fallback_mode, array( 'off', 'auto' ), true ) ) {
			$vision_fallback_mode = 'auto';
		}

		$items = array();
		$featured_upload_row = $this->absint_value( $featured_upload['attachment_id'] ?? 0 ) > 0 ? $featured_upload : $generated_featured_upload;
		if ( $this->absint_value( $featured_upload_row['attachment_id'] ?? 0 ) > 0 ) {
			$items[] = $this->build_media_seo_asset_row( $featured, $featured_upload_row, 'featured', 'auto' === $vision_fallback_mode );
		}

		$max_inline_uploads = max( count( $inline_uploads ), count( $generated_inline_uploads ) );
		for ( $index = 0; $index < $max_inline_uploads; ++$index ) {
			$upload_row = is_array( $inline_uploads[ $index ] ?? null ) ? $inline_uploads[ $index ] : array();
			if ( $this->absint_value( $upload_row['attachment_id'] ?? 0 ) <= 0 && is_array( $generated_inline_uploads[ $index ] ?? null ) ) {
				$upload_row = $generated_inline_uploads[ $index ];
			}
			if ( $this->absint_value( $upload_row['attachment_id'] ?? 0 ) <= 0 ) {
				continue;
			}
			$descriptor = is_array( $inline_plan[ $index ] ?? null ) ? $inline_plan[ $index ] : array();
			$items[] = $this->build_media_seo_asset_row( $descriptor, $upload_row, 'inline', 'auto' === $vision_fallback_mode );
		}

		return array(
			'success' => true,
			'data'    => array(
				'items'   => $items,
				'summary' => array(
					'asset_count'            => count( $items ),
					'featured_attachment_id' => $this->absint_value( $featured_upload_row['attachment_id'] ?? 0 ),
					'inline_attachment_count' => max( 0, count( $items ) - ( $this->absint_value( $featured_upload_row['attachment_id'] ?? 0 ) > 0 ? 1 : 0 ) ),
					'article_title'          => sanitize_text_field( (string) ( $article['title'] ?? '' ) ),
					'vision_fallback_mode'   => $vision_fallback_mode,
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'deterministic',
			),
		);
	}

	/**
	 * Builds media metadata optimization suggestions.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function optimize_media_metadata( $input ) {
		$input = is_array( $input ) ? $input : array();
		$article_title = sanitize_text_field( (string) ( $input['article_title'] ?? '' ) );
		$article_excerpt = sanitize_textarea_field( (string) ( $input['article_excerpt'] ?? '' ) );
		$article_content = (string) ( $input['article_content'] ?? '' );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$vision_fallback_mode = sanitize_key( (string) ( $input['vision_fallback_mode'] ?? 'auto' ) );
		if ( ! in_array( $vision_fallback_mode, array( 'off', 'auto' ), true ) ) {
			$vision_fallback_mode = 'auto';
		}
		$assets = $this->collect_media_assets_for_optimization(
			is_array( $input['media_assets'] ?? null ) ? $input['media_assets'] : array(),
			$article_content
		);

		$recommendations = array();
		$missing_alt_count = 0;
		$format_attention_count = 0;
		$vision_fallback_count = 0;
		foreach ( $assets as $index => $asset ) {
			$asset = is_array( $asset ) ? $asset : array();
			$alt = sanitize_text_field( (string) ( $asset['alt'] ?? '' ) );
			$title = sanitize_text_field( (string) ( $asset['title'] ?? '' ) );
			$caption = sanitize_textarea_field( (string) ( $asset['caption'] ?? '' ) );
			$description = sanitize_textarea_field( (string) ( $asset['description'] ?? '' ) );
			$mime_type = sanitize_text_field( (string) ( $asset['mime_type'] ?? '' ) );
			$section_heading = sanitize_text_field( (string) ( $asset['section_heading'] ?? '' ) );
			$section_summary = sanitize_textarea_field( (string) ( $asset['section_summary'] ?? '' ) );
			$generated_prompt = sanitize_textarea_field( (string) ( $asset['generated_prompt'] ?? '' ) );
			$image_profile = sanitize_key( (string) ( $asset['image_profile'] ?? '' ) );
			$provider_title = sanitize_text_field( (string) ( $asset['provider_title'] ?? '' ) );
			$provider_description = sanitize_textarea_field( (string) ( $asset['provider_description'] ?? $asset['alt_description'] ?? '' ) );
			$provider_hint = sanitize_key( (string) ( $asset['provider_hint'] ?? '' ) );
			$source_page_url = $this->esc_url_value( (string) ( $asset['source_page_url'] ?? '' ) );
			$photographer_name = sanitize_text_field( (string) ( $asset['photographer_name'] ?? '' ) );
			$attribution_text = sanitize_textarea_field( (string) ( $asset['attribution_text'] ?? '' ) );
			$license = sanitize_text_field( (string) ( $asset['license'] ?? '' ) );
			$copyright_notice = sanitize_textarea_field( (string) ( $asset['copyright_notice'] ?? '' ) );
			$file_name = $this->sanitize_file_name_value( (string) ( $asset['file_name'] ?? '' ) );
			$needs_format_attention = '' !== $mime_type && false === strpos( $mime_type, 'webp' ) && false === strpos( $mime_type, 'avif' );

			if ( '' === $alt ) {
				++$missing_alt_count;
			}
			if ( $needs_format_attention ) {
				++$format_attention_count;
			}

			$image_origin = $this->infer_media_image_origin( $asset );
			$strategy = 'manual_context';
			if ( 'ai_generated' === $image_origin ) {
				$strategy = 'ai_context';
			} elseif ( 'public_free' === $image_origin ) {
				$strategy = 'provider_metadata';
			}

			$title_seed = '';
			$caption_seed = '';
			$description_seed = '';
			$alt_context_parts = array();
			$metadata_confidence = 'low';
			if ( 'ai_generated' === $image_origin ) {
				$title_seed = '' !== $section_heading ? $section_heading : ( '' !== $article_title ? $article_title : $provider_title );
				$caption_seed = '' !== $section_summary ? $section_summary : $article_excerpt;
				$description_seed = '' !== $generated_prompt ? $generated_prompt : $caption_seed;
				$alt_context_parts = array_filter( array( $title_seed, $caption_seed, '' !== $image_profile ? '图像档位：' . $image_profile : '', '' !== $generated_prompt ? '生成提示：' . $generated_prompt : '' ) );
				$metadata_confidence = '' !== $generated_prompt ? 'high' : 'medium';
			} elseif ( 'public_free' === $image_origin ) {
				$title_seed = '' !== $provider_title ? $provider_title : ( '' !== $section_heading ? $section_heading : $article_title );
				$caption_seed = '' !== $section_summary ? $section_summary : $article_excerpt;
				$description_seed = '' !== $provider_description ? $provider_description : $caption_seed;
				$alt_context_parts = array_filter( array( $title_seed, $description_seed, '' !== $photographer_name ? '摄影：' . $photographer_name : '', '' !== $attribution_text ? '归因：' . $attribution_text : '' ) );
				$metadata_confidence = ( '' !== $provider_title || '' !== $provider_description || '' !== $photographer_name ) ? 'high' : 'medium';
			} else {
				$title_seed = '' !== $title ? $title : ( '' !== $section_heading ? $section_heading : $article_title );
				$caption_seed = '' !== $section_summary ? $section_summary : $article_excerpt;
				$description_seed = '' !== $description ? $description : $caption_seed;
				$alt_context_parts = array_filter( array( $title_seed, $description_seed, '' !== $file_name ? '文件：' . $file_name : '' ) );
				$manual_signal_count = 0;
				if ( '' !== $title || '' !== $caption || '' !== $description ) {
					++$manual_signal_count;
				}
				if ( '' !== $section_heading || '' !== $section_summary ) {
					++$manual_signal_count;
				}
				if ( '' !== $file_name ) {
					++$manual_signal_count;
				}
				$metadata_confidence = $manual_signal_count >= 2 ? 'medium' : 'low';
			}

			$vision_fallback_recommended = 'off' !== $vision_fallback_mode && ! empty( $asset['vision_fallback_allowed'] ) && 'low' === $metadata_confidence;
			if ( $vision_fallback_recommended ) {
				++$vision_fallback_count;
			}
			$license = $this->resolve_media_license_label( $asset, $image_origin );
			$disclosure_readiness = $this->resolve_media_disclosure_readiness( $image_origin, $metadata_confidence, $license, $source_page_url, $attribution_text, $generated_prompt );
			$recommended_alt = '' !== $alt
				? $alt
				: $this->trim_media_seo_text( trim( implode( ' - ', array_filter( array( $title_seed, '' !== $focus_keyword ? $focus_keyword : '', '' !== $section_heading && $section_heading !== $title_seed ? $section_heading : '' ) ) ) ), 140 );
			$recommended_title = '' !== $title ? $title : $this->trim_media_seo_text( $title_seed, 80 );
			$recommended_caption = '' !== $caption ? $caption : $this->trim_media_seo_text( $caption_seed, 160 );
			$recommended_description = '' !== $description ? $description : $this->trim_media_seo_text( $description_seed, 220 );

			if ( 'public_free' === $image_origin ) {
				$attribution_appendix = implode( ' ', array_filter( array( '' !== $attribution_text ? '归因：' . $attribution_text : '', '' !== $source_page_url ? '来源页：' . $source_page_url : '', '' !== $copyright_notice ? $copyright_notice : '' ) ) );
				if ( '' !== $attribution_appendix && false === strpos( $recommended_description, $attribution_appendix ) ) {
					$recommended_description = $this->trim_media_seo_text( trim( $recommended_description . ' ' . $attribution_appendix ), 280 );
				}
			}

			$recommendations[] = array(
				'attachment_id'                => $this->absint_value( $asset['attachment_id'] ?? 0 ),
				'role'                         => sanitize_key( (string) ( $asset['role'] ?? ( 0 === $index ? 'featured' : 'inline' ) ) ),
				'url'                          => $this->esc_url_value( (string) ( $asset['url'] ?? '' ) ),
				'image_origin'                 => $image_origin,
				'strategy'                     => $strategy,
				'metadata_confidence'          => $metadata_confidence,
				'vision_fallback_recommended'  => $vision_fallback_recommended,
				'disclosure_readiness'         => $disclosure_readiness,
				'alt_context'                  => $this->trim_media_seo_text( implode( '；', $alt_context_parts ), 280 ),
				'provider'                     => array(
					'hint'             => $provider_hint,
					'provider_title'   => $provider_title,
					'source_page_url'  => $source_page_url,
					'photographer_name' => $photographer_name,
					'attribution_text' => $attribution_text,
					'license'          => $license,
					'copyright_notice' => $copyright_notice,
				),
				'vision_fallback_target'       => array(),
				'issues'                       => array_values( array_filter( array( '' === $alt ? '缺少 alt 文本。' : '', $needs_format_attention ? '仍可评估是否转为 WebP/AVIF。' : '', in_array( $disclosure_readiness, array( 'needs_review', 'disclosure_recommended' ), true ) ? '建议补充来源、归因或披露说明。' : '', $vision_fallback_recommended ? '现有上下文较弱，后续可考虑追加视觉识别补强。' : '' ) ) ),
				'suggestions'                  => array(
					'title'       => $recommended_title,
					'alt'         => $recommended_alt,
					'caption'     => $recommended_caption,
					'description' => $recommended_description,
				),
				'attribution_persisted'        => 'public_free' === $image_origin && '' !== $attribution_text,
				'format_plan'                  => array(
					'should_convert'     => $needs_format_attention,
					'recommended_format' => $needs_format_attention ? 'webp' : sanitize_key( (string) preg_replace( '#^.*/#', '', $mime_type ) ),
					'reason'             => $needs_format_attention ? '当前资源仍是传统格式，可评估更轻量编码。' : '当前格式可继续沿用。',
				),
			);
		}

		return $this->build_analysis_success_response(
			array(
				'assets'  => $recommendations,
				'summary' => array(
					'asset_count'                  => count( $recommendations ),
					'missing_alt_count'            => $missing_alt_count,
					'format_attention_count'       => $format_attention_count,
					'vision_fallback_count'        => $vision_fallback_count,
					'vision_fallback_mode'         => $vision_fallback_mode,
					'vision_fallback_target_available' => false,
					'vision_fallback_target'       => array(),
					'disclosure_ready_count'       => count( array_filter( $recommendations, static function ( array $asset ) { return in_array( sanitize_key( (string) ( $asset['disclosure_readiness'] ?? '' ) ), array( 'ready_with_attribution', 'ready_with_source_note' ), true ); } ) ),
					'needs_review_count'           => count( array_filter( $recommendations, static function ( array $asset ) { return 'needs_review' === sanitize_key( (string) ( $asset['disclosure_readiness'] ?? '' ) ); } ) ),
					'origin_breakdown'             => array(
						'ai_generated'  => count( array_filter( $recommendations, static function ( array $asset ) { return 'ai_generated' === sanitize_key( (string) ( $asset['image_origin'] ?? '' ) ); } ) ),
						'public_free'   => count( array_filter( $recommendations, static function ( array $asset ) { return 'public_free' === sanitize_key( (string) ( $asset['image_origin'] ?? '' ) ); } ) ),
						'manual_upload' => count( array_filter( $recommendations, static function ( array $asset ) { return 'manual_upload' === sanitize_key( (string) ( $asset['image_origin'] ?? '' ) ); } ) ),
					),
					'attribution_persisted_count'  => count( array_filter( $recommendations, static function ( array $asset ) { return ! empty( $asset['attribution_persisted'] ); } ) ),
				),
			),
			array(
				'source'         => 'local_media_metadata_optimizer',
				'execution_mode' => 'deterministic',
			),
			'Media metadata optimization completed.'
		);
	}

	/**
	 * Positions inline image blocks after matching heading anchors or paragraph indexes.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function position_inline_image_blocks( $input ) {
		$input = is_array( $input ) ? $input : array();
		$existing_blocks = is_array( $input['existing_blocks'] ?? null ) ? array_values( $input['existing_blocks'] ) : array();
		$inline_blocks = is_array( $input['inline_blocks'] ?? null ) ? array_values( $input['inline_blocks'] ) : array();
		$inline_plan = is_array( $input['inline_plan'] ?? null ) ? array_values( $input['inline_plan'] ) : array();
		if ( empty( $inline_blocks ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'blocks'  => $existing_blocks,
					'summary' => array(
						'count'            => 0,
						'positioned_count' => 0,
						'appended_count'   => 0,
						'strategy'         => 'after_paragraph_or_heading',
					),
				),
				'meta'    => array(
					'contract_version' => 'v1',
					'execution_mode'   => 'projection_only',
				),
			);
		}

		$placement_keys = array();
		foreach ( $inline_plan as $plan_row ) {
			$plan_row = is_array( $plan_row ) ? $plan_row : array();
			$placement_key = $this->sanitize_html_class_value( (string) ( $plan_row['placement_key'] ?? '' ) );
			if ( '' !== $placement_key ) {
				$placement_keys[] = $placement_key;
			}
		}
		$existing_blocks = $this->filter_existing_inline_image_blocks( $existing_blocks, $placement_keys );

		$insertions_by_index = array();
		$append_blocks = array();
		$positioned_count = 0;
		$appended_count = 0;
		$matched_targets = array();
		foreach ( $inline_blocks as $index => $inline_block ) {
			$inline_block = is_array( $inline_block ) ? $inline_block : array();
			if ( empty( $inline_block ) ) {
				continue;
			}
			$plan_row = is_array( $inline_plan[ $index ] ?? null ) ? $inline_plan[ $index ] : array();
			$matched = false;
			$current_paragraph_index = 0;
			foreach ( $existing_blocks as $block_index => $existing_block ) {
				$existing_block = is_array( $existing_block ) ? $existing_block : array();
				if ( empty( $existing_block ) ) {
					continue;
				}
				$block_name = sanitize_text_field( (string) ( $existing_block['blockName'] ?? '' ) );
				$is_paragraph = in_array( $block_name, array( 'core/paragraph', 'core/freeform' ), true );
				if (
					$this->block_matches_inline_paragraph_target( $existing_block, $plan_row, $current_paragraph_index )
					|| $this->block_matches_inline_target( $existing_block, $plan_row )
				) {
					if ( ! isset( $insertions_by_index[ $block_index ] ) || ! is_array( $insertions_by_index[ $block_index ] ) ) {
						$insertions_by_index[ $block_index ] = array();
					}
					$insertions_by_index[ $block_index ][] = $inline_block;
					$matched = true;
					++$positioned_count;
					$matched_targets[] = array(
						'placement_key'    => $this->sanitize_html_class_value( (string) ( $plan_row['placement_key'] ?? '' ) ),
						'placement'        => sanitize_key( (string) ( $plan_row['placement'] ?? '' ) ),
						'paragraph_index'  => array_key_exists( 'paragraph_index', $plan_row ) ? $this->absint_value( $plan_row['paragraph_index'] ) : null,
						'section_anchor'   => $this->sanitize_metadata_slug( (string) ( $plan_row['section_anchor'] ?? '' ) ),
						'section_heading'  => sanitize_text_field( (string) ( $plan_row['section_heading'] ?? '' ) ),
					);
					break;
				}
				if ( $is_paragraph ) {
					++$current_paragraph_index;
				}
			}

			if ( ! $matched ) {
				$append_blocks[] = $inline_block;
				++$appended_count;
			}
		}

		$composed_blocks = array();
		foreach ( $existing_blocks as $block_index => $existing_block ) {
			$composed_blocks[] = $existing_block;
			if ( ! empty( $insertions_by_index[ $block_index ] ) && is_array( $insertions_by_index[ $block_index ] ) ) {
				foreach ( $insertions_by_index[ $block_index ] as $inserted_block ) {
					$composed_blocks[] = $inserted_block;
				}
			}
		}
		foreach ( $append_blocks as $append_block ) {
			$composed_blocks[] = $append_block;
		}

		return array(
			'success' => true,
			'data'    => array(
				'blocks'  => array_values( $composed_blocks ),
				'summary' => array(
					'count'            => count( $inline_blocks ),
					'positioned_count' => $positioned_count,
					'appended_count'   => $appended_count,
					'strategy'         => 'after_paragraph_or_heading',
					'matched_targets'  => array_values( $matched_targets ),
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Builds media cleanup opportunities.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_media_cleanup_opportunities( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read media cleanup opportunities.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$mime_type = sanitize_text_field( (string) ( $input['mime_type'] ?? '' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$query_result = $this->query_media_inventory( $mime_type, '', $per_page, $page );
		$items = array();
		$issue_counts = array();
		foreach ( (array) ( $query_result['attachment_ids'] ?? array() ) as $attachment_id ) {
			$attachment_id = $this->absint_value( $attachment_id );
			if ( $attachment_id <= 0 || ! current_user_can( 'edit_post', $attachment_id ) ) {
				continue;
			}
			$row = $this->build_media_inventory_health_row( $attachment_id );
			$issues = is_array( $row['issues'] ?? null ) ? $row['issues'] : array();
			$attached_to = get_post( $attachment_id );
			if ( is_object( $attached_to ) && 0 === $this->absint_value( $attached_to->post_parent ?? 0 ) ) {
				$issues[] = 'possibly_unattached';
			}
			foreach ( $issues as $issue ) {
				$issue = sanitize_key( (string) $issue );
				if ( '' !== $issue ) {
					$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
				}
			}
			if ( empty( $issues ) ) {
				continue;
			}
			$row['issues'] = array_values( array_unique( $issues ) );
			$row['issue_count'] = count( $row['issues'] );
			$row['priority'] = in_array( 'missing_alt', $row['issues'], true ) || in_array( 'possibly_unattached', $row['issues'], true ) ? 'medium' : 'low';
			$items[] = $row;
		}
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'        => (int) ( $query_result['total'] ?? 0 ),
				'page'         => $page,
				'per_page'     => $per_page,
				'items'        => $items,
				'issue_counts' => $issue_counts,
				'summary'      => array(
					'opportunity_count' => count( $items ),
					'mime_type'         => $mime_type,
				),
			),
			array(
				'source'         => 'local_media_cleanup_opportunities',
				'execution_mode' => 'deterministic',
			),
			'Media cleanup opportunities built.'
		);
	}

	/**
	 * Builds a media inventory health report.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_media_inventory_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read media inventory.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$mime_type = sanitize_text_field( (string) ( $input['mime_type'] ?? '' ) );
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$per_page = max( 1, min( 100, $this->absint_value( $input['per_page'] ?? 50 ) ) );
		$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
		$query_result = $this->query_media_inventory( $mime_type, $search, $per_page, $page );
		$attachment_ids = is_array( $query_result['attachment_ids'] ?? null ) ? $query_result['attachment_ids'] : array();
		$items = array();
		$issue_counts = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = $this->absint_value( $attachment_id );
			if ( $attachment_id <= 0 || ! current_user_can( 'edit_post', $attachment_id ) ) {
				continue;
			}
			$row = $this->build_media_inventory_health_row( $attachment_id );
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
			? max( 0, 100 - min( 100, (int) round( ( $total_issue_instances / max( 1, count( $items ) ) ) * 14 ) ) )
			: 100;
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'        => (int) ( $query_result['total'] ?? count( $attachment_ids ) ),
				'page'         => $page,
				'per_page'     => $per_page,
				'health_score' => $health_score,
				'issue_counts' => $issue_counts,
				'items'        => $items,
				'summary'      => array(
					'scanned_count'          => count( $items ),
					'assets_with_issues'     => count(
						array_filter(
							$items,
							static function ( array $item ) {
								return (int) ( $item['issue_count'] ?? 0 ) > 0;
							}
						)
					),
					'total_issue_instances' => $total_issue_instances,
					'mime_type'             => $mime_type,
					'search'                => $search,
				),
			),
			array(
				'source'         => 'local_media_inventory_health',
				'execution_mode' => 'deterministic',
			),
			'Media inventory health report built.'
		);
	}

	/**
	 * Builds a read-only fix plan for media inventory issues.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_media_inventory_fix_plan( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to build media inventory fix plans.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$attachment_ids = is_array( $input['attachment_ids'] ?? null ) ? array_values( array_filter( array_map( array( $this, 'absint_value' ), $input['attachment_ids'] ) ) ) : array();
		$attachment_ids = array_slice( array_values( array_unique( $attachment_ids ) ), 0, 50 );
		if ( empty( $attachment_ids ) ) {
			$mime_type = sanitize_text_field( (string) ( $input['mime_type'] ?? '' ) );
			$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
			$per_page = max( 1, min( 50, $this->absint_value( $input['per_page'] ?? 20 ) ) );
			$page = max( 1, $this->absint_value( $input['page'] ?? 1 ) );
			$query_result = $this->query_media_inventory( $mime_type, $search, $per_page, $page );
			$attachment_ids = is_array( $query_result['attachment_ids'] ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $query_result['attachment_ids'] ) ) : array();
		}

		$issue_types = $this->normalize_media_fix_issue_types( $input['issue_types'] ?? array() );
		$max_actions = max( 1, min( 100, $this->absint_value( $input['max_actions'] ?? 50 ) ) );
		$include_delete_candidates = ! empty( $input['include_delete_candidates'] );
		$context = array(
			'article_title'   => sanitize_text_field( (string) ( $input['article_title'] ?? '' ) ),
			'article_excerpt' => $this->sanitize_metadata_text( (string) ( $input['article_excerpt'] ?? '' ) ),
			'focus_keyword'   => sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) ),
		);
		$actions = array();
		$preview = array();
		$manual_review = array();
		$skipped_destructive_candidates = array();
		$issue_counts = array();

		foreach ( $attachment_ids as $attachment_id ) {
			if ( count( $actions ) >= $max_actions ) {
				break;
			}
			$attachment = $attachment_id > 0 ? get_post( $attachment_id ) : null;
			if ( ! is_object( $attachment ) || 'attachment' !== sanitize_key( (string) ( $attachment->post_type ?? '' ) ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
				continue;
			}

			$row = $this->build_media_inventory_health_row( $attachment_id );
			$parent_id = $this->absint_value( $attachment->post_parent ?? 0 );
			if ( 0 === $parent_id ) {
				$row['issues'][] = 'possibly_unattached';
			}
			$row['issues'] = array_values( array_unique( array_map( 'sanitize_key', (array) ( $row['issues'] ?? array() ) ) ) );
			$row['issue_count'] = count( $row['issues'] );
			$row['parent_post_id'] = $parent_id;

			$post_plan = $this->build_media_inventory_fix_plan_rows( $attachment_id, $row, $issue_types, $context, $max_actions - count( $actions ), $include_delete_candidates );
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
			$manual_review = array_merge( $manual_review, (array) ( $post_plan['manual_review'] ?? array() ) );
			$skipped_destructive_candidates = array_merge( $skipped_destructive_candidates, (array) ( $post_plan['skipped_destructive_candidates'] ?? array() ) );
		}

		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'batch_id'                       => 'media_inventory_fix_' . gmdate( 'Ymd_His' ),
				'issue_types'                    => $issue_types,
				'attachment_ids'                 => $attachment_ids,
				'requires_approval'              => true,
				'commit_execution'               => false,
				'dry_run'                        => true,
				'action_count'                   => count( $actions ),
				'issue_counts'                   => $issue_counts,
				'write_actions'                  => $actions,
				'preview'                        => $preview,
				'manual_review'                  => $manual_review,
				'skipped_destructive_candidates' => $skipped_destructive_candidates,
				'risk'                           => array(
					'level'  => $include_delete_candidates ? 'high' : 'medium',
					'reason' => $include_delete_candidates ? 'Metadata updates are reversible but media deletion is permanent and must be approved by the host.' : 'Default plan only proposes metadata updates and records permanent delete candidates without mapping them to write actions.',
				),
			),
			array(
				'source'         => 'local_media_inventory_fix_plan',
				'execution_mode' => 'deterministic',
				'plan_only'      => true,
			),
			'Media inventory fix plan built.'
		);
	}

	/**
	 * Collects featured and attached media for a post context payload.
	 *
	 * @param int $post_id Post id.
	 * @return array<string,mixed>
	 */
	private function collect_post_media_context( $post_id ) {
		$featured_id = function_exists( 'get_post_thumbnail_id' ) ? $this->absint_value( get_post_thumbnail_id( $post_id ) ) : 0;
		$featured = null;
		if ( $featured_id > 0 ) {
			$featured = $this->build_media_context_row( $featured_id, 'featured' );
		}

		$attachments = array();
		if ( function_exists( 'get_attached_media' ) ) {
			foreach ( (array) get_attached_media( '', $post_id ) as $attachment ) {
				$attachment_id = is_object( $attachment ) ? $this->absint_value( $attachment->ID ?? 0 ) : $this->absint_value( $attachment );
				if ( $attachment_id <= 0 ) {
					continue;
				}
				$attachments[] = $this->build_media_context_row( $attachment_id, 'attachment' );
				if ( count( $attachments ) >= 20 ) {
					break;
				}
			}
		}

		return array(
			'featured'    => $featured,
			'attachments' => $attachments,
			'total'       => count( $attachments ) + ( $featured ? 1 : 0 ),
		);
	}

	/**
	 * Normalizes requested media fix issue types.
	 *
	 * @param mixed $issue_types Raw issue types.
	 * @return string[]
	 */
	private function normalize_media_fix_issue_types( $issue_types ) {
		$allowed = array( 'missing_alt', 'missing_caption', 'missing_description', 'missing_source', 'format_attention', 'possibly_unattached' );
		$issue_types = is_array( $issue_types ) ? $issue_types : array();
		if ( empty( $issue_types ) ) {
			$issue_types = $allowed;
		}
		$normalized = array();
		foreach ( $issue_types as $issue ) {
			$issue = sanitize_key( (string) $issue );
			if ( in_array( $issue, $allowed, true ) ) {
				$normalized[] = $issue;
			}
		}

		return ! empty( $normalized ) ? array_values( array_unique( $normalized ) ) : array( 'missing_alt', 'missing_caption', 'missing_description' );
	}

	/**
	 * Builds action rows for one media inventory fix plan.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $row Media inventory row.
	 * @param string[]            $issue_types Requested issue types.
	 * @param array<string,mixed> $context Planning context.
	 * @param int                 $remaining_slots Remaining action slots.
	 * @param bool                $include_delete_candidates Whether to map delete candidates.
	 * @return array<string,mixed>
	 */
	private function build_media_inventory_fix_plan_rows( $attachment_id, array $row, array $issue_types, array $context, $remaining_slots, $include_delete_candidates ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$remaining_slots = max( 0, (int) $remaining_slots );
		$issues = array_values(
			array_filter(
				array_map( 'sanitize_key', (array) ( $row['issues'] ?? array() ) ),
				static function ( $issue ) use ( $issue_types ) {
					return in_array( $issue, $issue_types, true );
				}
			)
		);
		$actions = array();
		$manual_review = array();
		$skipped_destructive_candidates = array();
		if ( empty( $issues ) || $attachment_id <= 0 ) {
			return array(
				'issues'                         => array(),
				'actions'                        => array(),
				'preview'                        => array(),
				'manual_review'                  => array(),
				'skipped_destructive_candidates' => array(),
			);
		}

		$suggestions = $this->build_media_metadata_suggestions( $attachment_id, $row, $context );
		$update_input = array( 'attachment_id' => $attachment_id );
		$after = array();
		if ( in_array( 'missing_alt', $issues, true ) && '' !== (string) ( $suggestions['alt'] ?? '' ) ) {
			$update_input['alt'] = $suggestions['alt'];
			$after['alt'] = $suggestions['alt'];
		}
		if ( in_array( 'missing_caption', $issues, true ) && '' !== (string) ( $suggestions['caption'] ?? '' ) ) {
			$update_input['caption'] = $suggestions['caption'];
			$after['caption'] = $suggestions['caption'];
		}
		if ( in_array( 'missing_description', $issues, true ) && '' !== (string) ( $suggestions['description'] ?? '' ) ) {
			$update_input['description'] = $suggestions['description'];
			$after['description'] = $suggestions['description'];
		}
		if ( count( $update_input ) > 1 && count( $actions ) < $remaining_slots ) {
			$actions[] = $this->build_plan_action( 'update_media_details_' . $attachment_id, 'magick-ai/update-media-details', $update_input, array( 'media.write' ), 'medium', 'Fill missing media SEO metadata.' );
		}

		if ( in_array( 'missing_source', $issues, true ) ) {
			$manual_review[] = array(
				'action_id'         => 'review_media_source_' . $attachment_id,
				'attachment_id'     => $attachment_id,
				'issue'             => 'missing_source',
				'target_ability_id' => 'magick-ai/update-media-details',
				'requires_input'    => array( 'source_page_url_or_attribution_text' ),
				'proposal_ready'    => false,
				'reason'            => 'Source and attribution metadata must come from a verified source, not a deterministic guess.',
			);
		}

		if ( in_array( 'format_attention', $issues, true ) ) {
			$manual_review[] = array(
				'action_id'      => 'review_media_format_' . $attachment_id,
				'attachment_id'  => $attachment_id,
				'issue'          => 'format_attention',
				'requires_input' => array( 'converted_asset_or_operator_decision' ),
				'proposal_ready' => false,
				'reason'         => 'No existing governed ability replaces or converts an attachment file in place.',
			);
		}

		if ( in_array( 'possibly_unattached', $issues, true ) ) {
			if ( $include_delete_candidates && count( $actions ) < $remaining_slots ) {
				$actions[] = $this->build_plan_action( 'delete_unattached_media_' . $attachment_id, 'magick-ai/delete-media-permanently', array( 'attachment_id' => $attachment_id ), array( 'media.write' ), 'high', 'Permanently delete detected unattached media only after explicit host approval.' );
			} else {
				$skipped_destructive_candidates[] = array(
					'attachment_id'     => $attachment_id,
					'target_ability_id' => 'magick-ai/delete-media-permanently',
					'issue'             => 'possibly_unattached',
					'reason'            => 'Permanent deletion is excluded by default. Re-run with include_delete_candidates=true to include it in write_actions.',
				);
			}
		}

		return array(
			'issues'  => $issues,
			'actions' => $actions,
			'preview' => empty( $actions ) && empty( $manual_review ) && empty( $skipped_destructive_candidates ) ? array() : array(
				'attachment_id' => $attachment_id,
				'title'         => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
				'mime_type'     => sanitize_text_field( (string) ( $row['mime_type'] ?? '' ) ),
				'url'           => $this->esc_url_value( (string) ( $row['url'] ?? '' ) ),
				'before'        => array(
					'title'            => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
					'alt'              => sanitize_text_field( (string) ( $row['alt'] ?? '' ) ),
					'caption'          => $this->sanitize_metadata_text( (string) ( $row['caption'] ?? '' ) ),
					'description'      => $this->sanitize_metadata_text( (string) ( $row['description'] ?? '' ) ),
					'source_page_url'  => $this->esc_url_value( (string) ( $row['source_page_url'] ?? '' ) ),
					'attribution_text' => $this->sanitize_metadata_text( (string) ( $row['attribution_text'] ?? '' ) ),
					'parent_post_id'   => $this->absint_value( $row['parent_post_id'] ?? 0 ),
				),
				'after_suggestion' => $after,
				'issues'           => $issues,
			),
			'manual_review'                  => $manual_review,
			'skipped_destructive_candidates' => $skipped_destructive_candidates,
		);
	}

	/**
	 * Builds deterministic media metadata suggestions.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $row Media row.
	 * @param array<string,mixed> $context Planning context.
	 * @return array<string,string>
	 */
	private function build_media_metadata_suggestions( $attachment_id, array $row, array $context ) {
		$title = sanitize_text_field( (string) ( $row['title'] ?? '' ) );
		$url = $this->esc_url_value( (string) ( $row['url'] ?? '' ) );
			$url_path = '' !== $url ? (string) wp_parse_url( $url, PHP_URL_PATH ) : '';
		$file_name = '' !== $url_path ? $this->sanitize_file_name_value( (string) basename( $url_path ) ) : '';
		$file_label = '' !== $file_name ? preg_replace( '/\.[^.]+$/', '', $file_name ) : '';
		$file_label = sanitize_text_field( (string) str_replace( array( '-', '_' ), ' ', (string) $file_label ) );
		$article_title = sanitize_text_field( (string) ( $context['article_title'] ?? '' ) );
		$article_excerpt = $this->sanitize_metadata_text( (string) ( $context['article_excerpt'] ?? '' ) );
		$focus_keyword = sanitize_text_field( (string) ( $context['focus_keyword'] ?? '' ) );
		$seed = '' !== $title ? $title : ( '' !== $file_label ? $file_label : 'Media asset ' . $this->absint_value( $attachment_id ) );
		$alt_parts = array_filter( array( $seed, '' !== $focus_keyword ? $focus_keyword : '', '' !== $article_title && $article_title !== $seed ? $article_title : '' ) );
		$caption_seed = '' !== $article_excerpt ? $article_excerpt : ( '' !== $article_title ? $article_title : $seed );
		$description_seed = trim( implode( ' ', array_filter( array( $caption_seed, '' !== $seed && false === strpos( $caption_seed, $seed ) ? $seed : '' ) ) ) );

		return array(
			'alt'         => $this->trim_media_seo_text( implode( ' - ', $alt_parts ), 140 ),
			'caption'     => $this->trim_media_seo_text( $caption_seed, 160 ),
			'description' => $this->trim_media_seo_text( $description_seed, 220 ),
		);
	}

	/**
	 * Queries media attachment ids for a bounded inventory scan.
	 *
	 * @param string $mime_type Mime type filter.
	 * @param string $search Search term.
	 * @param int    $per_page Per page.
	 * @param int    $page Page.
	 * @return array<string,mixed>
	 */
	private function query_media_inventory( $mime_type, $search, $per_page, $page ) {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);
		if ( '' !== $mime_type ) {
			$args['post_mime_type'] = $mime_type;
		}
		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		if ( class_exists( '\WP_Query' ) ) {
			$query = new \WP_Query( $args );
			return array(
				'attachment_ids' => is_array( $query->posts ?? null ) ? array_values( array_map( array( $this, 'absint_value' ), $query->posts ) ) : array(),
				'total'          => (int) ( $query->found_posts ?? 0 ),
			);
		}

		$attachments = isset( $GLOBALS['maa_unit_style_posts'] ) && is_array( $GLOBALS['maa_unit_style_posts'] )
			? array_values( $GLOBALS['maa_unit_style_posts'] )
			: array();
		$filtered = array();
		foreach ( $attachments as $attachment ) {
			if ( ! is_object( $attachment ) || 'attachment' !== sanitize_key( (string) ( $attachment->post_type ?? '' ) ) ) {
				continue;
			}
			$current_mime = sanitize_text_field( (string) ( $attachment->post_mime_type ?? '' ) );
			if ( '' !== $mime_type && false === strpos( $current_mime, $mime_type ) ) {
				continue;
			}
			$title = sanitize_text_field( (string) ( $attachment->post_title ?? '' ) );
			if ( '' !== $search && false === stripos( $title, $search ) ) {
				continue;
			}
			$filtered[] = $this->absint_value( $attachment->ID ?? 0 );
		}

		return array(
			'attachment_ids' => array_slice( array_filter( $filtered ), ( $page - 1 ) * $per_page, $per_page ),
			'total'          => count( $filtered ),
		);
	}

	/**
	 * Builds one media inventory health row.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return array<string,mixed>
	 */
	private function build_media_inventory_health_row( $attachment_id ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$attachment = get_post( $attachment_id );
		$title = is_object( $attachment ) ? sanitize_text_field( (string) ( $attachment->post_title ?? '' ) ) : '';
		$caption = is_object( $attachment ) ? $this->sanitize_metadata_text( (string) ( $attachment->post_excerpt ?? '' ) ) : '';
		$description = is_object( $attachment ) ? $this->sanitize_metadata_text( (string) ( $attachment->post_content ?? '' ) ) : '';
		$mime_type = function_exists( 'get_post_mime_type' )
			? sanitize_text_field( (string) get_post_mime_type( $attachment_id ) )
			: ( is_object( $attachment ) ? sanitize_text_field( (string) ( $attachment->post_mime_type ?? '' ) ) : '' );
		$alt = function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) : '';
		$source_url = function_exists( 'get_post_meta' ) ? $this->esc_url_value( (string) get_post_meta( $attachment_id, '_magick_ai_source_page_url', true ) ) : '';
		if ( '' === $source_url && function_exists( 'get_post_meta' ) ) {
			$source_url = $this->esc_url_value( (string) get_post_meta( $attachment_id, '_magick_ai_media_source_page_url', true ) );
		}
		$attribution = function_exists( 'get_post_meta' ) ? $this->sanitize_metadata_text( (string) get_post_meta( $attachment_id, '_magick_ai_attribution_text', true ) ) : '';
		if ( '' === $attribution && function_exists( 'get_post_meta' ) ) {
			$attribution = $this->sanitize_metadata_text( (string) get_post_meta( $attachment_id, '_magick_ai_media_attribution_text', true ) );
		}
		$issues = array();
		if ( '' === $alt && 0 === strpos( $mime_type, 'image/' ) ) {
			$issues[] = 'missing_alt';
		}
		if ( '' === $caption ) {
			$issues[] = 'missing_caption';
		}
		if ( '' === $description ) {
			$issues[] = 'missing_description';
		}
		if ( '' === $source_url && '' === $attribution ) {
			$issues[] = 'missing_source';
		}
		if ( '' !== $mime_type && 0 === strpos( $mime_type, 'image/' ) && false === strpos( $mime_type, 'webp' ) && false === strpos( $mime_type, 'avif' ) ) {
			$issues[] = 'format_attention';
		}

		return array(
			'attachment_id' => $attachment_id,
			'title'         => $title,
			'mime_type'     => $mime_type,
			'url'           => function_exists( 'wp_get_attachment_url' ) ? $this->esc_url_value( (string) wp_get_attachment_url( $attachment_id ) ) : '',
			'alt'           => $alt,
			'caption'       => $caption,
			'description'   => $description,
			'source_page_url' => $source_url,
			'attribution_text' => $attribution,
			'issue_count'   => count( $issues ),
			'issues'        => $issues,
			'edit_link'     => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $attachment_id, 'raw' ) ) : '',
		);
	}

	/**
	 * Builds one normalized media context row.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $role Media role.
	 * @return array<string,mixed>
	 */
	private function build_media_context_row( $attachment_id, $role ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$attachment = $attachment_id > 0 ? get_post( $attachment_id ) : null;
		$alt = function_exists( 'get_post_meta' ) ? sanitize_text_field( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) : '';

		return array(
			'id'          => $attachment_id,
			'role'        => sanitize_key( (string) $role ),
			'title'       => is_object( $attachment ) ? sanitize_text_field( (string) ( $attachment->post_title ?? '' ) ) : '',
			'mime_type'   => is_object( $attachment ) ? sanitize_text_field( (string) ( $attachment->post_mime_type ?? '' ) ) : '',
			'url'         => function_exists( 'wp_get_attachment_url' ) ? $this->esc_url_value( (string) wp_get_attachment_url( $attachment_id ) ) : '',
			'alt'         => $alt,
			'caption'     => is_object( $attachment ) ? $this->sanitize_metadata_text( (string) ( $attachment->post_excerpt ?? '' ) ) : '',
			'description' => is_object( $attachment ) ? $this->sanitize_metadata_text( (string) ( $attachment->post_content ?? '' ) ) : '',
		);
	}

	/**
	 * Collects media assets from explicit rows or inline image HTML.
	 *
	 * @param array<int,array<string,mixed>> $media_assets Media assets.
	 * @param string                         $article_content Article content.
	 * @return array<int,array<string,mixed>>
	 */
	private function collect_media_assets_for_optimization( array $media_assets, $article_content ) {
		$assets = array();
		foreach ( $media_assets as $asset ) {
			$asset = is_array( $asset ) ? $asset : array();
			$assets[] = array(
				'attachment_id'           => $this->absint_value( $asset['attachment_id'] ?? 0 ),
				'url'                     => $this->esc_url_value( (string) ( $asset['url'] ?? '' ) ),
				'title'                   => sanitize_text_field( (string) ( $asset['title'] ?? '' ) ),
				'alt'                     => sanitize_text_field( (string) ( $asset['alt'] ?? '' ) ),
				'caption'                 => sanitize_textarea_field( (string) ( $asset['caption'] ?? '' ) ),
				'description'             => sanitize_textarea_field( (string) ( $asset['description'] ?? '' ) ),
				'image_origin'            => sanitize_key( (string) ( $asset['image_origin'] ?? '' ) ),
				'generated_prompt'        => sanitize_textarea_field( (string) ( $asset['generated_prompt'] ?? '' ) ),
				'image_profile'           => sanitize_key( (string) ( $asset['image_profile'] ?? '' ) ),
				'mime_type'               => sanitize_text_field( (string) ( $asset['mime_type'] ?? '' ) ),
				'role'                    => sanitize_key( (string) ( $asset['role'] ?? '' ) ),
				'provider_hint'           => sanitize_key( (string) ( $asset['provider_hint'] ?? '' ) ),
				'provider_title'          => sanitize_text_field( (string) ( $asset['provider_title'] ?? '' ) ),
				'provider_description'    => sanitize_textarea_field( (string) ( $asset['provider_description'] ?? '' ) ),
				'source_page_url'         => $this->esc_url_value( (string) ( $asset['source_page_url'] ?? '' ) ),
				'photographer_name'       => sanitize_text_field( (string) ( $asset['photographer_name'] ?? '' ) ),
				'attribution_text'        => sanitize_textarea_field( (string) ( $asset['attribution_text'] ?? '' ) ),
				'license'                 => sanitize_text_field( (string) ( $asset['license'] ?? $asset['license_policy'] ?? '' ) ),
				'copyright_notice'        => sanitize_textarea_field( (string) ( $asset['copyright_notice'] ?? '' ) ),
				'section_heading'         => sanitize_text_field( (string) ( $asset['section_heading'] ?? '' ) ),
				'section_summary'         => sanitize_textarea_field( (string) ( $asset['section_summary'] ?? '' ) ),
				'file_name'               => sanitize_text_field( (string) ( $asset['file_name'] ?? '' ) ),
				'vision_fallback_allowed' => ! empty( $asset['vision_fallback_allowed'] ),
			);
		}
		if ( ! empty( $assets ) ) {
			return $assets;
		}

		if ( preg_match_all( '/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/iu', (string) $article_content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$html = (string) ( $match[0] ?? '' );
				$url = $this->esc_url_value( (string) ( $match[1] ?? '' ) );
				$alt = '';
				if ( preg_match( '/alt=["\']([^"\']*)["\']/iu', $html, $alt_match ) ) {
					$alt = sanitize_text_field( (string) ( $alt_match[1] ?? '' ) );
				}
				if ( '' === $url ) {
					continue;
				}
				$assets[] = array(
					'attachment_id' => 0,
					'url'           => $url,
					'title'         => '',
					'alt'           => $alt,
					'caption'       => '',
					'description'   => '',
					'mime_type'     => '',
					'role'          => 'inline',
				);
			}
		}

		return $assets;
	}

	/**
	 * Trims media SEO text.
	 *
	 * @param string $value Value.
	 * @param int    $max_len Max length.
	 * @return string
	 */
	private function trim_media_seo_text( $value, $max_len = 180 ) {
		$value = trim( preg_replace( '/\s+/u', ' ', (string) $value ) ?? '' );
		if ( '' === $value ) {
			return '';
		}

		return $this->strlen_value( $value ) > $max_len ? trim( $this->substr_value( $value, 0, max( 0, $max_len - 1 ) ) ) : $value;
	}

	/**
	 * Resolves media license label.
	 *
	 * @param array<string,mixed> $asset Asset.
	 * @param string              $image_origin Image origin.
	 * @return string
	 */
	private function resolve_media_license_label( array $asset, $image_origin ) {
		$license = sanitize_text_field( (string) ( $asset['license'] ?? $asset['license_policy'] ?? '' ) );
		if ( '' !== $license ) {
			return $license;
		}
		$source_page_url = $this->esc_url_value( (string) ( $asset['source_page_url'] ?? '' ) );
		$attribution_text = sanitize_textarea_field( (string) ( $asset['attribution_text'] ?? '' ) );
		$photographer_name = sanitize_text_field( (string) ( $asset['photographer_name'] ?? '' ) );
		if ( 'public_free' === $image_origin && ( '' !== $source_page_url || '' !== $attribution_text || '' !== $photographer_name ) ) {
			return 'attribution_required';
		}
		if ( 'ai_generated' === $image_origin ) {
			return 'generated_content';
		}

		return '';
	}

	/**
	 * Resolves media disclosure readiness.
	 *
	 * @param string $image_origin Image origin.
	 * @param string $metadata_confidence Metadata confidence.
	 * @param string $license License.
	 * @param string $source_page_url Source page URL.
	 * @param string $attribution_text Attribution text.
	 * @param string $generated_prompt Generated prompt.
	 * @return string
	 */
	private function resolve_media_disclosure_readiness( $image_origin, $metadata_confidence, $license, $source_page_url, $attribution_text, $generated_prompt ) {
		$image_origin = sanitize_key( (string) $image_origin );
		$metadata_confidence = sanitize_key( (string) $metadata_confidence );
		if ( 'public_free' === $image_origin ) {
			return '' !== $license && '' !== $attribution_text ? 'ready_with_attribution' : 'needs_review';
		}
		if ( 'ai_generated' === $image_origin ) {
			return '' !== $generated_prompt || in_array( $metadata_confidence, array( 'high', 'medium' ), true ) ? 'disclosure_recommended' : 'needs_review';
		}
		if ( 'manual_upload' === $image_origin ) {
			if ( 'low' === $metadata_confidence ) {
				return 'needs_review';
			}
			return '' !== $license && '' !== $source_page_url ? 'ready_with_source_note' : 'needs_review';
		}

		return '';
	}

	/**
	 * Builds one canonical media SEO asset row.
	 *
	 * @param array<string,mixed> $descriptor Media descriptor.
	 * @param array<string,mixed> $upload_row Upload result.
	 * @param string              $fallback_role Fallback asset role.
	 * @param bool                $vision_allowed Whether vision fallback may be used.
	 * @return array<string,mixed>
	 */
	private function build_media_seo_asset_row( array $descriptor, array $upload_row, $fallback_role, $vision_allowed ) {
		$image_origin = $this->infer_media_image_origin( $descriptor );

		return array(
			'attachment_id'           => $this->absint_value( $upload_row['attachment_id'] ?? 0 ),
			'url'                     => $this->esc_url_value( (string) ( $descriptor['source_url'] ?? $upload_row['url'] ?? '' ) ),
			'title'                   => sanitize_text_field( (string) ( $descriptor['title'] ?? '' ) ),
			'alt'                     => sanitize_text_field( (string) ( $descriptor['alt'] ?? '' ) ),
			'caption'                 => $this->sanitize_metadata_text( (string) ( $descriptor['caption'] ?? '' ) ),
			'description'             => $this->sanitize_metadata_text( (string) ( $descriptor['description'] ?? '' ) ),
			'image_origin'            => $image_origin,
			'generated_prompt'        => $this->sanitize_metadata_text( (string) ( $descriptor['prompt'] ?? '' ) ),
			'image_profile'           => sanitize_key( (string) ( $descriptor['model_profile'] ?? '' ) ),
			'role'                    => sanitize_key( (string) ( $descriptor['role'] ?? $fallback_role ) ),
			'provider_hint'           => sanitize_key( (string) ( $descriptor['provider_hint'] ?? '' ) ),
			'provider_title'          => sanitize_text_field( (string) ( $descriptor['provider_title'] ?? '' ) ),
			'provider_description'    => $this->sanitize_metadata_text( (string) ( $descriptor['provider_description'] ?? '' ) ),
			'source_page_url'         => $this->esc_url_value( (string) ( $descriptor['source_page_url'] ?? '' ) ),
			'photographer_name'       => sanitize_text_field( (string) ( $descriptor['photographer_name'] ?? '' ) ),
			'attribution_text'        => $this->sanitize_metadata_text( (string) ( $descriptor['attribution_text'] ?? '' ) ),
			'copyright_notice'        => $this->sanitize_metadata_text( (string) ( $descriptor['copyright_notice'] ?? '' ) ),
			'section_heading'         => sanitize_text_field( (string) ( $descriptor['section_heading'] ?? '' ) ),
			'section_summary'         => $this->sanitize_metadata_text( (string) ( $descriptor['section_summary'] ?? '' ) ),
			'file_name'               => $this->sanitize_file_name_value( (string) ( $upload_row['file_name'] ?? '' ) ),
			'vision_fallback_allowed' => (bool) $vision_allowed && 'manual_upload' === $image_origin,
		);
	}

	/**
	 * Infers one canonical media origin from descriptor fields.
	 *
	 * @param array<string,mixed> $asset Media descriptor.
	 * @return string
	 */
	private function infer_media_image_origin( array $asset ) {
		$image_origin = sanitize_key( (string) ( $asset['image_origin'] ?? '' ) );
		if ( in_array( $image_origin, array( 'ai_generated', 'public_free', 'manual_upload' ), true ) ) {
			return $image_origin;
		}

		$generated_prompt = $this->sanitize_metadata_text( (string) ( $asset['generated_prompt'] ?? $asset['prompt'] ?? '' ) );
		$provider_hint = sanitize_key( (string) ( $asset['provider_hint'] ?? '' ) );
		$source_page_url = $this->esc_url_value( (string) ( $asset['source_page_url'] ?? '' ) );
		$photographer_name = sanitize_text_field( (string) ( $asset['photographer_name'] ?? '' ) );
		$attribution_text = $this->sanitize_metadata_text( (string) ( $asset['attribution_text'] ?? '' ) );
		if ( '' !== $source_page_url || '' !== $photographer_name || '' !== $attribution_text || in_array( $provider_hint, array( 'pexels', 'openverse', 'unsplash', 'pixabay', 'public_free' ), true ) ) {
			return 'public_free';
		}
		if ( '' !== sanitize_text_field( (string) ( $asset['model_profile'] ?? $asset['image_profile'] ?? '' ) ) || '' !== $generated_prompt || in_array( $provider_hint, array( 'generated', 'local_model', 'image_generate', 'ai' ), true ) ) {
			return 'ai_generated';
		}

		return 'manual_upload';
	}

	/**
	 * Filters previously inserted inline image blocks for matching placement keys.
	 *
	 * @param array<int,mixed>  $blocks Existing blocks.
	 * @param array<int,string> $placement_keys Placement keys.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_existing_inline_image_blocks( array $blocks, array $placement_keys ) {
		$placement_keys = array_values(
			array_filter(
				array_map(
					array( $this, 'sanitize_html_class_value' ),
					$placement_keys
				)
			)
		);
		if ( empty( $placement_keys ) ) {
			return array_values(
				array_filter(
					$blocks,
					'is_array'
				)
			);
		}

		$filtered = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$class_names = $this->extract_inline_block_class_names( $block );
			$is_inline_image = in_array( 'magick-ai-inline-image', $class_names, true );
			$matches_key = false;
			foreach ( $placement_keys as $placement_key ) {
				if ( in_array( $placement_key, $class_names, true ) ) {
					$matches_key = true;
					break;
				}
			}
			if ( $is_inline_image && $matches_key ) {
				continue;
			}
			$filtered[] = $block;
		}

		return array_values( $filtered );
	}

	/**
	 * Extracts class names from one block.
	 *
	 * @param array<string,mixed> $block Block payload.
	 * @return array<int,string>
	 */
	private function extract_inline_block_class_names( array $block ) {
		$class_name = '';
		$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		if ( '' !== sanitize_text_field( (string) ( $attrs['className'] ?? '' ) ) ) {
			$class_name = (string) $attrs['className'];
		} elseif ( '' !== sanitize_text_field( (string) ( $attrs['classname'] ?? '' ) ) ) {
			$class_name = (string) $attrs['classname'];
		} else {
			$inner_html = (string) ( $block['innerHTML'] ?? '' );
			if ( '' !== $inner_html && preg_match( '/class="([^"]+)"/', $inner_html, $matches ) ) {
				$class_name = (string) ( $matches[1] ?? '' );
			}
		}

		$parts = preg_split( '/\s+/', trim( (string) $class_name ) );
		return array_values(
			array_filter(
				array_map(
					array( $this, 'sanitize_html_class_value' ),
					is_array( $parts ) ? $parts : array()
				)
			)
		);
	}

	/**
	 * Returns whether a block matches one paragraph-index inline image target.
	 *
	 * @param array<string,mixed> $block Block payload.
	 * @param array<string,mixed> $target Target descriptor.
	 * @param int                 $paragraph_index Current paragraph index.
	 * @return bool
	 */
	private function block_matches_inline_paragraph_target( array $block, array $target, $paragraph_index ) {
		if ( 'after_paragraph' !== sanitize_key( (string) ( $target['placement'] ?? '' ) ) ) {
			return false;
		}
		if ( ! array_key_exists( 'paragraph_index', $target ) ) {
			return false;
		}
		$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
		if ( ! in_array( $block_name, array( 'core/paragraph', 'core/freeform' ), true ) ) {
			return false;
		}
		return (int) $paragraph_index === $this->absint_value( $target['paragraph_index'] );
	}

	/**
	 * Returns whether a heading block matches one anchor or heading target.
	 *
	 * @param array<string,mixed> $block Block payload.
	 * @param array<string,mixed> $target Target descriptor.
	 * @return bool
	 */
	private function block_matches_inline_target( array $block, array $target ) {
		$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
		if ( 'core/heading' !== $block_name ) {
			return false;
		}

		$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		$block_anchor = $this->sanitize_metadata_slug( (string) ( $attrs['anchor'] ?? '' ) );
		$target_anchor = $this->sanitize_metadata_slug( (string) ( $target['section_anchor'] ?? '' ) );
		if ( '' !== $target_anchor && '' !== $block_anchor && $target_anchor === $block_anchor ) {
			return true;
		}

		$target_heading = html_entity_decode( sanitize_text_field( (string) ( $target['section_heading'] ?? '' ) ), ENT_QUOTES, 'UTF-8' );
		$target_heading = preg_replace( '/\s+/u', ' ', trim( (string) $target_heading ) );
		$target_heading = is_string( $target_heading ) ? $target_heading : '';
		$heading_text = $this->extract_heading_text_from_block( $block );
		return '' !== $target_heading && '' !== $heading_text && $target_heading === $heading_text;
	}

	/**
	 * Extracts normalized heading text from one block.
	 *
	 * @param array<string,mixed> $block Block payload.
	 * @return string
	 */
	private function extract_heading_text_from_block( array $block ) {
		$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
		if ( 'core/heading' !== $block_name ) {
			return '';
		}
		$heading_text = html_entity_decode( $this->strip_all_tags_value( (string) ( $block['innerHTML'] ?? '' ) ), ENT_QUOTES, 'UTF-8' );
		$heading_text = preg_replace( '/\s+/u', ' ', trim( (string) $heading_text ) );
		return is_string( $heading_text ) ? $heading_text : '';
	}

}
