<?php
/**
 * Lightweight test bootstrap.
 *
 * @package MagickAIAbilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'MAGICK_AI_ABILITIES_VERSION' ) ) {
	define( 'MAGICK_AI_ABILITIES_VERSION', '0.1.0-test' );
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( (string) $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( preg_replace( '/[\r\n\t]+/', ' ', (string) $value ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $value ) {
		return trim( (string) $value );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $value ) {
		$value = strtolower( trim( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
		return trim( (string) $value, '-' );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $value ) {
		return filter_var( (string) $value, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $value ) {
		return sanitize_text_field( $value );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( (string) $url, $component );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text, $remove_breaks = false ) {
		$text = strip_tags( (string) $text );
		if ( $remove_breaks ) {
			$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
		}
		return trim( (string) $text );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		unset( $args );
		$capability = sanitize_key( (string) $capability );
		$cap_map    = isset( $GLOBALS['maa_unit_current_user_caps'] ) && is_array( $GLOBALS['maa_unit_current_user_caps'] )
			? $GLOBALS['maa_unit_current_user_caps']
			: array();
		if ( array_key_exists( $capability, $cap_map ) ) {
			return ! empty( $cap_map[ $capability ] );
		}

		return 'do_not_allow' !== $capability;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value, ...$args ) {
		$filters = isset( $GLOBALS['maa_unit_filters'][ $hook_name ] ) && is_array( $GLOBALS['maa_unit_filters'][ $hook_name ] )
			? $GLOBALS['maa_unit_filters'][ $hook_name ]
			: array();
		foreach ( $filters as $callback ) {
			if ( is_callable( $callback ) ) {
				$value = $callback( $value, ...$args );
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		unset( $priority, $accepted_args );
		if ( ! isset( $GLOBALS['maa_unit_filters'] ) || ! is_array( $GLOBALS['maa_unit_filters'] ) ) {
			$GLOBALS['maa_unit_filters'] = array();
		}
		if ( ! isset( $GLOBALS['maa_unit_filters'][ $hook_name ] ) || ! is_array( $GLOBALS['maa_unit_filters'][ $hook_name ] ) ) {
			$GLOBALS['maa_unit_filters'][ $hook_name ] = array();
		}
		$GLOBALS['maa_unit_filters'][ $hook_name ][] = $callback;

		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		unset( $priority, $accepted_args );
		if ( ! isset( $GLOBALS['maa_unit_actions'] ) || ! is_array( $GLOBALS['maa_unit_actions'] ) ) {
			$GLOBALS['maa_unit_actions'] = array();
		}
		if ( ! isset( $GLOBALS['maa_unit_actions'][ $hook_name ] ) || ! is_array( $GLOBALS['maa_unit_actions'][ $hook_name ] ) ) {
			$GLOBALS['maa_unit_actions'][ $hook_name ] = array();
		}
		$GLOBALS['maa_unit_actions'][ $hook_name ][] = $callback;

		return true;
	}
}

if ( ! function_exists( 'remove_all_filters' ) ) {
	function remove_all_filters( $hook_name ) {
		if ( isset( $GLOBALS['maa_unit_filters'][ $hook_name ] ) ) {
			unset( $GLOBALS['maa_unit_filters'][ $hook_name ] );
		}

		return true;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post_id ) {
		if ( is_object( $post_id ) ) {
			return $post_id;
		}
		$posts = isset( $GLOBALS['maa_unit_style_posts'] ) && is_array( $GLOBALS['maa_unit_style_posts'] )
			? $GLOBALS['maa_unit_style_posts']
			: array();
		$post_id = (int) $post_id;
		return $posts[ $post_id ] ?? null;
	}
}

if ( ! function_exists( 'post_type_exists' ) ) {
	function post_type_exists( $post_type ) {
		return in_array( (string) $post_type, array( 'post', 'page' ), true );
	}
}

if ( ! function_exists( 'get_post_type_object' ) ) {
	function get_post_type_object( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return null;
		}
		return (object) array(
			'name' => (string) $post_type,
			'cap'  => (object) array(
				'create_posts' => 'edit_posts',
			),
		);
	}
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $postarr, $wp_error = false ) {
		unset( $wp_error );
		if ( ! isset( $GLOBALS['maa_unit_style_posts'] ) || ! is_array( $GLOBALS['maa_unit_style_posts'] ) ) {
			$GLOBALS['maa_unit_style_posts'] = array();
		}
		$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : count( $GLOBALS['maa_unit_style_posts'] ) + 1000;
		$GLOBALS['maa_unit_style_posts'][ $post_id ] = (object) array(
			'ID'           => $post_id,
			'post_type'    => (string) ( $postarr['post_type'] ?? 'post' ),
			'post_status'  => (string) ( $postarr['post_status'] ?? 'draft' ),
			'post_title'   => (string) ( $postarr['post_title'] ?? '' ),
			'post_content' => (string) ( $postarr['post_content'] ?? '' ),
			'post_excerpt' => (string) ( $postarr['post_excerpt'] ?? '' ),
			'post_author'  => (int) ( $postarr['post_author'] ?? 7 ),
			'post_name'    => (string) ( $postarr['post_name'] ?? '' ),
			'post_parent'  => (int) ( $postarr['post_parent'] ?? 0 ),
		);
		return $post_id;
	}
}

if ( ! function_exists( 'wp_update_post' ) ) {
	function wp_update_post( $postarr, $wp_error = false ) {
		unset( $wp_error );
		$post_id = (int) ( $postarr['ID'] ?? 0 );
		if ( $post_id <= 0 || ! isset( $GLOBALS['maa_unit_style_posts'][ $post_id ] ) ) {
			return new WP_Error( 'not_found', 'Post not found.' );
		}
		foreach ( array( 'post_title', 'post_content', 'post_excerpt', 'post_status', 'post_name', 'post_author' ) as $field ) {
			if ( array_key_exists( $field, $postarr ) ) {
				$GLOBALS['maa_unit_style_posts'][ $post_id ]->{$field} = $postarr[ $field ];
			}
		}
		return $post_id;
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $meta_key, $meta_value ) {
		if ( ! isset( $GLOBALS['maa_unit_post_meta'] ) || ! is_array( $GLOBALS['maa_unit_post_meta'] ) ) {
			$GLOBALS['maa_unit_post_meta'] = array();
		}
		$GLOBALS['maa_unit_post_meta'][ (int) $post_id ][ (string) $meta_key ] = $meta_value;
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $meta_key = '', $single = false ) {
		$post_meta = $GLOBALS['maa_unit_post_meta'][ (int) $post_id ] ?? array();
		if ( '' === (string) $meta_key ) {
			$all_meta = array();
			foreach ( ( is_array( $post_meta ) ? $post_meta : array() ) as $key => $value ) {
				$all_meta[ (string) $key ] = is_array( $value ) ? $value : array( $value );
			}
			return $all_meta;
		}
		$value = is_array( $post_meta ) && array_key_exists( (string) $meta_key, $post_meta )
			? $post_meta[ (string) $meta_key ]
			: '';
		if ( $single && is_array( $value ) ) {
			return $value[0] ?? '';
		}
		return $value;
	}
}

if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( $post_id ) {
		$post = get_post( (int) $post_id );
		return is_object( $post ) ? (string) ( $post->post_status ?? '' ) : '';
	}
}

if ( ! function_exists( 'get_edit_post_link' ) ) {
	function get_edit_post_link( $post_id, $context = 'display' ) {
		unset( $context );
		return 'https://example.test/wp-admin/post.php?post=' . (int) $post_id . '&action=edit';
	}
}

if ( ! function_exists( 'get_preview_post_link' ) ) {
	function get_preview_post_link( $post_id ) {
		return 'https://example.test/?p=' . (int) $post_id . '&preview=true';
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $content ) {
		return (string) $content;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $content ) {
		return strip_tags( (string) $content );
	}
}

if ( ! function_exists( 'wpautop' ) ) {
	function wpautop( $content ) {
		$paragraphs = preg_split( "/\n\s*\n/", str_replace( array( "\r\n", "\r" ), "\n", trim( (string) $content ) ) );
		$paragraphs = is_array( $paragraphs ) ? $paragraphs : array();
		return implode(
			"\n\n",
			array_map(
				static function ( $paragraph ) {
					return '<p>' . nl2br( trim( (string) $paragraph ) ) . '</p>';
				},
				array_filter(
					$paragraphs,
					static function ( $paragraph ) {
						return '' !== trim( (string) $paragraph );
					}
				)
			)
		);
	}
}

if ( ! function_exists( 'parse_blocks' ) ) {
	function parse_blocks( $content ) {
		preg_match_all( '/<!-- wp:([a-z0-9_\/-]+)(?:\\s+\\{.*?\\})? -->/i', (string) $content, $matches );
		$blocks = array();
		foreach ( $matches[1] ?? array() as $block_name ) {
			$blocks[] = array(
				'blockName'   => 0 === strpos( $block_name, 'core/' ) ? $block_name : 'core/' . $block_name,
				'attrs'       => array(),
				'innerHTML'   => '',
				'innerBlocks' => array(),
			);
		}
		return $blocks;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $flags = 0, $depth = 512 ) {
		return json_encode( $data, $flags, $depth );
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = array() ) {
		$args = is_array( $args ) ? $args : array();
		$posts = isset( $GLOBALS['maa_unit_style_posts'] ) && is_array( $GLOBALS['maa_unit_style_posts'] )
			? array_values( $GLOBALS['maa_unit_style_posts'] )
			: array();
		if ( isset( $args['author'] ) ) {
			$author_id = (int) $args['author'];
			$posts = array_values(
				array_filter(
					$posts,
					static function ( $post ) use ( $author_id ) {
						return is_object( $post ) && (int) ( $post->post_author ?? 0 ) === $author_id;
					}
				)
			);
		}
		$limit = isset( $args['posts_per_page'] ) ? max( 1, (int) $args['posts_per_page'] ) : count( $posts );
		return array_slice( $posts, 0, $limit );
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		if ( is_object( $post ) ) {
			return (string) ( $post->post_title ?? '' );
		}
		$post = get_post( (int) $post );
		return is_object( $post ) ? (string) ( $post->post_title ?? '' ) : '';
	}
}

if ( ! function_exists( 'get_comment' ) ) {
	function get_comment( $comment_id ) {
		$comments = isset( $GLOBALS['maa_unit_comments'] ) && is_array( $GLOBALS['maa_unit_comments'] )
			? $GLOBALS['maa_unit_comments']
			: array();
		$comment_id = (int) $comment_id;
		return $comments[ $comment_id ] ?? null;
	}
}

if ( ! function_exists( 'get_comments' ) ) {
	function get_comments( $args = array() ) {
		$args = is_array( $args ) ? $args : array();
		$comments = isset( $GLOBALS['maa_unit_comments'] ) && is_array( $GLOBALS['maa_unit_comments'] )
			? array_values( $GLOBALS['maa_unit_comments'] )
			: array();
		if ( isset( $args['post_id'] ) ) {
			$post_id = (int) $args['post_id'];
			$comments = array_values(
				array_filter(
					$comments,
					static function ( $comment ) use ( $post_id ) {
						return is_object( $comment ) && (int) ( $comment->comment_post_ID ?? 0 ) === $post_id;
					}
				)
			);
		}
		if ( isset( $args['status'] ) && 'all' !== $args['status'] ) {
			$status = (string) $args['status'];
			$comments = array_values(
				array_filter(
					$comments,
					static function ( $comment ) use ( $status ) {
						return is_object( $comment ) && (string) ( $comment->comment_approved ?? '' ) === $status;
					}
				)
			);
		}
		$offset = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
		$number = isset( $args['number'] ) ? max( 1, (int) $args['number'] ) : count( $comments );
		return array_slice( $comments, $offset, $number );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 7;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		$options = isset( $GLOBALS['maa_unit_options'] ) && is_array( $GLOBALS['maa_unit_options'] )
			? $GLOBALS['maa_unit_options']
			: array();
		return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $name ) {
		$transients = isset( $GLOBALS['maa_unit_transients'] ) && is_array( $GLOBALS['maa_unit_transients'] )
			? $GLOBALS['maa_unit_transients']
			: array();
		return array_key_exists( $name, $transients ) ? $transients[ $name ] : false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $name, $value, $expiration = 0 ) {
		unset( $expiration );
		if ( ! isset( $GLOBALS['maa_unit_transients'] ) || ! is_array( $GLOBALS['maa_unit_transients'] ) ) {
			$GLOBALS['maa_unit_transients'] = array();
		}
		$GLOBALS['maa_unit_transients'][ $name ] = $value;
		return true;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public $data;

		public function __construct( $code = '', $message = '', $data = array() ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $value ) {
		return $value instanceof WP_Error;
	}
}

require_once dirname( __DIR__ ) . '/includes/Registry/Schema_Normalizer.php';
require_once dirname( __DIR__ ) . '/includes/Registry/Annotation_Normalizer.php';
require_once dirname( __DIR__ ) . '/includes/Security/Permission_Callbacks.php';
require_once dirname( __DIR__ ) . '/includes/Registry/Contract_Normalizer.php';
require_once dirname( __DIR__ ) . '/includes/Registry/Category_Registrar.php';
require_once dirname( __DIR__ ) . '/includes/Registry/Ability_Registrar.php';
require_once dirname( __DIR__ ) . '/includes/Integration/Magick_Catalog_Bridge.php';
require_once dirname( __DIR__ ) . '/includes/Admin/Test_Page.php';
require_once dirname( __DIR__ ) . '/includes/Workflow/Workflow_Definition_Provider.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Read_Pack_Classifier.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Article_Optimization_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Content_Inventory_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Content_Refresh_SEO_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Diagnostics_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Media_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Page_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Publishing_Workflow_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Style_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Traits/Taxonomy_Read_Methods.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Definitions/Agent_Usage_Metadata.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Definitions/Core_WordPress_Read_Definitions.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Read_Definitions/WordPress_Diagnostics_Definitions.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Read_Package.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Write_Package.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Destructive_Package.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Comment_Pack_Classifier.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Comment_Definitions/Core_Comment_Definitions.php';
require_once dirname( __DIR__ ) . '/includes/Packages/Core_Comment_Package.php';
require_once dirname( __DIR__ ) . '/includes/Plugin.php';
require_once dirname( __DIR__ ) . '/includes/functions.php';
