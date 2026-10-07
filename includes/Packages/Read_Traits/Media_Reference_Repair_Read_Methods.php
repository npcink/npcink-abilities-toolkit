<?php
/**
 * Media content reference repair helper methods for Core_Read_Package.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Packages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides URL and settings reference-repair helpers for media read plans.
 */
trait Media_Reference_Repair_Read_Methods {
	/**
	 * Builds before/after media URL context for content reference repair.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function media_reference_repair_replacement_context( $attachment_id, array $input ) {
		$attachment_id = $this->absint_value( $attachment_id );
		$replacement_id = sanitize_text_field( (string) ( $input['replacement_id'] ?? '' ) );
		$history = array();
		if ( function_exists( 'get_post_meta' ) ) {
			$latest = get_post_meta( $attachment_id, '_npcink_ai_media_latest_file_replacement', true );
			if ( is_array( $latest ) ) {
				$history[] = $latest;
			}
			$all = get_post_meta( $attachment_id, '_npcink_ai_media_file_replacement_history', true );
			if ( is_array( $all ) ) {
				if ( isset( $all['replacement_id'] ) ) {
					$history[] = $all;
				} else {
					$history = array_merge( $history, array_values( array_filter( $all, 'is_array' ) ) );
				}
			}
		}

		$record = array();
		foreach ( $history as $candidate ) {
			if ( ! is_array( $candidate ) ) {
				continue;
			}
			if ( '' !== $replacement_id && $replacement_id !== (string) ( $candidate['replacement_id'] ?? '' ) ) {
				continue;
			}
			$record = $candidate;
			break;
		}

		$before = is_array( $record['before'] ?? null ) ? $record['before'] : array();
		$after = is_array( $record['after'] ?? null ) ? $record['after'] : array();
		$old_relative = $this->normalize_media_reference_relative( (string) ( $input['old_relative_file'] ?? $before['relative_file'] ?? '' ) );
		$new_relative = $this->normalize_media_reference_relative( (string) ( $input['new_relative_file'] ?? $after['relative_file'] ?? '' ) );
		$old_url = $this->esc_url_value( (string) ( $input['old_url'] ?? $before['url'] ?? '' ) );
		$new_url = $this->esc_url_value( (string) ( $input['new_url'] ?? $after['url'] ?? '' ) );
		if ( '' === $old_url && '' !== $old_relative ) {
			$old_url = $this->media_reference_upload_url( $old_relative );
		}
		if ( '' === $new_url && '' !== $new_relative ) {
			$new_url = $this->media_reference_upload_url( $new_relative );
		}
		if ( '' === $old_relative && '' !== $old_url ) {
			$old_relative = $this->media_reference_relative_from_url( $old_url );
		}
		if ( '' === $new_relative && '' !== $new_url ) {
			$new_relative = $this->media_reference_relative_from_url( $new_url );
		}
		if ( '' === $old_url || '' === $new_url ) {
			return new \WP_Error( 'npcink_abilities_toolkit_media_replacement_reference_missing', __( 'No completed media replacement history or explicit old/new URLs were found for this attachment.', 'npcink-abilities-toolkit' ), array( 'status' => 404 ) );
		}

		return array(
			'attachment_id'  => $attachment_id,
			'replacement_id' => '' !== $replacement_id ? $replacement_id : sanitize_text_field( (string) ( $record['replacement_id'] ?? '' ) ),
			'old'            => array(
				'url'           => $old_url,
				'relative_file' => $old_relative,
				'path'          => $this->media_reference_url_path( $old_url ),
			),
			'new'            => array(
				'url'           => $new_url,
				'relative_file' => $new_relative,
				'path'          => $this->media_reference_url_path( $new_url ),
			),
		);
	}

	/**
	 * Returns candidate posts for reference repair scanning.
	 *
	 * @param int $limit Candidate limit.
	 * @return array<int,object>
	 */
	private function media_reference_repair_candidate_posts( $limit ) {
		$limit = max( 1, min( 150, (int) $limit ) );
		if ( isset( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] ) ) {
			return array_values( $GLOBALS['npcink_abilities_toolkit_unit_style_posts'] );
		}
		if ( ! function_exists( 'get_posts' ) ) {
			return array();
		}
		return get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page' => $limit,
			)
		);
	}

	/**
	 * Builds candidate settings for media reference repair scanning.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @param int                 $limit Candidate limit.
	 * @return array<int,array<string,mixed>>
	 */
	private function media_settings_reference_repair_candidates( array $input, $limit ) {
		$limit = max( 1, min( 100, (int) $limit ) );
		$candidates = array();
		$option_names = $this->media_settings_reference_names( $input['option_names'] ?? array(), 50 );
		if ( empty( $option_names ) && isset( $GLOBALS['npcink_abilities_toolkit_unit_options'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_options'] ) ) {
			$option_names = array_slice( array_map( 'strval', array_keys( $GLOBALS['npcink_abilities_toolkit_unit_options'] ) ), 0, $limit );
		}
		if ( empty( $option_names ) ) {
			$option_names = $this->media_settings_reference_option_names_from_db( $input, $limit );
		}
		foreach ( $option_names as $option_name ) {
			if ( count( $candidates ) >= $limit ) {
				break;
			}
			$candidates[] = array(
				'target_type' => 'option',
				'target_name' => $option_name,
				'value'       => function_exists( 'get_option' ) ? get_option( $option_name, null ) : null,
			);
		}

		if ( ! array_key_exists( 'include_theme_mods', $input ) || ! empty( $input['include_theme_mods'] ) ) {
			$theme_mod_names = $this->media_settings_reference_names( $input['theme_mod_names'] ?? array(), 50 );
			$theme_mods = array();
			if ( function_exists( 'get_theme_mods' ) ) {
				$theme_mods = get_theme_mods();
				$theme_mods = is_array( $theme_mods ) ? $theme_mods : array();
			}
			if ( isset( $GLOBALS['npcink_abilities_toolkit_unit_theme_mods'] ) && is_array( $GLOBALS['npcink_abilities_toolkit_unit_theme_mods'] ) ) {
				$theme_mods = array_merge( $theme_mods, $GLOBALS['npcink_abilities_toolkit_unit_theme_mods'] );
			}
			if ( empty( $theme_mod_names ) ) {
				$theme_mod_names = array_slice( array_map( 'strval', array_keys( $theme_mods ) ), 0, $limit );
			}
			foreach ( $theme_mod_names as $theme_mod_name ) {
				if ( count( $candidates ) >= $limit ) {
					break;
				}
				$candidates[] = array(
					'target_type' => 'theme_mod',
					'target_name' => $theme_mod_name,
					'value'       => array_key_exists( $theme_mod_name, $theme_mods ) ? $theme_mods[ $theme_mod_name ] : ( function_exists( 'get_theme_mod' ) ? get_theme_mod( $theme_mod_name, null ) : null ),
				);
			}
		}

		return $candidates;
	}

	/**
	 * Sanitizes bounded setting names.
	 *
	 * @param mixed $names Raw names.
	 * @param int   $limit Max names.
	 * @return string[]
	 */
	private function media_settings_reference_names( $names, $limit ) {
		$names = is_array( $names ) ? $names : array();
		$clean = array();
		foreach ( $names as $name ) {
			$name = sanitize_key( (string) $name );
			if ( '' !== $name && ! in_array( $name, $clean, true ) ) {
				$clean[] = $name;
			}
			if ( count( $clean ) >= $limit ) {
				break;
			}
		}
		return $clean;
	}

	/**
	 * Queries bounded option names likely to contain media references.
	 *
	 * @param array<string,mixed> $input Input args.
	 * @param int                 $limit Max names.
	 * @return string[]
	 */
	private function media_settings_reference_option_names_from_db( array $input, $limit ) {
		global $wpdb;
		if ( ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'get_col' ) || ! method_exists( $wpdb, 'prepare' ) || ! method_exists( $wpdb, 'esc_like' ) ) {
			return array();
		}
		$needle = '';
		foreach ( array( 'old_url', 'old_relative_file' ) as $key ) {
			$value = trim( (string) ( $input[ $key ] ?? '' ) );
			if ( '' !== $value ) {
				$needle = basename( $value );
				break;
			}
		}
		if ( '' === $needle ) {
			return array();
		}
		$limit     = max( 1, min( 100, (int) $limit ) );
		$cache_key = 'media_settings_reference_options_' . md5( $needle . '|' . $limit );
		$cached    = function_exists( 'wp_cache_get' ) ? wp_cache_get( $cache_key, 'npcink_abilities_toolkit' ) : false;
		if ( is_array( $cached ) ) {
			return array_values( array_filter( array_map( 'sanitize_key', $cached ) ) );
		}

		$like = '%' . $wpdb->esc_like( $needle ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Bounded options search is the feature; options table name is provided by WordPress.
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_value LIKE %s LIMIT %d", $like, $limit ) );
		$rows = array_values( array_filter( array_map( 'sanitize_key', is_array( $rows ) ? $rows : array() ) ) );
		if ( function_exists( 'wp_cache_set' ) ) {
			wp_cache_set( $cache_key, $rows, 'npcink_abilities_toolkit', MINUTE_IN_SECONDS );
		}

		return $rows;
	}

	/**
	 * Builds the conservative settings reference repair policy.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $replacement Replacement context.
	 * @param array<string,mixed> $input Input args.
	 * @return array<string,mixed>
	 */
	private function media_settings_reference_repair_policy( $attachment_id, array $replacement, array $input ) {
		$old = is_array( $replacement['old'] ?? null ) ? $replacement['old'] : array();
		$relative = (string) ( $old['relative_file'] ?? '' );
		$extension = strtolower( pathinfo( $relative, PATHINFO_EXTENSION ) );
		$excluded_formats = $this->media_settings_reference_keys( $input['excluded_formats'] ?? array( 'svg', 'gif', 'ico', 'pdf' ), 20 );
		$patterns = $this->media_settings_reference_keys( $input['excluded_filename_patterns'] ?? array( 'logo', 'favicon', 'icon', 'brand', 'payment', 'placeholder' ), 20 );
		$metadata = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( $attachment_id ) : array();
		$metadata = is_array( $metadata ) ? $metadata : array();
		$width = $this->absint_value( $metadata['width'] ?? 0 );
		$height = $this->absint_value( $metadata['height'] ?? 0 );
		$filesize = $this->absint_value( $metadata['filesize'] ?? $metadata['filesize_bytes'] ?? 0 );
		$min_width = max( 0, min( 7680, $this->absint_value( $input['min_width'] ?? 64 ) ) );
		$min_height = max( 0, min( 7680, $this->absint_value( $input['min_height'] ?? 64 ) ) );
		$min_filesize = max( 0, $this->absint_value( $input['min_filesize_bytes'] ?? 0 ) );
		$basename = strtolower( basename( $relative ) );
		$excluded = false;
		$reason = '';
		if ( '' !== $extension && in_array( $extension, $excluded_formats, true ) ) {
			$excluded = true;
			$reason = 'source_format_excluded';
		}
		if ( ! $excluded && $width > 0 && $height > 0 && ( $width < $min_width || $height < $min_height ) ) {
			$excluded = true;
			$reason = 'source_dimensions_below_policy';
		}
		if ( ! $excluded && $min_filesize > 0 && $filesize > 0 && $filesize < $min_filesize ) {
			$excluded = true;
			$reason = 'source_filesize_below_policy';
		}
		if ( ! $excluded ) {
			foreach ( $patterns as $pattern ) {
				if ( '' !== $pattern && false !== strpos( $basename, $pattern ) ) {
					$excluded = true;
					$reason = 'source_filename_pattern_excluded';
					break;
				}
			}
		}

		return array(
			'excluded'                   => $excluded,
			'reason'                     => $reason,
			'excluded_formats'           => $excluded_formats,
			'excluded_filename_patterns' => $patterns,
			'min_width'                  => $min_width,
			'min_height'                 => $min_height,
			'min_filesize_bytes'         => $min_filesize,
			'source_format'              => $extension,
			'source_width'               => $width,
			'source_height'              => $height,
			'source_filesize_bytes'      => $filesize,
		);
	}

	/**
	 * Sanitizes simple policy keys.
	 *
	 * @param mixed $values Raw values.
	 * @param int   $limit Max values.
	 * @return string[]
	 */
	private function media_settings_reference_keys( $values, $limit ) {
		$values = is_array( $values ) ? $values : array();
		$clean = array();
		foreach ( $values as $value ) {
			$value = sanitize_key( (string) $value );
			if ( '' !== $value && ! in_array( $value, $clean, true ) ) {
				$clean[] = $value;
			}
			if ( count( $clean ) >= $limit ) {
				break;
			}
		}
		return $clean;
	}

	/**
	 * Converts a setting value to searchable text.
	 *
	 * @param mixed $value Setting value.
	 * @return string
	 */
	private function media_settings_reference_value_text( $value ) {
		if ( is_string( $value ) || is_numeric( $value ) || is_bool( $value ) ) {
			return (string) $value;
		}
		if ( is_array( $value ) || is_object( $value ) ) {
			$encoded = wp_json_encode( $value, defined( 'JSON_UNESCAPED_SLASHES' ) ? JSON_UNESCAPED_SLASHES : 0 );
			return is_string( $encoded ) ? $encoded : '';
		}
		return '';
	}

	/**
	 * Returns a compact setting value type label.
	 *
	 * @param mixed $value Setting value.
	 * @return string
	 */
	private function media_settings_reference_value_type( $value ) {
		if ( is_array( $value ) ) {
			return 'array';
		}
		if ( is_object( $value ) ) {
			return 'object';
		}
		return gettype( $value );
	}

	/**
	 * Detects raw serialized strings that should not be patched by text replacement.
	 *
	 * @param string $value Setting value.
	 * @return bool
	 */
	private function media_settings_reference_looks_serialized( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return false;
		}
		if ( function_exists( 'is_serialized' ) && is_serialized( $value ) ) {
			return true;
		}
		return (bool) preg_match( '/^(a|O|s|i|b|d):[0-9]+[:;]/', $value );
	}

	/**
	 * Returns paired old/new reference strings to scan.
	 *
	 * @param array<string,mixed> $replacement Replacement context.
	 * @return array<int,array{old:string,new:string}>
	 */
	private function media_reference_repair_ref_pairs( array $replacement ) {
		$old = is_array( $replacement['old'] ?? null ) ? $replacement['old'] : array();
		$new = is_array( $replacement['new'] ?? null ) ? $replacement['new'] : array();
		$pairs = array(
			array(
				'old' => (string) ( $old['url'] ?? '' ),
				'new' => (string) ( $new['url'] ?? '' ),
			),
			array(
				'old' => (string) ( $old['path'] ?? '' ),
				'new' => (string) ( $new['path'] ?? '' ),
			),
		);
		$clean = array();
		foreach ( $pairs as $pair ) {
			$old_ref = trim( (string) ( $pair['old'] ) );
			$new_ref = trim( (string) ( $pair['new'] ) );
			if ( '' === $old_ref || '' === $new_ref ) {
				continue;
			}
			$key = $old_ref . "\n" . $new_ref;
			if ( isset( $clean[ $key ] ) ) {
				continue;
			}
			$clean[ $key ] = array(
				'old' => $old_ref,
				'new' => $new_ref,
			);
		}
		return array_values( $clean );
	}

	/**
	 * Detects old sized image variant references for manual review.
	 *
	 * @param string              $content Post content.
	 * @param array<string,mixed> $replacement Replacement context.
	 * @return array<int,string>
	 */
	private function media_reference_repair_sized_variant_matches( $content, array $replacement ) {
		$old = is_array( $replacement['old'] ?? null ) ? $replacement['old'] : array();
		$path = (string) ( $old['path'] ?? '' );
		$basename = basename( $path );
		if ( '' === $basename || false === strpos( $basename, '.' ) ) {
			return array();
		}
		$stem = preg_replace( '/\.[^.]+$/', '', $basename );
		$extension = pathinfo( $basename, PATHINFO_EXTENSION );
		if ( '' === (string) $stem || '' === $extension ) {
			return array();
		}
		$pattern = '/' . preg_quote( (string) $stem, '/' ) . '-[0-9]{2,5}x[0-9]{2,5}\.' . preg_quote( $extension, '/' ) . '/';
		preg_match_all( $pattern, (string) $content, $matches );
		return array_slice( array_values( array_unique( array_map( 'sanitize_text_field', (array) ( $matches[0] ?? array() ) ) ) ), 0, 10 );
	}

	/**
	 * Returns unique non-empty string values.
	 *
	 * @param array<int,string> $values Values.
	 * @return array<int,string>
	 */
	private function media_reference_unique_strings( array $values ) {
		$clean = array();
		foreach ( $values as $value ) {
			$value = trim( (string) $value );
			if ( '' !== $value && ! in_array( $value, $clean, true ) ) {
				$clean[] = $value;
			}
		}
		return $clean;
	}

	/**
	 * Normalizes uploads-relative media paths.
	 *
	 * @param string $relative_file Relative file.
	 * @return string
	 */
	private function normalize_media_reference_relative( $relative_file ) {
		$relative_file = ltrim( str_replace( '\\', '/', sanitize_text_field( (string) $relative_file ) ), '/' );
		if ( '' === $relative_file || false !== strpos( $relative_file, '../' ) || '..' === $relative_file ) {
			return '';
		}
		return $relative_file;
	}

	/**
	 * Builds an uploads URL for a relative file.
	 *
	 * @param string $relative_file Relative file.
	 * @return string
	 */
	private function media_reference_upload_url( $relative_file ) {
		$relative_file = $this->normalize_media_reference_relative( $relative_file );
		if ( '' === $relative_file || ! function_exists( 'wp_upload_dir' ) ) {
			return '';
		}
		$upload_dir = wp_upload_dir();
		$baseurl = is_array( $upload_dir ) ? $this->esc_url_value( (string) ( $upload_dir['baseurl'] ) ) : '';
		return '' !== $baseurl ? rtrim( $baseurl, '/' ) . '/' . $relative_file : '';
	}

	/**
	 * Returns a URL path.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function media_reference_url_path( $url ) {
		$path = '' !== (string) $url && function_exists( 'wp_parse_url' ) ? (string) wp_parse_url( (string) $url, PHP_URL_PATH ) : '';
		return '' !== $path ? sanitize_text_field( $path ) : '';
	}

	/**
	 * Infers an uploads-relative file from an uploads URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function media_reference_relative_from_url( $url ) {
		$path = $this->media_reference_url_path( $url );
		if ( '' === $path || ! function_exists( 'wp_upload_dir' ) ) {
			return '';
		}
		$upload_dir = wp_upload_dir();
		$baseurl = is_array( $upload_dir ) ? $this->esc_url_value( (string) ( $upload_dir['baseurl'] ) ) : '';
		$base_path = $this->media_reference_url_path( $baseurl );
		if ( '' !== $base_path && 0 === strpos( $path, rtrim( $base_path, '/' ) . '/' ) ) {
			return $this->normalize_media_reference_relative( substr( $path, strlen( rtrim( $base_path, '/' ) ) + 1 ) );
		}
		return '';
	}
}
