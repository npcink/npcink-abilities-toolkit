<?php
/** Bounded media reference evidence. @package NpcinkAbilitiesToolkit */
namespace Npcink_Abilities_Toolkit\Packages;
if ( ! defined( 'ABSPATH' ) ) { exit; }
trait Media_Usage_Read_Methods {
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

		$featured_posts = array();
		$featured_count = 0;
		if ( class_exists( '\WP_Query' ) ) {
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
			$featured_count = (int) $featured_query->found_posts;
			foreach ( (array) $featured_query->posts as $post_id ) {
				$post_id = absint( $post_id );
				if ( $post_id > 0 && current_user_can( 'read_post', $post_id ) ) {
					$featured_posts[] = array(
						'post_id'    => $post_id,
						'post_type'  => sanitize_key( (string) get_post_type( $post_id ) ),
						'post_status' => sanitize_key( (string) get_post_status( $post_id ) ),
						'post_title' => sanitize_text_field( (string) get_the_title( $post_id ) ),
					);
				}
			}
		} else {
			$featured_posts = $this->find_media_featured_image_references( $attachment_id, 5 );
			$featured_count = count( $featured_posts );
			$featured_posts = array_values( array_filter( $featured_posts, static function ( $row ) { return current_user_can( 'read_post', $row['post_id'] ); } ) );
		}

		$content_scan = $this->find_media_content_references( $attachment_id, 10 );
		$content_posts = $content_scan['posts'];

		return array(
			'featured_image_count' => $featured_count,
			'featured_image_posts' => $featured_posts,
			'content_reference_count' => $content_scan['count'],
			'content_reference_posts' => $content_posts,
			'content_reference_scan_run' => true,
			'content_reference_scan_complete' => $content_scan['complete'],
		);
	}
	/**
	 * Finds live content references to an attachment in post bodies.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $limit Maximum rows.
	 * @return array{posts:array<int,array<string,mixed>>,count:int,complete:bool}
	 */
	private function find_media_content_references( $attachment_id, $limit ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$limit = max( 1, (int) $limit );
		$url = function_exists( 'wp_get_attachment_url' ) ? $this->esc_url_value( (string) wp_get_attachment_url( $attachment_id ) ) : '';
		$posts = function_exists( 'get_posts' ) ? get_posts( array( 'post_type' => 'any', 'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private' ), 'posts_per_page' => 51, 'orderby' => 'ID', 'order' => 'DESC' ) ) : array_slice( array_values( (array) ( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ?? array() ) ), 0, 51 );
		$complete = count( $posts ) <= 50;
		$posts = array_slice( $posts, 0, 50 );
		$count = 0;
		$references = array();

		foreach ( $posts as $post ) {
			if ( ! is_object( $post ) || 'attachment' === sanitize_key( (string) ( $post->post_type ?? '' ) ) ) {
				continue;
			}
			if ( ! in_array( sanitize_key( (string) ( $post->post_status ?? '' ) ), array( 'publish', 'future', 'draft', 'pending', 'private' ), true ) ) {
				continue;
			}
			$content = (string) ( $post->post_content ?? '' );
			if ( ! $this->media_content_contains_reference( $content, $attachment_id, $url ) ) {
				continue;
			}
			++$count;
			if ( count( $references ) < $limit && current_user_can( 'read_post', $post->ID ) ) {
				$references[] = $this->media_reference_post_row( $post );
			}
		}

		return array( 'posts' => $references, 'count' => $count, 'complete' => $complete );
	}
	/**
	 * Finds live featured-image references to an attachment for unit fallback.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @param int $limit Maximum rows.
	 * @return array<int,array<string,mixed>>
	 */
	private function find_media_featured_image_references( $attachment_id, $limit ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$limit = max( 1, (int) $limit );
		$references = array();
		$posts = isset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ) ? array_values( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ) : array();

		foreach ( $posts as $post ) {
			if ( ! is_object( $post ) || 'attachment' === sanitize_key( (string) ( $post->post_type ?? '' ) ) ) {
				continue;
			}
			if ( ! in_array( sanitize_key( (string) ( $post->post_status ?? '' ) ), array( 'publish', 'future', 'draft', 'pending', 'private' ), true ) ) {
				continue;
			}
			$post_id = $this->absint_value( $post->ID ?? 0 );
			if ( $attachment_id !== $this->absint_value( $GLOBALS['npcink_abilities_toolkit_unit_post_meta'][ $post_id ]['_thumbnail_id'] ?? 0 ) ) {
				continue;
			}
			$references[] = $this->media_reference_post_row( $post );
			if ( count( $references ) >= $limit ) {
				break;
			}
		}

		return $references;
	}
	/**
	 * Checks common WordPress content references for an attachment.
	 *
	 * @param string $content Post content.
	 * @param int    $attachment_id Attachment ID.
	 * @param string $url Attachment URL.
	 * @return bool
	 */
	private function media_content_contains_reference( $content, $attachment_id, $url ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$content = (string) $content;
		if ( $attachment_id <= 0 || '' === $content ) {
			return false;
		}

		foreach ( array( 'wp-image-' . $attachment_id, '"id":' . $attachment_id, "'id':" . $attachment_id, 'data-id="' . $attachment_id . '"', 'data-id=\'' . $attachment_id . '\'' ) as $needle ) {
			if ( false !== strpos( $content, $needle ) ) {
				return true;
			}
		}

		return '' !== $url && false !== strpos( $content, $url );
	}
	/**
	 * Builds a safe post row for media reference diagnostics.
	 *
	 * @param object $post Post object.
	 * @return array<string,mixed>
	 */
	private function media_reference_post_row( $post ) {
		$post_id = $this->absint_value( $post->ID ?? 0 );
		return array(
			'post_id'     => $post_id,
			'post_type'   => sanitize_key( (string) ( $post->post_type ?? '' ) ),
			'post_status' => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'post_title'  => sanitize_text_field( (string) ( $post->post_title ?? ( function_exists( 'get_the_title' ) ? get_the_title( $post_id ) : '' ) ) ),
		);
	}
}
