<?php
/**
 * Exact current-editor source matching for internal-link suggestions.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Validates host hints without trusting provider-supplied positions. */
final class Internal_Link_Source_Matcher {
	/**
	 * Keeps only bounded, plain-text hint phrases.
	 *
	 * @param mixed $value Host hint.
	 * @return string
	 */
	public static function normalize_hint( $value ): string {
		$hint = is_string( $value ) ? sanitize_text_field( $value ) : '';
		$length = self::text_length( $hint );
		return $length >= 4 && $length <= 80 ? $hint : '';
	}

	/**
	 * Locates a phrase in already-normalized current editor blocks.
	 *
	 * @param array<int,array{client_id:string,block_name:string,text:string}> $blocks Source blocks.
	 * @param string $selected_text Author selection.
	 * @param string $anchor_text Existing title-derived anchor.
	 * @param string $title Target title fallback.
	 * @param string $anchor_hint Bounded host hint.
	 * @return array<string,string|int>
	 */
	public static function find( array $blocks, string $selected_text, string $anchor_text, string $title, string $anchor_hint = '' ): array {
		// A host hint is only a phrase to locate in current editor text, never a trusted position.
		$phrases = array_filter( array_unique( array_map( 'trim', array( $selected_text, $anchor_hint, $anchor_text, $title ) ) ) );
		foreach ( $blocks as $block ) {
			if ( self::is_heading_block( $block['block_name'] ) ) {
				continue;
			}
			foreach ( $phrases as $phrase ) {
				$phrase_length = self::text_length( $phrase );
				$is_explicit_selection = $phrase === trim( (string) $selected_text );
				if ( ( ! $is_explicit_selection && $phrase_length < 4 ) || $phrase_length > 80 ) {
					continue;
				}
				$offset = function_exists( 'mb_stripos' ) ? mb_stripos( $block['text'], $phrase ) : stripos( $block['text'], $phrase );
				if ( false !== $offset ) {
					return array(
						'block_client_id' => $block['client_id'],
						'block_name'      => $block['block_name'],
						'matched_text'    => $phrase,
						'text_offset'     => (int) $offset,
						'expected_text'   => $block['text'],
						'match_basis'     => $phrase === trim( (string) $selected_text ) ? 'editor_selection' : 'existing_article_phrase',
					);
				}
			}
		}
		return array();
	}

	private static function is_heading_block( $block_name ) {
		$name = sanitize_key( str_replace( '/', '-', (string) $block_name ) );
		return in_array( $name, array( 'core-heading', 'core-post-title', 'core-query-title' ), true );
	}

	private static function text_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}
}
