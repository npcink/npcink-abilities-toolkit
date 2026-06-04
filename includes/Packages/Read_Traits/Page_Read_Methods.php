<?php
/**
 * Page read methods for Core_Read_Package.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides page listing, inspection, and structure health read callbacks.
 */
trait Page_Read_Methods {
	/**
	 * Lists pages.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_pages( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read pages.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		if ( ! in_array( $status, array( 'publish', 'draft', 'pending', 'private', 'any' ), true ) ) {
			$status = 'publish';
		}
		$parent = isset( $input['parent'] ) ? (int) $input['parent'] : 0;
		$search = sanitize_text_field( (string) ( $input['search'] ?? '' ) );
		$orderby = sanitize_key( (string) ( $input['orderby'] ?? 'menu_order' ) );
		if ( ! in_array( $orderby, array( 'menu_order', 'title', 'date', 'modified', 'id' ), true ) ) {
			$orderby = 'menu_order';
		}
		$order = strtoupper( sanitize_key( (string) ( $input['order'] ?? 'ASC' ) ) );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'ASC';
		}
		$number = max( 1, min( 100, absint( $input['number'] ?? 20 ) ) );
		$offset = absint( $input['offset'] ?? 0 );

		$query_args = array(
			'post_type'              => 'page',
			'post_status'            => $status,
			'posts_per_page'         => $number,
			'offset'                 => $offset,
			'orderby'                => $orderby,
			'order'                  => $order,
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		if ( 0 !== $parent ) {
			if ( -1 === $parent ) {
				$query_args['post_parent__not_in'] = array( 0 );
			} else {
				$query_args['post_parent'] = $parent;
			}
		}
		if ( '' !== $search ) {
			$query_args['s'] = $search;
		}

		$query = new \WP_Query( $query_args );
		$pages = array();
		foreach ( ( is_array( $query->posts ?? null ) ? $query->posts : array() ) as $post ) {
			if ( ! is_object( $post ) ) {
				continue;
			}
			$page_id = absint( $post->ID ?? 0 );
			if ( $page_id <= 0 || ! current_user_can( 'edit_post', $page_id ) ) {
				continue;
			}
			$template = function_exists( 'get_page_template_slug' ) ? get_page_template_slug( $page_id ) : '';
			$template = is_string( $template ) ? $template : '';
			$url = 'publish' === (string) ( $post->post_status ?? '' ) ? get_permalink( $page_id ) : '';

			$pages[] = array(
				'id'         => $page_id,
				'title'      => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'slug'       => sanitize_title( (string) ( $post->post_name ?? '' ) ),
				'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'template'   => $template,
				'parent_id'  => absint( $post->post_parent ?? 0 ),
				'menu_order' => (int) ( $post->menu_order ?? 0 ),
				'date'       => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
				'modified'   => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
				'url'        => is_string( $url ) ? esc_url_raw( $url ) : '',
			);
		}
		if ( function_exists( 'wp_reset_postdata' ) ) {
			wp_reset_postdata();
		}

		$total = (int) $query->found_posts;

		return array(
			'pages'    => $pages,
			'total'    => $total,
			'has_more' => ( $offset + $number ) < $total,
		);
	}

	/**
	 * Gets one page.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_page( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read pages.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$page_id = absint( $input['page_id'] ?? 0 );
		if ( $page_id <= 0 ) {
			return new \WP_Error( 'magick_ai_abilities_page_not_found', __( 'Page ID is invalid.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		$post = get_post( $page_id );
		if ( ! $post || 'page' !== sanitize_key( (string) ( $post->post_type ?? '' ) ) ) {
			return new \WP_Error( 'magick_ai_abilities_page_not_found', __( 'Page was not found.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $page_id ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read this page.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$template = function_exists( 'get_page_template_slug' ) ? get_page_template_slug( $page_id ) : '';
		$template = is_string( $template ) ? $template : '';
		$url = 'publish' === (string) ( $post->post_status ?? '' ) ? get_permalink( $page_id ) : '';
		$author = null;
		$author_id = absint( $post->post_author ?? 0 );
		$author_user = $author_id > 0 ? get_userdata( $author_id ) : false;
		if ( $author_user ) {
			$author = array(
				'id'   => absint( $author_user->ID ?? 0 ),
				'name' => sanitize_text_field( (string) ( $author_user->display_name ?? '' ) ),
			);
		}

		$available_templates = $this->get_page_templates();
			$result = array(
				'id'                  => $page_id,
				'title'               => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'slug'                => sanitize_title( (string) ( $post->post_name ?? '' ) ),
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- the_content is a WordPress core hook required for rendered page content.
				'content'             => apply_filters( 'the_content', (string) ( $post->post_content ?? '' ) ),
				'content_raw'         => (string) ( $post->post_content ?? '' ),
				'excerpt'             => sanitize_textarea_field( (string) ( $post->post_excerpt ?? '' ) ),
			'status'              => sanitize_key( (string) ( $post->post_status ?? '' ) ),
			'template'            => $template,
			'parent_id'           => absint( $post->post_parent ?? 0 ),
			'menu_order'          => (int) ( $post->menu_order ?? 0 ),
			'author'              => $author,
			'date'                => sanitize_text_field( (string) ( $post->post_date ?? '' ) ),
			'modified'            => sanitize_text_field( (string) ( $post->post_modified ?? '' ) ),
			'url'                 => is_string( $url ) ? esc_url_raw( $url ) : '',
			'edit_url'            => admin_url( 'post.php?post=' . $page_id . '&action=edit' ),
			'available_templates' => $available_templates,
		);

		if ( ! empty( $input['include_meta'] ) ) {
			$result['meta'] = $this->get_scoped_post_meta( $page_id, $input['meta_keys'] ?? array() );
		}

		return $result;
	}

	/**
	 * Inspects page structure.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function inspect_page_structure( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read pages.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$max_pages = max( 1, min( 100, absint( $input['max_pages'] ?? 50 ) ) );
		$query = new \WP_Query(
			array(
				'post_type'              => 'page',
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => $max_pages,
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$pages = array();
		$top_level_count = 0;
		$parent_ids = array();
		foreach ( ( is_array( $query->posts ?? null ) ? $query->posts : array() ) as $post ) {
			if ( ! is_object( $post ) ) {
				continue;
			}
			$page_id = absint( $post->ID ?? 0 );
			if ( $page_id <= 0 || ! current_user_can( 'edit_post', $page_id ) ) {
				continue;
			}
			$parent_id = absint( $post->post_parent ?? 0 );
			$template = function_exists( 'get_page_template_slug' ) ? get_page_template_slug( $page_id ) : '';
			$template = is_string( $template ) ? $template : '';
			$pages[] = array(
				'id'         => $page_id,
				'title'      => sanitize_text_field( (string) ( $post->post_title ?? '' ) ),
				'slug'       => sanitize_title( (string) ( $post->post_name ?? '' ) ),
				'status'     => sanitize_key( (string) ( $post->post_status ?? '' ) ),
				'parent_id'  => $parent_id,
				'menu_order' => (int) ( $post->menu_order ?? 0 ),
				'template'   => $template,
			);
			if ( 0 === $parent_id ) {
				++$top_level_count;
			} else {
				$parent_ids[] = $parent_id;
			}
		}
		if ( function_exists( 'wp_reset_postdata' ) ) {
			wp_reset_postdata();
		}

		$orphan_pages = $this->find_orphan_pages( $pages, $parent_ids );
		$total_pages = (int) $query->found_posts;

		return array(
			'pages'           => $pages,
			'total_pages'     => $total_pages,
			'top_level_count' => $top_level_count,
			'has_more'        => $total_pages > $max_pages,
			'findings'        => array(
				'orphan_pages' => $orphan_pages,
				'orphan_count' => count( $orphan_pages ),
			),
			'max_pages'       => $max_pages,
		);
	}

	/**
	 * Lists a page tree.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>
	 */
	public function list_pages_tree( $input ) {
		$input = is_array( $input ) ? $input : array();
		$root_id = absint( $input['root_id'] ?? 0 );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		if ( '' === $status ) {
			$status = 'publish';
		}
		$max_depth = max( 1, min( 6, absint( $input['max_depth'] ?? 3 ) ) );

		$query = new \WP_Query(
			array(
				'post_type'      => 'page',
				'post_status'    => $status,
				'posts_per_page' => 500,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$page_ids = is_array( $query->posts ?? null ) ? $query->posts : array();
		$pages = array();
		foreach ( $page_ids as $page_id ) {
			$page_id = absint( $page_id );
			if ( $page_id <= 0 || ! current_user_can( 'edit_post', $page_id ) ) {
				continue;
			}
			$page = get_post( $page_id );
			if ( $page ) {
				$pages[ $page_id ] = $page;
			}
		}

		$items = array();
		$walk = function ( $parent_id, $depth ) use ( &$walk, &$items, $pages, $max_depth ) {
			if ( $depth > $max_depth ) {
				return;
			}
			foreach ( $pages as $page ) {
				$current_parent = absint( $page->post_parent ?? 0 );
				if ( $current_parent !== $parent_id ) {
					continue;
				}
				$page_id = absint( $page->ID ?? 0 );
				if ( $page_id <= 0 ) {
					continue;
				}
				$items[] = array(
					'id'        => $page_id,
					'parent_id' => $current_parent,
					'depth'     => $depth,
					'title'     => sanitize_text_field( (string) get_the_title( $page_id ) ),
					'slug'      => sanitize_title( (string) ( $page->post_name ?? '' ) ),
					'status'    => sanitize_key( (string) ( $page->post_status ?? '' ) ),
					'edit_link' => get_edit_post_link( $page_id, 'raw' ),
				);
				$walk( $page_id, $depth + 1 );
			}
		};
		$walk( $root_id, 0 );

		return array(
			'root_id' => $root_id,
			'total'   => count( $items ),
			'items'   => $items,
		);
	}

	/**
	 * Builds page structure health.
	 *
	 * @param mixed $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_page_structure_health( $input ) {
		$input = is_array( $input ) ? $input : array();
		if ( ! current_user_can( 'edit_pages' ) ) {
			return new \WP_Error( 'magick_ai_abilities_permission_denied', __( 'You do not have permission to read page structure health.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}

		$page_id = $this->absint_value( $input['page_id'] ?? 0 );
		$status = sanitize_key( (string) ( $input['status'] ?? 'publish' ) );
		$max_pages = max( 1, min( 100, $this->absint_value( $input['max_pages'] ?? 50 ) ) );
		$page_ids = array();
		if ( $page_id > 0 ) {
			$page_ids = array( $page_id );
		} else {
			$query_result = $this->query_inventory_posts( 'page', '' !== $status ? $status : 'publish', $max_pages, 1 );
			$page_ids = is_array( $query_result['post_ids'] ?? null ) ? $query_result['post_ids'] : array();
		}

		$items = array();
		$issue_counts = array();
		foreach ( $page_ids as $current_page_id ) {
			$current_page_id = $this->absint_value( $current_page_id );
			$page = $current_page_id > 0 ? get_post( $current_page_id ) : null;
			if ( ! is_object( $page ) || ! current_user_can( 'edit_post', $current_page_id ) ) {
				continue;
			}
			$content = (string) ( $page->post_content ?? '' );
			$plain_text = $this->strip_all_tags_value( $content );
			$heading_count = preg_match_all( '/<h[1-6][^>]*>/i', $content, $heading_matches );
			$heading_count = is_int( $heading_count ) ? $heading_count : 0;
			$cta_count = preg_match_all( '/<a\\s[^>]*href=|wp:button|wp:buttons|class=["\'][^"\']*(?:button|cta)/i', $content, $cta_matches );
			$cta_count = is_int( $cta_count ) ? $cta_count : 0;
			$block_count = count( $this->parse_content_blocks( $content ) );
			$issues = array();
			if ( $this->strlen_value( $plain_text ) < 160 ) {
				$issues[] = 'thin_page_content';
			}
			if ( $heading_count < 1 ) {
				$issues[] = 'missing_heading_structure';
			}
			if ( $cta_count < 1 ) {
				$issues[] = 'missing_cta';
			}
			if ( $block_count < 2 ) {
				$issues[] = 'low_block_structure';
			}
			foreach ( $issues as $issue ) {
				$issue_counts[ $issue ] = (int) ( $issue_counts[ $issue ] ?? 0 ) + 1;
			}
			$items[] = array(
				'page_id'           => $current_page_id,
				'title'             => sanitize_text_field( (string) get_the_title( $current_page_id ) ),
				'status'            => sanitize_key( (string) ( $page->post_status ?? '' ) ),
				'plain_text_length' => $this->strlen_value( $plain_text ),
				'heading_count'     => $heading_count,
				'cta_count'         => $cta_count,
				'block_count'       => $block_count,
				'issue_count'       => count( $issues ),
				'issues'            => $issues,
				'edit_link'         => function_exists( 'get_edit_post_link' ) ? $this->esc_url_value( (string) get_edit_post_link( $current_page_id, 'raw' ) ) : '',
			);
		}
		arsort( $issue_counts );

		return $this->build_analysis_success_response(
			array(
				'total'        => count( $items ),
				'items'        => $items,
				'issue_counts' => $issue_counts,
				'summary'      => array(
					'scanned_count'     => count( $items ),
					'pages_with_issues' => count(
						array_filter(
							$items,
							static function ( array $item ) {
								return (int) ( $item['issue_count'] ?? 0 ) > 0;
							}
						)
					),
				),
			),
			array(
				'source'         => 'local_page_structure_health',
				'execution_mode' => 'deterministic',
			),
			'Page structure health built.'
		);
	}

	/**
	 * Returns active theme page templates.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function get_page_templates() {
		$available_templates = array(
			array(
				'label' => __( 'Default Template', 'npcink-abilities-toolkit' ),
				'slug'  => 'default',
			),
		);
		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
		if ( ! $theme || ! is_object( $theme ) || ! method_exists( $theme, 'get_page_templates' ) ) {
			return $available_templates;
		}

		$templates = $theme->get_page_templates( null, 'page' );
		foreach ( ( is_array( $templates ) ? $templates : array() ) as $slug => $label ) {
			$available_templates[] = array(
				'label' => sanitize_text_field( (string) $label ),
				'slug'  => sanitize_key( (string) $slug ),
			);
		}

		return $available_templates;
	}

	/**
	 * Finds pages whose parent id no longer resolves to a post.
	 *
	 * @param array<int,array<string,mixed>> $pages Page rows.
	 * @param array<int,int>                 $parent_ids Parent ids.
	 * @return array<int,array<string,mixed>>
	 */
	private function find_orphan_pages( array $pages, array $parent_ids ) {
		if ( empty( $parent_ids ) ) {
			return array();
		}

		$parent_ids = array_values( array_unique( array_map( 'absint', $parent_ids ) ) );
		$missing_parent_ids = array();
		foreach ( $parent_ids as $parent_id ) {
			if ( $parent_id > 0 && ! get_post( $parent_id ) ) {
				$missing_parent_ids[] = $parent_id;
			}
		}
		if ( empty( $missing_parent_ids ) ) {
			return array();
		}

		$orphan_pages = array();
		foreach ( $pages as $page ) {
			$parent_id = absint( $page['parent_id'] ?? 0 );
			if ( in_array( $parent_id, $missing_parent_ids, true ) ) {
				$orphan_pages[] = array(
					'id'                => absint( $page['id'] ?? 0 ),
					'title'             => sanitize_text_field( (string) ( $page['title'] ?? '' ) ),
					'missing_parent_id' => $parent_id,
				);
			}
		}

		return $orphan_pages;
	}
}
