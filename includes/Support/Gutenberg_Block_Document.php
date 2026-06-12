<?php
/**
 * Shared Gutenberg block document helpers.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes, serializes, validates, and previews Gutenberg block documents.
 */
final class Gutenberg_Block_Document {
	/**
	 * Maximum normalized blocks allowed in one submitted block tree.
	 */
	public const MAX_BLOCKS = 200;

	/**
	 * Maximum nested block depth allowed in one submitted block tree.
	 */
	public const MAX_DEPTH = 8;

	/**
	 * Maximum cumulative raw block text payload size before serialization.
	 */
	public const MAX_SERIALIZED_BYTES = 262144;

	/**
	 * Normalizes one block list payload recursively.
	 *
	 * @param mixed        $blocks Raw block list.
	 * @param array<mixed> $errors Validation error rows.
	 * @param string       $path Path hint.
	 * @param int          $depth Current recursion depth.
	 * @param array<mixed> $stats Traversal stats.
	 * @return array<int,array<string,mixed>>
	 */
	public function normalize_blocks( $blocks, &$errors, $path = 'root', $depth = 0, &$stats = null ) {
		$blocks     = is_array( $blocks ) ? array_values( $blocks ) : array();
		$errors     = is_array( $errors ) ? $errors : array();
		$depth      = absint( $depth );
		if ( ! is_array( $stats ) ) {
			$stats = array(
				'count'                => 0,
				'bytes'                => 0,
				'block_count_exceeded' => false,
				'block_bytes_exceeded' => false,
				'block_depth_exceeded' => false,
			);
		}
		$normalized = array();
		foreach ( $blocks as $index => $block ) {
			$current_path = $path . '.' . $index;
			if ( ! is_array( $block ) ) {
				$errors[] = array( 'path' => $current_path, 'error' => 'block_must_be_object' );
				continue;
			}
			if ( $depth >= self::MAX_DEPTH ) {
				if ( empty( $stats['block_depth_exceeded'] ) ) {
					$errors[]                      = array( 'path' => $current_path, 'error' => 'block_depth_exceeded', 'max_depth' => self::MAX_DEPTH );
					$stats['block_depth_exceeded'] = true;
				}
				continue;
			}
			$stats['count'] = absint( $stats['count'] ?? 0 ) + 1;
			if ( $stats['count'] > self::MAX_BLOCKS ) {
				if ( empty( $stats['block_count_exceeded'] ) ) {
					$errors[]                      = array( 'path' => $current_path, 'error' => 'block_count_exceeded', 'max_blocks' => self::MAX_BLOCKS );
					$stats['block_count_exceeded'] = true;
				}
				break;
			}
			$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? $block['block_name'] ?? '' ) );
			if ( '' === $block_name ) {
				$errors[] = array( 'path' => $current_path . '.blockName', 'error' => 'block_name_required' );
				continue;
			}
			$this->track_block_text_bytes( $block_name, $stats, $errors, $current_path . '.blockName' );
			$this->track_block_text_bytes( (string) ( $block['innerHTML'] ?? $block['inner_html'] ?? '' ), $stats, $errors, $current_path . '.innerHTML' );
			$raw_inner_content = $block['innerContent'] ?? ( $block['inner_content'] ?? null );
			if ( is_array( $raw_inner_content ) ) {
				foreach ( $raw_inner_content as $content_index => $chunk ) {
					if ( null !== $chunk ) {
						$this->track_block_text_bytes( (string) $chunk, $stats, $errors, $current_path . '.innerContent.' . absint( $content_index ) );
					}
				}
			}
			$inner_blocks_errors = array();
			$inner_blocks        = $this->normalize_blocks( $block['innerBlocks'] ?? array(), $inner_blocks_errors, $current_path . '.innerBlocks', $depth + 1, $stats );
			if ( ! empty( $inner_blocks_errors ) ) {
				$errors = array_merge( $errors, $inner_blocks_errors );
			}
			$normalized[] = array(
				'blockName'    => $block_name,
				'attrs'        => is_array( $block['attrs'] ?? null ) ? $this->sanitize_block_attrs( $block['attrs'], 0 ) : array(),
				'innerHTML'    => (string) ( $block['innerHTML'] ?? $block['inner_html'] ?? '' ),
				'innerBlocks'  => $inner_blocks,
				'innerContent' => $this->normalize_block_inner_content( $block, count( $inner_blocks ) ),
			);
		}
		return $normalized;
	}

	/**
	 * Tracks cumulative block text bytes and records the first overflow.
	 *
	 * @param string       $text Text fragment.
	 * @param array<mixed> $stats Traversal stats.
	 * @param array<mixed> $errors Validation error rows.
	 * @param string       $path Path hint.
	 * @return void
	 */
	private function track_block_text_bytes( $text, array &$stats, array &$errors, $path ) {
		$stats['bytes'] = absint( $stats['bytes'] ?? 0 ) + strlen( (string) $text );
		if ( $stats['bytes'] > self::MAX_SERIALIZED_BYTES && empty( $stats['block_bytes_exceeded'] ) ) {
			$errors[]                       = array( 'path' => $path, 'error' => 'block_payload_too_large', 'max_bytes' => self::MAX_SERIALIZED_BYTES );
			$stats['block_bytes_exceeded'] = true;
		}
	}

	/**
	 * Counts a block tree recursively.
	 *
	 * @param mixed $blocks Block list.
	 * @return int
	 */
	public function count_blocks( $blocks ) {
		$blocks = is_array( $blocks ) ? $blocks : array();
		$total  = 0;
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			++$total;
			$total += $this->count_blocks( $block['innerBlocks'] ?? array() );
		}
		return $total;
	}

	/**
	 * Serializes blocks using WordPress' native parsed-block format.
	 *
	 * @param array<mixed> $blocks Normalized blocks.
	 * @return string
	 */
	public function serialize_blocks( array $blocks ) {
		if ( function_exists( 'serialize_blocks' ) ) {
			return serialize_blocks( $blocks );
		}

		$serialized = '';
		foreach ( $blocks as $block ) {
			$serialized .= is_array( $block ) ? $this->serialize_block_fallback( $block ) : '';
		}
		return $serialized;
	}

	/**
	 * Builds a minimal diff preview for audit output.
	 *
	 * @param string $before Before content.
	 * @param string $after After content.
	 * @return array<string,mixed>
	 */
	public function build_text_diff_preview( $before, $after ) {
		$before     = (string) $before;
		$after      = (string) $after;
		$before_len = strlen( $before );
		$after_len  = strlen( $after );
		$prefix     = 0;
		$limit      = min( $before_len, $after_len );
		while ( $prefix < $limit && $before[ $prefix ] === $after[ $prefix ] ) {
			++$prefix;
		}

		$suffix = 0;
		while (
			$suffix < ( $before_len - $prefix ) &&
			$suffix < ( $after_len - $prefix ) &&
			substr( $before, $before_len - 1 - $suffix, 1 ) === substr( $after, $after_len - 1 - $suffix, 1 )
		) {
			++$suffix;
		}

		$before_changed_len = max( 0, $before_len - $prefix - $suffix );
		$after_changed_len  = max( 0, $after_len - $prefix - $suffix );
		$prefix_start       = max( 0, $prefix - 120 );

		return array(
			'changed'               => $before !== $after,
			'before_hash'           => md5( $before ),
			'after_hash'            => md5( $after ),
			'prefix_length'         => $prefix,
			'suffix_length'         => $suffix,
			'before_changed_length' => $before_changed_len,
			'after_changed_length'  => $after_changed_len,
			'prefix_context'        => $this->truncate_fragment( substr( $before, $prefix_start, $prefix - $prefix_start ), 240 ),
			'before_fragment'       => $this->truncate_fragment( substr( $before, $prefix, $before_changed_len ), 600 ),
			'after_fragment'        => $this->truncate_fragment( substr( $after, $prefix, $after_changed_len ), 600 ),
			'suffix_context'        => $this->truncate_fragment( $suffix > 0 ? substr( $after, $after_len - min( 120, $suffix ) ) : '', 240 ),
		);
	}

	/**
	 * Builds a minimal changed range from a before/after diff.
	 *
	 * @param string $before Before content.
	 * @param string $after After content.
	 * @return array<int,array<string,mixed>>
	 */
	public function build_impact_ranges_from_diff( $before, $after ) {
		$preview = $this->build_text_diff_preview( $before, $after );
		if ( empty( $preview['changed'] ) ) {
			return array();
		}
		$start                 = max( 0, absint( $preview['prefix_length'] ?? 0 ) );
		$before_changed_length = max( 0, absint( $preview['before_changed_length'] ?? 0 ) );
		$after_changed_length  = max( 0, absint( $preview['after_changed_length'] ?? 0 ) );
		return array(
			array(
				'op'            => 'replace',
				'start'         => $start,
				'end'           => $start + $before_changed_length,
				'before_length' => $before_changed_length,
				'after_length'  => $after_changed_length,
			),
		);
	}

	/**
	 * Finds top-level payload keys not declared by a strict output schema.
	 *
	 * @param array<mixed> $schema Ability output schema.
	 * @param array<mixed> $payload Ability output payload.
	 * @return array<int,string>
	 */
	public function output_schema_missing_payload_keys( array $schema, array $payload ) {
		$properties = is_array( $schema['properties'] ?? null ) ? $schema['properties'] : array();
		$missing    = array();
		foreach ( array_keys( $payload ) as $key ) {
			if ( is_string( $key ) && ! array_key_exists( $key, $properties ) ) {
				$missing[] = $key;
			}
		}
		sort( $missing );
		return $missing;
	}

	/**
	 * Sanitizes one Gutenberg attribute key without destroying camelCase names.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	private function sanitize_block_attr_key( $key ) {
		$key = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $key );
		return is_string( $key ) ? $key : '';
	}

	/**
	 * Sanitizes block attrs recursively.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $depth Recursion depth.
	 * @return mixed
	 */
	private function sanitize_block_attrs( $value, $depth = 0 ) {
		$depth = absint( $depth );
		if ( $depth >= 5 ) {
			return null;
		}
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}
		if ( is_array( $value ) ) {
			$normalized = array();
			foreach ( $value as $key => $item ) {
				$normalized_key = is_int( $key ) ? $key : $this->sanitize_block_attr_key( (string) $key );
				if ( '' === $normalized_key ) {
					continue;
				}
				$normalized[ $normalized_key ] = $this->sanitize_block_attrs( $item, $depth + 1 );
			}
			return $normalized;
		}
		if ( is_object( $value ) ) {
			return $this->sanitize_block_attrs( (array) $value, $depth + 1 );
		}
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Normalizes parsed-block innerContent chunks.
	 *
	 * @param array<string,mixed> $block Raw block.
	 * @param int                 $inner_block_count Number of normalized inner blocks.
	 * @return array<int,string|null>
	 */
	private function normalize_block_inner_content( array $block, $inner_block_count ) {
		$raw_inner_content = $block['innerContent'] ?? ( $block['inner_content'] ?? null );
		if ( is_array( $raw_inner_content ) ) {
			$inner_content = array();
			foreach ( $raw_inner_content as $chunk ) {
				$inner_content[] = null === $chunk ? null : (string) $chunk;
			}
			return $inner_content;
		}

		$inner_html = (string) ( $block['innerHTML'] ?? ( $block['inner_html'] ?? '' ) );
		if ( $inner_block_count <= 0 ) {
			return array( $inner_html );
		}

		return array_merge( array( $inner_html ), array_fill( 0, absint( $inner_block_count ), null ) );
	}

	/**
	 * Fallback for environments without WordPress' serialize_block().
	 *
	 * @param array<string,mixed> $block Normalized parsed block.
	 * @return string
	 */
	private function serialize_block_fallback( array $block ) {
		$block_name = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
		$block_name = preg_replace( '/[^A-Za-z0-9_\/-]/', '', $block_name );
		$block_name = is_string( $block_name ) ? $block_name : '';
		$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? array_values( $block['innerBlocks'] ) : array();
		$inner_content = is_array( $block['innerContent'] ?? null ) ? array_values( $block['innerContent'] ) : array( (string) ( $block['innerHTML'] ?? '' ) );
		$content = '';
		$inner_block_index = 0;
		foreach ( $inner_content as $chunk ) {
			if ( is_string( $chunk ) ) {
				$content .= $chunk;
				continue;
			}
			if ( isset( $inner_blocks[ $inner_block_index ] ) && is_array( $inner_blocks[ $inner_block_index ] ) ) {
				$content .= $this->serialize_block_fallback( $inner_blocks[ $inner_block_index ] );
			}
			++$inner_block_index;
		}

		if ( '' === $block_name || 'core/freeform' === $block_name ) {
			return $content;
		}

		$comment_block_name = 0 === strpos( $block_name, 'core/' ) ? substr( $block_name, 5 ) : $block_name;
		$attrs              = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		$attrs_json         = '';
		if ( ! empty( $attrs ) ) {
			$encoded = wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( is_string( $encoded ) && '' !== $encoded && '{}' !== $encoded ) {
				$attrs_json = ' ' . $encoded;
			}
		}
		return '<!-- wp:' . $comment_block_name . $attrs_json . ' -->' . $content . '<!-- /wp:' . $comment_block_name . ' -->';
	}

	/**
	 * Truncates a preview fragment.
	 *
	 * @param string $text Raw text.
	 * @param int    $max_len Max length.
	 * @return string
	 */
	private function truncate_fragment( $text, $max_len = 400 ) {
		$text    = (string) $text;
		$max_len = max( 32, absint( $max_len ) );
		if ( strlen( $text ) <= $max_len ) {
			return $text;
		}
		return substr( $text, 0, $max_len ) . '...';
	}
}
