<?php
/**
 * Internal Link Read Methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides internal-link target discovery callbacks.
 */
trait Internal_Link_Read_Methods {
	/**
	 * Resolves local published posts that can be used as internal-link targets.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function resolve_internal_link_targets( $input ) {
		$input = is_array( $input ) ? $input : array();
		$current_post_id = $this->absint_value( $input['current_post_id'] ?? 0 );
		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$content = $this->normalize_plain_text( $input['content'] ?? '' );
		$focus_keyword = sanitize_text_field( (string) ( $input['focus_keyword'] ?? '' ) );
		$keywords = is_array( $input['keywords'] ?? null ) ? $input['keywords'] : array();
		$terms = $this->collect_focus_terms( $title, $focus_keyword, $keywords );
		$max_targets = max( 1, min( 6, $this->absint_value( $input['max_targets'] ?? 3 ) ) );

		$targets = array();
		foreach ( array_slice( $terms, 0, 4 ) as $term ) {
			$query = new \WP_Query(
				array(
					'post_type'      => 'any',
					'post_status'    => array( 'publish' ),
					'posts_per_page' => $max_targets,
					's'              => $term,
					// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Bounded candidate search must exclude the source post from its own internal-link targets.
					'post__not_in'   => $current_post_id > 0 ? array( $current_post_id ) : array(),
					'fields'         => 'ids',
				)
			);
			foreach ( (array) $query->posts as $post_id ) {
				$post_id = $this->absint_value( $post_id );
				if ( $post_id <= 0 ) {
					continue;
				}
				$targets[ $post_id ] = array(
					'post_id'         => $post_id,
					'title'           => sanitize_text_field( (string) get_the_title( $post_id ) ),
					'url'             => esc_url_raw( (string) get_permalink( $post_id ) ),
					'anchor_text'     => $term,
						/* translators: %s: Related search term. */
						'reason'          => sprintf( __( 'Existing site content related to "%s" can be used as supplemental reading.', 'magick-ai-abilities' ), $term ),
					'relevance_score' => 0.72,
				);
				if ( count( $targets ) >= $max_targets ) {
					break 2;
				}
			}
		}

		$placement_plan = array();
		foreach ( array_values( $targets ) as $target ) {
			$placement_plan[] = array(
				'target_post_id' => $this->absint_value( $target['post_id'] ?? 0 ),
				'target_url'     => esc_url_raw( (string) ( $target['url'] ?? '' ) ),
				'anchor_text'    => sanitize_text_field( (string) ( $target['anchor_text'] ?? '' ) ),
				'placement_hint' => __( 'Insert one internal link after the first paragraph that explains the core concept.', 'magick-ai-abilities' ),
				'reason'         => $this->sanitize_metadata_text( (string) ( $target['reason'] ?? '' ) ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'targets'        => array_values( $targets ),
				'placement_plan' => $placement_plan,
				'no_link_zones'  => '' !== $content ? array( __( 'Keep titles, opening summaries, and CTA paragraphs link-free to avoid diluting the main answer.', 'magick-ai-abilities' ) ) : array(),
				'summary'        => array(
					'candidate_count' => count( $targets ),
					'placement_count' => count( $placement_plan ),
					'focus_terms'     => $terms,
				),
			),
			'meta'    => array(
				'source'         => 'local_internal_link_inventory',
				'execution_mode' => 'deterministic',
			),
			'message' => __( 'Internal link targets resolved.', 'magick-ai-abilities' ),
		);
	}
}
