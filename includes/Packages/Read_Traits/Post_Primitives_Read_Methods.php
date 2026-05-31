<?php
/**
 * Post Primitives Read Methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides post list, post context, revision, block, and post type callbacks.
 */
trait Post_Primitives_Read_Methods {
	/**
	 * Searches posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function search_posts( $input ) {
		$input = is_array( $input ) ? $input : array();
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );

		if ( '' === $search ) {
			return new \WP_Error( 'magick_ai_abilities_search_empty', __( 'Search keyword cannot be empty.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_type_invalid', __( 'Post type does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$query = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => '' !== $status ? $status : 'publish',
				's'              => $search,
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'relevance',
				'order'          => 'DESC',
			)
		);

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( ! is_object( $post ) ) {
				continue;
			}
			$post_id = absint( $post->ID ?? 0 );
			if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$items[] = array(
				'id'        => $post_id,
				'title'     => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'slug'      => sanitize_title( (string) ( $post->post_name ?? '' ) ),
				'status'    => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'type'      => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'author_id' => absint( $post->post_author ?? 0 ),
				'date'      => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
				'excerpt'   => wp_trim_words( wp_strip_all_tags( (string) ( $post->post_content ?? '' ) ), 30 ),
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
	 * Gets post statistics.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_stats( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$content = (string) ( $post->post_content ?? '' );
		$text = wp_strip_all_tags( $content );
		$word_count = str_word_count( $text );
		$image_count = substr_count( strtolower( $content ), '<img' );

		return array(
			'post_id'              => $post_id,
			'word_count'           => $word_count,
			'image_count'          => $image_count,
			'reading_time_minutes' => max( 1, (int) ceil( $word_count / 200 ) ),
			'comment_count'        => absint( $post->comment_count ?? 0 ),
			'published_date'       => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
			'modified_date'        => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
			'permalink'            => esc_url_raw( (string) get_permalink( $post_id ) ),
		);
	}

	/**
	 * Lists revisions using the legacy list-revisions response shape.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_revisions( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post revision history.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$all_revisions = function_exists( 'wp_get_post_revisions' )
			? wp_get_post_revisions(
				$post_id,
				array(
					'check_enabled' => false,
					'order'         => 'DESC',
					'orderby'       => 'date ID',
				)
			)
			: array();
		$all_revisions = is_array( $all_revisions ) ? array_values( $all_revisions ) : array();
		$total = count( $all_revisions );
		$slice = array_slice( $all_revisions, ( $page - 1 ) * $per_page, $per_page );
		$items = array();

		foreach ( $slice as $revision ) {
			if ( ! is_object( $revision ) ) {
				continue;
			}
			$author_id = absint( $revision->post_author ?? 0 );
			$items[] = array(
				'id'     => absint( $revision->ID ?? 0 ),
				'date'   => sanitize_text_field( (string) ( $revision->post_date ?? '' ) ),
				'author' => $author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) ) : '',
				'title'  => sanitize_text_field( (string) ( $revision->post_title ?? '' ) ),
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
	 * Gets post meta.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_meta( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		$meta_key = sanitize_key( (string) ( $input['meta_key'] ?? '' ) );
		$single = ! array_key_exists( 'single', $input ) || ! empty( $input['single'] );

		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post meta.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

			if ( '' !== $meta_key ) {
				return array(
					'post_id'  => $post_id,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Response field name mirrors the requested post meta key and is not a query argument.
					'meta_key' => $meta_key,
					'value'    => get_post_meta( $post_id, $meta_key, $single ),
					'single'   => $single,
			);
		}

		$all_meta = get_post_meta( $post_id );
		$items = array();
		foreach ( ( is_array( $all_meta ) ? $all_meta : array() ) as $key => $values ) {
			$items[] = array(
				'key'    => sanitize_key( (string) $key ),
				'values' => is_array( $values ) ? array_values( $values ) : array( $values ),
			);
		}

		return array(
			'post_id' => $post_id,
			'items'   => $items,
		);
	}

	/**
	 * Lists posts.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_posts( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		$status_input = $input['status'] ?? $input['post_status'] ?? 'publish';
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$per_page = max( 1, min( 50, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$author_id = absint( $input['author_id'] ?? 0 );
		$orderby = sanitize_key( (string) ( $input['orderby'] ?? 'date' ) );
		$order = strtoupper( sanitize_key( (string) ( $input['order'] ?? 'DESC' ) ) );
		$date_after = sanitize_text_field( (string) ( $input['date_after'] ?? '' ) );
		$date_before = sanitize_text_field( (string) ( $input['date_before'] ?? '' ) );
		$modified_after = sanitize_text_field( (string) ( $input['modified_after'] ?? '' ) );
		$modified_before = sanitize_text_field( (string) ( $input['modified_before'] ?? '' ) );
		$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? '' ) );
		$term_id = absint( $input['term_id'] ?? 0 );
		$term_slug = sanitize_title( (string) ( $input['term_slug'] ?? '' ) );

		$statuses = is_array( $status_input ) ? $status_input : explode( ',', (string) $status_input );
		$statuses = array_values( array_filter( array_map( 'sanitize_key', $statuses ) ) );
		if ( empty( $statuses ) ) {
			$statuses = array( 'publish' );
		}
		if ( ! in_array( $orderby, array( 'date', 'modified', 'title', 'id', 'menu_order', 'comment_count' ), true ) ) {
			$orderby = 'date';
		}
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'magick_ai_abilities_post_type_invalid', __( 'Post type does not exist.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'        => $post_type,
			'post_status'      => 1 === count( $statuses ) ? $statuses[0] : $statuses,
			'posts_per_page'   => $per_page,
			'paged'            => $page,
			'orderby'          => 'id' === $orderby ? 'ID' : $orderby,
			'order'            => $order,
			'fields'           => 'ids',
			'suppress_filters' => false,
		);
		if ( $author_id > 0 ) {
			$args['author'] = $author_id;
		}
		if ( '' !== $search ) {
			$args['s'] = $search;
		}
		if ( '' !== $date_after || '' !== $date_before ) {
			$args['date_query'] = array( array( 'column' => 'post_date' ) );
			if ( '' !== $date_after ) {
				$args['date_query'][0]['after'] = $date_after;
			}
			if ( '' !== $date_before ) {
				$args['date_query'][0]['before'] = $date_before;
			}
		}
		if ( '' !== $modified_after || '' !== $modified_before ) {
			$args['date_query'] = is_array( $args['date_query'] ?? null ) ? $args['date_query'] : array();
			$modified_query = array( 'column' => 'post_modified' );
			if ( '' !== $modified_after ) {
				$modified_query['after'] = $modified_after;
			}
			if ( '' !== $modified_before ) {
				$modified_query['before'] = $modified_before;
			}
				$args['date_query'][] = $modified_query;
			}
			if ( '' !== $taxonomy && ( $term_id > 0 || '' !== $term_slug ) && taxonomy_exists( $taxonomy ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- This read endpoint exposes an explicitly requested bounded taxonomy filter.
				$args['tax_query'] = array(
					array(
						'taxonomy' => $taxonomy,
					'field'    => $term_id > 0 ? 'term_id' : 'slug',
					'terms'    => $term_id > 0 ? array( $term_id ) : array( $term_slug ),
				),
			);
		}

		$query = new \WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post_id ) {
			$post_id = absint( $post_id );
			$post = $post_id > 0 ? get_post( $post_id ) : null;
			if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$item_author_id = absint( $post->post_author ?? 0 );
			$status_value = sanitize_key( (string) ( $post->post_status ?? '' ) );
			$items[] = array(
				'id'          => $post_id,
				'title'       => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'status'      => $status_value,
				'post_status' => $status_value,
				'slug'        => $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) ),
				'date'        => sanitize_text_field( (string) get_post_field( 'post_date', $post_id ) ),
				'modified'    => sanitize_text_field( (string) get_post_field( 'post_modified', $post_id ) ),
				'post_type'   => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'author_id'   => $item_author_id,
				'author'      => $item_author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $item_author_id ) ) : '',
				'excerpt'     => wp_trim_words( wp_strip_all_tags( (string) ( $post->post_excerpt ?: $post->post_content ) ), 30 ),
				'comment_count' => absint( $post->comment_count ?? 0 ),
				'permalink'   => function_exists( 'get_permalink' ) ? $this->esc_url_value( (string) get_permalink( $post_id ) ) : '',
				'edit_link'   => get_edit_post_link( $post_id, 'raw' ),
			);
		}

		return array(
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
			'filters'  => array(
				'post_type'       => $post_type,
				'status'          => $statuses,
				'search'          => $search,
				'author_id'       => $author_id,
				'orderby'         => $orderby,
				'order'           => $order,
				'date_after'      => $date_after,
				'date_before'     => $date_before,
				'modified_after'  => $modified_after,
				'modified_before' => $modified_before,
				'taxonomy'        => $taxonomy,
				'term_id'         => $term_id,
				'term_slug'       => $term_slug,
			),
			'items'    => $items,
		);
	}

	/**
	 * Gets one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$include_content = ! empty( $input['include_content'] );
		$author_id = absint( $post->post_author ?? 0 );
		$data = array(
			'id'        => $post_id,
			'title'     => sanitize_text_field( (string) get_the_title( $post_id ) ),
			'status'    => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'post_type' => sanitize_key( (string) ( $post->post_type ?? '' ) ),
			'date'      => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
			'author'    => $author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) ) : '',
			'excerpt'   => wp_trim_words( wp_strip_all_tags( (string) ( $post->post_content ?? '' ) ), 55 ),
			'edit_link' => get_edit_post_link( $post_id, 'raw' ),
		);
		if ( $include_content ) {
			$data['content'] = (string) ( $post->post_content ?? '' );
		}

		return $data;
	}

	/**
	 * Gets an agent-ready context bundle for one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_context( $input ) {
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

		$include_content = ! array_key_exists( 'include_content', $input ) || ! empty( $input['include_content'] );
		$include_blocks = ! array_key_exists( 'include_blocks', $input ) || ! empty( $input['include_blocks'] );
		$include_terms = ! array_key_exists( 'include_terms', $input ) || ! empty( $input['include_terms'] );
		$include_media = ! array_key_exists( 'include_media', $input ) || ! empty( $input['include_media'] );
		$include_revisions = ! empty( $input['include_revisions'] );
		$include_meta = ! empty( $input['include_meta'] );

		$content = (string) ( $post->post_content ?? '' );
		$plain_text = $this->strip_all_tags_value( $content );
		$parsed_blocks = $include_blocks && function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		$blocks = $include_blocks ? $this->normalize_block_tree( is_array( $parsed_blocks ) ? $parsed_blocks : array(), true ) : array();
		if ( $include_blocks && empty( $blocks ) && '' !== trim( $content ) ) {
			$blocks[] = array(
				'blockName'   => 'core/freeform',
				'attrs'       => array(),
				'innerHTML'   => $content,
				'innerBlocks' => array(),
			);
		}

		$author_id = $this->absint_value( $post->post_author ?? 0 );
		$author_name = '';
		if ( $author_id > 0 && function_exists( 'get_the_author_meta' ) ) {
			$author_name = sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) );
		}
		$template = function_exists( 'get_page_template_slug' ) ? sanitize_text_field( (string) get_page_template_slug( $post_id ) ) : '';
		if ( '' === $template && function_exists( 'get_post_meta' ) ) {
			$template = sanitize_text_field( (string) get_post_meta( $post_id, '_wp_page_template', true ) );
		}
		$seo_provider = $this->detect_seo_provider();
		$seo_keys = $this->seo_meta_keys( $seo_provider );
		$featured_media_id = function_exists( 'get_post_thumbnail_id' ) ? $this->absint_value( get_post_thumbnail_id( $post_id ) ) : 0;

		$data = array(
			'post'  => array(
				'id'         => $post_id,
				'title'      => sanitize_text_field( (string) get_the_title( $post_id ) ),
				'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'post_type'  => sanitize_key( (string) ( $post->post_type ?? '' ) ),
				'slug'       => $this->sanitize_metadata_slug( (string) ( $post->post_name ?? '' ) ),
				'parent_id'  => $this->absint_value( $post->post_parent ?? 0 ),
				'menu_order' => (int) ( $post->menu_order ?? 0 ),
				'author_id'  => $author_id,
				'author'     => $author_name,
				'excerpt'    => sanitize_textarea_field( (string) ( $post->post_excerpt ?? '' ) ),
				'date'       => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
				'date_gmt'   => sanitize_text_field( (string) ( $post->post_date_gmt ?? '' ) ),
				'modified'   => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
				'modified_gmt' => sanitize_text_field( (string) ( $post->post_modified_gmt ?? '' ) ),
				'comment_status' => sanitize_key( (string) ( $post->comment_status ?? '' ) ),
				'ping_status' => sanitize_key( (string) ( $post->ping_status ?? '' ) ),
				'comment_count' => absint( $post->comment_count ?? 0 ),
				'template'   => $template,
				'format'     => function_exists( 'get_post_format' ) ? sanitize_key( (string) get_post_format( $post_id ) ) : '',
				'featured_media_id' => $featured_media_id,
				'permalink'  => function_exists( 'get_permalink' ) ? $this->esc_url_value( (string) get_permalink( $post_id ) ) : '',
				'edit_link'  => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
				'content'    => $include_content ? $content : '',
				'text_excerpt' => $this->trim_words_value( $plain_text, 80 ),
			),
			'stats' => array(
				'content_length'        => strlen( $content ),
				'plain_text_length'     => $this->strlen_value( $plain_text ),
				'word_count'            => str_word_count( $plain_text ),
				'image_count'           => substr_count( strtolower( $content ), '<img' ),
				'block_count'           => count( $blocks ),
				'reading_time_minutes'  => max( 1, (int) ceil( max( 1, str_word_count( $plain_text ) ) / 200 ) ),
			),
			'seo'   => array(
				'provider'      => $seo_provider,
				'title'         => $this->get_first_post_meta_text( $post_id, $seo_keys['title'] ?? '' ),
				'description'   => $this->sanitize_metadata_text( $this->get_first_post_meta_text( $post_id, $seo_keys['description'] ?? '' ) ),
				'focus_keyword' => $this->get_first_post_meta_text( $post_id, $seo_keys['focus_keyword'] ?? array( '_yoast_wpseo_focuskw', 'rank_math_focus_keyword', 'aioseo_keywords' ) ),
				'meta_keys'     => $seo_keys,
			),
		);

		if ( $include_terms ) {
			$data['terms'] = $this->collect_post_terms_context( $post_id, sanitize_key( (string) ( $post->post_type ?? '' ) ) );
		}
		if ( $include_media ) {
			$data['media'] = $this->collect_post_media_context( $post_id );
		}
		if ( $include_blocks ) {
			$data['blocks'] = $blocks;
		}
		if ( $include_revisions ) {
			$revisions = $this->list_post_revisions(
				array(
					'post_id'  => $post_id,
					'per_page' => 5,
					'page'     => 1,
				)
			);
			$data['revisions'] = is_array( $revisions ) ? array_values( $revisions['items'] ?? array() ) : array();
		}
		if ( $include_meta ) {
			$data['meta'] = $this->get_scoped_post_meta( $post_id, $input['meta_keys'] ?? array() );
		}

		return $this->build_analysis_success_response(
			$data,
			array(
				'source'            => 'local_post_context',
				'execution_mode'    => 'deterministic',
				'included_sections' => array_values(
					array_filter(
							array(
								'post',
								'stats',
								'seo',
								$include_terms ? 'terms' : '',
							$include_media ? 'media' : '',
							$include_blocks ? 'blocks' : '',
							$include_revisions ? 'revisions' : '',
							$include_meta ? 'meta' : '',
						)
					)
				),
			),
			'Post context loaded.'
		);
	}

	/**
	 * Builds a revision change-risk report for one post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_revision_change_risk_report( $input ) {
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
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post revision history.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$max_revisions = max( 1, min( 20, $this->absint_value( $input['max_revisions'] ?? 10 ) ) );
		$revisions = array_slice( $this->get_revision_objects_for_post( $post_id ), 0, $max_revisions );
		$current_title = sanitize_text_field( (string) get_the_title( $post_id ) );
		$current_content = (string) ( $post->post_content ?? '' );
		$current_text_length = $this->strlen_value( $this->strip_all_tags_value( $current_content ) );
		$current_block_count = count( $this->parse_content_blocks( $current_content ) );
		$latest = is_object( $revisions[0] ?? null ) ? $revisions[0] : null;
		$risk_flags = array();
		$recent_rows = array();

		if ( ! is_object( $latest ) ) {
			$risk_flags[] = 'no_revisions';
		}

		$latest_summary = array();
		$change_summary = array(
			'title_changed'        => false,
			'content_length_delta' => 0,
			'block_count_delta'    => 0,
			'latest_modified'      => '',
		);

		if ( is_object( $latest ) ) {
			$latest_title = sanitize_text_field( (string) ( $latest->post_title ?? '' ) );
			$latest_content = (string) ( $latest->post_content ?? '' );
			$latest_text_length = $this->strlen_value( $this->strip_all_tags_value( $latest_content ) );
			$latest_block_count = count( $this->parse_content_blocks( $latest_content ) );
			$content_delta = $current_text_length - $latest_text_length;
			$block_delta = $current_block_count - $latest_block_count;
			$title_changed = '' !== $latest_title && $latest_title !== $current_title;
			if ( $title_changed ) {
				$risk_flags[] = 'title_changed';
			}
			if ( abs( $content_delta ) >= 800 || ( $latest_text_length > 0 && abs( $content_delta ) / max( 1, $latest_text_length ) >= 0.35 ) ) {
				$risk_flags[] = 'large_content_delta';
			}
			if ( abs( $block_delta ) >= 4 ) {
				$risk_flags[] = 'large_block_delta';
			}

			$modified = sanitize_text_field( (string) ( $latest->post_modified_gmt ?? $latest->post_modified ?? $latest->post_date ?? '' ) );
			$latest_summary = array(
				'revision_id'         => $this->absint_value( $latest->ID ?? 0 ),
				'modified'            => $modified,
				'author_id'           => $this->absint_value( $latest->post_author ?? 0 ),
				'title'               => $latest_title,
				'content_text_length' => $latest_text_length,
				'block_count'         => $latest_block_count,
				'excerpt'             => $this->build_revision_excerpt( $latest_content ),
			);
			$change_summary = array(
				'title_changed'        => $title_changed,
				'content_length_delta' => $content_delta,
				'block_count_delta'    => $block_delta,
				'latest_modified'      => $modified,
			);
		}

		foreach ( $revisions as $revision ) {
			if ( ! is_object( $revision ) ) {
				continue;
			}
			$content = (string) ( $revision->post_content ?? '' );
			$recent_rows[] = array(
				'revision_id'         => $this->absint_value( $revision->ID ?? 0 ),
				'modified'            => sanitize_text_field( (string) ( $revision->post_modified_gmt ?? $revision->post_modified ?? $revision->post_date ?? '' ) ),
				'author_id'           => $this->absint_value( $revision->post_author ?? 0 ),
				'title'               => sanitize_text_field( (string) ( $revision->post_title ?? '' ) ),
				'content_text_length' => $this->strlen_value( $this->strip_all_tags_value( $content ) ),
				'block_count'         => count( $this->parse_content_blocks( $content ) ),
				'excerpt'             => $this->build_revision_excerpt( $content ),
			);
		}

		$risk_flags = array_values( array_unique( array_map( 'sanitize_key', $risk_flags ) ) );
		$risk_level = 'none';
		if ( in_array( 'large_content_delta', $risk_flags, true ) || in_array( 'large_block_delta', $risk_flags, true ) ) {
			$risk_level = 'high';
		} elseif ( in_array( 'title_changed', $risk_flags, true ) || in_array( 'no_revisions', $risk_flags, true ) ) {
			$risk_level = 'medium';
		} elseif ( count( $recent_rows ) > 0 ) {
			$risk_level = 'low';
		}

		return $this->build_analysis_success_response(
			array(
				'post'             => array(
					'post_id'             => $post_id,
					'title'               => $current_title,
					'post_type'           => sanitize_key( (string) ( $post->post_type ?? '' ) ),
					'status'              => sanitize_key( (string) ( $post->post_status ?? '' ) ),
					'content_text_length' => $current_text_length,
					'block_count'         => $current_block_count,
					'edit_link'           => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $post_id, 'raw' ) ) : '',
				),
				'revision_count'   => count( $revisions ),
				'risk_level'       => $risk_level,
				'risk_flags'       => $risk_flags,
				'latest_revision'  => $latest_summary,
				'change_summary'   => $change_summary,
				'recent_revisions' => $recent_rows,
				'summary'          => array(
					'max_revisions' => $max_revisions,
					'next_action'   => 'high' === $risk_level ? 'review_latest_revision_before_write' : 'continue_with_standard_review',
				),
			),
			array(
				'source'         => 'local_revision_change_risk_report',
				'execution_mode' => 'deterministic',
			),
			'Revision change risk report built.'
		);
	}

	/**
	 * Resolves a URL or slug to a post.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function resolve_url_to_post( $input ) {
		$input = is_array( $input ) ? $input : array();
		$url = esc_url_raw( (string) ( $input['url'] ?? '' ) );
		$slug = sanitize_title( (string) ( $input['slug'] ?? '' ) );
		$post_type_hint = sanitize_key( (string) ( $input['post_type'] ?? 'any' ) );

		if ( '' === $url && '' === $slug ) {
			return new \WP_Error( 'magick_ai_abilities_resolve_input_required', __( 'A URL or slug is required.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}

		$post_id = 0;
		$matched_by = '';
		if ( '' !== $url && function_exists( 'url_to_postid' ) ) {
			$post_id = absint( url_to_postid( $url ) );
			if ( $post_id > 0 ) {
				$matched_by = 'url';
			}
		}

		if ( $post_id <= 0 && '' === $slug && '' !== $url ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			$path = is_string( $path ) ? trim( $path ) : '';
			$candidate_slug = sanitize_title( basename( trim( $path, '/' ) ) );
			if ( '' !== $candidate_slug ) {
				$post_id = $this->resolve_post_id_by_slug( $candidate_slug, $post_type_hint );
				if ( $post_id > 0 ) {
					$matched_by = 'url_slug';
				}
			}
		}

		if ( $post_id <= 0 && '' !== $slug ) {
			$post_id = $this->resolve_post_id_by_slug( $slug, $post_type_hint );
			if ( $post_id > 0 ) {
				$matched_by = 'slug';
			}
		}

		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'No matching post was found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'No matching post was found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		return array(
			'post_id'    => $post_id,
			'post_type'  => sanitize_key( (string) ( $post->post_type ?? '' ) ),
			'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'edit_link'  => get_edit_post_link( $post_id, 'raw' ),
			'permalink'  => esc_url_raw( (string) get_permalink( $post_id ) ),
			'matched_by' => '' !== $matched_by ? $matched_by : 'unknown',
		);
	}

	/**
	 * Gets a post block tree.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_post_blocks( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$target_post = $post;
		$revision_id = absint( $input['revision_id'] ?? 0 );
		if ( $revision_id > 0 ) {
			$revision = get_post( $revision_id );
			if ( ! $revision || 'revision' !== sanitize_key( (string) ( $revision->post_type ?? '' ) ) ) {
				return new \WP_Error( 'magick_ai_abilities_revision_not_found', __( 'Revision was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
			}
			if ( absint( $revision->post_parent ?? 0 ) !== $post_id ) {
				return new \WP_Error( 'magick_ai_abilities_revision_post_mismatch', __( 'Revision does not belong to the requested post.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
			}
			$target_post = $revision;
		}

		$include_inner_blocks = ! array_key_exists( 'include_inner_blocks', $input ) || ! empty( $input['include_inner_blocks'] );
		$content = (string) ( $target_post->post_content ?? '' );
		$parsed_blocks = function_exists( 'parse_blocks' ) ? parse_blocks( $content ) : array();
		$normalized_blocks = $this->normalize_block_tree( is_array( $parsed_blocks ) ? $parsed_blocks : array(), $include_inner_blocks );
		if ( empty( $normalized_blocks ) && '' !== trim( $content ) ) {
			$normalized_blocks[] = array(
				'blockName'   => 'core/freeform',
				'attrs'       => array(),
				'innerHTML'   => $content,
				'innerBlocks' => array(),
			);
		}

		return array(
			'post_id'        => $post_id,
			'revision_id'    => $revision_id,
			'post_type'      => sanitize_key( (string) ( $post->post_type ?? '' ) ),
			'status'         => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'block_count'    => count( $normalized_blocks ),
			'content_length' => strlen( $content ),
			'blocks'         => $normalized_blocks,
		);
	}

	/**
	 * Lists post revisions.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_post_revisions( $input ) {
		$input = is_array( $input ) ? $input : array();
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_post_invalid', __( 'Post ID is invalid.', 'magick-ai-abilities' ), array( 'status' => 400 ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'magick_ai_abilities_post_not_found', __( 'Post was not found.', 'magick-ai-abilities' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this post revision history.', 'magick-ai-abilities' ), array( 'status' => 403 ) );
		}

		$per_page = max( 1, min( 20, absint( $input['per_page'] ?? 10 ) ) );
		$page = max( 1, absint( $input['page'] ?? 1 ) );
		$all_revisions = function_exists( 'wp_get_post_revisions' )
			? wp_get_post_revisions(
				$post_id,
				array(
					'check_enabled' => false,
					'order'         => 'DESC',
					'orderby'       => 'date ID',
				)
			)
			: array();
		$all_revisions = is_array( $all_revisions ) ? array_values( $all_revisions ) : array();
		$total = count( $all_revisions );
		$slice = array_slice( $all_revisions, ( $page - 1 ) * $per_page, $per_page );
		$items = array();
		foreach ( $slice as $revision ) {
			if ( ! is_object( $revision ) ) {
				continue;
			}
			$revision_id = absint( $revision->ID ?? 0 );
			if ( $revision_id <= 0 ) {
				continue;
			}
			$author_id = absint( $revision->post_author ?? 0 );
			$items[] = array(
				'revision_id'    => $revision_id,
				'parent_id'      => absint( $revision->post_parent ?? 0 ),
				'modified_gmt'   => sanitize_text_field( (string) ( $revision->post_modified_gmt ?? '' ) ),
				'modified_local' => sanitize_text_field( (string) ( $revision->post_modified ?? '' ) ),
				'author_id'      => $author_id,
				'author'         => $author_id > 0 ? sanitize_text_field( (string) get_the_author_meta( 'display_name', $author_id ) ) : '',
				'excerpt'        => $this->build_revision_excerpt( (string) ( $revision->post_content ?? '' ) ),
				'edit_link'      => get_edit_post_link( $revision_id, 'raw' ),
			);
		}

		return array(
			'post_id'  => $post_id,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'items'    => $items,
		);
	}

	/**
	 * Lists post types.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_post_types( $input ) {
		$input = is_array( $input ) ? $input : array();
		$include_builtin = ! empty( $input['include_builtin'] );
		$include_private = ! empty( $input['include_private'] );
		$show_ui_only = ! array_key_exists( 'show_ui_only', $input ) || ! empty( $input['show_ui_only'] );

		$post_types = get_post_types( array(), 'objects' );
		$post_types = is_array( $post_types ) ? $post_types : array();
		$items = array();
		foreach ( $post_types as $post_type => $object ) {
			$post_type = sanitize_key( (string) $post_type );
			if ( '' === $post_type || ! is_object( $object ) ) {
				continue;
			}
			if ( ! $include_builtin && ! empty( $object->_builtin ) ) {
				continue;
			}
			if ( $show_ui_only && empty( $object->show_ui ) ) {
				continue;
			}
			if ( ! $include_private && empty( $object->public ) ) {
				continue;
			}
			$items[] = array(
				'post_type'    => $post_type,
				'label'        => sanitize_text_field( (string) ( $object->label ?? $post_type ) ),
				'public'       => ! empty( $object->public ),
				'hierarchical' => ! empty( $object->hierarchical ),
				'show_in_rest' => ! empty( $object->show_in_rest ),
			);
		}

		usort( $items, array( $this, 'sort_by_post_type' ) );

		return array(
			'total' => count( $items ),
			'items' => $items,
		);
	}

	/**
	 * Normalizes parsed block rows.
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
	 * @param bool                           $include_inner_blocks Include child blocks.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_block_tree( array $blocks, $include_inner_blocks ) {
		$normalized = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : array();
			$normalized[] = array(
				'blockName'   => sanitize_text_field( (string) ( $block['blockName'] ?? '' ) ),
				'attrs'       => is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array(),
				'innerHTML'   => (string) ( $block['innerHTML'] ?? '' ),
				'innerBlocks' => $include_inner_blocks ? $this->normalize_block_tree( $inner_blocks, true ) : array(),
			);
		}

		return $normalized;
	}

	/**
	 * Collects taxonomy terms for a post context payload.
	 *
	 * @param int    $post_id Post id.
	 * @param string $post_type Post type.
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	private function collect_post_terms_context( $post_id, $post_type ) {
		$terms_context = array();
		if ( ! function_exists( 'get_object_taxonomies' ) || ! function_exists( 'get_the_terms' ) ) {
			return $terms_context;
		}

		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		foreach ( ( is_array( $taxonomies ) ? $taxonomies : array() ) as $taxonomy => $taxonomy_object ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			if ( '' === $taxonomy ) {
				continue;
			}
			$terms = get_the_terms( $post_id, $taxonomy );
			if ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) {
				continue;
			}

			$rows = array();
			foreach ( ( is_array( $terms ) ? $terms : array() ) as $term ) {
				if ( ! is_object( $term ) ) {
					continue;
				}
				$rows[] = array(
					'id'          => $this->absint_value( $term->term_id ?? 0 ),
					'name'        => sanitize_text_field( (string) ( $term->name ?? '' ) ),
					'slug'        => sanitize_title( (string) ( $term->slug ?? '' ) ),
					'taxonomy'    => $taxonomy,
					'description' => $this->sanitize_metadata_text( (string) ( $term->description ?? '' ) ),
				);
			}

			if ( ! empty( $rows ) || ! empty( $taxonomy_object->show_ui ) ) {
				$terms_context[ $taxonomy ] = $rows;
			}
		}

		return $terms_context;
	}

	/**
	 * Reads revisions for a post with a unit-test fallback.
	 *
	 * @param int $post_id Post id.
	 * @return array<int,object>
	 */
	private function get_revision_objects_for_post( $post_id ) {
		$post_id = $this->absint_value( $post_id );
		$revisions = function_exists( 'wp_get_post_revisions' )
			? wp_get_post_revisions(
				$post_id,
				array(
					'check_enabled' => false,
					'order'         => 'DESC',
					'orderby'       => 'date ID',
				)
			)
			: array();
		$revisions = is_array( $revisions ) ? array_values( $revisions ) : array();

		if ( empty( $revisions ) && isset( $GLOBALS['maa_unit_style_posts'] ) && is_array( $GLOBALS['maa_unit_style_posts'] ) ) {
			foreach ( $GLOBALS['maa_unit_style_posts'] as $post ) {
				if ( is_object( $post ) && 'revision' === sanitize_key( (string) ( $post->post_type ?? '' ) ) && $post_id === $this->absint_value( $post->post_parent ?? 0 ) ) {
					$revisions[] = $post;
				}
			}
		}

		usort(
			$revisions,
			static function ( $a, $b ) {
				$a_time = strtotime( (string) ( is_object( $a ) ? ( $a->post_modified_gmt ?? $a->post_modified ?? $a->post_date ?? '' ) : '' ) );
				$b_time = strtotime( (string) ( is_object( $b ) ? ( $b->post_modified_gmt ?? $b->post_modified ?? $b->post_date ?? '' ) : '' ) );
				$a_time = false === $a_time ? 0 : $a_time;
				$b_time = false === $b_time ? 0 : $b_time;
				if ( $a_time === $b_time ) {
					return (int) ( is_object( $b ) ? ( $b->ID ?? 0 ) : 0 ) <=> (int) ( is_object( $a ) ? ( $a->ID ?? 0 ) : 0 );
				}
				return $b_time <=> $a_time;
			}
		);

		return array_values(
			array_filter(
				$revisions,
				static function ( $revision ) {
					return is_object( $revision );
				}
			)
		);
	}

	/**
	 * Parses Gutenberg blocks with a non-block fallback count.
	 *
	 * @param string $content Post content.
	 * @return array<int,mixed>
	 */
	private function parse_content_blocks( $content ) {
		if ( function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( (string) $content );
			if ( is_array( $blocks ) && ! empty( $blocks ) ) {
				return $blocks;
			}
		}

		$plain = trim( $this->strip_all_tags_value( (string) $content ) );
		return '' === $plain ? array() : array( array( 'blockName' => 'core/freeform' ) );
	}

	/**
	 * Builds a short revision excerpt.
	 *
	 * @param string $content Raw post content.
	 * @return string
	 */
	private function build_revision_excerpt( $content ) {
		$stripped = wp_strip_all_tags( (string) $content );
		if ( function_exists( 'wp_trim_words' ) ) {
			return wp_trim_words( $stripped, 24 );
		}
		$stripped = trim( $stripped );
		if ( strlen( $stripped ) <= 120 ) {
			return $stripped;
		}

		return substr( $stripped, 0, 117 ) . '...';
	}

	/**
	 * Returns post meta scoped by optional keys.
	 *
	 * @param int   $post_id Post id.
	 * @param mixed $meta_keys Optional meta keys.
	 * @return array<string,mixed>
	 */
	private function get_scoped_post_meta( $post_id, $meta_keys ) {
		$post_id = absint( $post_id );
		$keys = is_array( $meta_keys ) ? array_filter( array_map( 'sanitize_key', $meta_keys ) ) : array();
		$meta = array();

		if ( empty( $keys ) ) {
			$all_meta = get_post_meta( $post_id );
			foreach ( ( is_array( $all_meta ) ? $all_meta : array() ) as $key => $values ) {
				$key = sanitize_key( (string) $key );
				$meta[ $key ] = is_array( $values ) && 1 === count( $values ) ? $values[0] : $values;
			}

			return $meta;
		}

		foreach ( $keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== $value || ( function_exists( 'metadata_exists' ) && metadata_exists( 'post', $post_id, $key ) ) ) {
				$meta[ $key ] = $value;
			}
		}

		return $meta;
	}

	/**
	 * Resolves a post id by slug and optional post type hint.
	 *
	 * @param string $slug Post slug.
	 * @param string $post_type_hint Post type hint or any.
	 * @return int
	 */
	private function resolve_post_id_by_slug( $slug, $post_type_hint = 'any' ) {
		$slug = sanitize_title( (string) $slug );
		$post_type_hint = sanitize_key( (string) $post_type_hint );
		if ( '' === $slug ) {
			return 0;
		}

		$post_types = array();
		if ( '' !== $post_type_hint && 'any' !== $post_type_hint ) {
			if ( post_type_exists( $post_type_hint ) ) {
				$post_types[] = $post_type_hint;
			}
		} else {
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			$post_types = is_array( $post_types ) ? array_values( $post_types ) : array();
		}
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		$page = get_page_by_path( $slug, OBJECT, $post_types );
		if ( is_object( $page ) && ! empty( $page->ID ) ) {
			return absint( $page->ID );
		}

		$query = new \WP_Query(
			array(
				'name'           => $slug,
				'post_type'      => $post_types,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$post_id = ! empty( $query->posts[0] ) ? absint( $query->posts[0] ) : 0;

		return $post_id > 0 ? $post_id : 0;
	}

	/**
	 * Sort callback for post type rows.
	 *
	 * @param array<string,mixed> $left Left row.
	 * @param array<string,mixed> $right Right row.
	 * @return int
	 */
	private function sort_by_post_type( array $left, array $right ) {
		return strcmp( (string) ( $left['post_type'] ?? '' ), (string) ( $right['post_type'] ?? '' ) );
	}
}
