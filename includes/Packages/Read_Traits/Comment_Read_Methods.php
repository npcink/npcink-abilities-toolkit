<?php
/**
 * Comment Read Methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides comment list and comment-count callbacks.
 */
trait Comment_Read_Methods {
	/**
	 * Lists comments.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_comments( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$status = sanitize_key( (string) ( $input['status'] ?? 'approve' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$offset = ( $page - 1 ) * $per_page;

		$args = array(
			'status' => '' !== $status ? $status : 'approve',
			'number' => $per_page,
			'offset' => $offset,
		);
		if ( $post_id > 0 ) {
			$args['post_id'] = $post_id;
		}

		$comments = get_comments( $args );
		$count_args = $args;
		$count_args['count'] = true;
		$count_args['number'] = 0;
		$count_args['offset'] = 0;
		$total = (int) get_comments( $count_args );
		$items = array();
		foreach ( ( is_array( $comments ) ? $comments : array() ) as $comment ) {
			if ( ! is_object( $comment ) ) {
				continue;
			}
			$comment_post_id = absint( $comment->comment_post_ID ?? 0 );
			$comment_post = $comment_post_id > 0 ? get_post( $comment_post_id ) : null;
			$items[] = array(
				'id'         => absint( $comment->comment_ID ?? 0 ),
				'parent_id'  => absint( $comment->comment_parent ?? 0 ),
				'author'     => sanitize_text_field( (string) ( $comment->comment_author ?? '' ) ),
				'author_user_id' => absint( $comment->user_id ?? 0 ),
				'date'       => sanitize_text_field( (string) ( $comment->comment_date ?? '' ) ),
				'date_gmt'   => sanitize_text_field( (string) ( $comment->comment_date_gmt ?? '' ) ),
				'status'     => sanitize_key( (string) ( $comment->comment_approved ?? '' ) ),
				'type'       => sanitize_key( (string) ( $comment->comment_type ?? '' ) ),
				'post_id'    => $comment_post_id,
				'post_title' => $comment_post_id > 0 ? sanitize_text_field( (string) get_the_title( $comment_post_id ) ) : '',
				'post_type'  => is_object( $comment_post ) ? sanitize_key( (string) ( $comment_post->post_type ?? '' ) ) : '',
				'post_status' => is_object( $comment_post ) ? sanitize_key( (string) ( $comment_post->post_status ?? '' ) ) : '',
				'excerpt'    => wp_trim_words( wp_strip_all_tags( (string) ( $comment->comment_content ?? '' ) ), 20 ),
			);
		}

		return array(
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Counts comments for one status with isolated-runtime fallback.
	 *
	 * @param string $status Comment status.
	 * @return int
	 */
	private function count_comments_for_status( $status ) {
		$status = sanitize_key( (string) $status );
		if ( ! function_exists( 'get_comments' ) ) {
			return 0;
		}
		$count = get_comments(
			array(
				'status' => '' !== $status ? $status : 'hold',
				'count'  => true,
				'number' => 0,
				'offset' => 0,
			)
		);
		if ( is_numeric( $count ) ) {
			return max( 0, (int) $count );
		}
		if ( is_array( $count ) ) {
			return count( $count );
		}

		return 0;
	}
}
