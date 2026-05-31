<?php
/**
 * Style read methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides reference style, site baseline, and article style profile callbacks.
 */
trait Style_Read_Methods {
	/**
	 * Extracts a lightweight writing-style profile from reference posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function extract_reference_post_style( $input ) {
		$input = is_array( $input ) ? $input : array();
		$reference_post_ids = is_array( $input['reference_post_ids'] ?? null ) ? $input['reference_post_ids'] : array();
		$reference_post_ids = array_values(
			array_unique(
				array_filter(
					array_map( array( $this, 'absint_value' ), $reference_post_ids )
				)
			)
		);

		if ( empty( $reference_post_ids ) ) {
			return $this->empty_style_profile_result( '' );
		}

		$posts = array();
		foreach ( array_slice( $reference_post_ids, 0, 5 ) as $post_id ) {
			$post = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
			if ( ! is_object( $post ) || empty( $post->ID ) ) {
				continue;
			}
			if ( function_exists( 'current_user_can' ) && ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$posts[] = $post;
		}

		$payload = $this->build_style_profile_from_posts( $posts );

		return array(
			'success' => true,
			'data'    => array(
				'profile' => is_array( $payload['profile'] ?? null ) ? $payload['profile'] : array(),
				'samples' => is_array( $payload['samples'] ?? null ) ? $payload['samples'] : array(),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Extracts a site or author style baseline from recent posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function extract_style_baseline( $input ) {
		$input = is_array( $input ) ? $input : array();
		$mode = sanitize_key( (string) ( $input['mode'] ?? 'site_recent' ) );
		if ( ! in_array( $mode, array( 'off', 'site_recent', 'author_recent' ), true ) ) {
			$mode = 'site_recent';
		}
		if ( 'off' === $mode ) {
			return $this->empty_style_profile_result( 'off' );
		}

		$author_id = $this->absint_value( $input['author_id'] ?? 0 );
		if ( $author_id <= 0 && 'author_recent' === $mode && function_exists( 'get_current_user_id' ) ) {
			$author_id = $this->absint_value( get_current_user_id() );
		}

		$cache_key = 'magick_ai_style_baseline_' . $mode . '_' . ( $author_id > 0 ? 'author_' . $author_id : 'site' ) . '_v' . $this->get_style_baseline_cache_version();
		if ( function_exists( 'get_transient' ) ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_array( $cached ) && isset( $cached['success'], $cached['data'] ) ) {
				return $cached;
			}
		}

		$limit = max( 1, min( 5, $this->absint_value( $input['limit'] ?? 4 ) ) );
		$query_args = array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => $limit,
			'orderby'          => 'date',
			'order'            => 'DESC',
				'suppress_filters' => false,
		);
		if ( 'author_recent' === $mode && $author_id > 0 ) {
			$query_args['author'] = $author_id;
		}

		$posts = function_exists( 'get_posts' ) ? get_posts( $query_args ) : array();
		$posts = is_array( $posts ) ? $posts : array();
		if ( empty( $posts ) && 'author_recent' === $mode ) {
			unset( $query_args['author'] );
			$posts = function_exists( 'get_posts' ) ? get_posts( $query_args ) : array();
			$posts = is_array( $posts ) ? $posts : array();
			$mode = 'site_recent';
		}

		$payload = $this->build_style_profile_from_posts( $posts );
		$result = array(
			'success' => true,
			'data'    => array(
				'profile' => is_array( $payload['profile'] ?? null ) ? $payload['profile'] : array(),
				'samples' => is_array( $payload['samples'] ?? null ) ? $payload['samples'] : array(),
				'source'  => $mode,
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);

		if ( function_exists( 'set_transient' ) ) {
			$ttl = defined( 'HOUR_IN_SECONDS' ) ? 12 * HOUR_IN_SECONDS : 12 * 60 * 60;
			set_transient( $cache_key, $result, $ttl );
		}

		return $result;
	}

	/**
	 * Builds one merged article style profile from baseline, references, and explicit hints.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function build_article_style_profile( $input ) {
		$input = is_array( $input ) ? $input : array();
		$reference_profile = is_array( $input['reference_profile'] ?? null ) ? $input['reference_profile'] : array();
		$baseline_profile = is_array( $input['baseline_profile'] ?? null ) ? $input['baseline_profile'] : array();
		$voice_profile = sanitize_text_field( (string) ( $input['voice_profile'] ?? '' ) );
		$opening_style = sanitize_text_field( (string) ( $input['opening_style'] ?? '' ) );
		$structure_style = sanitize_text_field( (string) ( $input['structure_style'] ?? '' ) );

		$resolved_voice = '' !== $voice_profile
			? $voice_profile
			: sanitize_key( (string) ( $reference_profile['dominant_voice_profile'] ?? $baseline_profile['dominant_voice_profile'] ?? '' ) );
		$resolved_opening = '' !== $opening_style
			? $opening_style
			: sanitize_key( (string) ( $reference_profile['dominant_opening_style'] ?? $baseline_profile['dominant_opening_style'] ?? '' ) );
		$resolved_structure = '' !== $structure_style
			? $structure_style
			: sanitize_text_field( (string) ( $reference_profile['structure_style'] ?? $baseline_profile['structure_style'] ?? '' ) );

		$style_brief_parts = array();
		foreach ( array( $baseline_profile['style_brief'] ?? '', $reference_profile['style_brief'] ?? '' ) as $brief_part ) {
			$brief_part = $this->sanitize_metadata_text( (string) $brief_part );
			if ( '' !== $brief_part && ! in_array( $brief_part, $style_brief_parts, true ) ) {
				$style_brief_parts[] = $brief_part;
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'profile' => array(
					'resolved_voice_profile'     => $resolved_voice,
					'resolved_opening_style'     => $resolved_opening,
					'resolved_structure_style'   => $resolved_structure,
					'baseline_profile'           => $baseline_profile,
					'reference_profile'          => $reference_profile,
					'style_brief'                => implode( '; ', $style_brief_parts ),
				),
			),
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Builds a compact site style baseline.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function get_site_style_baseline( $input ) {
		$input = is_array( $input ) ? $input : array();
		$mode = sanitize_key( (string) ( $input['mode'] ?? 'site_recent' ) );
		if ( ! in_array( $mode, array( 'site_recent', 'author_recent' ), true ) ) {
			$mode = 'site_recent';
		}
		$baseline = $this->extract_style_baseline(
			array(
				'mode'      => $mode,
				'author_id' => $this->absint_value( $input['author_id'] ?? 0 ),
				'limit'     => max( 1, min( 5, $this->absint_value( $input['limit'] ?? 5 ) ) ),
			)
		);
		if ( is_array( $baseline ) ) {
			$baseline['message'] = __( 'Site style baseline built.', 'magick-ai-abilities' );
			$baseline['meta']['source'] = 'local_site_style_baseline';
			return $baseline;
		}

		return $this->build_analysis_success_response(
			array(
				'profile' => array(),
				'samples' => array(),
				'source'  => 'unavailable',
			),
			array(
				'source'         => 'local_site_style_baseline',
				'execution_mode' => 'deterministic',
			),
			'Site style baseline built.'
		);
	}

	/**
	 * Returns an empty style-profile response.
	 *
	 * @param string $source Optional baseline source.
	 * @return array<string,mixed>
	 */
	private function empty_style_profile_result( string $source ) {
		$data = array(
			'profile' => array(),
			'samples' => array(),
		);
		if ( '' !== $source ) {
			$data['source'] = $source;
		}

		return array(
			'success' => true,
			'data'    => $data,
			'meta'    => array(
				'contract_version' => 'v1',
				'execution_mode'   => 'projection_only',
			),
		);
	}

	/**
	 * Builds a style profile from raw post objects.
	 *
	 * @param array<int,mixed> $posts Posts.
	 * @return array<string,mixed>
	 */
	private function build_style_profile_from_posts( array $posts ) {
		$opening_counts = array();
		$ending_counts = array();
		$voice_counts = array();
		$heading_total = 0;
		$paragraph_total = 0;
		$paragraph_lengths = array();
		$samples = array();

		foreach ( $posts as $post ) {
			if ( ! is_object( $post ) || empty( $post->ID ) ) {
				continue;
			}
			$content = (string) ( $post->post_content ?? '' );
			$paragraphs = $this->extract_article_style_paragraphs( $content );
			$opening = $this->classify_article_opening_style( (string) ( $paragraphs[0] ?? '' ) );
			$ending = $this->classify_article_ending_style( (string) ( ! empty( $paragraphs ) ? $paragraphs[ count( $paragraphs ) - 1 ] : '' ) );
			$voice = $this->classify_article_voice_profile( $content );
			$heading_count = $this->count_article_heading_blocks( $content );

			foreach ( $paragraphs as $paragraph ) {
				++$paragraph_total;
				$paragraph_lengths[] = $this->measure_style_text_length( (string) $paragraph );
			}

			$heading_total += $heading_count;
			$opening_counts[ $opening ] = (int) ( $opening_counts[ $opening ] ?? 0 ) + 1;
			$ending_counts[ $ending ] = (int) ( $ending_counts[ $ending ] ?? 0 ) + 1;
			$voice_counts[ $voice ] = (int) ( $voice_counts[ $voice ] ?? 0 ) + 1;
			$samples[] = array(
				'post_id'         => (int) $post->ID,
				'title'           => sanitize_text_field( function_exists( 'get_the_title' ) ? (string) get_the_title( $post ) : (string) ( $post->post_title ?? '' ) ),
				'opening_style'   => $opening,
				'ending_style'    => $ending,
				'voice_profile'   => $voice,
				'heading_count'   => $heading_count,
				'paragraph_count' => count( $paragraphs ),
			);
		}

		$sample_count = count( $samples );
		$average_paragraph_length = $paragraph_total > 0 ? (int) round( array_sum( $paragraph_lengths ) / $paragraph_total ) : 0;
		$short_paragraph_count = 0;
		foreach ( $paragraph_lengths as $paragraph_length ) {
			if ( $paragraph_length > 0 && $paragraph_length <= 48 ) {
				++$short_paragraph_count;
			}
		}
		$short_paragraph_ratio = $paragraph_total > 0 ? round( $short_paragraph_count / $paragraph_total, 2 ) : 0;
		$dominant_opening = $this->pick_dominant_style_value( $opening_counts );
		$dominant_ending = $this->pick_dominant_style_value( $ending_counts );
		$dominant_voice = $this->pick_dominant_style_value( $voice_counts );
		$heading_density = $sample_count > 0 ? round( $heading_total / $sample_count, 1 ) : 0;
		$structure_style = $short_paragraph_ratio >= 0.35 ? 'mixed_paragraph_lengths' : 'longer_paragraph_sections';
		if ( $heading_density >= 4 ) {
			$structure_style .= '_with_dense_headings';
		}

		$style_brief = array();
		if ( '' !== $dominant_opening ) {
			$style_brief[] = '开头多为 ' . $dominant_opening;
		}
		if ( '' !== $dominant_voice ) {
			$style_brief[] = '语气偏 ' . $dominant_voice;
		}
		if ( $average_paragraph_length > 0 ) {
			$style_brief[] = '段落平均长度约 ' . $average_paragraph_length . ' 字';
		}
		if ( $heading_density > 0 ) {
			$style_brief[] = '平均每篇约 ' . $heading_density . ' 个小标题';
		}
		if ( '' !== $dominant_ending ) {
			$style_brief[] = '结尾多为 ' . $dominant_ending;
		}

		return array(
			'profile' => array(
				'sample_count'             => $sample_count,
				'dominant_opening_style'   => $dominant_opening,
				'dominant_ending_style'    => $dominant_ending,
				'dominant_voice_profile'   => $dominant_voice,
				'structure_style'          => $structure_style,
				'average_paragraph_length' => $average_paragraph_length,
				'short_paragraph_ratio'    => $short_paragraph_ratio,
				'average_heading_count'    => $heading_density,
				'style_brief'              => implode( '，', $style_brief ),
			),
			'samples' => $samples,
		);
	}

	/**
	 * Extracts readable paragraph strings from article content.
	 *
	 * @param string $content Article content.
	 * @return array<int,string>
	 */
	private function extract_article_style_paragraphs( string $content ): array {
		$paragraphs = array();
		$parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		if ( ! empty( $parsed_blocks ) ) {
			foreach ( $parsed_blocks as $block ) {
				$block = is_array( $block ) ? $block : array();
				$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
				if ( ! in_array( $block_name, array( 'core/paragraph', 'core/quote', 'core/freeform' ), true ) ) {
					continue;
				}
				$text = trim( $this->strip_all_tags_value( (string) ( $block['innerHTML'] ?? '' ) ) );
				if ( '' !== $text ) {
					$paragraphs[] = $text;
				}
			}
		}

		if ( empty( $paragraphs ) ) {
			$raw_paragraphs = preg_split( '/\n\s*\n/u', trim( $this->strip_all_tags_value( $content ) ) ) ?: array();
			foreach ( $raw_paragraphs as $paragraph ) {
				$paragraph = trim( (string) $paragraph );
				if ( '' !== $paragraph ) {
					$paragraphs[] = $paragraph;
				}
			}
		}

		return array_values( $paragraphs );
	}

	/**
	 * Measures a text length with multibyte support.
	 *
	 * @param string $text Text.
	 * @return int
	 */
	private function measure_style_text_length( string $text ) {
		$text = trim( $text );
		if ( '' === $text ) {
			return 0;
		}
		if ( function_exists( 'mb_strlen' ) ) {
			return (int) mb_strlen( $text, 'UTF-8' );
		}
		return strlen( $text );
	}

	/**
	 * Counts heading blocks in one article body.
	 *
	 * @param string $content Article content.
	 * @return int
	 */
	private function count_article_heading_blocks( string $content ) {
		$parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		$heading_count = 0;
		foreach ( $parsed_blocks as $block ) {
			$block_name = sanitize_text_field( (string) ( is_array( $block ) ? ( $block['blockName'] ?? '' ) : '' ) );
			if ( 'core/heading' === $block_name ) {
				++$heading_count;
			}
		}
		return $heading_count;
	}

	/**
	 * Classifies one opening paragraph pattern.
	 *
	 * @param string $paragraph Paragraph text.
	 * @return string
	 */
	private function classify_article_opening_style( string $paragraph ): string {
		$paragraph = trim( $paragraph );
		if ( '' === $paragraph ) {
			return 'unknown';
		}
		if ( false !== strpos( $paragraph, '?' ) || false !== strpos( $paragraph, '？' ) ) {
			return 'question';
		}
		if ( preg_match( '/^(当|如果|最近|上周|有一次|很多时候|在)/u', $paragraph ) ) {
			return 'scene';
		}
		return 'direct_judgement';
	}

	/**
	 * Classifies one ending paragraph pattern.
	 *
	 * @param string $paragraph Paragraph text.
	 * @return string
	 */
	private function classify_article_ending_style( string $paragraph ) {
		$paragraph = trim( $paragraph );
		if ( '' === $paragraph ) {
			return 'unknown';
		}
		if ( preg_match( '/(建议|可以|不妨|值得|下一步)/u', $paragraph ) ) {
			return 'action';
		}
		if ( preg_match( '/(总的来说|总之|归根结底|一句话)/u', $paragraph ) ) {
			return 'summary';
		}
		return 'observation';
	}

	/**
	 * Classifies one article voice profile.
	 *
	 * @param string $content Plain content.
	 * @return string
	 */
	private function classify_article_voice_profile( string $content ): string {
		$content = $this->strip_all_tags_value( $content );
		if ( preg_match_all( '/(我|我们|团队|实操|踩坑)/u', $content ) >= 3 ) {
			return 'experiential_editorial';
		}
		if ( preg_match_all( '/[0-9]{2,}|%|增长|下降|数据|指标/u', $content ) >= 3 ) {
			return 'analytical_editorial';
		}
		return 'practical_editorial';
	}

	/**
	 * Picks one dominant style value from a count map.
	 *
	 * @param array<string,int> $counts Count map.
	 * @return string
	 */
	private function pick_dominant_style_value( array $counts ) {
		if ( empty( $counts ) ) {
			return '';
		}
		arsort( $counts );
		$key = key( $counts );
		return is_string( $key ) ? sanitize_key( $key ) : '';
	}

	/**
	 * Returns the current style baseline cache version.
	 *
	 * @return int
	 */
	private function get_style_baseline_cache_version() {
		if ( function_exists( 'get_option' ) ) {
			return max( 1, (int) get_option( 'magick_ai_style_baseline_cache_version', 1 ) );
		}
		return 1;
	}
}
